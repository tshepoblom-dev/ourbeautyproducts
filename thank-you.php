<?php
// Thank-you page. The Google Ads conversion fires from here, and ONLY here, and only when:
//   1. the server recorded a successfully saved registration for THIS browser session, and
//   2. the conversion has not already been fired for that registration (refresh/back never re-fires).
// Visiting /thank-you directly, or after a failed submission, redirects to the form and fires nothing.
require __DIR__ . '/partials/config.php';
require __DIR__ . '/partials/session.php';
require __DIR__ . '/partials/shell.php';

header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');

reg_session_start();
$cfg = register_config();
$key = $_SESSION['reg_last'] ?? '';
$rec = $key !== '' ? ($_SESSION['reg_tokens'][$key] ?? null) : null;

if (!$rec || ($rec['state'] ?? '') !== 'done' || !preg_match('/^OBP-\d{8}-[A-Z0-9]{5}$/', (string)($rec['ref'] ?? ''))) {
  header('Location: /register', true, 302);
  exit;
}

$ref   = $rec['ref'];
$brand = in_array($rec['brand'] ?? '', ['Avon', 'Justine'], true) ? $rec['brand'] : 'Avon or Justine';
$first = (string)($rec['first'] ?? '');
$label = conversion_label($cfg);

$conversion = '';
if (empty($rec['fired']) && $label !== '') {
  $_SESSION['reg_tokens'][$key]['fired'] = true;      // consumed: a refresh will not fire it again
  $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
  $conversion = '<script>(function(){'
    . 'var ref=' . json_encode($ref, $flags) . ',k="obp_conv_"+ref;'
    . 'try{if(sessionStorage.getItem(k))return;sessionStorage.setItem(k,"1");}catch(e){}'      // second guard, per browser tab
    . 'if(typeof gtag!=="function")return;'
    . 'gtag("event","conversion",{send_to:' . json_encode($cfg['google_ads_id'] . '/' . $label, $flags) . ',transaction_id:ref});'
    . 'gtag("event","generate_lead",{form:"registration",brand:' . json_encode($brand, $flags) . '});'
    . '})();</script>';
}
session_write_close();

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$waText = rawurlencode("Hi, I just submitted my registration enquiry. My reference is $ref.");

shell_head('Thank you | Our Beauty Products', 'Your registration enquiry has been received.', '/thank-you', $cfg['google_ads_id'], true);
shell_top();
?>
<main id="main">
  <section class="intro thanks" aria-labelledby="ty-title">
    <div class="wrap">
      <p class="kicker">Registration enquiry received</p>
      <h1 id="ty-title">Thank you<?= $first !== '' ? ', ' . $h($first) : '' ?>!</h1>
      <p class="lede">We have your details and your documents. Your reference number is:</p>
      <p class="ref-box" aria-label="Reference number"><?= $h($ref) ?></p>
      <ol class="next-steps">
        <li>We check your details and documents.</li>
        <li>We contact you on WhatsApp, by phone or by email to help you register with <?= $h($brand) ?>.</li>
        <li>Keep your reference number handy in case you want to ask us about your enquiry.</li>
      </ol>
      <p class="trust">Your documents are stored privately and are not shown anywhere on this website.</p>
      <div class="cta-row thanks-cta">
        <a class="btn btn-ink btn-caps" href="https://wa.me/27826448208?text=<?= $waText ?>" data-track="whatsapp_click" data-location="thank_you">WhatsApp us</a>
        <a class="btn btn-outline btn-caps" href="/">Back to home</a>
      </div>
    </div>
  </section>
</main>
<?php shell_bottom($conversion); ?>
