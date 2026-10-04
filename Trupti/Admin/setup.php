<?php
// ONE-TIME SETUP: creates the first admin account. DELETE THIS FILE afterwards.
require_once __DIR__ . '/auth.php';

$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)");

$count = (int)$conn->query("SELECT COUNT(*) c FROM admins")->fetch_assoc()['c'];
if ($count > 0) {
    die("Setup is already done. Please delete admin/setup.php from your server.");
}

$error = ''; $done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u  = trim($_POST['username'] ?? '');
    $p  = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';

    if (strlen($u) < 3 || strlen($u) > 50)  { $error = 'Username must be 3–50 characters.'; }
    elseif (strlen($p) < 10)                { $error = 'Password must be at least 10 characters.'; }
    elseif ($p !== $p2)                     { $error = 'Passwords do not match.'; }
    else {
        run_query($conn, "INSERT INTO admins (username, password) VALUES (?, ?)", "ss", [$u, password_hash($p, PASSWORD_DEFAULT)]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Setup — Health Prime</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="card">
    <h1>Admin Setup</h1>
    <?php if ($done): ?>
      <div class="flash">Admin account created. <strong>Now delete admin/setup.php</strong>, then <a href="login.php">log in</a>.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
      <form method="POST" autocomplete="off">
        <?= csrf_field() ?>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required minlength="3" maxlength="50">
        <label for="password">Password (min 10 characters)</label>
        <input type="password" id="password" name="password" required minlength="10">
        <label for="password2">Confirm password</label>
        <input type="password" id="password2" name="password2" required minlength="10">
        <button type="submit" class="btn">Create admin</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>