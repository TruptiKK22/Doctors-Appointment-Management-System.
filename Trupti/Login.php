<?php

session_start();


// ==========================================
// DATABASE CONNECTION
// ==========================================

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_prime";


$conn = mysqli_connect(
    $servername,
    $username,
    $password,
    $dbname
);


// Check connection

if (!$conn) {

    die(
        "Database connection failed: " .
        mysqli_connect_error()
    );

}


// ==========================================
// CHECK FORM SUBMISSION
// ==========================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    // Get login data

    $email = trim($_POST["email"]);

    $user_password = $_POST["password"];


    // ==========================================
    // FIND USER
    // ==========================================

    $sql = "
        SELECT
            id,
            firstname,
            email,
            password
        FROM signin
        WHERE email = ?
    ";


    $stmt = mysqli_prepare(
        $conn,
        $sql
    );


    if (!$stmt) {

        die(
            "Prepare failed: " .
            mysqli_error($conn)
        );

    }


    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result(
        $stmt
    );


    // ==========================================
    // CHECK USER
    // ==========================================

    if (mysqli_num_rows($result) == 1) {


        $user = mysqli_fetch_assoc(
            $result
        );


        // ==========================================
        // CHECK PASSWORD
        // ==========================================

        if (
            password_verify(
                $user_password,
                $user["password"]
            )
        ) {


            // ==========================================
            // CREATE LOGIN SESSION
            // ==========================================

            $_SESSION["user_id"] =
                $user["id"];


            $_SESSION["firstname"] =
                $user["firstname"];


            $_SESSION["email"] =
                $user["email"];


            // ==========================================
            // CHECK FOR PREVIOUS PAGE
            // ==========================================

            if (
                isset(
                    $_SESSION[
                        "redirect_after_login"
                    ]
                )
            ) {


                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
    echo "<script>
        alert('Login successful!');
        window.location.href = " . json_encode($redirect) . ";
        </script>";
}

            } else {


                // ======================================
                // NORMAL LOGIN
                // ======================================

                echo "<script>

                    alert('Login successful!');

                    window.location.href =
                    'Homepage.html';

                </script>";

            }


            exit();


        } else {


            // ==========================================
            // WRONG PASSWORD
            // ==========================================

            echo "<script>

                alert('Incorrect password!');

                window.location.href =
                'login.html';

            </script>";


            exit();

        }


    } else {


        // ==========================================
        // USER DOES NOT EXIST
        // ==========================================

        echo "<script>

            alert(
                'No account found with this email!'
            );

            window.location.href =
            'signin.html';

        </script>";


        exit();

    }


    mysqli_stmt_close($stmt);

}


mysqli_close($conn);

?>