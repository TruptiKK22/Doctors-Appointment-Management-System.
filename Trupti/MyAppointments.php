<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// One list across all 4 specialties, newest first
$sql = "SELECT * FROM (" . implode(" UNION ALL ", array_map(
    fn($name, $s) => "SELECT '$name' AS specialty, id, doctor_name, fullname, appointment_date, appointment_slot, token_number, status
                       FROM `{$s['table']}` WHERE user_id = " . (int)$_SESSION['user_id'],
    array_keys(SPECIALTIES), SPECIALTIES
)) . ") t ORDER BY appointment_date DESC, id DESC";

$rows = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Appointments — Health Prime</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root{--teal:#0a9396;--dark:#0d1b2a;--cream:#fdf8f0;--text:#2c3e50;--muted:#6b7a89}
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--text);min-height:100vh}
  .back-btn{position:fixed;top:15px;left:15px;border:none;background:none;font-size:24px;cursor:pointer}
  .wrap{max-width:760px;margin:0 auto;padding:6rem 1.5rem 3rem}
  h1{font-family:'Playfair Display',serif;font-size:clamp(1.8rem,4vw,2.4rem);color:var(--dark);margin-bottom:1.8rem;text-align:center}
  .card{background:#fff;border:1px solid rgba(10,147,150,.18);border-radius:14px;padding:20px 22px;margin-bottom:14px;
        display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;text-decoration:none;color:inherit}
  .card:hover{border-color:var(--teal)}
  .info h2{font-size:1.05rem;color:var(--dark);margin-bottom:4px}
  .info p{font-size:.88rem;color:var(--muted)}
  .right{text-align:right}
  .token{font-weight:700;color:var(--teal);font-size:1.05rem}
  .st{display:inline-block;margin-top:6px;padding:4px 14px;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:uppercase}
  .st-pending{background:#fff3cd;color:#8a6d00}.st-confirmed{background:#d1f2e0;color:#0a6b3d}.st-cancelled{background:#fde2e0;color:#b42318}
  .st-completed{background:#dbe9fb;color:#0b4a8f}.st-no-show{background:#eee0f5;color:#6a1b9a}
  .empty{text-align:center;color:var(--muted);background:#fff;border-radius:14px;padding:40px 20px}
  .empty a{color:var(--teal);font-weight:600}
</style>
</head>
<body>
<button class="back-btn" onclick="history.back()">←</button>
<div class="wrap">
  <h1>My Appointments</h1>

  <?php $n = 0; while ($r = $rows->fetch_assoc()): $n++;
        $status = $r['status'] ?? 'Pending';
        $dateText = date('j M Y', strtotime($r['appointment_date']));
        $token = str_pad($r['token_number'], 2, '0', STR_PAD_LEFT); ?>
  <a class="card" href="Receipt.php?specialist=<?= urlencode($r['specialty']) ?>&id=<?= (int)$r['id'] ?>">
    <div class="info">
      <h2><?= htmlspecialchars($r['specialty']) ?> — <?= htmlspecialchars($r['doctor_name']) ?></h2>
      <p><?= htmlspecialchars($dateText) ?> · <?= htmlspecialchars($r['appointment_slot']) ?></p>
    </div>
    <div class="right">
      <div class="token">#<?= $token ?></div>
      <span class="st st-<?= strtolower($status) ?>"><?= htmlspecialchars($status) ?></span>
    </div>
  </a>
  <?php endwhile; if (!$n): ?>
  <div class="empty">You haven't booked any appointments yet.<br><br><a href="Doctype.html">Book one now →</a></div>
  <?php endif; ?>
</div>
</body>
</html>