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

    $user_id          = $_SESSION['user_id'];           // i
    $doctor_name      = trim($_POST['doctor_name']);     // s
    $fullname         = trim($_POST['fullname']);         // s
    $age              = intval($_POST['age']);             // i
    $height           = floatval($_POST['height']);       // d
    $weight           = floatval($_POST['weight']);       // d
    $gender           = trim($_POST['gender']);           // s
    $mobile           = trim($_POST['mobile']);           // s
    $email            = trim($_POST['email']);             // s
    $address          = trim($_POST['address']);           // s
    $current_problem  = trim($_POST['current_problem']); // s
    $problem_area     = isset($_POST['problem_area']) ? implode(', ', $_POST['problem_area']) : ''; // s
    $bone_joint       = trim($_POST['bone_joint'] ?? ''); // s
    $bone_joint_other = trim($_POST['bone_joint_other'] ?? ''); // s
    $injuries         = trim($_POST['injuries'] ?? '');   // s
    $injuries_other   = trim($_POST['injuries_other'] ?? ''); // s
    $surgeries        = trim($_POST['surgeries'] ?? '');  // s
    $surgeries_other  = trim($_POST['surgeries_other'] ?? ''); // s
    $conditions       = trim($_POST['conditions'] ?? ''); // s
    $conditions_other = trim($_POST['conditions_other'] ?? ''); // s
    $visit_reason     = trim($_POST['visit_reason']);     // s
    $bed_rest         = trim($_POST['bed_rest']);          // s
    $appointment_date = trim($_POST['appointment_date']); // s
    $appointment_slot = trim($_POST['appointment_slot']); // s
    $token_number     = intval($_POST['token_number']);   // i
    // Total: i s s i d d s s s s s s s s s s s s s s s s s s s i = 26 params

    $sql = "INSERT INTO orthopedic_appointments (
        user_id, doctor_name, fullname, age, height, weight,
        gender, mobile, email, address,
        current_problem, problem_area,
        bone_joint, bone_joint_other,
        injuries, injuries_other,
        surgeries, surgeries_other,
        conditions, conditions_other,
        visit_reason, bed_rest,
        appointment_date, appointment_slot, token_number
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { die("Prepare failed: " . mysqli_error($conn)); }

    // 25 params: i s s i d d s s s s s s s s s s s s s s s s s s i
    mysqli_stmt_bind_param($stmt, "issiddssssssssssssssssssi",
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
        $current_problem,
        $problem_area,
        $bone_joint,
        $bone_joint_other,
        $injuries,
        $injuries_other,
        $surgeries,
        $surgeries_other,
        $conditions,
        $conditions_other,
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
    header("Location: Orthopedic.html");
    exit();
}
?>