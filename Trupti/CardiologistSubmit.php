<?php
session_start();

// ==========================================
// SESSION GUARD
// ==========================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// ==========================================
// DATABASE CONNECTION
// ==========================================
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "health_prime";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ==========================================
// COLLECT FORM DATA
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id             = $_SESSION['user_id'];
    $doctor_name         = trim($_POST['doctor_name']);
    $fullname            = trim($_POST['fullname']);
    $age                 = intval($_POST['age']);
    $height              = floatval($_POST['height']);
    $weight              = floatval($_POST['weight']);
    $gender              = trim($_POST['gender']);
    $mobile              = trim($_POST['mobile']);
    $email               = trim($_POST['email']);
    $address             = trim($_POST['address']);
    $current_issue       = trim($_POST['current_issue']);
    $blood_pressure      = trim($_POST['blood_pressure']);
    $heart_attack        = trim($_POST['heart_attack']);
    $heart_attack_count  = ($heart_attack === 'Yes') ? intval($_POST['heart_attack_count']) : 0;
    $bypass_surgery      = trim($_POST['bypass_surgery']);
    $visit_reason        = trim($_POST['visit_reason']);
    $bed_rest            = trim($_POST['bed_rest']);
    $appointment_date    = trim($_POST['appointment_date']);
    $appointment_slot    = trim($_POST['appointment_slot']);
    $token_number        = intval($_POST['token_number']);

    // ==========================================
    // INSERT INTO DATABASE
    // ==========================================
    $sql = "INSERT INTO cardiologist_appointments (
        user_id, doctor_name, fullname, age, height, weight,
        gender, mobile, email, address,
        current_issue, blood_pressure, heart_attack,
        heart_attack_count, bypass_surgery,
        visit_reason, bed_rest,
        appointment_date, appointment_slot, token_number
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "issiddssssssssissssi",
        $user_id,
        $doctor_name,
        $fullname,
        $age,
        $height,
        $weight,
        $gender,
        $mobile,
        $email,
        $address,
        $current_issue,
        $blood_pressure,
        $heart_attack,
        $heart_attack_count,
        $bypass_surgery,
        $visit_reason,
        $bed_rest,
        $appointment_date,
        $appointment_slot,
        $token_number
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        echo "<script>
            alert('Appointment booked successfully! Your token is #" . str_pad($token_number, 2, '0', STR_PAD_LEFT) . "');
            window.location.href = 'Homepage.html';
        </script>";
        exit();

    } else {

        die("Error saving appointment: " . mysqli_stmt_error($stmt));

    }

} else {

    header("Location: cardiologist.html");
    exit();

}
?>