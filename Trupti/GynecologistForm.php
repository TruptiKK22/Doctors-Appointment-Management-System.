<?php
session_start();

// SESSION GUARD
if (!isset($_SESSION['user_id'])) {
    $doctor = urlencode($_GET['doctor'] ?? '');
    $specialist = urlencode($_GET['specialist'] ?? '');
    $_SESSION['redirect_after_login'] = 'GynecologistForm.php?specialist=' . $specialist . '&doctor=' . $doctor;
    header("Location: login.html");
    exit();
}

$selected_doctor = isset($_GET['doctor']) ? htmlspecialchars($_GET['doctor']) : 'Not Selected';
$user_firstname  = $_SESSION['firstname'];
$user_email      = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Gynecologist Appointment - Health Prime</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --navy:#0d2137; --teal:#1a7f74; --teal-lt:#e6f4f2; --teal-md:#2da89a;
    --cream:#f6f8fb; --white:#ffffff; --border:#d4e2e0; --text:#0d2137;
    --muted:#6b8899; --red:#c0392b; --shadow:0 4px 24px rgba(13,33,55,0.10);
  }
  body { font-family:'Nunito',sans-serif; background:var(--cream); min-height:100vh; display:flex; justify-content:center; align-items:flex-start; padding:48px 16px 72px; }
  .page { width:100%; max-width:680px; }
  .hero { display:flex; align-items:center; gap:18px; margin-bottom:32px; }
  .back-btn-hero { display:inline-flex; align-items:center; gap:7px; flex-shrink:0; padding:8px 16px 8px 10px; background:var(--white); border:1.5px solid var(--border); border-radius:50px; cursor:pointer; font-family:'Nunito',sans-serif; font-size:0.83rem; font-weight:600; color:var(--muted); text-decoration:none; transition:all 0.2s ease; }
  .back-btn-hero:hover { background:var(--navy); border-color:var(--navy); color:#fff; }
  .hero-badge { width:58px; height:58px; flex-shrink:0; background:var(--navy); border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; }
  .hero-text h1 { font-family:'Playfair Display',serif; font-size:1.85rem; font-weight:600; color:var(--navy); }
  .hero-text span { font-size:0.82rem; color:var(--muted); }
  .doctor-banner { background:var(--navy); border-radius:14px; padding:16px 22px; display:flex; align-items:center; gap:14px; margin-bottom:24px; }
  .doctor-banner .avatar { width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg,#667eea,#764ba2); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:1.1rem; flex-shrink:0; }
  .doctor-banner .dinfo { flex:1; }
  .doctor-banner .dname { color:#fff; font-weight:700; font-size:1rem; }
  .doctor-banner .dspec { color:rgba(255,255,255,0.5); font-size:0.78rem; }
  .doctor-banner .dtag { background:rgba(45,168,154,0.25); color:#2da89a; padding:4px 12px; border-radius:50px; font-size:0.72rem; font-weight:700; }
  .user-banner { background:var(--teal-lt); border:1.5px solid rgba(26,127,116,0.2); border-radius:12px; padding:12px 18px; margin-bottom:24px; font-size:0.88rem; color:var(--teal); font-weight:600; }
  .card { background:var(--white); border-radius:24px; padding:40px 44px; box-shadow:var(--shadow); border:1px solid rgba(212,226,224,0.6); }
  .section+.section { margin-top:34px; }
  .sec-head { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
  .sec-num { width:26px; height:26px; flex-shrink:0; background:var(--navy); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:700; color:#fff; }
  .sec-title { font-family:'Playfair Display',serif; font-size:1.05rem; color:var(--navy); }
  .sec-line { flex:1; height:1px; background:linear-gradient(90deg,var(--border),transparent); }
  .grid { display:grid; gap:18px; } .g2 { grid-template-columns:1fr 1fr; } .full { grid-column:1/-1; }
  .field { display:flex; flex-direction:column; gap:6px; }
  label.lbl { font-size:0.79rem; font-weight:600; color:var(--navy); letter-spacing:0.3px; text-transform:uppercase; }
  label.lbl .req { color:var(--teal); margin-left:2px; }
  input[type="text"],input[type="email"],input[type="number"],input[type="tel"],input[type="date"],textarea,select { width:100%; padding:11px 15px; border:1.5px solid var(--border); border-radius:11px; background:var(--cream); font-family:'Nunito',sans-serif; font-size:0.9rem; color:var(--text); outline:none; transition:border-color 0.2s,box-shadow 0.2s; appearance:none; }
  input:focus,select:focus,textarea:focus { border-color:var(--teal-md); background:#fff; box-shadow:0 0 0 3.5px rgba(45,168,154,0.13); }
  .err-field { border-color:var(--red)!important; }
  input::placeholder,textarea::placeholder { color:#aac2be; font-weight:400; }
  textarea { resize:vertical; min-height:82px; }
  .sel-wrap { position:relative; }
  .sel-wrap::after { content:''; position:absolute; right:14px; top:50%; transform:translateY(-50%); border-left:5px solid transparent; border-right:5px solid transparent; border-top:6px solid var(--muted); pointer-events:none; }
  .sel-wrap select { padding-right:38px; cursor:pointer; }
  .hint { font-size:0.73rem; color:var(--muted); }
  .err-msg { font-size:0.73rem; color:var(--red); display:none; }
  .pill-group { display:flex; flex-wrap:wrap; gap:12px; }
  .pill { position:relative; }
  .pill input { position:absolute; opacity:0; width:0; height:0; }
  .pill label { display:flex; align-items:center; gap:9px; padding:10px 22px; border:1.5px solid var(--border); border-radius:50px; cursor:pointer; font-size:0.9rem; font-weight:600; color:var(--muted); background:var(--cream); transition:all 0.18s; }
  .pill label .dot { width:13px; height:13px; border-radius:50%; border:2px solid var(--border); background:#fff; flex-shrink:0; transition:all 0.18s; }
  .pill input:checked~label { border-color:var(--teal); background:var(--teal-lt); color:var(--teal); }
  .pill input:checked~label .dot { border-color:var(--teal); background:var(--teal); box-shadow:inset 0 0 0 2.5px #fff; }
  .checkbox-group { display:flex; flex-direction:column; gap:10px; }
  .checkbox-item { position:relative; }
  .checkbox-item input { position:absolute; opacity:0; width:0; height:0; }
  .checkbox-item label { display:flex; align-items:center; gap:12px; padding:12px 18px; border:1.5px solid var(--border); border-radius:11px; cursor:pointer; font-size:0.88rem; font-weight:500; color:var(--text); background:var(--cream); transition:all 0.18s; }
  .checkbox-item label::before { content:''; width:20px; height:20px; flex-shrink:0; border:2px solid var(--border); border-radius:4px; background:#fff; transition:all 0.18s; }
  .checkbox-item input:checked~label { border-color:var(--teal); background:var(--teal-lt); color:var(--teal); }
  .checkbox-item input:checked~label::before { background:var(--teal); border-color:var(--teal); }
  .checkbox-item input:checked~label::after { content:'✓'; position:absolute; left:23px; top:50%; transform:translateY(-50%); color:#fff; font-size:0.8rem; font-weight:700; }
  .toggle-pair { display:flex; gap:12px; flex-wrap:wrap; }
  .tog { position:relative; flex:1; min-width:140px; }
  .tog input { position:absolute; opacity:0; width:0; height:0; }
  .tog label { display:flex; flex-direction:column; align-items:center; padding:16px 12px; border:1.5px solid var(--border); border-radius:14px; cursor:pointer; font-size:0.87rem; font-weight:600; color:var(--muted); background:var(--cream); transition:all 0.2s; text-align:center; gap:6px; }
  .tog input:checked~label { border-color:var(--teal); background:var(--teal-lt); color:var(--teal); }
  .token-wrap { display:flex; flex-direction:column; gap:16px; }
  .appt-date-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .token-card { background:var(--navy); border-radius:18px; padding:20px 22px; display:flex; align-items:flex-start; gap:18px; position:relative; overflow:hidden; transition:opacity 0.35s; }
  .token-card::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(45,168,154,0.32) 0%,transparent 55%); }
  .token-card.inactive { opacity:0.4; pointer-events:none; }
  .token-badge { width:72px; height:72px; flex-shrink:0; background:rgba(255,255,255,0.07); border:2px solid rgba(45,168,154,0.4); border-radius:16px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:1px; position:relative; z-index:1; }
  .token-number { font-family:'Playfair Display',serif; font-size:2.1rem; font-weight:600; color:#fff; line-height:1; }
  .token-label-sm { font-size:0.58rem; font-weight:700; letter-spacing:1.1px; color:rgba(255,255,255,0.4); text-transform:uppercase; }
  .token-meta { position:relative; z-index:1; flex:1; }
  .tm-row { font-size:0.66rem; font-weight:700; letter-spacing:1px; color:rgba(255,255,255,0.4); text-transform:uppercase; margin-bottom:3px; }
  .tm-val { font-size:0.92rem; font-weight:700; color:#fff; margin-bottom:2px; }
  .tm-sub { font-size:0.71rem; color:rgba(255,255,255,0.38); }
  .token-status { display:flex; align-items:center; gap:7px; margin-top:9px; font-size:0.74rem; font-weight:700; }
  .status-dot { width:7px; height:7px; border-radius:50%; }
  .s-ok .status-dot { background:#2ecc71; animation:blink 1.8s infinite; } .s-ok { color:#2ecc71; }
  .s-idle .status-dot { background:rgba(255,255,255,0.3); } .s-idle { color:rgba(255,255,255,0.35); }
  .s-taken .status-dot { background:#e74c3c; } .s-taken { color:#ff8070; }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
  .reset-bar { display:flex; align-items:center; justify-content:space-between; background:var(--teal-lt); border:1.5px solid rgba(26,127,116,0.20); border-radius:12px; padding:11px 16px; }
  .reset-bar-left { font-size:0.78rem; font-weight:700; color:var(--teal); }
  .reset-bar-right { font-size:0.72rem; font-weight:600; color:var(--muted); }
  #countdown-timer { font-weight:700; color:var(--navy); }
  .divider { height:1px; background:var(--border); margin:32px 0 28px; }
  .submit-btn { width:100%; padding:15px; background:var(--navy); color:#fff; font-family:'Nunito',sans-serif; font-size:0.96rem; font-weight:700; border:none; border-radius:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; transition:background 0.2s,transform 0.15s; box-shadow:0 6px 20px rgba(13,33,55,0.25); }
  .submit-btn:hover { background:#162f47; transform:translateY(-1px); }
  .toast { position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(60px); background:var(--navy); color:#fff; padding:13px 28px; border-radius:50px; font-size:0.88rem; font-weight:600; box-shadow:0 10px 32px rgba(0,0,0,0.22); opacity:0; transition:all 0.4s; z-index:9999; border:1px solid rgba(45,168,154,0.4); }
  .toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
  @media (max-width:540px) { .card{padding:28px 20px;} .g2,.appt-date-grid{grid-template-columns:1fr;} .hero-text h1{font-size:1.45rem;} }
</style>
</head>
<body>
<div class="page">

  <div class="hero">
    <a href="Gynecologist.html" class="back-btn-hero">← Back</a>
    <div class="hero-badge">🌸</div>
    <div class="hero-text">
      <h1>Gynecologist Appointment</h1>
      <span>Welcome, <?php echo $user_firstname; ?> — fill in all fields carefully.</span>
    </div>
  </div>

  <div class="doctor-banner">
    <div class="avatar"><?php echo strtoupper(substr(explode(' ', $selected_doctor)[1] ?? 'D', 0, 2)); ?></div>
    <div class="dinfo">
      <div class="dname"><?php echo $selected_doctor; ?></div>
      <div class="dspec">Gynecologist · Health Prime</div>
    </div>
    <div class="dtag">Selected</div>
  </div>

  <div class="user-banner">✅ Logged in as: <?php echo $user_email; ?></div>

  <div class="card">
  <form id="pForm" method="POST" action="GynecologistSubmit.php" novalidate>

    <input type="hidden" name="doctor_name" value="<?php echo $selected_doctor; ?>">
    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
    <input type="hidden" name="token_number" id="hidden-token">

    <!-- 1. PERSONAL INFORMATION -->
    <div class="section">
      <div class="sec-head"><div class="sec-num">1</div><div class="sec-title">Personal Information</div><div class="sec-line"></div></div>
      <div class="grid g2">
        <div class="field full">
          <label class="lbl" for="fullname">Patient Full Name <span class="req">*</span></label>
          <input type="text" id="fullname" name="fullname" placeholder="Enter full name" required>
          <span class="err-msg" id="e-name">Full name is required.</span>
        </div>
        <div class="field">
          <label class="lbl" for="age">Age <span class="req">*</span></label>
          <input type="number" id="age" name="age" placeholder="e.g. 28" min="0" max="130" required>
          <span class="err-msg" id="e-age">Enter a valid age (0–130).</span>
        </div>
        <div class="field">
          <label class="lbl" for="height">Height (cm) <span class="req">*</span></label>
          <input type="number" id="height" name="height" placeholder="e.g. 165" min="50" max="250" step="0.1" required>
          <span class="err-msg" id="e-height">Enter a valid height.</span>
        </div>
        <div class="field">
          <label class="lbl" for="weight">Weight (kg) <span class="req">*</span></label>
          <input type="number" id="weight" name="weight" placeholder="e.g. 65" min="1" max="300" step="0.1" required>
          <span class="err-msg" id="e-weight">Enter a valid weight.</span>
        </div>
        <div class="field">
          <label class="lbl" for="gender">Gender <span class="req">*</span></label>
          <div class="sel-wrap">
            <select id="gender" name="gender" required>
              <option value="" disabled selected>Select gender</option>
              <option value="Female">Female</option>
              <option value="Male">Male</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <span class="err-msg" id="e-gender">Please select a gender.</span>
        </div>
        <div class="field">
          <label class="lbl" for="mobile">Mobile Number <span class="req">*</span></label>
          <input type="tel" id="mobile" name="mobile" placeholder="e.g. 9876543210" inputmode="numeric" required>
          <span class="hint">10-digit number, no spaces.</span>
          <span class="err-msg" id="e-mobile">Enter a valid 10-digit number.</span>
        </div>
        <div class="field full">
          <label class="lbl" for="email">Email Address <span class="req">*</span></label>
          <input type="email" id="email" name="email" value="<?php echo $user_email; ?>" placeholder="e.g. patient@email.com" required>
          <span class="hint">Token will be paired with this email.</span>
          <span class="err-msg" id="e-email">Enter a valid email address.</span>
        </div>
        <div class="field full">
          <label class="lbl" for="address">Address <span class="req">*</span></label>
          <textarea id="address" name="address" placeholder="House No., Street, City, State, PIN" required></textarea>
          <span class="err-msg" id="e-address">Please enter your address.</span>
        </div>
      </div>
    </div>

    <!-- 2. WHAT'S THE PROBLEM -->
    <div class="section">
      <div class="sec-head"><div class="sec-num">2</div><div class="sec-title">What's the Problem</div><div class="sec-line"></div></div>
      <div class="field">
        <label class="lbl" style="margin-bottom:8px;">Select all that apply <span class="req">*</span></label>
        <div class="checkbox-group">
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-periods" value="Periods (Menstrual problems)"><label for="prob-periods">Periods (Menstrual problems)</label></div>
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-pregnancy-plan" value="Pregnancy planning"><label for="prob-pregnancy-plan">Pregnancy planning</label></div>
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-pregnancy-care" value="Pregnancy related care"><label for="prob-pregnancy-care">Pregnancy related care</label></div>
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-hormonal" value="Hormonal issues"><label for="prob-hormonal">Hormonal issues</label></div>
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-infections" value="Infections"><label for="prob-infections">Infections</label></div>
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-fertility" value="Fertility issues"><label for="prob-fertility">Fertility issues</label></div>
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-general" value="General reproductive health"><label for="prob-general">General reproductive health</label></div>
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-pain" value="Lower abdominal pain"><label for="prob-pain">Lower abdominal pain</label></div>
          <div class="checkbox-item"><input type="checkbox" name="problem[]" id="prob-Other" value="Other"><label for="prob-Other">Other</label></div>
        </div>
        <span class="err-msg" id="e-problem" style="margin-top:6px;">Please select at least one problem.</span>
      </div>
    </div>

    <!-- 3. CURRENT PROBLEM -->
    <div class="section">
      <div class="sec-head"><div class="sec-num">3</div><div class="sec-title">Current Problem</div><div class="sec-line"></div></div>
      <div class="field">
        <label class="lbl" for="current_problem">What are you experiencing right now? <span class="req">*</span></label>
        <div class="sel-wrap">
          <select id="current_problem" name="current_problem" required>
            <option value="" disabled selected>Select current problem</option>
            <option value="Lower abdominal pain">Lower abdominal pain</option>
            <option value="Unusual discharge">Unusual discharge</option>
            <option value="Itching or infection">Itching or infection</option>
            <option value="Missed periods">Missed periods</option>
            <option value="Pregnancy doubt">Pregnancy doubt</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <span class="err-msg" id="e-current-problem">Please select a current problem.</span>
      </div>
    </div>

    <!-- 4. MEDICAL HISTORY -->
    <div class="section">
      <div class="sec-head"><div class="sec-num">4</div><div class="sec-title">Medical History</div><div class="sec-line"></div></div>
      <div class="grid g2">
        <div class="field">
          <label class="lbl" for="pcos">PCOS <span style="color:var(--muted);font-weight:400;">(Optional)</span></label>
          <div class="sel-wrap"><select id="pcos" name="pcos"><option value="">Select</option><option value="Yes">Yes</option><option value="No">No</option></select></div>
        </div>
        <div class="field">
          <label class="lbl" for="thyroid">Thyroid <span style="color:var(--muted);font-weight:400;">(Optional)</span></label>
          <div class="sel-wrap"><select id="thyroid" name="thyroid"><option value="">Select</option><option value="Yes">Yes</option><option value="No">No</option></select></div>
        </div>
        <div class="field">
          <label class="lbl" for="diabetes">Diabetes <span style="color:var(--muted);font-weight:400;">(Optional)</span></label>
          <div class="sel-wrap"><select id="diabetes" name="diabetes"><option value="">Select</option><option value="Yes">Yes</option><option value="No">No</option></select></div>
        </div>
        <div class="field">
          <label class="lbl" for="other_history">Other <span style="color:var(--muted);font-weight:400;">(Optional)</span></label>
          <input type="text" id="other_history" name="other_history" placeholder="e.g. Hypertension, Asthma, etc.">
        </div>
      </div>
    </div>

    <!-- 5. REASON FOR VISIT -->
    <div class="section">
      <div class="sec-head"><div class="sec-num">5</div><div class="sec-title">Reason for Visit</div><div class="sec-line"></div></div>
      <div class="field">
        <label class="lbl">Why is the patient visiting? <span class="req">*</span></label>
        <div class="pill-group">
          <div class="pill"><input type="radio" name="visit_reason" id="r-consult" value="Consultation"><label for="r-consult"><span class="dot"></span>Consultation</label></div>
          <div class="pill"><input type="radio" name="visit_reason" id="r-treat" value="Treatment"><label for="r-treat"><span class="dot"></span>Treatment</label></div>
          <div class="pill"><input type="radio" name="visit_reason" id="r-followup" value="Follow-Up"><label for="r-followup"><span class="dot"></span>Follow-Up</label></div>
        </div>
        <span class="err-msg" id="e-reason" style="margin-top:4px;">Please select a reason.</span>
      </div>
    </div>

    <!-- 6. BED REST -->
    <div class="section">
      <div class="sec-head"><div class="sec-num">6</div><div class="sec-title">Current Health Status</div><div class="sec-line"></div></div>
      <div class="field">
        <label class="lbl">Is the patient on bed rest? <span class="req">*</span></label>
        <div class="toggle-pair">
          <div class="tog"><input type="radio" name="bed_rest" id="br-yes" value="Yes"><label for="br-yes">🛏️ Yes<span style="font-size:0.72rem;color:#9db5b2;">Patient is on bed rest</span></label></div>
          <div class="tog"><input type="radio" name="bed_rest" id="br-no" value="No"><label for="br-no">🚶 No<span style="font-size:0.72rem;color:#9db5b2;">Able to visit in person</span></label></div>
        </div>
        <span class="err-msg" id="e-bedrest" style="margin-top:6px;">Please indicate bed rest status.</span>
      </div>
    </div>

    <!-- 7. APPOINTMENT & TOKEN -->
    <div class="section">
      <div class="sec-head"><div class="sec-num">7</div><div class="sec-title">Appointment &amp; Token</div><div class="sec-line"></div></div>
      <div class="token-wrap">
        <div class="appt-date-grid">
          <div class="field">
            <label class="lbl" for="appt_date">Appointment Date <span class="req">*</span></label>
            <input type="date" id="appt_date" name="appointment_date">
            <span class="err-msg" id="e-date">Please select a date.</span>
          </div>
          <div class="field">
            <label class="lbl" for="appt_slot">Preferred Slot <span class="req">*</span></label>
            <div class="sel-wrap">
              <select id="appt_slot" name="appointment_slot">
                <option value="" disabled selected>Choose slot</option>
                <option id="slot-morning" value="Morning (9 AM – 1 PM)">Morning (9 AM – 1 PM)</option>
                <option id="slot-afternoon" value="Afternoon (3 PM – 6 PM)">Afternoon (3 PM – 6 PM)</option>
              </select>
            </div>
            <span class="err-msg" id="e-slot">Please choose a slot.</span>
          </div>
        </div>
        <div class="token-card inactive" id="token-card">
          <div class="token-badge">
            <div class="token-number" id="token-num">—</div>
            <div class="token-label-sm">TOKEN</div>
          </div>
          <div class="token-meta">
            <div class="tm-row">Assigned To</div>
            <div class="tm-val" id="token-email-disp">—</div>
            <div class="tm-sub" id="token-date-disp">—</div>
            <div class="token-status s-idle" id="token-status-el">
              <div class="status-dot"></div>
              <span id="token-status-txt">Fill in date, slot and email above</span>
            </div>
          </div>
        </div>
        <div class="reset-bar">
          <div class="reset-bar-left">🕐 Token slots reset every 24 hours (6 Morning / 4 Afternoon)</div>
          <div class="reset-bar-right">Resets in &nbsp;<span id="countdown-timer">—</span></div>
        </div>
        <span class="err-msg" id="e-token">A valid token is required.</span>
      </div>
    </div>

    <div class="divider"></div>
    <button type="submit" class="submit-btn">✅ <span>Submit Appointment</span></button>

  </form>
  </div>
</div>

<div class="toast" id="toast">✅ Appointment booked! Token: <strong id="toast-token" style="margin-left:4px;color:#2da89a;"></strong></div>

<script>
const STORE_KEY = 'gyno_tokens';
const MORNING_MAX = 6, AFTERNOON_MAX = 4;
function loadStore() { try { return JSON.parse(localStorage.getItem(STORE_KEY)) || {}; } catch { return {}; } }
function saveStore(s) { localStorage.setItem(STORE_KEY, JSON.stringify(s)); }
function nextMidnight(d) { const x = new Date(d + 'T00:00:00'); x.setDate(x.getDate()+1); return x.getTime(); }
function getOrCreateDay(store, date) {
    const now = Date.now(), resetAt = nextMidnight(date);
    if (!store[date] || now >= store[date].morning.resetAt) {
        store[date] = { morning:{counter:1,resetAt,map:{}}, afternoon:{counter:1,resetAt,map:{}} };
    }
    return store[date];
}
function assignToken(date, email, slot) {
    if (!date||!email||!slot) return null;
    const key = email.toLowerCase().trim(), store = loadStore(), day = getOrCreateDay(store, date);
    const isMorning = slot.includes('Morning'), slotData = isMorning ? day.morning : day.afternoon, maxTokens = isMorning ? MORNING_MAX : AFTERNOON_MAX;
    if (slotData.map[key] !== undefined) { saveStore(store); return {token:slotData.map[key],isExisting:true,isFull:slotData.counter>maxTokens,slotName:isMorning?'Morning':'Afternoon'}; }
    if (slotData.counter > maxTokens) { saveStore(store); return {token:null,isExisting:false,isFull:true,slotName:isMorning?'Morning':'Afternoon'}; }
    const no = slotData.counter; slotData.map[key] = no; slotData.counter++; saveStore(store);
    return {token:no,isExisting:false,isFull:false,slotName:isMorning?'Morning':'Afternoon'};
}
const apptDateEl=document.getElementById('appt_date'), apptSlotEl=document.getElementById('appt_slot'), emailEl=document.getElementById('email');
const tokenCard=document.getElementById('token-card'), tokenNumEl=document.getElementById('token-num');
const tokenEmailEl=document.getElementById('token-email-disp'), tokenDateEl=document.getElementById('token-date-disp');
const tokenStEl=document.getElementById('token-status-el'), tokenStTxt=document.getElementById('token-status-txt');
let assignedToken=null, countdownTimer=null;
apptDateEl.min = new Date().toISOString().split('T')[0];
function fmtDate(s) { return s ? new Date(s+'T12:00:00').toLocaleDateString('en-IN',{weekday:'short',day:'numeric',month:'short',year:'numeric'}) : '—'; }
function setStatus(type, msg) { tokenStEl.className='token-status s-'+type; tokenStTxt.textContent=msg; }
function isValidEmail(e) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }
function refreshToken() {
    const date=apptDateEl.value, slot=apptSlotEl.value, email=emailEl.value.trim();
    if (!date||!slot||!email||!isValidEmail(email)) {
        tokenCard.classList.add('inactive'); tokenNumEl.textContent='—'; tokenEmailEl.textContent='—'; tokenDateEl.textContent='—';
        setStatus('idle','Fill in date, slot and email above'); assignedToken=null; document.getElementById('hidden-token').value=''; return;
    }
    const result = assignToken(date, email, slot);
    if (!result) return;
    if (result.isFull && result.token===null) { tokenCard.classList.add('inactive'); setStatus('taken',result.slotName+' slot is full'); assignedToken=null; document.getElementById('hidden-token').value=''; return; }
    assignedToken=result.token; document.getElementById('hidden-token').value=assignedToken;
    tokenCard.classList.remove('inactive');
    tokenNumEl.textContent=String(result.token).padStart(2,'0');
    tokenEmailEl.textContent=email.toLowerCase();
    tokenDateEl.textContent=fmtDate(date)+' · '+slot;
    if (result.isExisting) { setStatus('taken','This email already has a token for this slot'); }
    else { const left=(result.slotName==='Morning'?MORNING_MAX:AFTERNOON_MAX)-result.token; setStatus(left===0?'taken':'ok',left===0?'Last slot for '+result.slotName+'!':result.slotName+' · '+left+' slot(s) left'); }
    startCountdown(date);
}
function startCountdown(date) {
    clearInterval(countdownTimer);
    const store=loadStore(), resetAt=store[date]?store[date].morning.resetAt:nextMidnight(date), el=document.getElementById('countdown-timer');
    function tick() {
        const diff=resetAt-Date.now();
        if (diff<=0) { el.textContent='Resetting…'; clearInterval(countdownTimer); refreshToken(); return; }
        const h=Math.floor(diff/3600000), m=Math.floor((diff%3600000)/60000), s=Math.floor((diff%60000)/1000);
        el.textContent=`${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    }
    tick(); countdownTimer=setInterval(tick,1000);
}
function checkSlotAvailability() {
    const selectedDate = apptDateEl.value;
    const today = new Date().toISOString().split('T')[0];
    const morningOpt = document.getElementById('slot-morning');
    const afternoonOpt = document.getElementById('slot-afternoon');

    // Reset both slots first
    morningOpt.disabled = false;
    morningOpt.textContent = 'Morning (9 AM – 1 PM)';
    afternoonOpt.disabled = false;
    afternoonOpt.textContent = 'Afternoon (3 PM – 6 PM)';

    // Only check if user selected today's date
    if (selectedDate === today) {
        const now = new Date();
        const currentHour = now.getHours();
        const currentMin = now.getMinutes();
        const currentTime = currentHour + (currentMin / 60);

        // Morning slot cutoff: 9 AM
        if (currentTime >= 9) {
            morningOpt.disabled = true;
            morningOpt.textContent = 'Morning (9 AM – 1 PM) — Unavailable';
            // Reset if morning was selected
            if (apptSlotEl.value === 'Morning (9 AM – 1 PM)') {
                apptSlotEl.value = '';
                refreshToken();
            }
        }

        // Afternoon slot cutoff: 3 PM
        if (currentTime >= 15) {
            afternoonOpt.disabled = true;
            afternoonOpt.textContent = 'Afternoon (3 PM – 6 PM) — Unavailable';
            // Reset if afternoon was selected
            if (apptSlotEl.value === 'Afternoon (3 PM – 6 PM)') {
                apptSlotEl.value = '';
                refreshToken();
            }
        }
    }
}

apptDateEl.addEventListener('change', function() {
    checkSlotAvailability();
    refreshToken();
});
apptSlotEl.addEventListener('change',refreshToken);
emailEl.addEventListener('input',refreshToken);
startCountdown(new Date().toISOString().split('T')[0]);
document.getElementById('mobile').addEventListener('input',function(){this.value=this.value.replace(/\D/g,'');});
function showErr(id,show){const el=document.getElementById('e-'+id);if(el)el.style.display=show?'block':'none';}
function markField(id,bad){const el=document.getElementById(id);if(el)el.classList.toggle('err-field',bad);}
document.getElementById('pForm').addEventListener('submit',function(e){
    e.preventDefault(); let ok=true;
    const nameOk=document.getElementById('fullname').value.trim().length>=2; showErr('name',!nameOk); markField('fullname',!nameOk); if(!nameOk)ok=false;
    const av=parseInt(document.getElementById('age').value), ageOk=!isNaN(av)&&av>=0&&av<=130; showErr('age',!ageOk); markField('age',!ageOk); if(!ageOk)ok=false;
    const wv=parseFloat(document.getElementById('weight').value), weightOk=!isNaN(wv)&&wv>=1&&wv<=300; showErr('weight',!weightOk); markField('weight',!weightOk); if(!weightOk)ok=false;
    const gOk=document.getElementById('gender').value!==''; showErr('gender',!gOk); markField('gender',!gOk); if(!gOk)ok=false;
    const mOk=/^[0-9]{10}$/.test(document.getElementById('mobile').value.trim()); showErr('mobile',!mOk); markField('mobile',!mOk); if(!mOk)ok=false;
    const eOk=isValidEmail(emailEl.value.trim()); showErr('email',!eOk); markField('email',!eOk); if(!eOk)ok=false;
    const aOk=document.getElementById('address').value.trim().length>=5; showErr('address',!aOk); markField('address',!aOk); if(!aOk)ok=false;
    const problemOk=document.querySelectorAll('input[name="problem[]"]:checked').length>0; showErr('problem',!problemOk); if(!problemOk)ok=false;
    const cpOk=document.getElementById('current_problem').value!==''; showErr('current-problem',!cpOk); markField('current_problem',!cpOk); if(!cpOk)ok=false;
    const reasonOk=!!document.querySelector('input[name="visit_reason"]:checked'); showErr('reason',!reasonOk); if(!reasonOk)ok=false;
    const bedOk=!!document.querySelector('input[name="bed_rest"]:checked'); showErr('bedrest',!bedOk); if(!bedOk)ok=false;
    const dateOk=apptDateEl.value!==''; showErr('date',!dateOk); markField('appt_date',!dateOk); if(!dateOk)ok=false;
    const slotOk=apptSlotEl.value!==''; showErr('slot',!slotOk); markField('appt_slot',!slotOk); if(!slotOk)ok=false;
    const tokenOk=assignedToken!==null; showErr('token',!tokenOk); if(!tokenOk)ok=false;
    if(ok){this.submit();}
    else{const first=this.querySelector('.err-field');if(first)first.scrollIntoView({behavior:'smooth',block:'center'});}
});
</script>
</body>
</html>