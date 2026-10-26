<?php
// Receives the registration enquiry form.
//   GET  ?token=1  -> issues a fresh one-time token (used if the page was open for a long time)
//   POST multipart -> validates, checks the uploads, sends everything to the Google bridge
//                     (Sheet row + private Drive files) and ONLY THEN replies ok:true.
// The browser goes to /thank-you only after ok:true. That page is what fires the Google Ads conversion.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require __DIR__ . '/partials/config.php';
require __DIR__ . '/partials/session.php';

date_default_timezone_set('Africa/Johannesburg');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
@set_time_limit(120);

function out(int $code, array $body): void {
  http_response_code($code);
  $body['ok'] = $code === 200 && ($body['ok'] ?? true);
  echo json_encode($body, JSON_UNESCAPED_UNICODE);
  exit;
}
function fail(int $code, string $message, array $extra = []): void { out($code, ['ok' => false, 'message' => $message] + $extra); }

$cfg = register_config();

// ---------- token endpoint ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['token'])) out(200, ['ok' => true, 'token' => reg_issue_token(), 'wait' => (int)$cfg['min_fill_seconds'] + 1]);   // 'wait': seconds before a new token may be used
  fail(405, 'Use the registration form on the website.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Use the registration form on the website.');

// Same-origin only
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? '') && parse_url($origin, PHP_URL_HOST) !== preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '')) {
  fail(403, 'This request was not allowed.');
}

// Files bigger than the server allows arrive as an empty POST
if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
  fail(413, 'Your photos are too large to upload. Choose smaller photos and try again.');
}

// ---------- rate limits: per IP (private temp file, hashed) and per browser session ----------
// "all"  = every POST, lenient, so shared mobile/office IPs are not blocked by ordinary form corrections.
// "save" = only attempts that would write to your Sheet/Drive, strict, to stop floods of fake registrations.
function ip_limited(string $bucket, int $max, int $window = 3600): bool {
  $f  = sys_get_temp_dir() . '/obp_rl_' . $bucket . '_' . hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '0') . '|obp');
  $fh = @fopen($f, 'c+');
  if (!$fh) return false;
  flock($fh, LOCK_EX);
  $times = array_values(array_filter(json_decode(stream_get_contents($fh), true) ?: [], fn($t) => $t > time() - $window));
  $limited = count($times) >= $max;
  if (!$limited) $times[] = time();
  ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($times));
  flock($fh, LOCK_UN); fclose($fh);
  return $limited;
}
if (ip_limited('all', 120)) fail(429, 'Too many attempts. Please try again in a little while, or WhatsApp us.');

reg_session_start();
$_SESSION['reg_posts'] = array_values(array_filter($_SESSION['reg_posts'] ?? [], fn($t) => $t > time() - 3600));
if (count($_SESSION['reg_posts']) >= 10) fail(429, 'Too many attempts. Please try again in a little while, or WhatsApp us.');
$_SESSION['reg_posts'][] = time();

// Spam trap: the hidden field must stay empty. (No fake "success": a bot never gets to the thank-you page.)
if (!empty($_POST['website'])) fail(400, 'We could not process this request.');

// ---------- one-time token: stops replays and double submits ----------
$token = (string)($_POST['token'] ?? '');
$tok   = $_SESSION['reg_tokens'][$token] ?? null;
if (!$tok) fail(409, 'This page was open for a long time. Please try again.', ['code' => 'token']);

if (($tok['state'] ?? '') === 'done') {
  // Duplicate submit of an already-saved registration: do NOT save again, just send the visitor on.
  out(200, ['ok' => true, 'redirect' => '/thank-you', 'duplicate' => true]);
}
if (time() - (int)$tok['t'] < (int)$cfg['min_fill_seconds']) fail(429, 'Please give it a moment and try again.');

// ---------- validation ----------
$clean = function (string $k, int $max) {
  $v = (string)($_POST[$k] ?? '');
  $v = preg_replace('/[\x00-\x1F\x7F]/u', ' ', strip_tags($v)) ?? '';
  return mb_substr(trim(preg_replace('/\s+/u', ' ', $v) ?? ''), 0, $max);
};
$errors = [];

