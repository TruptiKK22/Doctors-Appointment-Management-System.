<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

// View-only: confirming/cancelling/completing is now each doctor's own job for their
// own patients (doctor/index.php, doctor/appointment.php). Admin keeps full visibility
// across every specialty and status for oversight, but no longer acts on appointments here.

// ---- Filters ----
$fSpec   = $_GET['specialty'] ?? '';
$fStatus = $_GET['status'] ?? '';
$fDate   = $_GET['date'] ?? '';
$q       = trim($_GET['q'] ?? '');

$where = []; $types = ''; $args = [];
if (isset(SPECIALTIES[$fSpec]))               { $where[] = "specialty = ?";        $types .= 's'; $args[] = $fSpec; }
if (in_array($fStatus, STATUSES, true))       { $where[] = "status = ?";           $types .= 's'; $args[] = $fStatus; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fDate)) { $where[] = "appointment_date = ?"; $types .= 's'; $args[] = $fDate; }
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = "(fullname LIKE ? OR mobile LIKE ? OR doctor_name LIKE ?)";
    $types .= 'sss'; array_push($args, $like, $like, $like);
}
$w = $where ? "WHERE " . implode(" AND ", $where) : "";
$base = "FROM (" . appointments_union() . ") t $w";

$perPage = 15;
$total = (int)run_query($conn, "SELECT COUNT(*) c $base", $types, $args)->fetch_assoc()['c'];
[$page, $pages, $offset] = paginate($total, $perPage);

$rows = run_query($conn, "SELECT * $base ORDER BY appointment_date DESC, specialty, id DESC LIMIT ? OFFSET ?",
                  $types . 'ii', array_merge($args, [$perPage, $offset]));

admin_header('Appointments', 'appointments');
?>
<form class="filters" method="GET">
  <input type="text" name="q" placeholder="Search patient, mobile, doctor" value="<?= h($q) ?>">
  <select name="specialty">
    <option value="">All specialists</option>
    <?php foreach (SPECIALTIES as $name => $_): ?><option <?= $fSpec === $name ? 'selected' : '' ?>><?= h($name) ?></option><?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">All statuses</option>
    <?php foreach (STATUSES as $s): ?><option <?= $fStatus === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
  </select>
  <input type="date" name="date" value="<?= h($fDate) ?>">
  <button class="btn">Filter</button>
  <a class="btn grey" href="appointments.php">Reset</a>
</form>

<div class="box"><table>
  <tr><th>Ref</th><th>Patient</th><th>Specialist</th><th>Doctor</th><th>Date</th><th>Slot</th><th>Token</th><th>Status</th></tr>
  <?php $n = 0; while ($r = $rows->fetch_assoc()): $n++; ?>
  <tr>
    <td><?= h(SPECIALTIES[$r['specialty']]['code'] . '-' . str_pad($r['id'], 5, '0', STR_PAD_LEFT)) ?></td>
    <td><a href="appointment.php?s=<?= urlencode($r['specialty']) ?>&id=<?= (int)$r['id'] ?>"><?= h($r['fullname']) ?></a></td>
    <td><?= h($r['specialty']) ?></td>
    <td><?= h($r['doctor_name']) ?></td>
    <td><?= h(date('j M Y', strtotime($r['appointment_date']))) ?></td>
    <td><?= h($r['appointment_slot']) ?></td>
    <td>#<?= h(str_pad($r['token_number'], 2, '0', STR_PAD_LEFT)) ?></td>
    <td><?= badge($r['status']) ?><?php if ($r['doctor_request'] !== 'None'): ?> <span style="color:#8a6d00;font-size:.78rem;white-space:nowrap">⏳ <?= h($r['doctor_request']) ?> requested</span><?php endif; ?></td>
  </tr>
  <?php endwhile; if (!$n): ?><tr><td colspan="8" class="empty">No appointments found.</td></tr><?php endif; ?>
</table></div>
<?= pager($page, $pages) ?>
<?php admin_footer();