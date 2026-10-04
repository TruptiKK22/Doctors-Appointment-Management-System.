<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$spec = $_GET['s'] ?? '';
$id   = (int)($_GET['id'] ?? 0);
if (!isset(SPECIALTIES[$spec]) || $id < 1) { http_response_code(404); die("Appointment not found."); }
$table = SPECIALTIES[$spec]['table'];

// Admin is read-only EXCEPT for one gated action: acting on a Confirmed appointment
// that the doctor has explicitly flagged with doctor_request. Both functions re-check
// that flag in their own WHERE clause, so this isn't just a UI restriction.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        $ok = admin_cancel_confirmed($conn, $table, $spec, $id, $_POST['reason'] ?? '');
        flash($ok ? 'Appointment cancelled and the patient has been notified.' : 'Could not cancel — write a reason, and make sure a cancellation request exists.');
    } elseif ($action === 'reschedule') {
        $ok = admin_reschedule_confirmed($conn, $table, $spec, $id, $_POST['note'] ?? '');
        flash($ok ? 'Appointment rescheduled and the patient has been notified.' : 'Could not reschedule — make sure a reschedule request with a date exists.');
    } elseif ($action === 'deny') {
        $ok = admin_deny_request($conn, $table, $id, $_POST['deny_reason'] ?? '');
        flash($ok ? "Request denied — the doctor will see why on their dashboard." : 'Could not deny — write a reason, and make sure a request exists.');
    }
    header("Location: appointment.php?s=" . urlencode($spec) . "&id=$id");
    exit();
}

$row = run_query($conn, "SELECT * FROM `$table` WHERE id = ?", "i", [$id])->fetch_assoc();
if (!$row) { http_response_code(404); die("Appointment not found."); }

admin_header($spec . ' appointment #' . $id, 'appointments');
?>
<p style="margin-bottom:16px">
  <?= badge($row['status']) ?>&nbsp;
  <a class="btn grey" href="appointments.php">← Back to list</a>
</p>
<?php if ($f = flash()): ?><div class="flash"><?= h($f) ?></div><?php endif; ?>

<?php if ($row['status'] === 'Confirmed' && $row['doctor_request'] === 'Cancel'): ?>
  <div class="box" style="padding:18px 20px;margin-bottom:20px;border-left:4px solid #d64545">
    <strong><?= h($row['doctor_name']) ?> has requested this appointment be cancelled.</strong>
    <p style="color:var(--muted);font-size:.88rem;margin:6px 0 12px">Doctor's note: <?= h($row['doctor_note']) ?></p>
    <form method="POST" onsubmit="return confirm('Cancel this appointment and notify the patient?')" style="margin-bottom:14px">
      <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
      <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px">Reason to show the patient (required)</label>
      <textarea name="reason" required style="width:100%;max-width:480px;padding:10px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:10px;min-height:60px"><?= h($row['doctor_note']) ?></textarea><br>
      <button class="btn red">Cancel appointment</button>
    </form>
    <details>
      <summary style="cursor:pointer;color:var(--muted);font-size:.85rem">Deny this request instead</summary>
      <form method="POST" style="margin-top:10px" onsubmit="return confirm('Deny this request? The appointment stays as it is.')">
        <?= csrf_field() ?><input type="hidden" name="action" value="deny">
        <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px">Reason for denying (shown to the doctor)</label>
        <textarea name="deny_reason" required style="width:100%;max-width:480px;padding:10px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:10px;min-height:50px"></textarea><br>
        <button class="btn grey">Deny request</button>
      </form>
    </details>
  </div>

<?php elseif ($row['status'] === 'Confirmed' && $row['doctor_request'] === 'Reschedule'): ?>
  <div class="box" style="padding:18px 20px;margin-bottom:20px;border-left:4px solid #e9c46a">
    <strong><?= h($row['doctor_name']) ?> has requested this appointment be rescheduled.</strong>
    <p style="color:var(--muted);font-size:.88rem;margin:6px 0 4px">Doctor's note: <?= h($row['doctor_note']) ?></p>
    <?php if ($row['requested_date']): ?>
      <p style="margin:0 0 14px">
        <strong>New date:</strong> <?= h(date('j M Y', strtotime($row['requested_date']))) ?> &nbsp;
        <strong>New slot:</strong> <?= h($row['requested_slot']) ?>
      </p>
      <form method="POST">
        <?= csrf_field() ?><input type="hidden" name="action" value="reschedule">
        <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px">Note to show the patient (optional)</label>
        <textarea name="note" style="width:100%;max-width:480px;padding:10px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:10px;min-height:50px"><?= h($row['doctor_note']) ?></textarea><br>
        <button class="btn">Apply reschedule</button>
      </form>
      <details>
        <summary style="cursor:pointer;color:var(--muted);font-size:.85rem">Deny this request instead</summary>
        <form method="POST" style="margin-top:10px" onsubmit="return confirm('Deny this request? The appointment stays as it is.')">
          <?= csrf_field() ?><input type="hidden" name="action" value="deny">
          <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px">Reason for denying (shown to the doctor)</label>
          <textarea name="deny_reason" required style="width:100%;max-width:480px;padding:10px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:10px;min-height:50px"></textarea><br>
          <button class="btn grey">Deny request</button>
        </form>
      </details>
    <?php else: ?>
      <p style="color:#b42318;font-size:.85rem">This request is missing a date — ask the doctor to resend it.</p>
    <?php endif; ?>
  </div>

<?php elseif ($row['status'] === 'Confirmed'): ?>
  <p style="color:var(--muted);font-size:.85rem;margin:-10px 0 16px">
    No cancellation/reschedule request from <?= h($row['doctor_name']) ?>. Admin can only cancel or reschedule a confirmed
    appointment once the doctor requests it from their own dashboard.
  </p>
<?php else: ?>
  <p style="color:var(--muted);font-size:.85rem;margin:-10px 0 16px">
    This appointment is managed by <?= h($row['doctor_name']) ?> from their own dashboard.
  </p>
<?php endif; ?>

<div class="detail">
  <?php foreach ($row as $k => $v): if (in_array($k, ['doctor_request', 'doctor_note', 'requested_date', 'requested_slot', 'denial_note'], true)) continue; ?>
    <div class="k"><?= h(ucwords(str_replace('_', ' ', $k))) ?></div>
    <div><?= $v === null || $v === '' ? '—' : nl2br(h($v)) ?></div>
  <?php endforeach; ?>
</div>
<?php admin_footer();