$fullName = $clean('full_name', 120);
if ($fullName === '' || !preg_match('/^[\p{L}][\p{L}\p{M} .\'’-]{1,118}$/u', $fullName) || count(preg_split('/\s+/u', $fullName)) < 2) {
  $errors['full_name'] = 'Enter your full names exactly as they appear on your ID (at least two names).';
}

$digits = preg_replace('/\D/', '', $clean('phone', 30));
if (strlen($digits) === 11 && strpos($digits, '27') === 0) $digits = '0' . substr($digits, 2);
if (!preg_match('/^0\d{9}$/', $digits)) $errors['phone'] = 'Enter a 10-digit South African number, for example 082 123 4567.';
$phone = preg_match('/^0\d{9}$/', $digits) ? substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6) : '';

$email = $clean('email', 254);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address, for example name@example.co.za.';

$brand = $clean('brand', 10);
if (!in_array($brand, ['Avon', 'Justine'], true)) $errors['brand'] = 'Choose Avon or Justine.';

$provinces = ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape'];
$province  = $clean('province', 30);
if (!in_array($province, $provinces, true)) $errors['province'] = 'Choose your province.';

$place = '/^[\p{L}\p{N}][\p{L}\p{N}\p{M} .,\'’()\/&-]{1,79}$/u';
$town   = $clean('town', 80);   if (!preg_match($place, $town))   $errors['town']   = 'Enter your town or city.';
$suburb = $clean('suburb', 80); if (!preg_match($place, $suburb)) $errors['suburb'] = 'Enter your suburb.';

$street = $clean('street', 120);
if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N}\p{M} .,\'’()\/#&-]{2,119}$/u', $street) || !preg_match('/\p{L}/u', $street)) {
  $errors['street'] = 'Enter your house number and street name, for example 12 Jacaranda Street.';
}

$postal = $clean('postal_code', 8);
if (!preg_match('/^\d{4}$/', $postal)) $errors['postal_code'] = 'Enter your 4-digit postal code.';

if (empty($_POST['consent']) || $_POST['consent'] !== '1') {
  $errors['consent'] = 'You need to tick the box before we can accept your documents.';
}

// ---------- uploads: real type, size, readable image; metadata stripped ----------
function mem_limit_bytes(): int {
  $m = trim((string)ini_get('memory_limit')); if ($m === '' || $m === '-1') return PHP_INT_MAX;
  $n = (int)$m; switch (strtolower(substr($m, -1))) { case 'g': $n *= 1024; case 'm': $n *= 1024; case 'k': $n *= 1024; }
  return $n;
}

function clean_image(string $bytes, string $mime): array {
  // Re-encode as a plain JPEG: removes EXIF/GPS metadata and anything hidden in the file.
  if (!function_exists('imagecreatefromstring')) return [$bytes, $mime];
  $info = @getimagesizefromstring($bytes);
  if (!$info) return [$bytes, $mime];
  [$w, $h] = $info;
  if ($w * $h * 8 > mem_limit_bytes() * 0.6) return [$bytes, $mime];      // too big to process safely: keep as uploaded
  $img = @imagecreatefromstring($bytes);
  if (!$img) return [$bytes, $mime];

  if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
    $tmp = tempnam(sys_get_temp_dir(), 'obp'); file_put_contents($tmp, $bytes);
    $exif = @exif_read_data($tmp); @unlink($tmp);
    $o = (int)($exif['Orientation'] ?? 1);
    $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
    if ($angle) { $rot = imagerotate($img, $angle, 0); if ($rot) { imagedestroy($img); $img = $rot; } }
  }
  $w = imagesx($img); $h = imagesy($img);
  $max = 2400; $scale = min(1, $max / max($w, $h));
  $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
  $dst = imagecreatetruecolor($nw, $nh);
  imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));        // flatten transparency onto white
  imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
  ob_start(); imagejpeg($dst, null, 85); $jpg = ob_get_clean();
  imagedestroy($img); imagedestroy($dst);
  return $jpg ? [$jpg, 'image/jpeg'] : [$bytes, $mime];
}

