<?php
// ==========================================
// EMAIL SETTINGS — fill these in
// ==========================================
// Using Gmail? You cannot use your normal Gmail password here.
// 1. Turn on 2-Step Verification: https://myaccount.google.com/security
// 2. Create an "App Password": https://myaccount.google.com/apppasswords
// 3. Paste that 16-character app password below (not your real Gmail password).
//
// Testing without sending real emails? Use Mailtrap.io instead (free):
// sign up, create an inbox, copy its SMTP host/username/password into the fields below.

return [
    'host'     => 'smtp.gmail.com',
    'port'     => 587,
    'username' => 'youraddress@gmail.com',   // the sending mailbox
    'password' => 'your-16-char-app-password',
    'from'     => 'youraddress@gmail.com',
    'from_name'=> 'Health Prime',
];