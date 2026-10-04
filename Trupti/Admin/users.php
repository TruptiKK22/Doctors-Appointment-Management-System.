<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$q = trim($_GET['q'] ?? '');
$where = ''; $types = ''; $args = [];
if ($q !== '') {
    $like  = '%' . addcslashes($q, '%_\\') . '%';
    $where = "WHERE s.firstname LIKE ? OR s.lastname LIKE ? OR s.email LIKE ? OR s.phone LIKE ?";
    $types = 'ssss'; $args = [$like, $like, $like, $like];
}

$perPage = 15;
$total = (int)run_query($conn, "SELECT COUNT(*) c FROM signin s $where", $types, $args)->fetch_assoc()['c'];
[$page, $pages, $offset] = paginate($total, $perPage);

$counts = [];
foreach (SPECIALTIES as $s) { $counts[] = "(SELECT COUNT(*) FROM `{$s['table']}` WHERE user_id = s.id)"; }
$bookings = implode(' + ', $counts);

// password column is never selected
$rows = run_query($conn, "SELECT s.id, s.firstname, s.lastname, s.phone, s.email, ($bookings) AS bookings
                          FROM signin s $where ORDER BY s.id DESC LIMIT ? OFFSET ?",
                  $types . 'ii', array_merge($args, [$perPage, $offset]));

admin_header('Patients', 'users');
?>
<form class="filters" method="GET">
  <input type="text" name="q" placeholder="Search name, email, phone" value="<?= h($q) ?>">
  <button class="btn">Search</button>
  <a class="btn grey" href="users.php">Reset</a>
</form>
<div class="box"><table>
  <tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Bookings</th></tr>
  <?php $n = 0; while ($r = $rows->fetch_assoc()): $n++; ?>
  <tr>
    <td><?= (int)$r['id'] ?></td>
    <td><?= h($r['firstname'] . ' ' . $r['lastname']) ?></td>
    <td><?= h($r['phone']) ?></td>
    <td><?= h($r['email']) ?></td>
    <td><?= (int)$r['bookings'] ?></td>
  </tr>
  <?php endwhile; if (!$n): ?><tr><td colspan="5" class="empty">No patients found.</td></tr><?php endif; ?>
</table></div>
<?= pager($page, $pages) ?>
<?php admin_footer();