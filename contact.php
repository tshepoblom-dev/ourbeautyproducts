<?php
// Receives the Contact us modal, saves it to contact-messages.csv (blocked from the web by .htaccess),
// then emails it through SMTP using PHPMailer. The message is saved even if the email fails.
// Expects a JSON POST: name, email, phone, topic, message, consent, website (honeypot), started.
// Replies with JSON: { ok, message, field? }  (field names the input to fix, if any)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer can live in /phpmailer/ (recommended) or next to this file
$lib = is_file(__DIR__ . '/phpmailer/PHPMailer.php') ? __DIR__ . '/phpmailer' : __DIR__;
require $lib . '/Exception.php';
require $lib . '/PHPMailer.php';
require $lib . '/SMTP.php';

date_default_timezone_set('Africa/Johannesburg');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(int $code, string $msg, string $field = ''): void {
  http_response_code($code);
  $out = ['ok' => $code === 200, 'message' => $msg];
  if ($field !== '') $out['field'] = $field;
  echo json_encode($out);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, 'Use the contact form on the website to send a message.');

$cfg = require __DIR__ . '/mail-config.php';
$in  = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Spam traps: hidden field must stay empty, and the form must not be submitted instantly
$thanks = 'Thanks, your message has been sent.';
if (!empty($in['website'])) reply(200, $thanks);
if (isset($in['started']) && (time() * 1000 - (int)$in['started']) < 3000) reply(200, $thanks);

// Simple rate limit: 5 messages per browser session per hour
session_start();
$_SESSION['sent'] = array_filter($_SESSION['sent'] ?? [], fn($t) => $t > time() - 3600);
if (count($_SESSION['sent']) >= 5) reply(429, 'You have sent 5 messages in the last hour. Try again later, or WhatsApp us.');

$clean = fn($k, $max = 120) => mb_substr(trim(strip_tags((string)($in[$k] ?? ''))), 0, $max);
$name     = $clean('name');
$email    = $clean('email');
$phone    = $clean('phone', 30);
$topic    = $clean('topic', 60);
$message  = $clean('message', 3000);
$consent  = !empty($in['consent']);

// South African cellphone: 0XX XXX XXXX, or +27 / 27 followed by 9 digits
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) === 11 && strpos($digits, '27') === 0) $digits = '0' . substr($digits, 2);
$phoneOk = (bool)preg_match('/^0\d{9}$/', $digits);

if ($name === '')                                        reply(422, 'Enter your name so we know who to reply to.', 'name');
if (!$phoneOk)                                           reply(422, 'Enter a 10-digit cellphone number, for example 082 123 4567.', 'phone');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) reply(422, 'Check your email address, for example name@example.co.za, or leave it empty.', 'email');
if ($message === '')                                     reply(422, 'Type your message so we know how to help.', 'message');
if (!$consent)                                           reply(422, 'Tick the box so we may contact you about your message.', 'consent');

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$rows = [
  'Name' => $name, 'Email' => $email ?: 'Not given', 'Cellphone' => $phone,
  'Topic' => $topic ?: 'General', 'Message' => $message,
  'Sent from' => $_SERVER['HTTP_REFERER'] ?? 'Website', 'Date' => date('j M Y, H:i'),
];
$html = '<table cellpadding="6" style="font-family:Arial,sans-serif;font-size:14px">';
$text = '';
foreach ($rows as $k => $v) {
  $html .= '<tr><td style="font-weight:bold;vertical-align:top">' . $h($k) . '</td><td>' . nl2br($h($v)) . '</td></tr>';
  $text .= "$k: $v\n";
}
$html .= '</table>';

// Save a copy first, so a message is never lost if the email fails.
// Stop spreadsheet apps from running anything typed into a cell as a formula.
$safe = fn($v) => preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
$saveCopy = function (string $status) use ($safe, $name, $phone, $email, $topic, $message): bool {
  $file  = __DIR__ . '/contact-messages.csv';
  $isNew = !is_file($file) || filesize($file) === 0;
  $fh    = @fopen($file, 'a');
  if (!$fh || !flock($fh, LOCK_EX)) {
    error_log('Contact form: cannot open contact-messages.csv for writing');
    if ($fh) fclose($fh);
    return false;
  }
  if ($isNew) {
    fwrite($fh, "\xEF\xBB\xBF");   // UTF-8 marker so Excel shows names correctly
    fputcsv($fh, ['Date', 'Name', 'Cellphone', 'Email', 'Topic', 'Message', 'Page', 'Status'], ',', '"', '');
  }
  $ok = fputcsv($fh, [
    date('Y-m-d H:i'), $safe($name), $safe($phone), $safe($email), $safe($topic ?: 'General'),
    $safe($message), $safe($_SERVER['HTTP_REFERER'] ?? 'Website'), $status,
  ], ',', '"', '') !== false;
  flock($fh, LOCK_UN);
  fclose($fh);
  return $ok;
};
$saved = $saveCopy('Received');   // if saving fails we still try to send the email

$mail = new PHPMailer(true);
try {
  $mail->isSMTP();
  $mail->Host       = $cfg['smtp_host'];
  $mail->Port       = (int)$cfg['smtp_port'];
  $mail->SMTPAuth   = true;
  $mail->Username   = $cfg['smtp_username'];
  $mail->Password   = $cfg['smtp_password'];
  $mail->SMTPSecure = $cfg['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
  $mail->CharSet    = 'UTF-8';
  $mail->Timeout    = 15;

  $mail->setFrom($cfg['from_email'], $cfg['from_name']);
  $mail->addAddress($cfg['to_email'], $cfg['to_name']);
  if ($email !== '') $mail->addReplyTo($email, $name);   // hitting Reply answers the visitor directly

  $mail->isHTML(true);
  $mail->Subject = $cfg['subject_prefix'] . ': ' . ($topic ?: 'General') . ' from ' . $name;
  $mail->Body    = $html;
  $mail->AltBody = $text;
  $mail->send();
} catch (Exception $e) {
  error_log('Contact form mail error: ' . $mail->ErrorInfo);
  if ($saved) $saveCopy('EMAIL FAILED');   // marks the saved message so you can spot it and follow up
  reply(500, 'We couldn\'t send your message. Try again, or WhatsApp or call us instead.');
}

$_SESSION['sent'][] = time();
reply(200, 'Thanks, your message has been sent. We\'ll reply within one working day.');
