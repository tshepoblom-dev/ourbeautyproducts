<?php
// Shared page shell for the PHP pages (register, thank-you). Mirrors the markup of index.html so the design is identical.
// Direct web access to this folder is blocked by partials/.htaccess.

/** Google tag: ONE loader per page. Add GA4 later with a second gtag("config", "G-...") line, never a second loader. */
function shell_google_tag(string $adsId): void { ?>
<!-- Google tag (gtag.js): Google Ads <?= htmlspecialchars($adsId, ENT_QUOTES) ?> -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($adsId, ENT_QUOTES) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","<?= htmlspecialchars($adsId, ENT_QUOTES) ?>");</script>
<?php }

function shell_head(string $title, string $description, string $canonicalPath, string $adsId, bool $noindex = false): void { ?>
<!DOCTYPE html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title, ENT_QUOTES) ?></title>
<meta name="description" content="<?= htmlspecialchars($description, ENT_QUOTES) ?>">
<?php if ($noindex): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<link rel="canonical" href="https://ourbeautyproducts.co.za<?= htmlspecialchars($canonicalPath, ENT_QUOTES) ?>">
<meta name="robots" content="index, follow">
<?php endif; ?>
<meta name="theme-color" content="#2F2F2F">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon-48.png" sizes="48x48" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="preload" href="/fonts/great-vibes-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/figtree-var-latin.woff2" as="font" type="font/woff2" crossorigin>
<?php shell_google_tag($adsId); ?>
<link rel="stylesheet" href="/styles.css?v=20261023">
</head>
<body>
<?php }

function shell_top(): void { ?>
<a class="skip" href="#main">Skip to content</a>

<header class="site">
  <div class="wrap">
    <div class="branding">
      <a class="brand" href="https://ourbeautyproducts.co.za/">Our Beauty Products</a>
      <p class="tagline">Independent business leaders for Avon &amp; Justine</p>
    </div>
    <div class="contact-top">
      <a class="wa-num" href="https://wa.me/27826448208?text=Hi%2C%20I%27d%20like%20more%20information%20about%20joining%20Avon%20or%20Justine" data-track="whatsapp_click" data-location="header">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><use href="#i-wa"/></svg>
        <strong>WhatsApp:</strong> <span>082 644 8208</span>
      </a>
      <a class="mail" href="mailto:info@ourbeautyproducts.co.za" data-open-contact aria-label="Send us an email" title="Send us an email"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></a>
    </div>
  </div>
</header>

<nav class="mainnav on-dark" aria-label="Main" id="nav">
  <div class="wrap">
    <button class="menu-btn" type="button" aria-expanded="false" aria-controls="menu">Menu</button>
    <ul class="top" id="menu">
      <li><a href="/">Home</a></li>
      <li class="has-sub">
        <a href="/#avon">Avon</a>
        <button class="sub-toggle" type="button" aria-expanded="false" aria-label="Avon menu"><svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2"/></svg></button>
        <ul class="sub">
          <li><a href="https://arp.avon.com/prp/ZA/en/shopwithRhee" data-track="join_click" data-brand="avon" data-location="nav">Sign up</a></li>
          <li><a href="https://my.avon.co.za/store/Rheeshop" rel="noopener" data-track="store_click" data-brand="avon" data-location="nav">Shop</a></li>
          <li><a href="/register?brand=avon" data-track="register_click" data-brand="avon" data-location="nav">Register with us</a></li>
        </ul>
      </li>
      <li class="has-sub">
        <a href="/#justine">Justine</a>
        <button class="sub-toggle" type="button" aria-expanded="false" aria-label="Justine menu"><svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2"/></svg></button>
        <ul class="sub">
          <li><a href="https://arp.avon.com/prp/JT/en/shopwith26064123_04" data-track="join_click" data-brand="justine" data-location="nav">Sign up</a></li>
          <li><a href="https://my.justine.co.za/store/Lizsbeautyshop" rel="noopener" data-track="store_click" data-brand="justine" data-location="nav">Shop</a></li>
          <li><a href="/register?brand=justine" data-track="register_click" data-brand="justine" data-location="nav">Register with us</a></li>
        </ul>
      </li>
      <li><a href="/#shop">Shop</a></li>
      <li class="nav-cta"><a href="mailto:info@ourbeautyproducts.co.za" data-open-contact>Contact us</a></li>
    </ul>
  </div>
</nav>
<?php }

