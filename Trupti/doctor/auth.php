<?php
// Doctor-portal bootstrap: own session, own CSRF, reuses db.php for the connection/constants.
session_name('HP_DOCTOR');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../mail.php';

function require_doctor() {
    if (empty($_SESSION['doctor_id'])) {
        header("Location: login.php");
        exit();
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check() {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(400);
        die("Invalid request. Please go back and try again.");
    }
}

function flash($msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $m = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $m;
}

function run_query($conn, $sql, $types = '', $args = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') { $stmt->bind_param($types, ...$args); }
    $stmt->execute();
    return $stmt->get_result();
}

function badge($status) {
    return '<span class="st st-' . h(strtolower($status)) . '">' . h($status) . '</span>';
}

// A doctor now owns the full lifecycle for their own appointments:
// Pending → Confirmed/Cancelled, then Confirmed → Completed/No-show.
// Each step is a one-way door — e.g. a Cancelled appointment can't be
// marked Completed, and a Pending one can't jump straight to Completed.
const DOCTOR_VISIBLE_STATUSES = ['Pending', 'Confirmed', 'Completed', 'No-show'];

const STATUS_TRANSITIONS = [
    'Pending'   => ['Confirmed', 'Cancelled'],
    'Confirmed' => ['Completed', 'No-show'],
];

// A doctor can no longer cancel a Confirmed appointment directly — they can only
// *request* a cancellation or reschedule, with a required reason. Admin is the only
// one who can actually carry that out (see admin/auth.php's admin_cancel_confirmed /
// admin_reschedule_confirmed), and only once a request like this exists.
//
// For a Reschedule request, the doctor picks the new date/slot themselves — admin's
// job is to review and apply it, not to choose it.
function doctor_request_action($conn, $table, $id, $doctorName, $type, $note, $newDate = null, $newSlot = null) {
    if (!in_array($type, ['Cancel', 'Reschedule'], true) || $id < 1 || trim($note) === '') return false;

    if ($type === 'Reschedule') {
        $d = DateTime::createFromFormat('Y-m-d', (string)$newDate);
        $isValidDate = $d && $d->format('Y-m-d') === $newDate;
        if (!$isValidDate || $newDate < date('Y-m-d') || trim((string)$newSlot) === '') return false;
    }

    $row = run_query($conn, "SELECT status, doctor_request FROM `$table` WHERE id = ? AND doctor_name = ?", "is", [$id, $doctorName])->fetch_assoc();
    if (!$row || $row['status'] !== 'Confirmed' || $row['doctor_request'] !== 'None') return false;

    // A new request clears any old denial note from a previous round, so the doctor
    // isn't still staring at yesterday's "denied" banner after trying again.
    if ($type === 'Reschedule') {
        $stmt = $conn->prepare("UPDATE `$table` SET doctor_request = ?, doctor_note = ?, requested_date = ?, requested_slot = ?, denial_note = NULL WHERE id = ? AND doctor_name = ?");
        $stmt->bind_param("ssssis", $type, $note, $newDate, $newSlot, $id, $doctorName);
    } else {
        $stmt = $conn->prepare("UPDATE `$table` SET doctor_request = ?, doctor_note = ?, denial_note = NULL WHERE id = ? AND doctor_name = ?");
        $stmt->bind_param("ssis", $type, $note, $id, $doctorName);
    }
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

function doctor_dismiss_denial($conn, $table, $id, $doctorName) {
    $stmt = $conn->prepare("UPDATE `$table` SET denial_note = NULL WHERE id = ? AND doctor_name = ?");
    $stmt->bind_param("is", $id, $doctorName);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// A doctor toggles their own availability — this doesn't touch any appointment;
// it's just a status admin and (eventually) the booking pages can read.
function doctor_set_availability($conn, $doctorId, $available) {
    $stmt = $conn->prepare("UPDATE doctors SET is_available = ? WHERE id = ?");
    $val = $available ? 1 : 0;
    $stmt->bind_param("ii", $val, $doctorId);
    return $stmt->execute();
}

function doctor_update_status($conn, $table, $specialty, $id, $doctorName, $newStatus) {
    if ($id < 1) return false;

    // doctor_name in the WHERE clause stops a doctor from touching another doctor's appointment
    $row = run_query($conn, "SELECT * FROM `$table` WHERE id = ? AND doctor_name = ?", "is", [$id, $doctorName])->fetch_assoc();
    if (!$row) return false;

    // Can't mark Completed/No-show while a cancellation/reschedule request is pending —
    // wait for admin to resolve it first, so the appointment doesn't end up in two states at once.
    if (($row['doctor_request'] ?? 'None') !== 'None') return false;

    $allowed = STATUS_TRANSITIONS[$row['status']] ?? [];
    if (!in_array($newStatus, $allowed, true)) return false;

    $stmt = $conn->prepare("UPDATE `$table` SET status = ? WHERE id = ? AND doctor_name = ?");
    $stmt->bind_param("sis", $newStatus, $id, $doctorName);
    $stmt->execute();

    // Email the patient on Confirm/Cancel, same as admin used to.
    if (($newStatus === 'Confirmed' || $newStatus === 'Cancelled') && $stmt->affected_rows > 0) {
        $row['status'] = $newStatus;
        send_status_email($row, $specialty, $newStatus);
    }

    return $stmt->affected_rows > 0;
}