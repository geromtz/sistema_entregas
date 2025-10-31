<?php

class RoleMiddleware
{
    /**
     * Verifica si el usuario tiene un rol o ID de usuario permitido.
     *
     * @param array|int $allowedRoles  ID o lista de IDs de posiciones permitidas.
     * @param bool $isApi              Si es true, responde en JSON.
     * @param array $allowedUsers      IDs de usuario específicos con acceso (opcional).
     */
    public static function requireRole($allowedRoles, bool $isApi = false, bool $showMessage = true, array $allowedUsers = [])
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    $user = $_SESSION['user'] ?? null;
    $userId = $user['id'] ?? 'SIN_SESION';
    $userRole = $user['id_position'] ?? 'SIN_ROL';
    $methodTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $methodTrace[1]['function'] ?? 'desconocido';
    $controller = $methodTrace[1]['class'] ?? '???';

    // 🧠 Log detallado para identificar quién bloquea
    error_log("🧩 MIDDLEWARE DEBUG => método: {$controller}::{$caller} | user_id={$userId} | role={$userRole}");

    // 🔒 Sin sesión
    if (!$user) {
        error_log("🚫 Bloqueado: sin sesión ({$controller}::{$caller})");
        if ($isApi) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }
        header('Location: index.php?page=login');
        exit;
    }

    $allowedRoles = (array) $allowedRoles;

    // ✅ Usuario específico permitido
    if (in_array($userId, $allowedUsers)) {
        error_log("✅ Permiso por usuario ({$userId}) en {$controller}::{$caller}");
        return;
    }

    // ✅ Rol permitido
    if (in_array($userRole, $allowedRoles)) {
        error_log("✅ Permiso por rol ({$userRole}) en {$controller}::{$caller}");
        return;
    }

    // 🚫 Bloqueado
    error_log("🚫 ACCESO RESTRINGIDO: user_id={$userId}, rol={$userRole}, método={$controller}::{$caller}");

    if ($isApi) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso restringido', 'from' => "{$controller}::{$caller}", 'role' => $userRole]);
        exit;
    }

    if ($showMessage) {
        echo "<div class='alert alert-danger mt-4 mx-3'>
                <h5>🚫 Acceso denegado</h5>
                <p>No tienes permisos para acceder a esta sección ({$controller}::{$caller}).</p>
              </div>";
    }
    exit;
}

}
