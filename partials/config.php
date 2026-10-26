<?php
// Loads register-config.php (your private settings) over safe defaults.
// register-config.php is blocked from the web by .htaccess and listed in .gitignore.

function register_config(): array {
  static $cfg = null;
  if ($cfg !== null) return $cfg;
  
  $defaults = [
    'google_ads_id'               => 'AW-590577903',
    'google_ads_conversion_label' => 'kdADCKm2qqodEO-BzpkC',          // from Google Ads: the part after the "/" in AW-590577903/XXXX
    'bridge_url'                  => 'https://script.google.com/macros/s/AKfycbzHknCwAiYfkZ4Ps9EYganYEDMSYmLLz6n1xm4dIGJvhX3ef29Go1AfuJ6iiq2ul_lNeA/exec',   // e.g. https://script.google.com/macros/s/AKfy.../exec
    'bridge_secret'               => '4abcdac7e81bacd24db4b09d99868a12',       // same value as Script property BRIDGE_SECRET
    'consent_version'             => '2026-10-23',// change whenever the consent wording on the form changes
    'notify_email'                => true,        // email you a short alert (no documents) for each registration
    'max_file_bytes'              => 6 * 1024 * 1024,
    'min_fill_seconds'            => 4,           // submissions faster than this are treated as bots
  ];

  $file = __DIR__ . '/../register-config.php';
  $user = is_file($file) ? require $file : [];
  $cfg  = array_merge($defaults, is_array($user) ? $user : []);
  return $cfg;
}

/** A conversion label is letters, digits, "-" and "_" only. Anything else is ignored rather than printed into a page. */
function conversion_label(array $cfg): string {
  $l = trim((string)($cfg['google_ads_conversion_label'] ?? ''));
  return preg_match('/^[A-Za-z0-9_-]{6,60}$/', $l) ? $l : '';
}