function read_upload(string $key, bool $allowPdf, int $maxBytes, array &$errors, string $label): ?array {
  $f = $_FILES[$key] ?? null;
  if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { $errors[$key] = "Add $label."; return null; }
  if (is_array($f['error']) || $f['error'] !== UPLOAD_ERR_OK) {
    $errors[$key] = in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
      ? 'That file is too large. Choose a smaller photo.' : 'The upload did not finish. Please choose the file again.';
    return null;
  }
  if (!is_uploaded_file($f['tmp_name'])) { $errors[$key] = 'The upload could not be verified. Please choose the file again.'; return null; }
  $size = (int)$f['size'];
  if ($size < 1 || $size > $maxBytes) { $errors[$key] = 'That file is too large. Choose a photo under ' . round($maxBytes / 1048576) . ' MB.'; return null; }

  $bytes = file_get_contents($f['tmp_name']);
  $mime  = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);          // real type from the content, never the file name
  $images = ['image/jpeg', 'image/png', 'image/webp'];
  if (!in_array($mime, $images, true) && !($allowPdf && $mime === 'application/pdf')) {
    $errors[$key] = $allowPdf ? 'Upload a JPG, PNG, WebP or PDF file.' : 'Upload a photo (JPG, PNG or WebP).';
    return null;
  }
  if ($mime === 'application/pdf') {
    if (strncmp($bytes, '%PDF-', 5) !== 0) { $errors[$key] = 'That PDF could not be read. Please choose it again.'; return null; }
    return ['kind' => $key, 'mime' => $mime, 'bytes' => $bytes];
  }
  $info = @getimagesizefromstring($bytes);
  if (!$info || $info[0] < 1 || $info[1] < 1) { $errors[$key] = 'That image could not be read. Please choose it again.'; return null; }
  if (max($info[0], $info[1]) < 600 || min($info[0], $info[1]) < 400) { $errors[$key] = 'That photo is too small to read. Take it again closer, in good light.'; return null; }
  if ($info[0] * $info[1] > 60000000) { $errors[$key] = 'That photo is too large. Choose a smaller one.'; return null; }
  [$bytes, $mime] = clean_image($bytes, $mime);
  return ['kind' => $key, 'mime' => $mime, 'bytes' => $bytes];
}

$maxBytes = (int)$cfg['max_file_bytes'];
$idCopy   = read_upload('id_copy',   true,  $maxBytes, $errors, 'a clear copy or photo of your ID');
$idSelfie = read_upload('id_selfie', false, $maxBytes, $errors, 'a photo of yourself holding your ID');

if ($errors) fail(422, 'Please check the highlighted fields.', ['errors' => $errors]);

// ---------- send to Google (Sheet row + private Drive files) ----------
$bridgeUrl = (string)$cfg['bridge_url']; $secret = (string)$cfg['bridge_secret'];
$urlOk = preg_match('#^https://#', $bridgeUrl) || preg_match('#^http://(127\.0\.0\.1|localhost)(:\d+)?/#', $bridgeUrl);
if (!$urlOk || strlen($secret) < 24) {
  error_log('Registration: bridge not configured (set bridge_url and bridge_secret in register-config.php)');
  fail(503, 'Registrations are temporarily unavailable. Please WhatsApp us on 082 644 8208 and we will help you.');
}

$ref = 'OBP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));
$payload = [
  'secret'          => $secret,
  'action'          => 'register',
  'submission_id'   => hash('sha256', $token),     // same token => same submission, so a retry can never create a second row
  'ref'             => $ref,
  'received'        => date('Y-m-d H:i'),
  'consent_at'      => date('Y-m-d H:i'),
  'consent_version' => (string)$cfg['consent_version'],
  'source'          => 'Website registration form',
  'fields'          => [
    'full_name' => $fullName, 'phone' => $phone, 'email' => $email, 'brand' => $brand,
    'province' => $province, 'town' => $town, 'suburb' => $suburb, 'street' => $street, 'postal_code' => $postal,
  ],
  'files' => array_map(fn($f) => ['kind' => $f['kind'], 'mime' => $f['mime'], 'data' => base64_encode($f['bytes'])], [$idCopy, $idSelfie]),
];
unset($idCopy, $idSelfie);

