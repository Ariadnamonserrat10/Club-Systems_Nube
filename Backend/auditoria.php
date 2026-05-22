<?php
/**
 * auditoria.php - Audit endpoint
 * 
 * SECURITY: 
 * - GET is allowed for viewing audit logs (requires authentication)
 * - DIRECT POST from frontend is RESTRICTED - use internal AuditHelper instead
 * - Only internal backend code should generate audit entries
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/db.php";
require_once __DIR__ . "/AuditHelper.php";
require_once __DIR__ . "/AuthMiddleware.php";

try {
    $auth = new AuthMiddleware();
    $authResult = $auth->requireAnyAuthenticated();
    $currentUser = $auth->getUser();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        if ($limit < 1) $limit = 1;
        if ($limit > 500) $limit = 500;
        if ($offset < 0) $offset = 0;
        
        $auditHelper = AuditHelper::getInstance();
        $logs = $auditHelper->getLogs($limit, $offset);
        $total = $auditHelper->getTotalCount();
        
        echo json_encode([
            'status' => 'success',
            'data' => $logs,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Direct POST to auditoria.php is restricted. Use internal AuditHelper in backend code.',
            'code' => 'DIRECT_AUDIT_POST_NOT_ALLOWED'
        ]);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
