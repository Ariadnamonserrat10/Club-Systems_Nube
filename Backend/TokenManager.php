<?php
class TokenManager {
    const TYPE_SESSION = 'SESSION';
    const TYPE_REMEMBER = 'REMEMBER';
    const TYPE_CSRF = 'CSRF';
    const TYPE_REFRESH = 'REFRESH';
    const TYPE_PASSWORD_RESET = 'RESET';
    const TYPE_VERIFICATION = 'VERIFY';

    const DEFAULT_TTL = [
        self::TYPE_SESSION => 7200,
        self::TYPE_REMEMBER => 1209600,
        self::TYPE_CSRF => 3600,
        self::TYPE_REFRESH => 604800,
        self::TYPE_PASSWORD_RESET => 1800,
        self::TYPE_VERIFICATION => 86400,
    ];

    private $mysqli;
    private $pdo;

    public function __construct($mysqli = null, $pdo = null) {
        global $conexion, $pdo;
        $this->mysqli = $mysqli ?? $conexion;
        $this->pdo = $pdo ?? $GLOBALS['pdo'] ?? null;
    }

    public function generateToken($length = 64): string {
        return bin2hex(random_bytes($length));
    }

    public function hashToken(string $token): string {
        return hash('sha256', $token, false);
    }

    public function hashTokenConstant(string $token): string {
        return hash_hmac('sha256', $token, $this->getSecretKey(), false);
    }

    private function getSecretKey(): string {
        static $key = null;
        if ($key === null) {
            $key = $this->getOrCreateSecretKey();
        }
        return $key;
    }

    private function getOrCreateSecretKey(): string {
        $configFile = __DIR__ . '/.env.secret';
        if (file_exists($configFile)) {
            $key = trim(file_get_contents($configFile));
            if (strlen($key) >= 32) {
                return $key;
            }
        }
        $key = bin2hex(random_bytes(32));
        file_put_contents($configFile, $key);
        chmod($configFile, 0600);
        return $key;
    }

    public function createToken(
        int $userId,
        string $tipo = self::TYPE_SESSION,
        ?int $ttl = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?array $fingerprint = null
    ): array {
        $plainToken = $this->generateToken();
        $hashedToken = $this->hashToken($plainToken);
        
        $ttl = $ttl ?? self::DEFAULT_TTL[$tipo] ?? 3600;
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        
        $fingerprintHash = $fingerprint ? $this->hashDeviceFingerprint($fingerprint) : null;
        $userAgentTruncated = $userAgent ? substr($userAgent, 0, 512) : null;
        
        $selector = $this->generateToken(16);
        
        $stmt = $this->mysqli->prepare("
            INSERT INTO tokens 
            (token, selector, user_id, tipo, expires_at, activo, ip, user_agent, fingerprint) 
            VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)
        ");
        
        if (!$stmt) {
            error_log('TokenManager: Prepare failed: ' . $this->mysqli->error);
            throw new Exception('Error al crear token');
        }
        
        $nullVal = null;
        $stmt->bind_param(
            'ssisssss',
            $hashedToken,
            $selector,
            $userId,
            $tipo,
            $expiresAt,
            $ip,
            $userAgentTruncated,
            $fingerprintHash
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log('TokenManager: Execute failed: ' . $stmt->error);
            throw new Exception('Error al crear token');
        }
        
        $tokenId = $stmt->insert_id;
        $stmt->close();
        
        return [
            'id' => $tokenId,
            'token' => $selector . '.' . $plainToken,
            'selector' => $selector,
            'hashed_token' => $hashedToken,
            'user_id' => $userId,
            'tipo' => $tipo,
            'expires_at' => $expiresAt,
            'ttl_seconds' => $ttl
        ];
    }

