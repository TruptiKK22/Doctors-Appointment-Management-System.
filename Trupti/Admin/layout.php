<?php
function admin_header($title, $active) {
    global $conn;
    $unread = unread_count($conn);
    $nav = [
        'index'        => ['index.php',        'Dashboard'],
        'appointments' => ['appointments.php', 'Appointments'],
        'users'        => ['users.php',        'Patients'],
        'doctors'      => ['doctors.php',      'Doctors'],
        'messages'     => ['messages.php',     'Messages'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title) ?> — Health Prime Admin</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand">Health <span>Prime</span></div>
    <?php foreach ($nav as $key => [$href, $label]): ?>
        <a href="<?= $href ?>" class="<?= $key === $active ? 'active' : '' ?>">
            <?= $label ?>
            <?php if ($key === 'messages' && $unread > 0): ?><span class="badge-n"><?= $unread ?></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
    <a href="logout.php" class="logout">Log out (<?= h($_SESSION['admin_name'] ?? '') ?>)</a>
</aside>
<main class="main">
    <h1><?= h($title) ?></h1>
    <?php if ($f = flash()): ?><div class="flash"><?= h($f) ?></div><?php endif; ?>
<?php
}

function admin_footer() {
    echo "</main>\n</body>\n</html>";
}