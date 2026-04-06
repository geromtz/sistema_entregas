<?php
require_once __DIR__ . '/../../config/db.php';

class User {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Validar login
   public function validateUser($username, $password) {
    try {
        $md5pass = md5($password);
        $stmt = $this->db->prepare("
            SELECT u.id, u.user, u.firstname, u.apellidos, u.email,
                   u.id_position, u.pass, u.is_leader, p.department_id  -- <-- Agregado u.is_leader
            FROM users u
            LEFT JOIN positions p ON u.id_position = p.id
            WHERE u.user = :user AND u.pass = :pass
            LIMIT 1
        ");
        $stmt->execute([
            ':user' => $username,
            ':pass' => $md5pass
        ]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) return false;
        
        unset($userData['pass']);
        return $userData;
    } catch (PDOException $e) {
        error_log("Error validating user: " . $e->getMessage());
        return false;
    }
}

    // Obtener IDs de usuario según jerarquía
    public function getTeamUserIds(int $userId): array {
        $user = $this->getUserById($userId);
        if (!$user) return [$userId];

        // CEO/Admin ve todos los usuarios
        if (in_array($user['id_position'], [1,7])) {
            $stmt = $this->db->query("SELECT id FROM users");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Usuario normal / líder
        $ids = [$userId]; // siempre incluir al propio usuario

        // Subordinados directos e indirectos
        try {
            $stmt = $this->db->prepare("
                WITH RECURSIVE team AS (
                    SELECT id FROM users WHERE parent_id = :userId
                    UNION ALL
                    SELECT u.id FROM users u INNER JOIN team t ON u.parent_id = t.id
                )
                SELECT id FROM team
            ");
            $stmt->execute([':userId' => $userId]);
            $subordinates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($subordinates) {
                $ids = array_merge($ids, $subordinates);
            }
        } catch (PDOException $e) {
            error_log("Error fetching subordinates: " . $e->getMessage());
        }

        // Incluir líder si existe
        if ($user['parent_id']) {
            $ids[] = $user['parent_id'];
        }

        return array_unique($ids);
    }

    // Obtener datos de un usuario
    public function getUserById(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT id, id_position, parent_id FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    // Obtener líder directo
    public function getParentId(int $userId) {
        try {
            $stmt = $this->db->prepare("SELECT parent_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching parent_id: " . $e->getMessage());
            return null;
        }
    }


}
