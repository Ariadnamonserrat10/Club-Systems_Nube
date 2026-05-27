<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";
require_once __DIR__ . "/TokenManager.php";
require_once __DIR__ . "/AuditHelper.php";

$tokenManager = new TokenManager();

$rawBody = file_get_contents("php://input");
$data = json_decode($rawBody, true);

$token = $tokenManager->getAuthorizationHeader();
$logoutMode = $data['mode'] ?? 'current';

if (!$token) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Token no proporcionado'
    ]);
    exit;
}

$parts = explode('.', $token, 2);
if (count($parts) !== 2) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Formato de token inválido'
    ]);
    exit;
}

[$selector, $plainToken] = $parts;

$tokenInfo = $tokenManager->getTokenInfoBySelector($selector, TokenManager::TYPE_SESSION);

if (!$tokenInfo) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Token no encontrado o ya revocado'
    ]);
    exit;
}

$userId = $tokenInfo['user_id'];
$currentTokenId = $tokenInfo['id'];

$usuarioAudit = 'Usuario';
$stmtUser = $conexion->prepare("SELECT nombre, apellidoP, usuario FROM usuarios WHERE id = ? LIMIT 1");
if ($stmtUser) {
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    if ($resUser && $rowUser = $resUser->fetch_assoc()) {
        $nombreCompleto = trim($rowUser['nombre'] . ' ' . $rowUser['apellidoP']);
        $usuarioAudit = $nombreCompleto ?: $rowUser['usuario'] ?: 'Usuario';
    }
}

if ($logoutMode === 'all') {
    $revoked = $tokenManager->revokeAllUserTokens($userId);
    $message = sprintf('Se cerraron %d sesiones', $revoked);
} elseif ($logoutMode === 'others') {
    $revoked = $tokenManager->revokeAllUserTokens($userId, null, $currentTokenId);
    $message = sprintf('Se cerraron %d sesiones, manteniendo la actual', $revoked);
} else {
    $revoked = $tokenManager->revokeToken($token, TokenManager::TYPE_SESSION);
    if ($data['remember_token'] ?? null) {
        $tokenManager->revokeToken($data['remember_token'], TokenManager::TYPE_REMEMBER);
    }
    $message = 'Sesión cerrada exitosamente';
}

$descAudit = "Cierre de sesión - Modo: $logoutMode";
audit_logout($userId, $usuarioAudit, $descAudit);

echo json_encode([
    'status' => 'success',
    'message' => $message,
    'mode' => $logoutMode,
    'sessions_revoked' => is_int($revoked) ? $revoked : ($revoked ? 1 : 0)
]);
?>
