<?php
require_once __DIR__ . '/auth.php';
require_doctor();

$table = SPECIALTIES[$_SESSION['specialty']]['table'];
$name  = $_SESSION['doctor_name'];
$id    = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'status';

    if ($action === 'dismiss_denial') {
        doctor_dismiss_denial($conn, $table, $id, $name);
    } elseif ($action === 'request') {
        $type = $_POST['type'] ?? '';
        $ok = doctor_request_action($conn, $table, $id, $name, $type, $_POST['note'] ?? '', $_POST['new_date'] ?? null, $_POST['new_slot'] ?? null);
        $failMsg = $type === 'Reschedule'
            ? 'Could not send — check the date (must be today or later) and slot, and write a reason.'
            : 'Could not send — please write a reason.';
        flash($ok ? 'Sent to admin.' : $failMsg . ' This appointment may also already have a request pending.');
    } else {
        $ok = doctor_update_status($conn, $table, $_SESSION['specialty'], $id, $name, $_POST['status'] ?? '');
        flash($ok ? 'Updated.' : 'Could not update — this appointment may not be yours, or that change isn\'t allowed right now.');
    }
    header("Location: appointment.php?id=$id");
    exit();
}

// doctor_name in the WHERE clause: a doctor can only ever see their own patients' records
$row = run_query($conn, "SELECT * FROM `$table` WHERE id = ? AND doctor_name = ?", "is", [$id, $name])->fetch_assoc();
if (!$row) { http_response_code(404); die("Appointment not found."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient details — Health Prime</title>
<link rel="stylesheet" href="doctor.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand">Health <span>Prime</span></div>
    <a href="index.php" class="active">← Dashboard</a>
    <a href="logout.php" class="logout">Log out</a>
</aside>
<main class="main">
    <h1><?= h($row['fullname']) ?></h1>
    <?php if ($f = flash()): ?><div class="flash"><?= h($f) ?></div><?php endif; ?>

    <p style="margin-bottom:16px">
      <?= badge($row['status']) ?>&nbsp;
      <?php if ($row['status'] === 'Pending'): ?>
        <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="status" value="Confirmed">
          <button class="btn">Confirm</button></form>
        <form method="POST" class="inline" onsubmit="return confirm('Cancel this appointment?')">
          <?= csrf_field() ?><input type="hidden" name="status" value="Cancelled">
          <button class="btn red">Cancel</button></form>
      <?php elseif ($row['status'] === 'Confirmed' && $row['doctor_request'] === 'None'): ?>
        <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="status" value="Completed">
          <button class="btn">Mark Completed</button></form>
        <form method="POST" class="inline" onsubmit="return confirm('Mark this patient as a no-show?')">
          <?= csrf_field() ?><input type="hidden" name="status" value="No-show">
          <button class="btn red">Mark No-show</button></form>
      <?php endif; ?>
      <a class="btn grey" href="index.php">← Back</a>
    </p>

    <?php if (!empty($row['denial_note'])): ?>
      <div class="box" style="padding:16px 20px;margin-bottom:20px;border-left:4px solid #d64545">
        <strong>Admin denied your request.</strong>
        <p style="color:var(--muted,#6b7a89);font-size:.88rem;margin:4px 0 10px">Reason: <?= h($row['denial_note']) ?></p>
        <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="dismiss_denial">
          <button class="btn grey">Dismiss</button></form>
      </div>
    <?php endif; ?>

    <?php if ($row['status'] === 'Confirmed' && $row['doctor_request'] !== 'None'): ?>
      <div class="box" style="padding:16px 20px;margin-bottom:20px;border-left:4px solid #e9c46a">
        ⏳ <strong><?= h($row['doctor_request']) ?> requested</strong> — waiting for admin to act on it.<br>
        <span style="color:var(--muted,#6b7a89);font-size:.88rem">
          Your note: <?= h($row['doctor_note']) ?>
          <?php if ($row['doctor_request'] === 'Reschedule' && $row['requested_date']): ?>
            <br>Proposed: <?= h(date('j M Y', strtotime($row['requested_date']))) ?> · <?= h($row['requested_slot']) ?>
          <?php endif; ?>
        </span>
      </div>
    <?php elseif ($row['status'] === 'Confirmed'): ?>
      <div class="box" style="padding:16px 20px;margin-bottom:20px">
        <strong>Need this appointment cancelled or rescheduled?</strong>
        <p style="color:var(--muted,#6b7a89);font-size:.85rem;margin:4px 0 12px">
          You can't cancel a confirmed appointment yourself — send a request to admin with a reason, and they'll action it.
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <form method="POST" style="flex:1;min-width:220px">
            <?= csrf_field() ?><input type="hidden" name="action" value="request"><input type="hidden" name="type" value="Cancel">
            <textarea name="note" required placeholder="Reason for cancellation…" style="width:100%;padding:8px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:6px;min-height:50px"></textarea>
            <button class="btn red" style="width:100%">Request Cancellation</button>
          </form>
          <form method="POST" style="flex:1;min-width:220px">
            <?= csrf_field() ?><input type="hidden" name="action" value="request"><input type="hidden" name="type" value="Reschedule">
            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px">New date</label>
            <input type="date" name="new_date" required min="<?= date('Y-m-d') ?>" style="width:100%;padding:8px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:6px">
            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px">New slot</label>
            <input type="text" name="new_slot" required value="<?= h($row['appointment_slot']) ?>" style="width:100%;padding:8px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:6px">
            <textarea name="note" required placeholder="Reason for reschedule…" style="width:100%;padding:8px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:6px;min-height:40px"></textarea>
            <button class="btn" style="width:100%">Request Reschedule</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="detail">
      <?php foreach ($row as $k => $v): if (in_array($k, ['id', 'user_id', 'doctor_name', 'doctor_request', 'doctor_note', 'requested_date', 'requested_slot', 'denial_note'], true)) continue; ?>
        <div class="k"><?= h(ucwords(str_replace('_', ' ', $k))) ?></div>
        <div><?= $v === null || $v === '' ? '—' : nl2br(h($v)) ?></div>
      <?php endforeach; ?>
    </div>
</main>
</body>
</html>