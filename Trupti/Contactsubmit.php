<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "health_prime";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $subject  = trim($_POST['subject']);
    $message  = trim($_POST['message']);

    // Basic validation
    if (empty($fullname) || empty($email) || empty($phone) || empty($subject) || empty($message)) {
        echo "<script>
            alert('Please fill in all fields.');
            window.history.back();
        </script>";
        exit();
    }

    $sql = "INSERT INTO contact_messages (fullname, email, phone, subject, message)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { die("Prepare failed: " . mysqli_error($conn)); }

    mysqli_stmt_bind_param($stmt, "sssss", $fullname, $email, $phone, $subject, $message);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        echo "<script>
            alert('Thank you, ' + " . json_encode($fullname) . " + '! Your message has been sent. We will get back to you soon.');
            window.location.href = 'Homepage.html';
        </script>";
        exit();
    } else {
        die("Error: " . mysqli_stmt_error($stmt));
    }

} else {
    header("Location: contact.html");
    exit();
}
?>