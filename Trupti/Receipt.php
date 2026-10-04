<?php
session_start();
require_once 'db.php';

// Only the logged-in patient who made the booking can see the receipt
if (empty($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$spec = $_GET['specialist'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if (!isset(SPECIALTIES[$spec]) || $id < 1) {
    header("Location: Homepage.html");
    exit();
}

$table = SPECIALTIES[$spec]['table'];
$stmt  = $conn->prepare("SELECT * FROM `$table` WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

if (!$r) {
    header("Location: Homepage.html");
    exit();
}

$mobile = (string)$r['mobile'];
$maskedMobile = str_repeat('*', max(0, strlen($mobile) - 4)) . substr($mobile, -4);
$dateText = date('j M Y', strtotime($r['appointment_date']));
$token    = str_pad($r['token_number'], 2, '0', STR_PAD_LEFT);
$ref      = SPECIALTIES[$spec]['code'] . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
$status   = $r['status'] ?? 'Pending';
// A Confirmed appointment with a note attached means admin rescheduled it on the
// doctor's request — flag it so the receipt can call that out clearly up top.
$isRescheduled = ($status === 'Confirmed' && !empty($r['status_note']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointment Receipt — Health Prime</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',Tahoma,sans-serif;background:linear-gradient(135deg,#0a9396,#0d1b2a);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:24px}
  .wrap{width:100%;max-width:440px}
  #receipt{background:#fff;border-radius:16px;padding:32px 30px;box-shadow:0 20px 40px rgba(0,0,0,.25)}
  .head{text-align:center;border-bottom:2px dashed #cfd8dc;padding-bottom:18px;margin-bottom:18px}
  .head h1{font-size:1.6rem;letter-spacing:2px;color:#0a9396;text-transform:uppercase}
  .head p{color:#5a6a7a;margin-top:4px}
  .ref{font-size:.8rem;color:#8492a0;margin-top:8px}
  .group{padding:6px 0 14px;border-bottom:1px dashed #e3e8ea;margin-bottom:12px}
  .group:last-of-type{border-bottom:none}
  .row{display:flex;justify-content:space-between;gap:16px;padding:5px 0;font-size:.95rem}
  .row span:first-child{color:#6b7a89}.row span:last-child{color:#0d1b2a;font-weight:600;text-align:right}
  .token{font-size:1.15rem;color:#0a9396}
  .status{text-align:center;margin-top:8px}
  .st{display:inline-block;padding:6px 20px;border-radius:30px;font-weight:700;letter-spacing:1px;font-size:.85rem;text-transform:uppercase}
  .st-pending{background:#fff3cd;color:#8a6d00}.st-confirmed{background:#d1f2e0;color:#0a6b3d}.st-cancelled{background:#fde2e0;color:#b42318}
  .st-completed{background:#dbe9fb;color:#0b4a8f}.st-no-show{background:#eee0f5;color:#6a1b9a}
  .note{text-align:center;color:#6b7a89;font-size:.8rem;margin-top:12px;padding:0 6px}
  .note.confirmed{color:#0a6b3d;font-weight:600}
  .note.cancelled{color:#b42318;font-weight:600}
  .alert{display:flex;gap:12px;align-items:flex-start;border-radius:12px;padding:16px 18px;margin-bottom:20px;text-align:left}
  .alert .icon{font-size:1.5rem;line-height:1;flex-shrink:0}
  .alert h3{font-size:.95rem;margin-bottom:4px}
  .alert p{font-size:.85rem;line-height:1.55;color:#4a4a4a}
  .alert p strong{color:inherit}
  .alert.cancelled{background:#fef2f0;border:1.5px solid #f3b5ae}
  .alert.cancelled h3{color:#b42318}
  .alert.rescheduled{background:#fff8e8;border:1.5px solid #f0d27a}
  .alert.rescheduled h3{color:#8a6d00}
  @media print{ .alert{-webkit-print-color-adjust:exact;print-color-adjust:exact} }
  .refresh{display:block;text-align:center;margin-top:10px;font-size:.78rem;color:#0a9396;text-decoration:none}
  .refresh:hover{text-decoration:underline}
  .actions{display:flex;flex-direction:column;gap:10px;margin-top:18px}
  .btn{display:block;text-align:center;padding:13px;border-radius:10px;border:none;font-size:1rem;font-weight:600;cursor:pointer;text-decoration:none;font-family:inherit}
  .btn.primary{background:#fff;color:#0a9396}.btn.outline{background:transparent;color:#fff;border:2px solid rgba(255,255,255,.7)}
  @media print{
    body{background:#fff;padding:0;display:block}
    .wrap{max-width:100%}
    #receipt{box-shadow:none;border:1px solid #ccc}
    .actions{display:none}
  }
</style>
</head>
<body>
<div class="wrap">
  <div id="receipt">
    <div class="head">
      <h1>Health Prime</h1>
      <p>Appointment Confirmation</p>
      <div class="ref">Booking ID: <?= h($ref) ?></div>
    </div>

    <?php if ($status === 'Cancelled'): ?>
      <div class="alert cancelled">
        <div class="icon">⚠️</div>
        <div>
          <h3>This appointment has been cancelled</h3>
          <p><?= !empty($r['status_note']) ? '<strong>Reason:</strong> ' . h($r['status_note']) : 'Please book a new slot if you still need to visit.' ?></p>
        </div>
      </div>
    <?php elseif ($isRescheduled): ?>
      <div class="alert rescheduled">
        <div class="icon">🔄</div>
        <div>
          <h3>This appointment has been rescheduled</h3>
          <p>New date: <strong><?= h($dateText) ?></strong> · New slot: <strong><?= h($r['appointment_slot']) ?></strong></p>
          <p style="margin-top:4px"><strong>Note:</strong> <?= h($r['status_note']) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <div class="group">
      <div class="row"><span>Patient</span><span><?= h($r['fullname']) ?></span></div>
      <div class="row"><span>Age</span><span><?= h($r['age']) ?></span></div>
      <div class="row"><span>Mobile</span><span><?= h($maskedMobile) ?></span></div>
    </div>

    <div class="group">
      <div class="row"><span>Specialist</span><span><?= h($spec) ?></span></div>
      <div class="row"><span>Doctor</span><span><?= h($r['doctor_name']) ?></span></div>
    </div>

    <div class="group">
      <div class="row"><span>Date</span><span><?= h($dateText) ?></span></div>
      <div class="row"><span>Slot</span><span><?= h($r['appointment_slot']) ?></span></div>
      <div class="row"><span>Token</span><span class="token">#<?= h($token) ?></span></div>
    </div>

    <div class="status"><span class="st st-<?= h(strtolower($status)) ?>"><?= h($status) ?></span></div>
    <?php if ($status === 'Pending'): ?>
      <div class="note">Your appointment is awaiting confirmation. You'll be notified by email once it's confirmed.</div>
    <?php elseif ($status === 'Confirmed' && !$isRescheduled): ?>
      <div class="note confirmed">✓ Your appointment is confirmed. Please arrive 10 minutes early with any previous reports.</div>
    <?php elseif ($status === 'Completed'): ?>
      <div class="note confirmed">This visit has been completed.</div>
    <?php elseif ($status === 'No-show'): ?>
      <div class="note cancelled">This appointment was marked as a no-show. Please book a new slot if you still need to visit.</div>
    <?php endif; ?>
    <a class="refresh" href="Receipt.php?specialist=<?= urlencode($spec) ?>&id=<?= (int)$id ?>">↻ Check latest status</a>
  </div>

  <div class="actions">
    <button class="btn primary" onclick="window.print()">Print Receipt</button>
    <button class="btn primary" id="dl" onclick="downloadReceipt()">Download Receipt</button>
    <a class="btn outline" href="Homepage.html">Back to Home</a>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function downloadReceipt() {
  const btn = document.getElementById('dl');
  try {
    btn.textContent = 'Preparing…';
    const canvas = await html2canvas(document.getElementById('receipt'), { scale: 2, backgroundColor: '#ffffff' });
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a5' });
    const w = pdf.internal.pageSize.getWidth() - 20;
    const h = canvas.height * w / canvas.width;
    pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 10, 10, w, h);
    pdf.save('HealthPrime-Receipt-' + <?= json_encode($ref) ?> + '.pdf');
  } catch (e) {
    window.print();   // fallback: "Save as PDF" from the print dialog
  } finally {
    btn.textContent = 'Download Receipt';
  }
}
</script>
</body>
</html>