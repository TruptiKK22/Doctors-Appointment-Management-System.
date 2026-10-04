<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

// Per-specialty numbers
$stats = [];
foreach (SPECIALTIES as $name => $_) {
    $stats[$name] = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'today' => 0];
}
$res = $conn->query("SELECT specialty, COUNT(*) total,
        SUM(status='Pending') pending, SUM(status='Confirmed') confirmed, SUM(status='Cancelled') cancelled,
        SUM(appointment_date = CURDATE()) today
    FROM (" . appointments_union() . ") t GROUP BY specialty");
while ($r = $res->fetch_assoc()) {
    foreach (['total', 'pending', 'confirmed', 'cancelled', 'today'] as $k) {
        $stats[$r['specialty']][$k] = (int)$r[$k];
    }
}

$total   = array_sum(array_column($stats, 'total'));
$today   = array_sum(array_column($stats, 'today'));
$pending = array_sum(array_column($stats, 'pending'));
$users   = (int)$conn->query("SELECT COUNT(*) c FROM signin")->fetch_assoc()['c'];
$unread  = unread_count($conn);

$upcoming = $conn->query("SELECT * FROM (" . appointments_union() . ") t
    WHERE appointment_date >= CURDATE() ORDER BY appointment_date, id LIMIT 8");

admin_header('Dashboard', 'index');
?>
<div class="cards">
  <div class="card"><div class="n"><?= $total ?></div><div class="l">Total appointments</div></div>
  <div class="card"><div class="n"><?= $today ?></div><div class="l">Today</div></div>
  <div class="card"><div class="n"><?= $pending ?></div><div class="l">Pending</div></div>
  <div class="card"><div class="n"><?= $users ?></div><div class="l">Registered patients</div></div>
  <div class="card"><div class="n"><?= $unread ?></div><div class="l">Unread messages</div></div>
</div>

<h2>By specialist</h2>
<div class="box"><table>
  <tr><th>Specialist</th><th>Total</th><th>Today</th><th>Pending</th><th>Confirmed</th><th>Cancelled</th></tr>
  <?php foreach ($stats as $name => $s): ?>
  <tr>
    <td><a href="appointments.php?specialty=<?= urlencode($name) ?>"><?= h($name) ?></a></td>
    <td><?= $s['total'] ?></td><td><?= $s['today'] ?></td><td><?= $s['pending'] ?></td><td><?= $s['confirmed'] ?></td><td><?= $s['cancelled'] ?></td>
  </tr>
  <?php endforeach; ?>
</table></div>

<h2>Upcoming appointments</h2>
<div class="box"><table>
  <tr><th>Date</th><th>Slot</th><th>Token</th><th>Patient</th><th>Specialist</th><th>Doctor</th><th>Status</th></tr>
  <?php $n = 0; while ($r = $upcoming->fetch_assoc()): $n++; ?>
  <tr>
    <td><?= h(date('j M Y', strtotime($r['appointment_date']))) ?></td>
    <td><?= h($r['appointment_slot']) ?></td>
    <td>#<?= h(str_pad($r['token_number'], 2, '0', STR_PAD_LEFT)) ?></td>
    <td><a href="appointment.php?s=<?= urlencode($r['specialty']) ?>&id=<?= (int)$r['id'] ?>"><?= h($r['fullname']) ?></a></td>
    <td><?= h($r['specialty']) ?></td><td><?= h($r['doctor_name']) ?></td><td><?= badge($r['status']) ?></td>
  </tr>
  <?php endwhile; if (!$n): ?><tr><td colspan="7" class="empty">No upcoming appointments.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer();