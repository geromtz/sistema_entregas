<?php
require_once __DIR__ . '/../../config/db.php';

class AuditModel
{
    private $db;

    public function __construct($dbConnection = null)
    {
        if ($dbConnection) {
            $this->db = $dbConnection;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }

    public function log(int $userId, string $actionType, string $tableAffected, int $recordId, $oldData = null, $newData = null): bool
    {
        $sql = "INSERT INTO audit_logs (user_id, action_type, table_affected, record_id, old_data, new_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $actionType,
            $tableAffected,
            $recordId,
            is_array($oldData) ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : $oldData,
            is_array($newData) ? json_encode($newData, JSON_UNESCAPED_UNICODE) : $newData
        ]);
    }

    public function getLogs($limit = 100)
    {
        try {
            // 1. Obtener los logs de auditoría con el usuario que realizó la acción
            $stmt = $this->db->prepare("
                SELECT a.*, u.user as username 
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                ORDER BY a.created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Diccionario de productos
            $prodStmt = $this->db->query("SELECT id, part_number, description FROM products");
            $productsMap = [];
            while ($p = $prodStmt->fetch(PDO::FETCH_ASSOC)) {
                $productsMap[$p['id']] = "[" . $p['id'] . "] " . $p['part_number'] . " - " . $p['description'];
            }

            // 3. Diccionario de usuarios (para choferes o asignaciones)
            $userStmt = $this->db->query("SELECT id, firstname, apellidos FROM users");
            $usersMap = [];
            while ($u = $userStmt->fetch(PDO::FETCH_ASSOC)) {
                $fullName = trim($u['firstname'] . ' ' . $u['apellidos']);
                $usersMap[$u['id']] = "[" . $u['id'] . "] " . ($fullName !== '' ? $fullName : 'Usuario #' . $u['id']);
            }

            // 4. Inyectar mapas en cada log
            foreach ($logs as &$log) {
                $log['products_map'] = $productsMap;
                $log['users_map'] = $usersMap;
            }

            return $logs;
        } catch (PDOException $e) {
            return [];
        }
    }
}