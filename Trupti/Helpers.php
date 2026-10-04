<?php
// Only allow redirects to known pages on this site.
// Anything else (external URLs, javascript:, unknown pages) falls back to the homepage.
function safe_redirect($url, $default = 'Homepage.html') {
    $url = (string)$url;

    // Booking forms, e.g. CardiologistForm.php?specialist=Cardiologist&doctor=Dr.%20Neha
    if (preg_match('/^(?:Cardiologist|Gynecologist|ENT|Orthopedic)Form\.php\?[A-Za-z0-9_%=&.+\-]*$/iD', $url)) {
        return $url;
    }

    // Plain pages
    if (in_array($url, ['Homepage.html', 'Doctype.html'], true)) {
        return $url;
    }

    return $default;
}