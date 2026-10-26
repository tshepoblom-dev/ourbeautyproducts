<?php
// ============================================================
// REGISTRATION SETTINGS
// 1. Copy this file to  register-config.php  (same folder) and fill it in.
// 2. NEVER commit register-config.php to Git or share it. It is listed in .gitignore
//    and blocked from the web by .htaccess.
// Full steps: setup/REGISTRATION-SETUP.md
// ============================================================
return [
  // Google Apps Script web app that stores the Sheet row and the private Drive files
  'bridge_url'    => '',   // e.g. https://script.google.com/macros/s/AKfy.../exec
  'bridge_secret' => '',   // a long random string (40+ characters); the same value goes in Script property BRIDGE_SECRET

  // Google Ads. The tag ID is already installed on every page.
  'google_ads_id'               => 'AW-590577903',
  'google_ads_conversion_label' => '',   // REQUIRED for conversions: Google Ads > Goals > Conversions > your action > Tag setup > Event snippet.
                                         // Copy only the label, the text after the "/" in 'send_to': 'AW-590577903/THIS_PART'

  // Change this date whenever you edit the consent wording on the form (it is recorded with each registration)
  'consent_version' => '2026-10-23',

  // Email you a short alert for each registration (name, brand, town, reference; never documents). Uses mail-config.php.
  'notify_email' => true,
];
