<?php
// Shared admin bootstrap: separate session, DB, CSRF, small helpers.
session_name('HP_ADMIN');   // separate from the patient session
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../mail.php';

function require_admin() {
    if (empty($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}

// ---------- CSRF ----------
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}
function csrf_check() {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(400);
        die("Invalid request. Please go back and try again.");
    }
}

// ---------- flash message ----------
function flash($msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $m = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $m;
}

// ---------- data helpers ----------
function run_query($conn, $sql, $types = '', $args = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') { $stmt->bind_param($types, ...$args); }
    $stmt->execute();
    return $stmt->get_result();
}

// All four appointment tables as one result set (table names come from constants, not user input)
function appointments_union() {
    $parts = [];
    foreach (SPECIALTIES as $name => $s) {
        $parts[] = "SELECT '$name' AS specialty, id, user_id, doctor_name, fullname, age, mobile,
                           appointment_date, appointment_slot, token_number, status, doctor_request
                    FROM `{$s['table']}`";
    }
    return implode(" UNION ALL ", $parts);
}

// Admin no longer changes appointment status on their own initiative — that's each
// doctor's job for their own patients (doctor/index.php, doctor/appointment.php).
// The one exception: a Confirmed appointment that the doctor has flagged via
// doctor_request. These two functions are gated on that flag in the WHERE clause
// itself, not just hidden in the UI — a forged request with no matching flag updates
// zero rows.

function admin_cancel_confirmed($conn, $table, $specialty, $id, $reason) {
    if ($id < 1 || trim($reason) === '') return false;

    $stmt = $conn->prepare("UPDATE `$table` SET status = 'Cancelled', status_note = ?, doctor_request = 'None', doctor_note = NULL
                             WHERE id = ? AND status = 'Confirmed' AND doctor_request = 'Cancel'");
    $stmt->bind_param("si", $reason, $id);
    $stmt->execute();
    if ($stmt->affected_rows < 1) return false;

    $row = $conn->query("SELECT * FROM `$table` WHERE id = " . (int)$id)->fetch_assoc();
    if ($row) send_status_email($row, $specialty, 'Cancelled', $reason);
    return true;
}

// The new date/slot are NOT accepted as input here — they come only from
// requested_date/requested_slot, which the doctor set when filing the request.
// Admin reviews and applies it; admin cannot supply a different date.
function admin_reschedule_confirmed($conn, $table, $specialty, $id, $note) {
    if ($id < 1) return false;

    $stmt = $conn->prepare("UPDATE `$table`
                             SET appointment_date = requested_date, appointment_slot = requested_slot,
                                 status_note = ?, doctor_request = 'None', doctor_note = NULL,
                                 requested_date = NULL, requested_slot = NULL
                             WHERE id = ? AND status = 'Confirmed' AND doctor_request = 'Reschedule'
                               AND requested_date IS NOT NULL AND requested_slot IS NOT NULL");
    $stmt->bind_param("si", $note, $id);
    $stmt->execute();
    if ($stmt->affected_rows < 1) return false;

    $row = $conn->query("SELECT * FROM `$table` WHERE id = " . (int)$id)->fetch_assoc();
    if ($row) send_status_email($row, $specialty, 'Rescheduled', $note);
    return true;
}

// Deny a doctor's pending request without changing the appointment at all — it stays
// Confirmed, the request is cleared, and the doctor sees why on their own dashboard.
// No patient email here: the patient was never affected by a request that gets denied.
function admin_deny_request($conn, $table, $id, $reason) {
    if ($id < 1 || trim($reason) === '') return false;

    $stmt = $conn->prepare("UPDATE `$table` SET doctor_request = 'None', doctor_note = NULL,
                                 requested_date = NULL, requested_slot = NULL, denial_note = ?
                             WHERE id = ? AND status = 'Confirmed' AND doctor_request != 'None'");
    $stmt->bind_param("si", $reason, $id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

function unread_count($conn) {
    try {
        return (int)$conn->query("SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0")->fetch_assoc()['c'];
    } catch (Throwable $e) {
        return 0;
    }
}

function badge($status) {
    return '<span class="st st-' . h(strtolower($status)) . '">' . h($status) . '</span>';
}

function paginate($total, $perPage) {
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    return [$page, $pages, ($page - 1) * $perPage];
}

function pager($page, $pages) {
    if ($pages <= 1) return '';
    $out = '<div class="pager">';
    $gap = false;
    for ($i = 1; $i <= $pages; $i++) {
        if ($i == 1 || $i == $pages || abs($i - $page) <= 2) {
            $qs = http_build_query(array_merge($_GET, ['page' => $i]));
            $out .= '<a href="?' . h($qs) . '"' . ($i == $page ? ' class="on"' : '') . '>' . $i . '</a>';
            $gap = false;
        } elseif (!$gap) {
            $out .= '<span>…</span>';
            $gap = true;
        }
    }
    return $out . '</div>';
}