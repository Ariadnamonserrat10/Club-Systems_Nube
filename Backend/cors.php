<?php
// Comma-separated list, e.g. https://clubsystems.netlify.app,https://tuapp.com
$rawAllowedOrigins = getenv('ALLOWED_ORIGINS') ?: 'https://clubsystems.netlify.app';
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $rawAllowedOrigins))));

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}
?>