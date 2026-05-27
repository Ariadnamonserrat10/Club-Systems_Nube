<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";
require_once __DIR__ . "/AuthMiddleware.php";

$auth = new AuthMiddleware();
$authResult = $auth->requireAnyAuthenticated();

$user = $authResult['user'];
$tokenManager = new TokenManager();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sessions = $tokenManager->getUserActiveSessions($user['id']);
    
    $sessionsFormatted = array_map(function($s) {
        $ua = $s['user_agent'] ?? '';
        $deviceInfo = parseUserAgent($ua);
        
        return [
            'id' => (int)$s['id'],
            'ip' => maskIpAddress($s['ip'] ?? null),
            'device' => $deviceInfo,
            'user_agent_preview' => substr($ua, 0, 100),
            'expires_at' => $s['expires_at'],
            'creado_en' => $s['creado_en'],
            'is_current' => isCurrentSession($s)
        ];
    }, $sessions);
    
    $auth->successResponse([
        'sessions' => $sessionsFormatted,
        'count' => count($sessionsFormatted)
    ]);
    
} elseif ($method === 'DELETE') {
    $rawBody = file_get_contents("php://input");
    $data = json_decode($rawBody, true);
    
    $sessionId = $data['session_id'] ?? ($_GET['session_id'] ?? null);
    $mode = $data['mode'] ?? 'single';
    
    if ($mode === 'others') {
        $parts = explode('.', $tokenManager->getAuthorizationHeader(), 2);
        $selector = $parts[0] ?? '';
        $currentToken = $tokenManager->getTokenInfoBySelector($selector, TokenManager::TYPE_SESSION);
        
        $revoked = $tokenManager->revokeAllUserTokens(
            $user['id'],
            TokenManager::TYPE_SESSION,
            $currentToken['id'] ?? null
        );
        
        $auth->successResponse([
            'message' => sprintf('Se cerraron %d sesiones', $revoked),
            'revoked_count' => $revoked,
            'mode' => $mode
        ]);
        
    } elseif ($mode === 'all') {
        $revoked = $tokenManager->revokeAllUserTokens($user['id'], TokenManager::TYPE_SESSION);
        
        $auth->successResponse([
            'message' => sprintf('Se cerraron todas las %d sesiones', $revoked),
            'revoked_count' => $revoked,
            'mode' => $mode
        ]);
        
    } else {
        if (!$sessionId) {
            $auth->errorResponse('Session ID no proporcionado', 400);
        }
        
        $sessionId = (int)$sessionId;
        
        $sessionCheck = $tokenManager->getTokenInfoBySelector('dummy', TokenManager::TYPE_SESSION);
        
        $validSession = false;
        $userSessions = $tokenManager->getUserActiveSessions($user['id']);
        foreach ($userSessions as $s) {
            if ($s['id'] === $sessionId) {
                $validSession = true;
                break;
            }
        }
        
        if (!$validSession) {
            $auth->errorResponse('Sesión no encontrada o no pertenece al usuario', 404);
        }
        
        $revoked = $tokenManager->revokeTokenById($sessionId);
        
        $auth->successResponse([
            'message' => 'Sesión cerrada exitosamente',
            'session_id' => $sessionId,
            'revoked' => $revoked
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido'
    ]);
}

function maskIpAddress($ip): string {
    if (!$ip) return 'unknown';
    $parts = explode('.', $ip);
    if (count($parts) >= 2) {
        return $parts[0] . '.' . $parts[1] . '.x.x';
    }
    return $ip;
}

function parseUserAgent($ua): array {
    $ua = strtolower($ua);
    
    $isMobile = strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false;
    $isTablet = strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false;
    
    $device = 'Desktop';
    if ($isTablet) $device = 'Tablet';
    elseif ($isMobile) $device = 'Mobile';
    
    $browser = 'Unknown';
    if (strpos($ua, 'firefox') !== false) $browser = 'Firefox';
    elseif (strpos($ua, 'chrome') !== false) $browser = 'Chrome';
    elseif (strpos($ua, 'safari') !== false) $browser = 'Safari';
    elseif (strpos($ua, 'edge') !== false) $browser = 'Edge';
    elseif (strpos($ua, 'opera') !== false) $browser = 'Opera';
    
    $os = 'Unknown';
    if (strpos($ua, 'windows') !== false) $os = 'Windows';
    elseif (strpos($ua, 'mac') !== false) $os = 'macOS';
    elseif (strpos($ua, 'linux') !== false) $os = 'Linux';
    elseif (strpos($ua, 'android') !== false) $os = 'Android';
    elseif (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) $os = 'iOS';
    
    return [
        'device' => $device,
        'browser' => $browser,
        'os' => $os
    ];
}

function isCurrentSession($session): bool {
    global $tokenManager;
    $token = $tokenManager->getAuthorizationHeader();
    if (!$token) return false;
    
    $parts = explode('.', $token, 2);
    $selector = $parts[0] ?? '';
    
    $parts2 = explode('.', $session['token'] ?? '', 2);
    $sessionSelector = $parts2[0] ?? $session['selector'] ?? '';
    
    return hash_equals($selector, $sessionSelector);
}
?>
