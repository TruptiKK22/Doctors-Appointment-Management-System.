<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_prime";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $phone = trim($_POST["phone"]);
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $email = trim($_POST["email"]);
    $hashed_password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Check if email already exists
    $check = mysqli_prepare($conn, "SELECT id FROM signin WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>
                alert('Email already registered!');
                window.location='login.html';
              </script>";
        exit();
    }

    mysqli_stmt_close($check);

    // Insert data
    $stmt = mysqli_prepare($conn, "INSERT INTO signin (phone, firstname, lastname, email, password) VALUES (?, ?, ?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "sssss", $phone, $firstname, $lastname, $email, $hashed_password);

    if (mysqli_stmt_execute($stmt)) {
        
    echo "<script>
        alert('Account created successfully! Please login to continue.');
        window.location='login.html';
        </script>";

    } else {
        die("SQL Error: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>