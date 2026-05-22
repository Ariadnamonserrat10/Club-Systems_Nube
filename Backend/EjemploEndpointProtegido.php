<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/AuthMiddleware.php";

$auth = new AuthMiddleware();
$authResult = $auth->requireAnyAuthenticated();

$user = $authResult['user'];

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $response = [
            'status' => 'success',
            'data' => [
                'message' => 'API protegida - acceso autorizado',
                'usuario' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'] . ' ' . $user['apellidoP'],
                    'tipo' => $user['tipo']
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
        if ($authResult['is_refreshed'] && $authResult['new_token']) {
            $response['new_token'] = $authResult['new_token'];
        }
        
        echo json_encode($response);
        break;
        
    case 'POST':
        $rawBody = file_get_contents("php://input");
        $payload = json_decode($rawBody, true);
        
        $response = [
            'status' => 'success',
            'data' => [
                'message' => 'Datos recibidos correctamente',
                'usuario_id' => $user['id'],
                'datos_recibidos' => $payload,
                'procesado_en' => date('Y-m-d H:i:s')
            ]
        ];
        
        echo json_encode($response);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Método no permitido'
        ]);
        break;
}
