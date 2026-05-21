<?php
/**
 * security.php - Security headers and CSP
 * Included by cors.php for all API responses.
 */

// --- CSP dinámico según entorno ---
$appEnv = getenv('APP_ENV') ?: 'production';
if (file_exists(__DIR__ . '/.env')) {
  $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (str_starts_with(trim($line), 'APP_ENV=')) {
      $appEnv = trim(substr(trim($line), 8));
      break;
    }
  }
}

$imgCdn = 'https://cdn-icons-png.flaticon.com';

if ($appEnv === 'development' || $appEnv === 'local') {
  $csp = "default-src 'self'; ".
         "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; ".
         "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; ".
         "img-src 'self' data: blob: $imgCdn; ".
         "font-src 'self' https://cdn.jsdelivr.net; ".
         "connect-src 'self' http://localhost:* ws://localhost:* https://cdn.jsdelivr.net; ".
         "frame-ancestors 'none'";
} else {
  $csp = "default-src 'self'; ".
         "script-src 'self' https://cdn.jsdelivr.net; ".
         "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; ".
         "img-src 'self' data: blob: $imgCdn; ".
         "font-src 'self' https://cdn.jsdelivr.net; ".
         "connect-src 'self' https://cdn.jsdelivr.net; ".
         "frame-ancestors 'none'";
}

header("Content-Security-Policy: $csp");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
