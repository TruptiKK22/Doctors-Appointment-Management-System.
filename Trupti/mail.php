<?php
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Sends the status-change email to a patient. Never throws — a failed email
// must not stop an admin from confirming/cancelling an appointment, so
// errors are logged instead and the caller gets a plain true/false back.
function send_status_email(array $appt, string $specialty, string $status, ?string $note = null): bool {
    $cfgPath = __DIR__ . '/mail_config.php';
    if (!file_exists($cfgPath)) {
        error_log("mail_config.php missing — email not sent");
        return false;
    }
    $cfg = require $cfgPath;

    if (empty($appt['email']) || !filter_var($appt['email'], FILTER_VALIDATE_EMAIL)) {
        error_log("send_status_email: no valid patient email, skipped");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $cfg['port'];

        $mail->setFrom($cfg['from'], $cfg['from_name']);
        $mail->addAddress($appt['email'], $appt['fullname'] ?? '');

        $dateText = date('j M Y', strtotime($appt['appointment_date']));
        $token    = str_pad((string)$appt['token_number'], 2, '0', STR_PAD_LEFT);

        $mail->isHTML(true);
        $mail->Subject = "Health Prime — Appointment $status";
        $mail->Body    = "
            <div style='font-family:Segoe UI,Arial,sans-serif;color:#2c3e50;max-width:480px'>
            <h2 style='color:#0a9396'>Health Prime</h2>
            <p>Dear " . htmlspecialchars($appt['fullname'] ?? 'Patient') . ",</p>
            <p>Your appointment has been <strong>" . htmlspecialchars($status) . "</strong>.</p>
            <table style='border-collapse:collapse;margin:16px 0'>
                <tr><td style='padding:4px 12px 4px 0;color:#6b7a89'>Specialist</td><td><strong>" . htmlspecialchars($specialty) . "</strong></td></tr>
                <tr><td style='padding:4px 12px 4px 0;color:#6b7a89'>Doctor</td><td><strong>" . htmlspecialchars($appt['doctor_name']) . "</strong></td></tr>
                <tr><td style='padding:4px 12px 4px 0;color:#6b7a89'>Date</td><td><strong>" . htmlspecialchars($dateText) . "</strong></td></tr>
                <tr><td style='padding:4px 12px 4px 0;color:#6b7a89'>Slot</td><td><strong>" . htmlspecialchars($appt['appointment_slot']) . "</strong></td></tr>
                <tr><td style='padding:4px 12px 4px 0;color:#6b7a89'>Token</td><td><strong>#$token</strong></td></tr>
            </table>
            " . (!empty($note) ? "<p style='background:#f7f4ec;padding:10px 14px;border-radius:8px'><strong>" . ($status === 'Cancelled' ? 'Reason' : 'Note') . ":</strong> " . htmlspecialchars($note) . "</p>" : "") . "
            " . ($status === 'Cancelled'
                    ? "<p>If you'd like to rebook, please visit the site and choose a new slot.</p>"
                    : "<p>Please arrive 10 minutes before your slot with any previous reports.</p>") . "
            <p style='color:#6b7a89;font-size:.85rem;margin-top:20px'>— Health Prime</p>
            </div>";
        $mail->AltBody = "Your $specialty appointment with {$appt['doctor_name']} on $dateText ({$appt['appointment_slot']}, token #$token) has been $status."
                        . (!empty($note) ? " " . ($status === 'Cancelled' ? 'Reason' : 'Note') . ": $note" : "");

        $mail->send();
        return true;

    } catch (PHPMailerException $e) {
        error_log("Email to {$appt['email']} failed: " . $mail->ErrorInfo);
        return false;
    }
}