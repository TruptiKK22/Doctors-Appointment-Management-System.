<?php
// Shared database connection + helpers (used by Receipt.php and the admin panel)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli("localhost", "root", "", "health_prime");
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("DB connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Something went wrong. Please try again later.");
}

const SPECIALTIES = [
    'Cardiologist' => ['table' => 'cardiologist_appointments', 'code' => 'CAR'],
    'Gynecologist' => ['table' => 'gynecologist_appointments', 'code' => 'GYN'],
    'ENT'          => ['table' => 'ent_appointments',          'code' => 'ENT'],
    'Orthopedic'   => ['table' => 'orthopedic_appointments',   'code' => 'ORT'],
];

const STATUSES = ['Pending', 'Confirmed', 'Cancelled', 'Completed', 'No-show'];

// Exact doctor_name strings used in Cardiologist.html, Gynecologist.html, Ent.html, Orthopedic.html.
// A doctor's dashboard login is matched to their appointments by this exact text,
// since the appointment tables store the doctor's name, not an ID — so when creating
// a doctor account, pick a name from this list rather than typing it freehand.
const DOCTOR_NAMES = [
    'Cardiologist' => ['Dr. Vikas Patel', 'Dr. Bhoomika Gupta', 'Dr. Neha Sharma', 'Dr. Amit Sharma'],
    'Gynecologist' => ["Dr. Neethu Joseph", 'Dr. Ritu Nair', "Dr. John D'Souza", 'Dr. Neha Joshi'],
    'ENT'          => ['Dr. Vikram Tiwari', 'Dr. Bhavna Verma', 'Dr. Alisha George', 'Dr. Deepak Yadav'],
    'Orthopedic'   => ['Dr. Amit Kulkarni', 'Dr. Karan Meheta', 'Dr. Sneha Iyer', 'Dr. Daniel Mathew'],
];

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}