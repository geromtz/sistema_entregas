<?php
require_once __DIR__ . '/../../config/db.php';

class OtherTrip
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Crear nueva actividad
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO other_trips (id_user, title, description)
                VALUES (:id_driver, :title, :description)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_driver', $data['id_driver'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
 * Obtener las actividades del usuario actual
 */
public function getAll(int $userId): array
{
    $sql = "
        SELECT 
            ot.*,
            CONCAT(u.firstname, ' ', u.apellidos) AS driver_name
        FROM other_trips ot
        LEFT JOIN users u ON ot.id_user = u.id
        WHERE ot.id_user = :user_id
        ORDER BY ot.created_at DESC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    /**
     * 🔹 Chofer actualiza estado (marca recolección)
     */
    public function updateStatus(array $data): bool
    {
        $sql = "UPDATE other_trips
                SET status = :status,
                    collected_by = :collected_by,
                    collected_at = NOW(),
                    driver_comment = :driver_comment
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $stmt->bindValue(':collected_by', $data['collected_by'], PDO::PARAM_INT);
        $stmt->bindValue(':driver_comment', $data['driver_comment'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * 🔹 Usuario actualiza información (corrige título o descripción)
     */
    public function update(array $data): bool
    {
        $sql = "UPDATE other_trips
                SET title = :title,
                    description = :description,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        return $stmt->execute();
    }
}
