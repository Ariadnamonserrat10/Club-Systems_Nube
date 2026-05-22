<?php
require_once __DIR__ . '/TokenManager.php';

class AuthMiddleware {
    private $tokenManager;
    private $currentUser = null;
    private $currentToken = null;
    private $fingerprint = null;

    public function __construct() {
        $this->tokenManager = new TokenManager();
        $this->fingerprint = $this->tokenManager->generateDeviceFingerprint();
    }

    public function authenticate($requiredTipo = null): array {
        $token = $this->tokenManager->getAuthorizationHeader();
        
        if (!$token) {
            $this->denyAccess('Token de autenticación no proporcionado');
        }
        
        $result = $this->tokenManager->validateAndRefreshSession($token, $this->fingerprint);
        
        if (!$result) {
            $this->denyAccess('Token inválido o expirado');
        }
        
        $tokenData = $result['original_data'] ?? $result;
        
        if ($requiredTipo !== null) {
            $userTipo = $tokenData['user_tipo'] ?? $tokenData['tipo'] ?? null;
            if ($userTipo !== $requiredTipo) {
                $this->denyAccess('Permisos insuficientes: se requiere ' . $requiredTipo);
            }
        }
        
        $this->currentToken = $result;
        $this->currentUser = [
            'id' => $tokenData['user_id'],
            'nombre' => $tokenData['nombre'],
            'apellidoP' => $tokenData['apellidoP'],
            'apellidoM' => $tokenData['apellidoM'],
            'tipo' => $tokenData['user_tipo'],
            'foto' => $tokenData['foto'] ?? null,
            'club_asignado' => isset($tokenData['club_asignado']) ? (int)$tokenData['club_asignado'] : null,
        ];
        
        return [
            'user' => $this->currentUser,
            'token' => $result['token'],
            'is_refreshed' => $result['is_refreshed'] ?? false,
            'new_token' => $result['is_refreshed'] ? $result['token'] : null
        ];
    }

    public function getUser(): ?array {
        return $this->currentUser;
    }

    public function getUserId(): ?int {
        return $this->currentUser['id'] ?? null;
    }

    public function getUserType(): ?string {
        return $this->currentUser['tipo'] ?? null;
    }

    public function isOficina(): bool {
        return $this->getUserType() === 'OFICINA';
    }

    public function isMonitor(): bool {
        return $this->getUserType() === 'MONITOR';
    }

    public function requireOficina(): array {
        return $this->authenticate('OFICINA');
    }

    public function requireMonitor(): array {
        return $this->authenticate('MONITOR');
    }

    public function requireAnyAuthenticated(): array {
        return $this->authenticate();
    }

    private function denyAccess(string $message): void {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'code' => 'UNAUTHORIZED'
        ]);
        exit;
    }

    public function requirePostMethod(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => 'Método no permitido. Use POST.',
                'code' => 'METHOD_NOT_ALLOWED'
            ]);
            exit;
        }
    }

    public function requireGetMethod(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => 'Método no permitido. Use GET.',
                'code' => 'METHOD_NOT_ALLOWED'
            ]);
            exit;
        }
    }

    public function getJsonInput(): array {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => 'JSON inválido',
                'code' => 'INVALID_JSON'
            ]);
            exit;
        }
        
        return $data ?? [];
    }

    public function successResponse($data, $message = null): void {
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'status' => 'success'
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($this->currentToken && $this->currentToken['is_refreshed']) {
            $response['new_token'] = $this->currentToken['token'];
        }
        
        $response['data'] = $data;
        
        echo json_encode($response);
        exit;
    }

    public function errorResponse(string $message, int $httpCode = 400, $code = null): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        if ($code !== null) {
            $response['code'] = $code;
        }
        
        echo json_encode($response);
        exit;
    }

    public function sendRefreshedTokenHeader(): void {
        if ($this->currentToken && $this->currentToken['is_refreshed']) {
            header('X-Token-Refreshed: ' . $this->currentToken['token']);
        }
    }
}
