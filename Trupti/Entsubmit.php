<?php
session_start();

// SESSION GUARD
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "health_prime";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id          = $_SESSION['user_id'];       // i
    $doctor_name      = trim($_POST['doctor_name']); // s
    $fullname         = trim($_POST['fullname']);     // s
    $age              = intval($_POST['age']);         // i
    $height           = floatval($_POST['height']);   // d
    $weight           = floatval($_POST['weight']);   // d
    $gender           = trim($_POST['gender']);       // s
    $mobile           = trim($_POST['mobile']);       // s
    $email            = trim($_POST['email']);         // s
    $address          = trim($_POST['address']);       // s
    $problem_location = trim($_POST['problem_location'] ?? ''); // s
    $specific_problem = trim($_POST['specific_problem'] ?? ''); // s
    $ear_surgery      = trim($_POST['ear_surgery'] ?? '');      // s
    $nose_surgery     = trim($_POST['nose_surgery'] ?? '');     // s
    $throat_surgery   = trim($_POST['throat_surgery'] ?? '');   // s
    $visit_reason     = trim($_POST['visit_reason']); // s
    $bed_rest         = trim($_POST['bed_rest']);      // s
    $appointment_date = trim($_POST['appointment_date']); // s
    $appointment_slot = trim($_POST['appointment_slot']); // s
    $token_number     = intval($_POST['token_number']); // i
    // Total: i s s i d d s s s s s s s s s s s s s i = 20 params

    $sql = "INSERT INTO ent_appointments (
        user_id, doctor_name, fullname, age, height, weight,
        gender, mobile, email, address,
        problem_location, specific_problem,
        ear_surgery, nose_surgery, throat_surgery,
        visit_reason, bed_rest,
        appointment_date, appointment_slot, token_number
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { die("Prepare failed: " . mysqli_error($conn)); }

    // 20 params: i s s i d d s s s s s s s s s s s s s i
    mysqli_stmt_bind_param($stmt, "issiddsssssssssssssi",
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
        $problem_location,
        $specific_problem,
        $ear_surgery,
        $nose_surgery,
        $throat_surgery,
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
            alert('Appointment booked! Your token is #" . str_pad($token_number, 2, '0', STR_PAD_LEFT) . "');
            window.location.href = 'Homepage.html';
        </script>";
        exit();
    } else {
        die("Error: " . mysqli_stmt_error($stmt));
    }

} else {
    header("Location: ENT.html");
    exit();
}
?>