function bridge_send(string $url, array $payload): array {
  $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  if ($body === false) return ['ok' => false, 'why' => 'encode'];
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
    CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 85, CURLOPT_SSL_VERIFYPEER => true,
  ]);
  $resp = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
  if ($resp === false) return ['ok' => false, 'why' => 'network: ' . $err];
  $j = json_decode($resp, true);
  if ($code !== 200 || !is_array($j)) return ['ok' => false, 'why' => "http $code"];
  if (($j['ok'] ?? false) !== true || !preg_match('/^OBP-\d{8}-[A-Z0-9]{5}$/', (string)($j['ref'] ?? ''))) return ['ok' => false, 'why' => 'bridge: ' . substr((string)($j['error'] ?? 'unknown'), 0, 40)];
  return ['ok' => true, 'ref' => $j['ref'], 'duplicate' => !empty($j['duplicate'])];
}

if (ip_limited('save', 10)) fail(429, 'Too many registrations were sent from your connection. Please try again later, or WhatsApp us on 082 644 8208.');

$_SESSION['reg_tokens'][$token]['state'] = 'processing';
$res = bridge_send($bridgeUrl, $payload);
unset($payload);

if (!$res['ok']) {
  $_SESSION['reg_tokens'][$token]['state'] = 'open';           // allow a retry with the same token
  error_log("Registration $ref not saved: " . $res['why']);     // no personal data in the log
  fail(502, 'We could not save your registration just now, and nothing was sent. Please try again in a few minutes, or WhatsApp us on 082 644 8208.');
}

// Saved. Record success server-side: /thank-you only works for a browser holding this.
$_SESSION['reg_tokens'][$token] = [
  't' => $tok['t'], 'state' => 'done', 'ref' => $res['ref'], 'brand' => $brand,
  'first' => mb_substr(preg_split('/\s+/u', $fullName)[0], 0, 40), 'fired' => false,
];
$_SESSION['reg_last'] = $token;
session_write_close();

echo json_encode(['ok' => true, 'redirect' => '/thank-you'], JSON_UNESCAPED_UNICODE);
ignore_user_abort(true);
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();

// ---------- best-effort alert email (no documents, no links). Failure here never affects the registration. ----------
if (!empty($cfg['notify_email']) && !$res['duplicate'] && is_file(__DIR__ . '/mail-config.php')) {
  try {
    $mc  = require __DIR__ . '/mail-config.php';
    $lib = is_file(__DIR__ . '/phpmailer/PHPMailer.php') ? __DIR__ . '/phpmailer' : __DIR__;
    require_once $lib . '/Exception.php'; require_once $lib . '/PHPMailer.php'; require_once $lib . '/SMTP.php';
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $m = new PHPMailer(true);
    $m->isSMTP(); $m->Host = $mc['smtp_host']; $m->Port = (int)$mc['smtp_port']; $m->SMTPAuth = true;
    $m->Username = $mc['smtp_username']; $m->Password = $mc['smtp_password'];
    $m->SMTPSecure = $mc['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $m->CharSet = 'UTF-8'; $m->Timeout = 10;
    $m->setFrom($mc['from_email'], $mc['from_name']); $m->addAddress($mc['to_email'], $mc['to_name']);
    $m->addReplyTo($email, $fullName);
    $m->isHTML(true);
    $m->Subject = "New $brand registration enquiry: {$res['ref']}";
    $m->Body = '<p>A new registration enquiry was saved to your Registrations sheet.</p><table cellpadding="6" style="font-family:Arial,sans-serif;font-size:14px">'
      . '<tr><td><b>Reference</b></td><td>' . $h($res['ref']) . '</td></tr><tr><td><b>Brand</b></td><td>' . $h($brand) . '</td></tr>'
      . '<tr><td><b>Name</b></td><td>' . $h($fullName) . '</td></tr><tr><td><b>Cellphone</b></td><td>' . $h($phone) . '</td></tr>'
      . '<tr><td><b>Email</b></td><td>' . $h($email) . '</td></tr><tr><td><b>Town</b></td><td>' . $h("$town, $province") . '</td></tr></table>'
      . '<p>ID documents are in your private Google Drive folder. Open the sheet to find them.</p>';
    $m->AltBody = "New $brand registration enquiry {$res['ref']} from $fullName ($phone). Open your Registrations sheet for the details and documents.";
    $m->send();
  } catch (MailException $e) {
    error_log('Registration alert email failed for ' . $res['ref']);
  } catch (Throwable $e) {
    error_log('Registration alert email error for ' . $res['ref']);
  }
}
exit;
