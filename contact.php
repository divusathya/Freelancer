<?php
/* Contact form handler: sends enquiries to your inbox.
   Upload this file next to index.html on a PHP-enabled host. */
header('Content-Type: application/json; charset=utf-8');

$TO = 'sadinimelethil@gmail.com';

function out($ok, $code = 200) {
    http_response_code($code);
    echo json_encode(['ok' => $ok]);
    exit;
}
function clean($v, $max = 2000) {
    $v = trim(strip_tags((string)($v ?? '')));
    return mb_substr($v, 0, $max);
}
function oneline($v) {
    return trim(preg_replace('/[\r\n]+/', ' ', $v));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 405);

$d = json_decode(file_get_contents('php://input'), true);
if (!is_array($d)) $d = $_POST;

// spam trap: real visitors never fill this hidden field
if (!empty($d['_gotcha'])) out(true);

$name    = oneline(clean($d['name']    ?? '', 100));
$company = oneline(clean($d['company'] ?? '', 150));
$email   = oneline(clean($d['email']   ?? '', 150));
$phone   = oneline(clean($d['phone']   ?? '', 40));
$service = oneline(clean($d['service'] ?? '', 100));
$details = clean($d['details'] ?? '', 4000);

if ($name === '' || $service === '' || $details === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    out(false, 400);
}

$host = preg_replace('/^www\./', '', $_SERVER['SERVER_NAME'] ?? 'localhost');
$from = 'noreply@' . $host;

$subject = '=?UTF-8?B?' . base64_encode('Project enquiry: ' . $service) . '?=';
$body = "Name: $name\n"
      . "Company: " . ($company ?: '-') . "\n"
      . "Email: $email\n"
      . "Phone: " . ($phone ?: '-') . "\n"
      . "Service: $service\n\n"
      . "Project details:\n$details\n";

$headers  = "From: Portfolio Enquiry <$from>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (@mail($TO, $subject, $body, $headers)) out(true);
out(false, 500);
