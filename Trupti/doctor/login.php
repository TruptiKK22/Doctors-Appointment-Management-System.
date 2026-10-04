<?php
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['doctor_id'])) { header("Location: index.php"); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_SESSION['login_lock'] ?? 0) > time()) {
        $error = 'Too many attempts. Please wait a minute and try again.';
    } else {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';

        $row = run_query($conn, "SELECT * FROM doctors WHERE username = ?", "s", [$u])->fetch_assoc();

        if ($row && password_verify($p, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['doctor_id']   = $row['id'];
            $_SESSION['doctor_name'] = $row['doctor_name'];
            $_SESSION['specialty']   = $row['specialty'];
            unset($_SESSION['login_fails'], $_SESSION['login_lock']);
            header("Location: index.php");
            exit();
        }

        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_lock']  = time() + 60;
            $_SESSION['login_fails'] = 0;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Login — Health Prime</title>
<link rel="stylesheet" href="doctor.css">
</head>
<body>
<div class="login-wrap">
  <div class="card">
    <h1>Doctor Login</h1>
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <form method="POST" autocomplete="off">
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <button type="submit" class="btn">Login</button>
    </form>
  </div>
</div>
</body>
</html>