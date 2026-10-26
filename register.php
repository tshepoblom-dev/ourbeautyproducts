<?php
// Registration enquiry form page. Renders the same site shell as the home page.
// A fresh one-time token is placed in the form on every load (see partials/session.php).
require __DIR__ . '/partials/config.php';
require __DIR__ . '/partials/session.php';
require __DIR__ . '/partials/shell.php';

header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$cfg   = register_config();
$token = reg_issue_token();
$pre   = strtolower((string)($_GET['brand'] ?? ''));
$pre   = in_array($pre, ['avon', 'justine'], true) ? ucfirst($pre) : '';
$provinces = ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape'];

shell_head(
  'Register to sell Avon or Justine | Our Beauty Products',
  'Register your interest in becoming an Avon representative or Justine consultant in South Africa. Simple mobile-friendly form with private, secure document upload.',
  '/register',
  $cfg['google_ads_id']
);
shell_top();
?>
<main id="main">

  <section class="intro" aria-labelledby="reg-title">
    <div class="wrap">
      <p class="kicker">Registration enquiry</p>
      <h1 id="reg-title">Register to sell Avon or Justine</h1>
      <p class="lede">Tell us a little about yourself, add your ID documents and we'll contact you to help you get started. It takes about five minutes.</p>
    </div>
  </section>

  <section class="block reg-section" aria-label="Registration form">
    <div class="reg-wrap">

      <div class="notice" role="note" aria-labelledby="why-title">
        <h2 id="why-title">Why we ask for your ID documents</h2>
        <ul>
          <li>A copy of your ID and a photo of you holding it let us confirm who you are and help you register as an Avon or Justine representative.</li>
          <li>Avon and Justine set their own sign-up requirements, and these can change. We'll tell you what applies when we contact you.</li>
          <li>Your documents go straight into a private Google Drive folder that only we can open. They are never shown on this website and never made public.</li>
          <li>We use them only to confirm your identity and process your registration enquiry. You can ask us to delete them at any time.</li>
        </ul>
      </div>

      <noscript><p class="form-alert">This form needs JavaScript. Please WhatsApp us on 082 644 8208 and we'll help you instead.</p></noscript>

      <form id="reg-form" class="reg-form" method="post" action="/register-submit.php" enctype="multipart/form-data" novalidate>
        <div id="reg-summary" class="form-alert" role="alert" tabindex="-1"></div>
        <input type="hidden" name="token" id="reg-token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <fieldset class="reg-card">
          <legend>1. Your details</legend>
          <div class="field">
            <label for="f-full_name">Full names <span class="req">as shown on your ID</span></label>
            <input id="f-full_name" name="full_name" type="text" autocomplete="name" maxlength="120" required aria-describedby="f-full_name-err">
            <p class="field-error" id="f-full_name-err"></p>
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="f-phone">Contact number</label>
              <input id="f-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="082 123 4567" maxlength="20" required aria-describedby="f-phone-err">
              <p class="field-error" id="f-phone-err"></p>
            </div>
            <div class="field">
              <label for="f-email">Email address</label>
              <input id="f-email" name="email" type="email" inputmode="email" autocomplete="email" maxlength="254" required aria-describedby="f-email-err">
              <p class="field-error" id="f-email-err"></p>
            </div>
          </div>
          <div class="field" id="brand-field">
            <span class="label" id="brand-label">I want to register with</span>
            <div class="brand-choice" role="radiogroup" aria-labelledby="brand-label" aria-describedby="f-brand-err">
              <label><input type="radio" name="brand" value="Avon" required<?= $pre === 'Avon' ? ' checked' : '' ?>><span>Avon</span></label>
              <label><input type="radio" name="brand" value="Justine" required<?= $pre === 'Justine' ? ' checked' : '' ?>><span>Justine</span></label>
            </div>
            <p class="field-error" id="f-brand-err"></p>
          </div>
        </fieldset>

        <fieldset class="reg-card">
          <legend>2. Delivery address</legend>
          <div class="field">
            <label for="f-province">Province</label>
            <select id="f-province" name="province" autocomplete="address-level1" required aria-describedby="f-province-err">
              <option value="">Choose your province</option>
