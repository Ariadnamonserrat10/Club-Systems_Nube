<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";
require_once __DIR__ . "/AuthMiddleware.php";

$auth = new AuthMiddleware();
$authResult = $auth->requireAnyAuthenticated();

$user = $authResult['user'];

$tokenManager = new TokenManager();
$activeSessions = $tokenManager->getUserActiveSessions($user['id']);

$stmt = $conexion->prepare("
    SELECT u.id, u.nombre, u.apellidoP, u.apellidoM, u.numeroControl, u.telefono, 
           u.carrera_id, u.semestre_id, u.usuario, u.tipo, u.club_asignado, u.foto,
           c.nombre as club_nombre
    FROM usuarios u
    LEFT JOIN clubs c ON u.club_asignado = c.id
    WHERE u.id = ?
");

$response = [
    'id' => (int)$user['id'],
    'nombre' => $user['nombre'],
    'apellidoP' => $user['apellidoP'],
    'apellidoM' => $user['apellidoM'],
    'tipo' => $user['tipo'],
    'foto' => $user['foto'],
    'club_asignado' => $user['club_asignado'],
    'active_sessions_count' => count($activeSessions)
];

if ($stmt) {
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $dbUser = $result->fetch_assoc();
        $response = array_merge($response, [
            'numeroControl' => $dbUser['numeroControl'],
            'telefono' => $dbUser['telefono'],
            'carrera_id' => $dbUser['carrera_id'] !== null ? (int)$dbUser['carrera_id'] : null,
            'semestre_id' => $dbUser['semestre_id'] !== null ? (int)$dbUser['semestre_id'] : null,
            'club_nombre' => $dbUser['club_nombre']
        ]);
    }
    $stmt->close();
}

$auth->successResponse($response);
?>
