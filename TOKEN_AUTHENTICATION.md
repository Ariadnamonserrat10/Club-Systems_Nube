# Sistema de Autenticación Segura con Tokens

## Tabla de Contenidos
1. [Resumen General](#resumen-general)
2. [Estructura de la Tabla `tokens`](#estructura-de-la-tabla-tokens)
3. [Flujo de Autenticación](#flujo-de-autenticación)
4. [Archivos del Backend](#archivos-del-backend)
5. [Archivos del Frontend](#archivos-del-frontend)
6. [Características de Seguridad](#características-de-seguridad)
7. [Cómo Proteger un Endpoint](#cómo-proteger-un-endpoint)
8. [Ejemplos de Uso](#ejemplos-de-uso)
9. [Limpieza de Tokens](#limpieza-de-tokens)

---

## Resumen General

Este sistema reemplaza la autenticación basada únicamente en `sessionStorage` del lado del cliente, por un sistema seguro de tokens:

### Problemas Resueltos
- **Sin validación del lado del servidor**: Antes, cualquier `usuarioId` en `sessionStorage` otorgaba acceso
- **Tokens sin hashear**: Ahora los tokens se guardan hasheados con SHA-256
- **Sin expiración**: Todos los tokens tienen fecha de expiración
- **Sin invalidación**: Logout revoca inmediatamente el token
- **Sin límite de sesiones**: Máximo 5 sesiones por usuario
- **Sin detección de dispositivos**: Fingerprinting de dispositivo

### Tipos de Tokens Soportados
| Tipo | Constante | Duración | Uso |
|------|-----------|----------|-----|
| SESSION | `TokenManager::TYPE_SESSION` | 2 horas | Token de sesión principal |
| REMEMBER | `TokenManager::TYPE_REMEMBER` | 2 semanas | "Recordarme" |
| CSRF | `TokenManager::TYPE_CSRF` | 1 hora | Protección CSRF |
| RESET | `TokenManager::TYPE_PASSWORD_RESET` | 30 minutos | Restablecer contraseña |
| REFRESH | `TokenManager::TYPE_REFRESH` | 1 semana | Renovación de token |

---

## Estructura de la Tabla `tokens`

La tabla **YA EXISTE** en la base de datos. Asegúrate de que tenga estas columnas:

```sql
CREATE TABLE IF NOT EXISTS tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(128) NOT NULL,        -- Token HASHEADO con SHA-256
    selector VARCHAR(32) NOT NULL,       -- Selector público para búsquedas
    user_id INT NOT NULL,                 -- ID del usuario
    tipo VARCHAR(20) NOT NULL,            -- SESSION, REMEMBER, CSRF, etc.
    expires_at DATETIME NOT NULL,         -- Fecha de expiración
    activo TINYINT(1) DEFAULT 1,          -- 1 = activo, 0 = revocado
    ip VARCHAR(45) NULL,                   -- IP del cliente (IPv4/IPv6)
    user_agent VARCHAR(512) NULL,         -- Navegador/dispositivo
    fingerprint VARCHAR(64) NULL,          -- Hash del fingerprint del dispositivo
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (token),
    INDEX idx_selector (selector),
    INDEX idx_user_id (user_id),
    INDEX idx_tipo (tipo),
    INDEX idx_expires_at (expires_at),
    INDEX idx_user_tipo (user_id, tipo, activo)
);
```

### Columnas Explicadas

| Columna | Propósito | Seguridad |
|---------|-----------|-----------|
| `token` | Token **hasheado**, NUNCA el token plano | SHA-256, no se puede revertir |
| `selector` | Identificador público para buscar el token | Permite búsqueda sin exponer el hash |
| `user_id` | Usuario dueño del token | FK a `usuarios.id` |
| `tipo` | Qué tipo de token es | Diferentes propósitos/TTL |
| `expires_at` | Cuándo expira | Forza expiración incluso si el token se filtra |
| `activo` | Está revocado o no | `activo=0` invalida inmediatamente |
| `ip` | IP que creó el token | Detección de actividad sospechosa |
| `user_agent` | Navegador/dispositivo | Identificar sesiones |
| `fingerprint` | Hash de características del dispositivo | Detección de robo de token |
| `creado_en` | Momento de creación | Auditoría, ordenar sesiones |

---

## Flujo de Autenticación

### Paso 1: Login
```
FRONTEND                                BACKEND
   |                                       |
   |-- POST /Backend/Login.php ----------->|
   |   { usuario, password, userType }     |
   |                                       |
   |   Verificar credenciales              |
   |   bcrypt verify                       |
   |   Generar token (128 bytes aleatorios)|
   |   Hashear con SHA-256                 |
   |   Guardar hash en BD                  |
   |                                       |
   |<-- { token, csrf_token, userData }---|
   |   Token = selector + "." + tokenPlano |
   |                                       |
```

### Paso 2: Guardar Token (Frontend)
```javascript
// authService.login() guarda automáticamente:
sessionStorage.setItem('auth_token', token);        // Token de sesión
sessionStorage.setItem('auth_user_data', userData); // Datos del usuario
localStorage.setItem('auth_refresh_token', rememberToken); // Solo si "Recordarme"
```

### Paso 3: Solicitudes Autenticadas
```
FRONTEND                                BACKEND
   |                                       |
   |-- GET /Backend/Endpoint.php --------->|
   |   Header: Authorization: Bearer <token>|
   |                                       |
   |   TokenManager::validateToken()       |
   |   1. Extraer selector y tokenPlano    |
   |   2. Buscar por selector en BD        |
   |   3. Hashear tokenPlano con SHA-256   |
   |   4. Comparar hash_equals (tiempo constante)
   |   5. Verificar que activo=1           |
   |   6. Verificar expires_at > NOW()     |
   |   7. Opcional: Verificar fingerprint   |
   |                                       |
   |<-- { status: 'success', data: ... }--|
   |                                       |
```

### Paso 4: Logout
```
FRONTEND                                BACKEND
   |                                       |
   |-- POST /Backend/Logout.php ---------->|
   |   Header: Authorization: Bearer <token>|
   |   Body: { mode: 'current' | 'all' | 'others' }
   |                                       |
   |   UPDATE tokens SET activo=0 WHERE ...|
   |                                       |
   |<-- { status: 'success' }-------------|
   |                                       |
   | authService.clearAuth()               |
   | - Borrar sessionStorage               |
   | - Borrar localStorage                 |
   | - Redirigir a /                       |
```

---

## Archivos del Backend

### `TokenManager.php`
Clase principal para gestionar todos los aspectos de los tokens.

#### Métodos Principales:
```php
$tm = new TokenManager();

// Generar tokens seguros
$tokenData = $tm->createToken(
    userId: 1,
    tipo: TokenManager::TYPE_SESSION,
    ip: $_SERVER['REMOTE_ADDR'],
    userAgent: $_SERVER['HTTP_USER_AGENT'],
    fingerprint: $tm->generateDeviceFingerprint()
);

// Validar tokens
$tokenData = $tm->validateToken(
    token: $tokenString,
    tipo: TokenManager::TYPE_SESSION,
    fingerprint: $fingerprint  // Opcional, pero recomendado
);

// Revocar tokens
$tm->revokeToken($tokenString, TokenManager::TYPE_SESSION);
$tm->revokeTokenById($tokenId);
$tm->revokeAllUserTokens($userId);  // Cerrar todas las sesiones

// Limpieza
$tm->cleanupExpiredTokens();  // Borrar expirados/revocados

// Gestión de sesiones
$tm->getUserActiveSessions($userId);
$tm->countActiveSessions($userId);
$tm->enforceMaximumSessions($userId, 5);  // Máximo 5 sesiones
```

#### Seguridad Clave:
1. **`hash('sha256', $token)`**: Nunca guardar tokens planos
2. **`hash_equals()`**: Comparación de tiempo constante (previene timing attacks)
3. **`random_bytes()`**: Generación criptográficamente segura
4. **`bin2hex()`**: Convierte bytes aleatorios a string hexadecimal

### `AuthMiddleware.php`
Middleware para proteger endpoints.

```php
require_once __DIR__ . '/AuthMiddleware.php';

$auth = new AuthMiddleware();

// Opción 1: Cualquier usuario autenticado
$authResult = $auth->requireAnyAuthenticated();

// Opción 2: Solo Oficina (admin)
$authResult = $auth->requireOficina();

// Opción 3: Solo Monitor
$authResult = $auth->requireMonitor();

// Obtener datos del usuario
$user = $auth->getUser();
$userId = $auth->getUserId();
$userType = $auth->getUserType();

if ($auth->isOficina()) { /* Acceso administrativo */ }
if ($auth->isMonitor()) { /* Acceso de monitor */ }
```

### `Login.php`
Actualizado para generar tokens en lugar de solo devolver datos del usuario.

**Respuesta Exitosa:**
```json
{
  "status": "success",
  "token": "abc123...xyz.456def...789",
  "token_expires_at": "2026-05-21 16:00:00",
  "token_ttl_seconds": 7200,
  "csrf_token": "abc...def",
  "id": 1,
  "nombre": "Usuario",
  "tipo": "OFICINA",
  ...
}
```

### `Logout.php`
Revoca tokens del lado del servidor.

**Modos de Logout:**
- `mode: 'current'` → Cerrar solo esta sesión
- `mode: 'all'` → Cerrar TODAS las sesiones
- `mode: 'others'` → Cerrar todas MENOS esta

### `RefreshToken.php`
Renueva tokens expirados o a punto de expirar.

**Renovación Automática:**
- El token se renueva automáticamente cuando le quedan menos de 60 minutos
- El token antiguo se revoca inmediatamente
- Nuevo token con 2 horas completas

### `Me.php`
Obtiene datos del usuario actual (para validar el token y refrescar datos).

### `Sessions.php`
Gestión de múltiples sesiones por usuario.

```javascript
// Listar todas las sesiones activas
const sessions = await authService.getActiveSessions();

// Cerrar una sesión específica
await authService.revokeSession(sessionId);

// Cerrar todas las demás sesiones
await authService.revokeOtherSessions();
```

### `CleanupTokens.php`
Elimina tokens expirados o revocados.

**Para ejecutar periódicamente:**
```bash
# CLI
php Backend/CleanupTokens.php

# Web (solo Oficina)
curl http://localhost/Backend/CleanupTokens.php?cron=1 \
  -H "Authorization: Bearer <token_oficina>"
```

**MySQL Event Scheduler (automático):**
```sql
SET GLOBAL event_scheduler = ON;

CREATE EVENT limpiar_tokens_expirados
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
    DELETE FROM tokens WHERE expires_at < NOW() OR activo = 0;
```

---

## Archivos del Frontend

### `src/services/auth.js`
Servicio de autenticación principal.

```javascript
import { authService } from '@/services/auth';

// Login
const result = await authService.login({
    usuario: 'ADM00001',
    password: 'Admin123!',
    userType: 'oficina',
    remember_me: true  // Opcional
});

// Verificar autenticación
if (authService.isAuthenticated()) {
    // Usuario logueado
}

// Obtener datos del usuario
const userData = authService.getUserData();
const userId = authService.getUserId();

// Logout
await authService.logout();           // Cerrar sesión actual
await authService.logout('all');      // Cerrar todas
await authService.logout('others');   // Cerrar otras

// Limpiar todo (sin llamada al servidor)
authService.clearAuth();
```

### `src/services/http.js`
Axios configurado con interceptores.

```javascript
import api from '@/services/http';

// Automáticamente:
// - Añade Header: Authorization: Bearer <token>
// - Añade Header: X-CSRF-Token (para POST/PUT/DELETE)
// - Detecta 401 y renueva token automáticamente
// - Si no puede renovar, limpia auth y redirige

const response = await api.get('/Clubs.php');
const result = await api.post('/Alumnos.php', data);
```

### `src/services/api.js`
Funciones de API existentes, actualizadas para incluir tokens en las solicitudes `fetch()`.

### `src/main.js`
Router con **Navigation Guards**:

```javascript
// Rutas protegidas requieren autenticación
{ 
    path: '/oficina', 
    component: Oficina, 
    meta: { requiresAuth: true, role: 'OFICINA' } 
}

// Redirige automáticamente si:
// - No está autenticado → '/'
// - Rol incorrecto → su ruta correspondiente
// - Autenticado y va a '/' → su dashboard
```

### Flujo de Router Guards
```
Usuario navega a '/oficina'
         |
         v
router.beforeEach()
         |
         v
¿requiresAuth: true?
         |
    Sí---+---No
    |        |
    v        v
¿authService   Continuar
.isAuthenticated()?   normal
    |
Sí--+--No
|       |
v       v
¿Rol    authService.
correcto?  clearAuth()
|         Redirige '/'
v
Sí: Permitir acceso
No: Redirige a dashboard correcto
```

---

## Características de Seguridad

### 1. Tokens Hasheados (SHA-256)
```php
// Al crear:
$tokenPlano = $this->generateToken();     // "abc123xyz..."
$tokenHash = hash('sha256', $tokenPlano); // "a1b2c3d4..." (64 chars)

// Guardar solo el hash en BD
// Devolver al frontend: selector + "." + tokenPlano
```

**¿Por qué es importante?**
- Si la BD se compromete, los atacantes NO pueden usar los tokens
- Cada token es único, no hay forma de revertir el hash

### 2. Selector + Token (Patrón de Búsqueda Seguro)
```
Formato del token devuelto al cliente:
   <selector> . <token_plano>
      |            |
      |            +-- 128 bytes aleatorios (256 chars hex)
      |
      +-- 16 bytes aleatorios (32 chars hex) - BUSCAR POR ESTE
```

**Al validar:**
1. Buscar en BD por `selector` (rápido, tiene índice)
2. Obtener el `token` (hash) de la fila
3. Hashear el `token_plano` recibido
4. Comparar con `hash_equals()`

**Ventaja:** No buscamos por el hash, evitamos que los logs revelen información sensible.

### 3. Comparación de Tiempo Constante
```php
// MAL: vulnerable a timing attacks
if ($dbHash === $inputHash) { ... }

// BIEN: tiempo constante, sin importar coincidencia
if (hash_equals($dbHash, $inputHash)) { ... }
```

**¿Cómo funciona un timing attack?**
- Las comparaciones de strings normalmente se detienen en el primer carácter diferente
- Un atacante medirá diferencias de microsegundos
- `hash_equals()` siempre compara TODOS los caracteres

### 4. Expiración Obligatoria
```php
// Tipos y sus TTL:
const DEFAULT_TTL = [
    self::TYPE_SESSION => 7200,        // 2 horas
    self::TYPE_REMEMBER => 1209600,    // 2 semanas
    self::TYPE_CSRF => 3600,            // 1 hora
    self::TYPE_REFRESH => 604800,       // 1 semana
    self::TYPE_PASSWORD_RESET => 1800,   // 30 minutos
];
```

**Doble protección:**
1. `expires_at` en BD - validación del lado del servidor
2. Limpieza automática - tokens expirados se borran

### 5. Invalidación Inmediata
```php
// No es necesario esperar a la expiración
$tm->revokeTokenById($tokenId);  // UPDATE tokens SET activo=0 WHERE id=?

// Incluso si el atacante tiene el token, no sirve:
// validateToken() verifica que activo=1
```

### 6. Fingerprint de Dispositivo
```php
$fingerprint = $tm->generateDeviceFingerprint();
// Incluye:
// - IP subnet (primeros 3 octetos)
// - User-Agent hash
// - Accept-Language hash

$fingerprintHash = $tm->hashDeviceFingerprint($fingerprint);
// Se guarda en la columna fingerprint
```

**Al validar:**
- Si el fingerprint no coincide, el token se revoca AUTOMÁTICAMENTE
- Protege contra robo de token (XSS, man-in-the-middle)

### 7. Límite de Sesiones
```php
// Máximo 5 sesiones por defecto
$tm->enforceMaximumSessions($userId, 5);

// Si hay 5 sesiones y se crea una nueva:
// - La sesión más antigua se REVOCA
// - Nuevo usuario no sabe que se cerró otra sesión
```

### 8. Renovación Automática (Anti-Replay)
```php
// validateAndRefreshSession():
// 1. Valida el token
// 2. Si le quedan < 60 minutos (la mitad de 2h):
//    a. Revoca el token actual
//    b. Crea uno NUEVO con 2h completas
//    c. Devuelve el nuevo token
```

**¿Por qué?**
- Un token no puede usarse dos veces si es "refrescado"
- Reduce la ventana de ataque si un token se filtra
- El frontend intercepta el nuevo token automáticamente

### 9. Rate Limiting (Login)
```php
// Login.php: máximo 5 intentos por IP/usuario en 5 minutos
function checkRateLimit($usuario, $maxAttempts = 5, $windowSeconds = 300)
```

### 10. Constant-Time Response (Login)
```php
// Tanto login exitoso como fallido devuelven:
// - Tiempo de ejecución similar
// - Mismo tamaño de respuesta aproximado
// - Incluyen un fakeHash para ofuscar

function constantTimeResponse($success) {
    $fakeHash = '$2y$10$FakeHashForTiming...';
    if ($success) {
        echo json_encode(["status" => "success", "fake" => $fakeHash]);
    } else {
        echo json_encode(["status" => "error", "fake" => $fakeHash]);
    }
}
```

---

## Cómo Proteger un Endpoint

### Método 1: Rápido (AuthMiddleware)
```php
<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/AuthMiddleware.php";

$auth = new AuthMiddleware();

// Solo Oficina
$authResult = $auth->requireOficina();

// Ó solo Monitor
// $authResult = $auth->requireMonitor();

// Ó cualquier usuario autenticado
// $authResult = $auth->requireAnyAuthenticated();

$user = $auth->getUser();

// A partir de aquí, el usuario está validado
echo json_encode([
    'status' => 'success',
    'data' => [
        'mensaje' => 'Endpoint protegido',
        'usuario' => $user['nombre']
    ]
]);
```

### Método 2: TokenManager Directo
```php
<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/TokenManager.php";

$tm = new TokenManager();

// Obtener token del header
$token = $tm->getAuthorizationHeader();
if (!$token) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No token']);
    exit;
}

// Validar (opcionalmente con fingerprint)
$fingerprint = $tm->generateDeviceFingerprint();
$tokenData = $tm->validateToken(
    $token, 
    TokenManager::TYPE_SESSION, 
    $fingerprint
);

if (!$tokenData) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token inválido']);
    exit;
}

// Token válido, proceder
$userId = $tokenData['user_id'];
$userType = $tokenData['user_tipo'];
```

### Ejemplo Endpoint Protegido (`EjemploEndpointProtegido.php`)
Archivo de ejemplo listo para usar que implementa GET y POST protegidos.

---

## Ejemplos de Uso

### Ejemplo Axios (Frontend)
```javascript
import api from '@/services/http';

// GET - token auto-incluido
const clubs = await api.get('/Clubs.php');

// POST - token + CSRF auto-incluidos
const newClub = await api.post('/Clubs.php', {
    nombre: 'Club de Robótica',
    tipo: 'CULTURAL',
    descripcion: 'Robótica y programación',
    cupo_limite: 30
});

// PUT
await api.put(`/Clubs.php?id=${clubId}`, {
    nombre: 'Nuevo nombre'
});

// DELETE
await api.delete(`/Clubs.php?id=${clubId}`);
```

### Ejemplo Fetch (api.js)
```javascript
// Las funciones de src/services/api.js ya están actualizadas
// getClubs(), createClub(), etc. añaden el token automáticamente

import { getClubs, createClub } from '@/services/api';

const clubs = await getClubs();  // Token incluido

// Interceptor: si el token está expirado, intenta renovar
// Si no puede renovar, limpia auth y redirige
```

### Ejemplo Manejo de Errores
```javascript
try {
    const data = await api.get('/EndpointProtegido.php');
} catch (error) {
    if (error.response?.status === 401) {
        // Token inválido/expirado - el interceptor ya intentó renovar
        // El interceptor ya redirigió a /
        console.log('Sesión expirada');
    } else if (error.response?.status === 403) {
        // Permisos insuficientes
        console.log('No tienes permiso');
    } else {
        console.error('Error:', error.message);
    }
}
```

### Ejemplo Gestión de Sesiones
```vue
<template>
  <div>
    <h3>Sesiones Activas</h3>
    <div v-for="session in sessions" :key="session.id">
      <p>{{ session.device.browser }} en {{ session.device.os }}</p>
      <p>IP: {{ session.ip }}</p>
      <p>Expira: {{ session.expires_at }}</p>
      <button 
        v-if="!session.is_current" 
        @click="revokeSession(session.id)"
      >
        Cerrar esta sesión
      </button>
      <span v-else class="text-primary">(Sesión actual)</span>
    </div>
    <button @click="revokeOthers">Cerrar otras sesiones</button>
  </div>
</template>

<script>
import { authService } from '@/services/auth';

export default {
  data() {
    return { sessions: [] };
  },
  async mounted() {
    this.sessions = await authService.getActiveSessions();
  },
  methods: {
    async revokeSession(id) {
      await authService.revokeSession(id);
      this.sessions = await authService.getActiveSessions();
    },
    async revokeOthers() {
      await authService.revokeOtherSessions();
      this.sessions = await authService.getActiveSessions();
    }
  }
};
</script>
```

---

## Limpieza de Tokens

### Opción 1: MySQL Event (Recomendado)
```sql
-- Verificar si event_scheduler está ON
SHOW VARIABLES LIKE 'event_scheduler';

-- Habilitar si es OFF
SET GLOBAL event_scheduler = ON;

-- Crear evento
DELIMITER //
CREATE EVENT IF NOT EXISTS limpiar_tokens_expirados
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DELETE FROM tokens 
    WHERE expires_at < NOW() 
       OR activo = 0;
END //
DELIMITER ;

-- Verificar eventos
SHOW EVENTS;
```

### Opción 2: Cron Job (Linux/macOS)
```bash
# Editar crontab
crontab -e

# Añadir línea para ejecutar cada hora
0 * * * * /usr/bin/php /var/www/html/Backend/CleanupTokens.php
```

### Opción 3: Tarea Programada (Windows)
```powershell
# Crear tarea programada con PowerShell
$action = New-ScheduledTaskAction -Execute 'php.exe' -Argument 'C:\xampp\htdocs\Club-Systems_Nube\Backend\CleanupTokens.php'
$trigger = New-ScheduledTaskTrigger -Daily -At '00:00' -RepetitionInterval (New-TimeSpan -Hours 1)
Register-ScheduledTask -TaskName 'Limpiar Tokens' -Action $action -Trigger $trigger
```

---

## Actualización de Tabla Existente

Si la tabla `tokens` ya existe pero le faltan columnas:

```sql
-- Añadir selector (si no existe)
ALTER TABLE tokens ADD COLUMN selector VARCHAR(32) NULL AFTER token;
ALTER TABLE tokens ADD INDEX idx_selector (selector);

-- IMPORTANTE: Si hay datos existentes, generar selectores:
-- UPDATE tokens SET selector = LEFT(token, 32) WHERE selector IS NULL;
-- Nota: esto es solo un ejemplo, los tokens existentes no serán válidos

-- Añadir ip (si no existe)
ALTER TABLE tokens ADD COLUMN ip VARCHAR(45) NULL AFTER activo;

-- Añadir user_agent (si no existe)
ALTER TABLE tokens ADD COLUMN user_agent VARCHAR(512) NULL AFTER ip;

-- Añadir fingerprint (si no existe)
ALTER TABLE tokens ADD COLUMN fingerprint VARCHAR(64) NULL AFTER user_agent;

-- Añadir FK (si no existe)
ALTER TABLE tokens ADD CONSTRAINT fk_tokens_usuario 
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE;
```

---

## Configuración de Headers CORS

El archivo `cors.php` ya está configurado para aceptar el header `Authorization`:
```php
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
```

### Headers Enviados por el Frontend
```
Authorization: Bearer <selector.token_plano>
X-CSRF-Token: <csrf_token> (para POST/PUT/DELETE)
```

### Headers Devueltos por el Backend
```
X-Token-Refreshed: <nuevo_token> (cuando el token se renueva)
```

---

## Flujo Completo: Login a Dashboard

### 1. Usuario en Login.vue
```
Usuario: ADM00001
Password: *******
Tipo: Oficina
[Recordarme: ✓]
[Entrar]
```

### 2. authService.login()
```javascript
POST /Backend/Login.php
Body: {
  usuario: "ADM00001",
  password: "Admin123!",
  userType: "oficina",
  remember_me: true
}
```

### 3. Backend Login.php
```php
1. Verificar rate limit (5 intentos/5min)
2. Validar formato de usuario/contraseña
3. Buscar usuario: SELECT * FROM usuarios WHERE usuario=?
4. password_verify($password, $hash)
5. Verificar userType coincide
6. enforceMaximumSessions(5) - cierra más antigua si >5
7. createToken(SESSION) + createToken(REMEMBER) [por remember_me]
8. createCsrfToken()
9. Devolver:
   {
     status: "success",
     token: "selector.sesion_token_plano",
     remember_token: "selector.remember_token_plano",
     csrf_token: "token_csrf",
     id, nombre, tipo, ...
   }
```

### 4. Frontend - Guardar en Storage
```javascript
sessionStorage: {
  auth_token: "selector.sesion_token_plano",
  csrf_token: "token_csrf",
  auth_user_data: "{id:1,nombre:'Admin',tipo:'OFICINA'}",
  usuarioId: "1",
  token_expires_at: "2026-05-21 16:00:00"
}

localStorage: {
  auth_refresh_token: "selector.remember_token_plano"
}
```

### 5. Router - Redirigir a /oficina
```javascript
// Navigation guard detecta:
// - requiresAuth: true
// - role: 'OFICINA'
// - authService.isAuthenticated(): true
// - userData.tipo: 'OFICINA' === requiredRole

// ✅ Permitir acceso
```

### 6. Oficina.vue - mounted()
```javascript
// Antes (inseguro):
const usuarioId = sessionStorage.getItem("usuarioId");
// ❌ Cualquier valor en sessionStorage otorgaba acceso

// Ahora (seguro):
// 1. Navigation guard ya validó el token
// 2. Cualquier llamada a la API pasa por http.js interceptor
// 3. Interceptor añade: Authorization: Bearer <token>
// 4. Backend valida el token en cada llamada

// Cuando se llama a:
await getClubs();  // fetch() en api.js con token

// O:
await axios.get(...)  // http.js axios interceptor con token
```

### 7. Cierre de Sesión
```javascript
// Botón "Cerrar sesión" llama a:
authService.logout();

// Que hace:
POST /Backend/Logout.php
Header: Authorization: Bearer <token>
Body: { mode: 'current' }

// Backend:
UPDATE tokens SET activo=0 WHERE selector=? AND token_hash=?

// Frontend:
authService.clearAuth();  // Borra todo de storage
router.push('/');  // Redirige a login
```

---

## Resumen de Seguridad

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| **Validación servidor** | ❌ Ninguna | ✅ Cada llamada valida token |
| **Token almacenado** | ❌ Plano en sessionStorage | ✅ Hash SHA-256 en BD |
| **Expiración** | ❌ Nunca expira | ✅ 2 horas (SESSION) |
| **Invalidación** | ❌ Solo clearStorage | ✅ activo=0 en BD (inmediato) |
| **Sesiones múltiples** | ❌ Sin límite | ✅ Máximo 5 |
| **Dispositivo** | ❌ Sin verificación | ✅ Fingerprint hash |
| **Timing attacks** | ❌ Vulnerable | ✅ hash_equals() |
| **Rate limiting** | ⚠️ Session-based | ✅ Session-based (aún mejorable) |
| **CSRF** | ❌ Sin protección | ✅ Token CSRF |

### Diferencias Clave

**Antes:**
```
// Cualquier usuario podía acceder haciendo:
sessionStorage.setItem("usuarioId", "1");  // ¡Acceso de Admin!
// El servidor NUNCA validaba esto
```

**Ahora:**
```
// El token es:
// - Aleatorio (no se puede adivinar)
// - Hasheado en BD (no se puede usar si BD se compromete)
// - Con expiración (ventana de ataque limitada)
// - Revocable instantáneamente
// - Ligado a dispositivo/fingerprint
```

---

## Próximos Pasos Recomendados

1. **Mover credenciales de DB a .env**
   - Actualmente en `db.php` están hardcodeadas
   - Usar vlucas/phpdotenv o similar

2. **Implementar HTTPS**
   - Tokens via HTTP son vulnerables a sniffing
   - Configurar XAMPP con SSL

3. **Rate Limiting en BD**
   - Actual rate limiting usa `$_SESSION`
   - Implementar en tabla `login_attempts` para persistencia

4. **Auditoría de Login**
   - Registrar cada intento de login (IP, User-Agent, éxito/fallo)

5. **Notificaciones de Nueva Sesión**
   - Email/push cuando se inicie sesión desde nuevo dispositivo

6. **Rotación de Secretos**
   - Implementar rotación periódica de la clave HMAC en `.env.secret`

---

## Archivos Nuevos/Modificados

### Backend (Backend/)
| Archivo | Estado | Propósito |
|---------|--------|-----------|
| `TokenManager.php` | ✅ Nuevo | Gestión completa de tokens |
| `AuthMiddleware.php` | ✅ Nuevo | Middleware para proteger endpoints |
| `Login.php` | ✅ Modificado | Devuelve tokens en lugar de solo datos |
| `Logout.php` | ✅ Nuevo | Revoca tokens del lado del servidor |
| `RefreshToken.php` | ✅ Nuevo | Renueva tokens expirados |
| `Me.php` | ✅ Nuevo | Obtiene usuario actual (valida token) |
| `Sessions.php` | ✅ Nuevo | Gestión de múltiples sesiones |
| `CleanupTokens.php` | ✅ Nuevo | Limpia tokens expirados |
| `EjemploEndpointProtegido.php` | ✅ Nuevo | Ejemplo de endpoint protegido |

### Frontend (src/)
| Archivo | Estado | Propósito |
|---------|--------|-----------|
| `services/auth.js` | ✅ Nuevo | Servicio de autenticación |
| `services/http.js` | ✅ Nuevo | Axios con interceptors |
| `services/api.js` | ✅ Modificado | Fetch con token auto-incluido |
| `main.js` | ✅ Modificado | Router guards de autenticación |
| `Pages/Login.vue` | ✅ Modificado | Usa authService.login() |
| `Pages/Oficina.vue` | ✅ Modificado | Usa authService.logout() |
| `components/Monitor.vue` | ✅ Modificado | Usa authService.logout() |

### SQL
| Archivo | Estado | Propósito |
|---------|--------|-----------|
| `sql/tokens.sql` | ✅ Nuevo | Estructura y ejemplos de la tabla |

---

## Para Verificar la Instalación

1. **Verificar tabla `tokens` existe**
```sql
DESCRIBE tokens;
SHOW INDEX FROM tokens;
```

2. **Probar login**
```javascript
// En consola del navegador
import { authService } from '@/services/auth';
await authService.login({
  usuario: 'ADM00001',
  password: 'password',  // La contraseña real
  userType: 'oficina'
});
```

3. **Verificar token guardado**
```javascript
sessionStorage.getItem('auth_token');
sessionStorage.getItem('auth_user_data');
```

4. **Probar endpoint protegido**
```javascript
import api from '@/services/http';
await api.get('/EjemploEndpointProtegido.php');
// Debería devolver datos del usuario actual
```

5. **Probar logout**
```javascript
await authService.logout();
// Verificar:
// - sessionStorage esté vacío
// - Router redirija a /
// - Token invalidado en BD (activo=0)
```