<?php foreach ($provinces as $p): ?>
              <option><?= htmlspecialchars($p, ENT_QUOTES) ?></option>
<?php endforeach; ?>
            </select>
            <p class="field-error" id="f-province-err"></p>
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="f-town">Town or city</label>
              <input id="f-town" name="town" type="text" autocomplete="address-level2" maxlength="80" required aria-describedby="f-town-err">
              <p class="field-error" id="f-town-err"></p>
            </div>
            <div class="field">
              <label for="f-suburb">Suburb</label>
              <input id="f-suburb" name="suburb" type="text" autocomplete="address-level3" maxlength="80" required aria-describedby="f-suburb-err">
              <p class="field-error" id="f-suburb-err"></p>
            </div>
          </div>
          <div class="grid-2 street-row">
            <div class="field">
              <label for="f-street">House number and street name</label>
              <input id="f-street" name="street" type="text" autocomplete="address-line1" placeholder="12 Jacaranda Street" maxlength="120" required aria-describedby="f-street-err">
              <p class="field-error" id="f-street-err"></p>
            </div>
            <div class="field">
              <label for="f-postal_code">Postal code</label>
              <input id="f-postal_code" name="postal_code" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="postal-code" required aria-describedby="f-postal_code-err">
              <p class="field-error" id="f-postal_code-err"></p>
            </div>
          </div>
        </fieldset>

        <fieldset class="reg-card">
          <legend>3. Identity verification</legend>
          <p class="hint tips">Use good light, keep all four corners in the picture and make sure the writing is easy to read. Photos are shrunk on your phone before they upload.</p>

          <div class="field file-field">
            <label for="f-id_copy">Clear copy or photo of your ID</label>
            <p class="hint" id="f-id_copy-hint">JPG, PNG, WebP or PDF.</p>
            <input id="f-id_copy" name="id_copy" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required aria-describedby="f-id_copy-hint f-id_copy-err">
            <div class="file-preview" id="f-id_copy-preview" hidden></div>
            <p class="field-error" id="f-id_copy-err"></p>
          </div>

          <div class="field file-field">
            <label for="f-id_selfie">Photo of you holding your ID</label>
            <p class="hint" id="f-id_selfie-hint">Hold the same ID next to your face. Your face and the ID must both be clear. JPG, PNG or WebP.</p>
            <input id="f-id_selfie" name="id_selfie" type="file" accept="image/jpeg,image/png,image/webp" required aria-describedby="f-id_selfie-hint f-id_selfie-err">
            <div class="file-preview" id="f-id_selfie-preview" hidden></div>
            <p class="field-error" id="f-id_selfie-err"></p>
          </div>
        </fieldset>

        <fieldset class="reg-card consent-card">
          <legend>4. Your consent</legend>
          <label class="consent" for="f-consent">
            <input type="checkbox" id="f-consent" name="consent" value="1" required aria-describedby="f-consent-err">
            <span>I consent to Our Beauty Products collecting and storing my personal details and ID documents, as explained above, to confirm my identity and process my registration enquiry. I understand I can ask for them to be deleted at any time. <a href="/privacy" target="_blank" rel="noopener">Privacy policy</a></span>
          </label>
          <p class="field-error" id="f-consent-err"></p>
        </fieldset>

        <button class="btn btn-ink btn-caps btn-block btn-submit" type="submit" id="reg-submit">Submit registration enquiry</button>
        <p class="reg-status" id="reg-status" role="status" aria-live="polite"></p>
        <p class="reg-alt">Prefer to talk first? <a class="link" href="https://wa.me/27826448208?text=Hi%2C%20I%27d%20like%20to%20register%20to%20sell%20Avon%20or%20Justine" data-track="whatsapp_click" data-location="register_form">WhatsApp us on 082 644 8208</a></p>
      </form>
    </div>
  </section>

</main>
<?php shell_bottom('<script src="/register.js?v=20261023" defer></script>'); ?>
