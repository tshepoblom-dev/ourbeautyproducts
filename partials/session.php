<?php
// Session + one-time form tokens for the registration form.
// Cookie is httponly, secure on https, SameSite=Lax. It holds no personal data: only token states.

function reg_session_start(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
  session_name('obp_sid');
  session_start();
}

/** Issue a one-time token for a form. Tokens expire after 3 hours; at most 6 are kept per browser. */
function reg_issue_token(): string {
  reg_session_start();
  $tokens = array_filter($_SESSION['reg_tokens'] ?? [], fn($t) => ($t['t'] ?? 0) > time() - 10800);
  $tokens = array_slice($tokens, -5, null, true);
  $token  = bin2hex(random_bytes(24));
  $tokens[$token] = ['t' => time(), 'state' => 'open'];
  $_SESSION['reg_tokens'] = $tokens;
  return $token;
}
