<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

// ---- Mark read/unread or delete (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0 && $action === 'read')   { run_query($conn, "UPDATE contact_messages SET is_read = 1 WHERE id = ?", "i", [$id]); flash('Marked as read.'); }
    if ($id > 0 && $action === 'unread') { run_query($conn, "UPDATE contact_messages SET is_read = 0 WHERE id = ?", "i", [$id]); flash('Marked as unread.'); }
    if ($id > 0 && $action === 'delete') { run_query($conn, "DELETE FROM contact_messages WHERE id = ?", "i", [$id]); flash('Message deleted.'); }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$onlyUnread = ($_GET['show'] ?? '') === 'unread';
$where = $onlyUnread ? "WHERE is_read = 0" : "";

$perPage = 10;
$total = (int)$conn->query("SELECT COUNT(*) c FROM contact_messages $where")->fetch_assoc()['c'];
[$page, $pages, $offset] = paginate($total, $perPage);
$rows = run_query($conn, "SELECT * FROM contact_messages $where ORDER BY id DESC LIMIT ? OFFSET ?", "ii", [$perPage, $offset]);

function msg_form($id, $action, $label, $class = 'grey', $confirm = '') {
    $js = $confirm ? " onsubmit=\"return confirm('" . $confirm . "')\"" : '';
    return '<form method="POST" class="inline"' . $js . '>' . csrf_field()
        . '<input type="hidden" name="id" value="' . (int)$id . '"><input type="hidden" name="action" value="' . $action . '">'
        . '<button class="btn ' . $class . '">' . $label . '</button></form> ';
}

admin_header('Messages', 'messages');
?>
<div class="filters">
  <a class="btn <?= $onlyUnread ? 'grey' : '' ?>" href="messages.php">All</a>
  <a class="btn <?= $onlyUnread ? '' : 'grey' ?>" href="messages.php?show=unread">Unread only</a>
</div>

<?php $n = 0; while ($m = $rows->fetch_assoc()): $n++; $unread = empty($m['is_read']); ?>
<div class="msg <?= $unread ? 'unread' : '' ?>">
  <h3><?= h($m['subject']) ?></h3>
  <div class="meta"><?= h($m['fullname']) ?> · <a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a> · <?= h($m['phone']) ?></div>
  <p><?= nl2br(h($m['message'])) ?></p>
  <?= $unread ? msg_form($m['id'], 'read', 'Mark as read', '') : msg_form($m['id'], 'unread', 'Mark as unread') ?>
  <?= msg_form($m['id'], 'delete', 'Delete', 'red', 'Delete this message permanently?') ?>
</div>
<?php endwhile; if (!$n): ?><div class="box"><div class="empty">No messages.</div></div><?php endif; ?>
<?= pager($page, $pages) ?>
<?php admin_footer();