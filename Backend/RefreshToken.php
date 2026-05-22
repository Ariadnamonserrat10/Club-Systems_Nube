<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";
require_once __DIR__ . "/TokenManager.php";

$tokenManager = new TokenManager();

$rawBody = file_get_contents("php://input");
$data = json_decode($rawBody, true);

$oldToken = $tokenManager->getAuthorizationHeader();

if (!$oldToken) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Token no proporcionado'
    ]);
    exit;
}

$fingerprint = $tokenManager->generateDeviceFingerprint();

$result = $tokenManager->validateAndRefreshSession($oldToken, $fingerprint);

if (!$result) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Token inválido o expirado',
        'code' => 'INVALID_TOKEN'
    ]);
    exit;
}

if ($result['is_refreshed']) {
    $tokenData = $result;
    $userData = $result['original_data'];
} else {
    $userData = $result['original_data'];
    $tokenManager->revokeTokenById($userData['id']);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $tokenData = $tokenManager->createToken(
        $userData['user_id'],
        TokenManager::TYPE_SESSION,
        null,
        $ip,
        $userAgent,
        $fingerprint
    );
}

$csrfToken = $tokenManager->createCsrfToken($tokenData['user_id']);

echo json_encode([
    'status' => 'success',
    'message' => 'Token renovado exitosamente',
    'token' => $tokenData['token'],
    'token_expires_at' => $tokenData['expires_at'],
    'token_ttl_seconds' => $tokenData['ttl_seconds'],
    'csrf_token' => $csrfToken,
    'is_refreshed' => $tokenData['is_refreshed'] ?? true
]);
?>
