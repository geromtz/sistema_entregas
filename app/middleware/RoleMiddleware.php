<?php

class RoleMiddleware
{
    /**
     * Verifica si el usuario tiene alguno de los roles permitidos.
     *
     * @param array|int $allowedRoles  ID o lista de IDs de posiciones permitidas.
     * @param bool $isApi              Si es true, responde en JSON en lugar de HTML.
     */
    public static function requireRole($allowedRoles, bool $isApi = false)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // 🔒 Si no hay sesión
        if (!isset($_SESSION['user'])) {
            if ($isApi) {
                http_response_code(401);
                echo json_encode(['error' => 'No autenticado']);
                exit;
            }
            header('Location: index.php?page=login');
            exit;
        }

        $userRole = $_SESSION['user']['id_position'] ?? null;
        $allowedRoles = (array) $allowedRoles;

        // 🚫 Si el usuario no tiene el rol requerido
        if (!in_array($userRole, $allowedRoles)) {
            if ($isApi) {
                http_response_code(403);
                echo json_encode(['error' => 'Acceso restringido']);
                exit;
            }

            echo "<div class='alert alert-danger mt-4 mx-3'>
                    <h5>🚫 Acceso denegado</h5>
                    <p>No tienes permisos para acceder a esta sección.</p>
                  </div>";
            exit;
        }
    }
}