    public function validateToken(string $token, string $tipo = self::TYPE_SESSION, ?array $fingerprint = null): ?array {
        $parts = explode('.', $token, 2);
        
        if (count($parts) !== 2) {
            return null;
        }
        
        [$selector, $plainToken] = $parts;
        
        $hashedToken = $this->hashToken($plainToken);
        
        $stmt = $this->mysqli->prepare("
            SELECT t.*, u.id as user_id, u.nombre, u.apellidoP, u.apellidoM, u.tipo as user_tipo, u.foto, u.club_asignado
            FROM tokens t
            INNER JOIN usuarios u ON t.user_id = u.id
            WHERE t.selector = ? 
            AND t.tipo = ? 
            AND t.activo = 1
            LIMIT 1
        ");
        
        if (!$stmt) {
            error_log('TokenManager validate prepare: ' . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param('ss', $selector, $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $tokenData = $result->fetch_assoc();
        $stmt->close();
        
        if (!$this->constantTimeCompare($tokenData['token'], $hashedToken)) {
            $this->logSuspiciousActivity($tokenData['user_id'] ?? null, 'token_hash_mismatch', $selector);
            return null;
        }
        
        if (!$this->isTokenActive($tokenData)) {
            return null;
        }
        
        if ($fingerprint && $tokenData['fingerprint']) {
            $currentFingerprintHash = $this->hashDeviceFingerprint($fingerprint);
            if (!$this->constantTimeCompare($tokenData['fingerprint'], $currentFingerprintHash)) {
                $this->logSuspiciousActivity($tokenData['user_id'], 'fingerprint_mismatch', $selector);
                $this->revokeTokenById($tokenData['id']);
                return null;
            }
        }
        
        unset($tokenData['token']);
        return $tokenData;
    }

    public function validateAndRefreshSession(string $token, ?array $fingerprint = null): ?array {
        $result = $this->validateToken($token, self::TYPE_SESSION, $fingerprint);
        
        if (!$result) {
            return null;
        }
        
        $expiryTime = strtotime($result['expires_at']);
        $now = time();
        $midpoint = $expiryTime - (self::DEFAULT_TTL[self::TYPE_SESSION] / 2);
        
        if ($now >= $midpoint) {
            $this->revokeTokenById($result['id']);
            
            $newTokenData = $this->createToken(
                $result['user_id'],
                self::TYPE_SESSION,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $fingerprint
            );
            
            $newTokenData['is_refreshed'] = true;
            $newTokenData['original_data'] = $result;
            return $newTokenData;
        }
        
        return [
            'token' => $token,
            'is_refreshed' => false,
            'original_data' => $result
        ];
    }

    public function revokeToken(string $token, string $tipo = self::TYPE_SESSION): bool {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }
        
        [$selector, $plainToken] = $parts;
        $hashedToken = $this->hashToken($plainToken);
        
        $stmt = $this->mysqli->prepare("
            UPDATE tokens 
            SET activo = 0 
            WHERE selector = ? AND token = ? AND tipo = ? AND activo = 1
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('sss', $selector, $hashedToken, $tipo);
        $result = $stmt->execute();
        $affected = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $result && $affected;
    }

    public function revokeTokenById(int $tokenId): bool {
        $stmt = $this->mysqli->prepare("
            UPDATE tokens 
            SET activo = 0 
            WHERE id = ?
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('i', $tokenId);
        $result = $stmt->execute();
        $affected = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $result && $affected;
    }

    public function revokeAllUserTokens(int $userId, ?string $tipo = null, ?int $exceptTokenId = null): int {
        if ($tipo) {
            if ($exceptTokenId) {
                $stmt = $this->mysqli->prepare("
                    UPDATE tokens 
                    SET activo = 0 
                    WHERE user_id = ? AND tipo = ? AND activo = 1 AND id != ?
                ");
                $stmt->bind_param('isi', $userId, $tipo, $exceptTokenId);
            } else {
                $stmt = $this->mysqli->prepare("
                    UPDATE tokens 
                    SET activo = 0 
                    WHERE user_id = ? AND tipo = ? AND activo = 1
                ");
                $stmt->bind_param('is', $userId, $tipo);
            }
        } else {
            if ($exceptTokenId) {
                $stmt = $this->mysqli->prepare("
                    UPDATE tokens 
                    SET activo = 0 
                    WHERE user_id = ? AND activo = 1 AND id != ?
                ");
                $stmt->bind_param('ii', $userId, $exceptTokenId);
            } else {
                $stmt = $this->mysqli->prepare("
                    UPDATE tokens 
                    SET activo = 0 
                    WHERE user_id = ? AND activo = 1
                ");
                $stmt->bind_param('i', $userId);
            }
        }
        
        if (!$stmt) {
            return 0;
        }
        
        $stmt->execute();
        $revoked = $stmt->affected_rows;
        $stmt->close();
        
        return $revoked;
    }

    public function cleanupExpiredTokens(): int {
        $stmt = $this->mysqli->prepare("
            DELETE FROM tokens 
            WHERE expires_at < NOW() OR activo = 0
        ");
        
        if (!$stmt) {
            return 0;
        }
        
        $stmt->execute();
        $cleaned = $stmt->affected_rows;
        $stmt->close();
        
        return $cleaned;
    }

    public function getUserActiveSessions(int $userId, string $tipo = self::TYPE_SESSION): array {
        $stmt = $this->mysqli->prepare("
            SELECT id, user_id, tipo, expires_at, activo, ip, user_agent, creado_en
            FROM tokens 
            WHERE user_id = ? AND tipo = ? AND activo = 1 AND expires_at > NOW()
            ORDER BY creado_en DESC
        ");
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param('is', $userId, $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        $stmt->close();
        return $sessions;
    }

    public function createCsrfToken(int $userId, ?string $sessionSelector = null): string {
        $ttl = self::DEFAULT_TTL[self::TYPE_CSRF];
        
        $stmt = $this->mysqli->prepare("
            DELETE FROM tokens 
            WHERE user_id = ? AND tipo = ? AND (expires_at < NOW() OR activo = 0)
        ");
        $tipoCsrf = self::TYPE_CSRF;
        $stmt->bind_param('is', $userId, $tipoCsrf);
        $stmt->execute();
        $stmt->close();
        
        $tokenData = $this->createToken($userId, self::TYPE_CSRF, $ttl);
        
        return $tokenData['token'];
    }

    public function validateCsrfToken(int $userId, string $token): bool {
        $result = $this->validateToken($token, self::TYPE_CSRF);
        
        if (!$result) {
            return false;
        }
        
        if ($result['user_id'] !== $userId) {
            $this->logSuspiciousActivity($userId, 'csrf_token_user_mismatch', $token);
            return false;
        }
        
        $this->revokeTokenById($result['id']);
        
        return true;
    }

    public function createPasswordResetToken(int $userId): array {
        $this->revokeAllUserTokens($userId, self::TYPE_PASSWORD_RESET);
        
        return $this->createToken(
            $userId,
            self::TYPE_PASSWORD_RESET,
            self::DEFAULT_TTL[self::TYPE_PASSWORD_RESET],
            $_SERVER['REMOTE_ADDR'] ?? null
        );
    }

    public function validatePasswordResetToken(string $token): ?array {
        return $this->validateToken($token, self::TYPE_PASSWORD_RESET);
    }

    public function consumePasswordResetToken(string $token): bool {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }
        
        [$selector, $plainToken] = $parts;
        $hashedToken = $this->hashToken($plainToken);
        
        $stmt = $this->mysqli->prepare("
            UPDATE tokens 
            SET activo = 0 
            WHERE selector = ? AND token = ? AND tipo = ? AND activo = 1
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $tipoReset = self::TYPE_PASSWORD_RESET;
        $stmt->bind_param('sss', $selector, $hashedToken, $tipoReset);
        $result = $stmt->execute();
        $affected = $stmt->affected_rows > 0;
        $stmt->close();
        
        return $result && $affected;
    }

    public function getAuthorizationHeader(): ?string {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if ($authHeader && str_starts_with(strtolower($authHeader), 'bearer ')) {
            return trim(substr($authHeader, 7));
        }
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (str_starts_with(strtolower($auth), 'bearer ')) {
                return trim(substr($auth, 7));
            }
        }
        
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (str_starts_with(strtolower($auth), 'bearer ')) {
                return trim(substr($auth, 7));
            }
        }
        
        return null;
    }

    public function generateDeviceFingerprint(?array $additionalData = null): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        $ipParts = explode('.', $ip);
        $ipSubnet = count($ipParts) >= 3 ? implode('.', array_slice($ipParts, 0, 3)) : $ip;
        
        return [
            'ip_subnet' => $ipSubnet,
            'user_agent_hash' => hash('sha256', $userAgent),
            'accept_lang_hash' => hash('sha256', $acceptLang),
            'accept_encoding_hash' => hash('sha256', $acceptEncoding),
            'timestamp' => time()
        ];
    }

    public function hashDeviceFingerprint(array $fingerprint): string {
        $data = [
            $fingerprint['ip_subnet'] ?? '',
            $fingerprint['user_agent_hash'] ?? '',
            $fingerprint['accept_lang_hash'] ?? ''
        ];
        return hash('sha256', implode('|', $data), false);
    }

    private function isTokenActive(array $tokenData): bool {
        if ($tokenData['activo'] !== 1) {
            return false;
        }
        
        $expiryTime = strtotime($tokenData['expires_at']);
        if ($expiryTime === false || $expiryTime < time()) {
            return false;
        }
        
        return true;
    }

    private function constantTimeCompare(string $hash1, string $hash2): bool {
        return hash_equals($hash1, $hash2);
    }

    private function logSuspiciousActivity(?int $userId, string $reason, string $selector): void {
        error_log(sprintf(
            '[TokenManager] Suspicious activity: %s | User: %s | Selector: %s | IP: %s | UA: %s',
            $reason,
            $userId ?? 'unknown',
            $selector,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100)
        ));
    }

    public function getTokenInfoBySelector(string $selector, $tipo = self::TYPE_SESSION): ?array {
        $stmt = $this->mysqli->prepare("
            SELECT id, user_id, tipo, expires_at, activo, ip, creado_en
            FROM tokens 
            WHERE selector = ? AND tipo = ?
            LIMIT 1
        ");
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param('ss', $selector, $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data;
    }

    public function countActiveSessions(int $userId, string $tipo = self::TYPE_SESSION): int {
        $stmt = $this->mysqli->prepare("
            SELECT COUNT(*) as total
            FROM tokens 
            WHERE user_id = ? AND tipo = ? AND activo = 1 AND expires_at > NOW()
        ");
        
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param('is', $userId, $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total'] ?? 0);
    }

    public function enforceMaximumSessions(int $userId, int $maxSessions = 5, string $tipo = self::TYPE_SESSION): bool {
        $activeCount = $this->countActiveSessions($userId, $tipo);
        
        if ($activeCount < $maxSessions) {
            return true;
        }
        
        $stmt = $this->mysqli->prepare("
            SELECT id FROM tokens 
            WHERE user_id = ? AND tipo = ? AND activo = 1 
            ORDER BY creado_en ASC 
            LIMIT 1
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('is', $userId, $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return true;
        }
        
        $oldest = $result->fetch_assoc();
        $stmt->close();
        
        return $this->revokeTokenById($oldest['id']);
    }
}
