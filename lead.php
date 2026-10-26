<?php
// Receives the hero "WhatsApp me" form and appends it to leads.csv (blocked from the web by .htaccess).
// Expects a JSON POST: name, phone, province, interest, source, website (honeypot), started.
// Replies with JSON: { ok, message, field? }  (field names the input to fix, if any)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, 'Use the form on the website to send your details.');

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Spam traps: hidden field must stay empty, and the form must not be submitted instantly
$thanks = 'Details saved.';
if (!empty($in['website'])) reply(200, $thanks);
if (isset($in['started']) && (time() * 1000 - (int)$in['started']) < 2000) reply(200, $thanks);

// Simple rate limit: 5 submissions per browser session per hour
session_start();
$_SESSION['leads'] = array_filter($_SESSION['leads'] ?? [], fn($t) => $t > time() - 3600);
if (count($_SESSION['leads']) >= 5) reply(429, 'You have sent your details 5 times in the last hour. WhatsApp us instead.');

$clean = fn($k, $max = 120) => mb_substr(trim(strip_tags((string)($in[$k] ?? ''))), 0, $max);
$name     = $clean('name');
$phone    = $clean('phone', 30);
$province = $clean('province', 40);
$interest = $clean('interest', 40);
$source   = $clean('source', 300);

$provinces = ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape'];
$interests = ['Joining Avon', 'Joining Justine', 'Joining both', 'Buying products'];

// South African cellphone: 0XX XXX XXXX, or +27 / 27 followed by 9 digits
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) === 11 && strpos($digits, '27') === 0) $digits = '0' . substr($digits, 2);

if ($name === '')                             reply(422, 'Enter your name so we know who to ask for.', 'name');
if (!preg_match('/^0\d{9}$/', $digits))       reply(422, 'Enter a 10-digit cellphone number, for example 082 123 4567.', 'phone');
if (!in_array($province, $provinces, true))   reply(422, 'Choose your province from the list.', 'province');
if (!in_array($interest, $interests, true))   $interest = 'Joining Avon';
if (empty($in['consent']))                    reply(422, 'Tick the box so we may contact you.', 'consent');

// Stop spreadsheet apps from running anything typed into a cell as a formula
$safe = fn($v) => preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;

$row = [
  date('Y-m-d H:i'),
  $safe($name),
  substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6),
  $province,
  $interest,
  $safe($source),
];

$file  = __DIR__ . '/leads.csv';
$isNew = !is_file($file) || filesize($file) === 0;
$fh    = @fopen($file, 'a');
if (!$fh || !flock($fh, LOCK_EX)) {
  error_log('Lead form: cannot open leads.csv for writing');
  reply(500, 'We couldn\'t save your details. Tap Open WhatsApp to send them to us instead.');
}
if ($isNew) {
  fwrite($fh, "\xEF\xBB\xBF");   // UTF-8 marker so Excel shows names correctly
  fputcsv($fh, ['Date', 'Name', 'Cellphone', 'Province', 'Interested in', 'Page'], ',', '"', '');
}
$ok = fputcsv($fh, $row, ',', '"', '') !== false;
flock($fh, LOCK_UN);
fclose($fh);

if (!$ok) reply(500, 'We couldn\'t save your details. Tap Open WhatsApp to send them to us instead.');

$_SESSION['leads'][] = time();
reply(200, $thanks);