function shell_bottom(string $extraScripts = ''): void { ?>
<footer class="on-dark">
  <div class="wrap">
    <div class="cols">
      <div>
        <h2>Our Beauty Products</h2>
        <p>Independent Avon and Justine business leaders based in YOUR TOWN, PROVINCE, helping people join and shop across South Africa.</p>
      </div>
      <div>
        <h2>Contact</h2>
        <ul>
          <li><a href="https://wa.me/27826448208" data-track="whatsapp_click" data-location="footer">WhatsApp 082 644 8208</a></li>
          <li><a href="mailto:info@ourbeautyproducts.co.za" data-open-contact>info@ourbeautyproducts.co.za</a></li>
          <li><a href="https://www.facebook.com/YOUR-FACEBOOK" rel="noopener">Facebook</a></li>
          <li><a href="https://www.instagram.com/YOUR-INSTAGRAM/" rel="noopener">Instagram</a></li>
        </ul>
      </div>
    </div>
    <div class="disclaimer">
      <p>Our Beauty Products is an independent Avon representative and Justine consultant business. Avon and Justine are trademarks of their respective owners. This site is not operated by Avon or Justine.</p>
      <p>We process your personal information in line with POPIA and only use it to respond to your enquiry. <a class="link" href="/privacy">Privacy policy</a></p>
    </div>
  </div>
</footer>

<dialog class="modal" id="contact-modal" aria-labelledby="cm-title" aria-describedby="cm-desc">
  <div class="modal-head">
    <div>
      <h2 id="cm-title">Send us a message</h2>
      <p id="cm-desc">We reply within one working day.</p>
    </div>
    <button class="modal-close" type="button" data-close aria-label="Close">
      <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 4l12 12M16 4L4 16" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
    </button>
  </div>
  <div class="modal-body">
    <form id="contact-form" novalidate>
      <div class="row">
        <div class="field">
          <label for="cm-name">Your name</label>
          <input id="cm-name" name="name" autocomplete="name" autocapitalize="words" required aria-describedby="cm-name-err">
          <p class="field-error" id="cm-name-err"></p>
        </div>
        <div class="field">
          <label for="cm-phone">Cellphone number</label>
          <input id="cm-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="082 123 4567" required aria-describedby="cm-phone-err">
          <p class="field-error" id="cm-phone-err"></p>
        </div>
      </div>
      <div class="field">
        <label for="cm-email">Email address (optional)</label>
        <input id="cm-email" name="email" type="email" inputmode="email" autocomplete="email" spellcheck="false" aria-describedby="cm-email-err">
        <p class="field-error" id="cm-email-err"></p>
      </div>
      <div class="field">
        <label for="cm-topic">What's it about?</label>
        <select id="cm-topic" name="topic">
          <option value="Joining Avon">Joining Avon</option>
          <option value="Joining Justine">Joining Justine</option>
          <option value="Joining both">Joining both</option>
          <option value="Placing an order">Help with an order</option>
          <option value="Something else">Something else</option>
        </select>
      </div>
      <div class="field">
        <label for="cm-message">Your message</label>
        <textarea id="cm-message" name="message" maxlength="3000" required aria-describedby="cm-message-err"></textarea>
        <p class="field-error" id="cm-message-err"></p>
      </div>
      <div class="hp" aria-hidden="true">
        <label for="cm-website">Leave this field empty</label>
        <input id="cm-website" name="website" tabindex="-1" autocomplete="off">
      </div>
      <div class="consent-wrap">
        <label class="consent">
          <input type="checkbox" id="cm-consent" name="consent" required aria-describedby="cm-consent-err">
          <span>I agree that Our Beauty Products may contact me about my enquiry. My details won't be shared or sold.</span>
        </label>
        <p class="field-error" id="cm-consent-err"></p>
      </div>
      <button class="btn btn-ink btn-block" type="submit">Send message</button>
      <div class="form-alert" id="cm-alert" role="alert"></div>
    </form>
    <div class="sent" id="cm-sent" hidden>
      <h3 id="cm-sent-title" tabindex="-1">Message sent</h3>
      <p>Thanks. We'll reply within one working day. For a faster answer, WhatsApp us on 082 644 8208.</p>
      <div class="cta-row">
        <button class="btn btn-ink" type="button" data-close>Close</button>
        <a class="link" href="https://wa.me/27826448208" data-track="whatsapp_click" data-location="modal_sent">WhatsApp us</a>
      </div>
    </div>
  </div>
</dialog>

<a class="wa-float" href="https://wa.me/27826448208?text=Hi%2C%20I%27d%20like%20to%20join%20Avon%20or%20Justine" data-track="whatsapp_click" data-location="float">
  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><use href="#i-wa"/></svg>
  <span>WhatsApp us</span>
</a>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-wa" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3z"/></symbol>
</svg>
<script src="/site.js?v=20261023" defer></script>
<?= $extraScripts ?>
</body>
</html>
<?php }
