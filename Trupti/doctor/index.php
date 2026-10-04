<?php
require_once __DIR__ . '/auth.php';
require_doctor();

$table     = SPECIALTIES[$_SESSION['specialty']]['table'];
$name      = $_SESSION['doctor_name'];
$specialty = $_SESSION['specialty'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['action'] ?? '') === 'availability') {
        doctor_set_availability($conn, $_SESSION['doctor_id'], $_POST['available'] === '1');
        flash($_POST['available'] === '1' ? "You're marked as available." : "You're marked as unavailable.");
    } else {
        // Quick Confirm/Cancel straight from the dashboard lists, without opening the detail page
        $ok = doctor_update_status($conn, $table, $specialty, (int)($_POST['id'] ?? 0), $name, $_POST['status'] ?? '');
        flash($ok ? 'Updated.' : 'Could not update that appointment.');
    }
    header("Location: index.php");
    exit();
}

$isAvailable = (bool) run_query($conn, "SELECT is_available FROM doctors WHERE id = ?", "i", [$_SESSION['doctor_id']])->fetch_assoc()['is_available'];

$pending = run_query($conn,
    "SELECT * FROM `$table` WHERE doctor_name = ? AND status = 'Pending' ORDER BY appointment_date, token_number",
    "s", [$name]);

$today = run_query($conn,
    "SELECT * FROM `$table` WHERE doctor_name = ? AND appointment_date = CURDATE() AND status = 'Confirmed' ORDER BY token_number",
    "s", [$name]);

$upcoming = run_query($conn,
    "SELECT * FROM `$table` WHERE doctor_name = ? AND appointment_date > CURDATE() AND status = 'Confirmed' ORDER BY appointment_date, token_number",
    "s", [$name]);

function quick_form($id, $to, $label, $class, $confirm = '') {
    $js = $confirm ? " onsubmit=\"return confirm('" . $confirm . "')\"" : '';
    return '<form method="POST" class="inline"' . $js . '>' . csrf_field()
        . '<input type="hidden" name="id" value="' . (int)$id . '">'
        . '<input type="hidden" name="status" value="' . $to . '">'
        . '<button class="btn ' . $class . '">' . $label . '</button></form> ';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Health Prime</title>
<link rel="stylesheet" href="doctor.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand">Health <span>Prime</span></div>
    <div style="color:rgba(255,255,255,.6);font-size:.82rem;margin:0 10px 18px">
      <?= h($specialty) ?><br><strong style="color:#fff"><?= h($name) ?></strong>
    </div>
    <div style="margin:0 10px 18px">
      <form method="POST">
        <?= csrf_field() ?><input type="hidden" name="action" value="availability">
        <input type="hidden" name="available" value="<?= $isAvailable ? '0' : '1' ?>">
        <button type="submit" class="btn" style="width:100%;background:<?= $isAvailable ? '#0a9396' : '#8492a0' ?>">
          <?= $isAvailable ? '🟢 Available' : '⚪ Unavailable' ?> — tap to change
        </button>
      </form>
    </div>
    <a href="logout.php" class="logout">Log out</a>
</aside>
<main class="main">
    <h1>Dashboard</h1>
    <?php if ($f = flash()): ?><div class="flash"><?= h($f) ?></div><?php endif; ?>

    <h2>Needs your action</h2>
    <div class="box"><table>
      <tr><th>Date</th><th>Token</th><th>Slot</th><th>Patient</th><th>Age</th><th>Actions</th></tr>
      <?php $n = 0; while ($r = $pending->fetch_assoc()): $n++; ?>
      <tr>
        <td><?= h(date('j M Y', strtotime($r['appointment_date']))) ?></td>
        <td>#<?= h(str_pad($r['token_number'], 2, '0', STR_PAD_LEFT)) ?></td>
        <td><?= h($r['appointment_slot']) ?></td>
        <td><a href="appointment.php?id=<?= (int)$r['id'] ?>"><?= h($r['fullname']) ?></a></td>
        <td><?= h($r['age']) ?></td>
        <td>
          <?= quick_form($r['id'], 'Confirmed', 'Confirm', '') ?>
          <?= quick_form($r['id'], 'Cancelled', 'Cancel', 'red', 'Cancel this appointment?') ?>
        </td>
      </tr>
      <?php endwhile; if (!$n): ?><tr><td colspan="6" class="empty">Nothing waiting on you.</td></tr><?php endif; ?>
    </table></div>

    <h2>Today</h2>
    <div class="box"><table>
      <tr><th>Token</th><th>Slot</th><th>Patient</th><th>Age</th><th>Status</th><th></th></tr>
      <?php $n = 0; while ($r = $today->fetch_assoc()): $n++; ?>
      <tr>
        <td>#<?= h(str_pad($r['token_number'], 2, '0', STR_PAD_LEFT)) ?></td>
        <td><?= h($r['appointment_slot']) ?></td>
        <td><a href="appointment.php?id=<?= (int)$r['id'] ?>"><?= h($r['fullname']) ?></a></td>
        <td><?= h($r['age']) ?></td>
        <td><?= badge($r['status']) ?><?php if ($r['doctor_request'] !== 'None'): ?> <span style="color:#8a6d00;font-size:.78rem">⏳ <?= h($r['doctor_request']) ?> requested</span><?php endif; ?></td>
        <td><a class="btn" href="appointment.php?id=<?= (int)$r['id'] ?>">Open</a></td>
      </tr>
      <?php endwhile; if (!$n): ?><tr><td colspan="6" class="empty">No appointments today.</td></tr><?php endif; ?>
    </table></div>

    <h2>Upcoming</h2>
    <div class="box"><table>
      <tr><th>Date</th><th>Token</th><th>Slot</th><th>Patient</th><th>Status</th></tr>
      <?php $n = 0; while ($r = $upcoming->fetch_assoc()): $n++; ?>
      <tr>
        <td><?= h(date('j M Y', strtotime($r['appointment_date']))) ?></td>
        <td>#<?= h(str_pad($r['token_number'], 2, '0', STR_PAD_LEFT)) ?></td>
        <td><?= h($r['appointment_slot']) ?></td>
        <td><a href="appointment.php?id=<?= (int)$r['id'] ?>"><?= h($r['fullname']) ?></a></td>
        <td><?= badge($r['status']) ?><?php if ($r['doctor_request'] !== 'None'): ?> <span style="color:#8a6d00;font-size:.78rem">⏳ <?= h($r['doctor_request']) ?> requested</span><?php endif; ?></td>
      </tr>
      <?php endwhile; if (!$n): ?><tr><td colspan="5" class="empty">No upcoming appointments.</td></tr><?php endif; ?>
    </table></div>
</main>
</body>
</html>