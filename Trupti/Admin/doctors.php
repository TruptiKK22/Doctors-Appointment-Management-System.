<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['do'] ?? '') === 'delete') {
        run_query($conn, "DELETE FROM doctors WHERE id = ?", "i", [(int)($_POST['id'] ?? 0)]);
        flash('Doctor account removed.');
        header("Location: doctors.php");
        exit();
    }

    $specialty = $_POST['specialty'] ?? '';
    $name      = $_POST['doctor_name'] ?? '';
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (!isset(DOCTOR_NAMES[$specialty]) || !in_array($name, DOCTOR_NAMES[$specialty], true)) {
        $error = 'Please choose a specialty and doctor name from the list.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters.';
    } else {
        try {
            run_query($conn, "INSERT INTO doctors (specialty, doctor_name, username, password) VALUES (?, ?, ?, ?)",
                "ssss", [$specialty, $name, $username, password_hash($password, PASSWORD_DEFAULT)]);
            flash('Doctor account created.');
            header("Location: doctors.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $error = str_contains($e->getMessage(), 'Duplicate')
                ? 'That username or that doctor already has an account.'
                : 'Could not create the account.';
        }
    }
}

$doctors = $conn->query("SELECT * FROM doctors ORDER BY specialty, doctor_name");

admin_header('Doctors', 'doctors');
?>
<h2>Add a doctor account</h2>
<?php if ($error): ?><div class="flash" style="background:#fde2e0;border-color:#f3b5ae;color:#b42318"><?= h($error) ?></div><?php endif; ?>

<div class="box" style="padding:20px 22px;max-width:480px">
  <form method="POST">
    <?= csrf_field() ?>
    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px">Specialty</label>
    <select name="specialty" id="specialty" required style="width:100%;padding:10px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:14px"
            onchange="fillNames()">
      <option value="">Choose…</option>
      <?php foreach (DOCTOR_NAMES as $spec => $_): ?><option value="<?= h($spec) ?>"><?= h($spec) ?></option><?php endforeach; ?>
    </select>

    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px">Doctor name</label>
    <select name="doctor_name" id="doctor_name" required style="width:100%;padding:10px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:14px">
      <option value="">Choose a specialty first…</option>
    </select>

    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px">Username</label>
    <input type="text" name="username" required minlength="3" style="width:100%;padding:10px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:14px">

    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px">Password (min 10 characters)</label>
    <input type="password" name="password" required minlength="10" style="width:100%;padding:10px;border:1.5px solid #e6e2d9;border-radius:8px;margin-bottom:16px">

    <button class="btn">Create account</button>
  </form>
</div>

<script>
  const names = <?= json_encode(DOCTOR_NAMES) ?>;
  function fillNames() {
    const spec = document.getElementById('specialty').value;
    const sel  = document.getElementById('doctor_name');
    sel.innerHTML = '<option value="">Choose…</option>';
    (names[spec] || []).forEach(n => {
      const o = document.createElement('option'); o.value = n; o.textContent = n; sel.appendChild(o);
    });
  }
</script>

<h2>Existing doctor accounts</h2>
<div class="box"><table>
  <tr><th>Specialty</th><th>Doctor</th><th>Username</th><th>Availability</th><th></th></tr>
  <?php $n = 0; while ($d = $doctors->fetch_assoc()): $n++; ?>
  <tr>
    <td><?= h($d['specialty']) ?></td>
    <td><?= h($d['doctor_name']) ?></td>
    <td><?= h($d['username']) ?></td>
    <td><?= $d['is_available'] ? '<span class="st st-confirmed">Available</span>' : '<span class="st st-cancelled">Unavailable</span>' ?></td>
    <td>
      <form method="POST" class="inline" onsubmit="return confirm('Remove this doctor\'s login? They will no longer be able to access their dashboard.')">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="delete">
        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
        <button class="btn red">Remove</button>
      </form>
    </td>
  </tr>
  <?php endwhile; if (!$n): ?><tr><td colspan="5" class="empty">No doctor accounts yet.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer();