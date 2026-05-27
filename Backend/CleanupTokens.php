<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";
require_once __DIR__ . "/TokenManager.php";

$tokenManager = new TokenManager();

$isCli = php_sapi_name() === 'cli';
$isScheduled = isset($_GET['cron']) || isset($argv);

if (!$isCli && !$isScheduled) {
    $token = $tokenManager->getAuthorizationHeader();
    if (!$token) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Acceso denegado'
        ]);
        exit;
    }
    
    $result = $tokenManager->validateToken($token, TokenManager::TYPE_SESSION);
    if (!$result || $result['user_tipo'] !== 'OFICINA') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Permisos insuficientes'
        ]);
        exit;
    }
}

$cleaned = $tokenManager->cleanupExpiredTokens();

$response = [
    'status' => 'success',
    'message' => sprintf('Limpieza completada. %d tokens eliminados', $cleaned),
    'tokens_cleaned' => $cleaned,
    'executed_at' => date('Y-m-d H:i:s')
];

if ($isCli) {
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
} else {
    echo json_encode($response);
}
?>
