<?php
require_once __DIR__ . '/../../config/db.php';

class Prospect
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Obtener conexión PDO externa si se requiere (ej. Auditoría)
     */
    public function getConnection()
    {
        return $this->db;
    }

    /**
     * Obtener prospectos por usuarios del equipo con su última interacción
     */
    public function getProspectsByUsers(array $userIds): array
    {
        if (empty($userIds)) return [];

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        $sql = "
            SELECT p.*, u.firstname, u.apellidos,
                   (SELECT COUNT(*) FROM prospect_interactions pi WHERE pi.prospect_id = p.id) AS total_interactions
            FROM prospects p
            LEFT JOIN users u ON u.id = p.prospect_owner
            WHERE p.prospect_owner IN ($placeholders)
            ORDER BY p.created_at DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($userIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching prospects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un prospecto por su ID
     */
    public function getProspectById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM prospects WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching prospect by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar un prospecto existente
     */
    public function updateProspect(int $id, array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE prospects 
                SET company_name = :company, 
                    contact_name = :contact, 
                    phone = :phone, 
                    email = :email, 
                    stage = :stage
                WHERE id = :id
            ");
            return $stmt->execute([
                ':company' => $data['company_name'],
                ':contact' => $data['contact_name'] ?? null,
                ':phone'   => $data['phone'] ?? null,
                ':email'   => $data['email'] ?? null,
                ':stage'   => $data['stage'] ?? 'Nuevo',
                ':id'      => $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating prospect: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener el historial de interacciones de un prospecto específico
     */
    public function getInteractionsByProspect(int $prospectId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pi.*, u.firstname, u.apellidos 
                FROM prospect_interactions pi
                LEFT JOIN users u ON u.id = pi.user_id
                WHERE pi.prospect_id = :prospectId
                ORDER BY pi.created_at DESC
            ");
            $stmt->execute([':prospectId' => $prospectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching interactions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener una interacción específica por su ID
     */
    public function getInteractionById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM prospect_interactions WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching interaction by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registrar una nueva interacción en la bitácora
     */
    public function addInteraction(array $data, int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO prospect_interactions 
                (prospect_id, user_id, interaction_type, result_status, next_action_date, comments)
                VALUES (:prospectId, :userId, :type, :status, :nextDate, :comments)
            ");
            $stmt->execute([
                ':prospectId' => $data['prospect_id'],
                ':userId'     => $userId,
                ':type'       => $data['interaction_type'],
                ':status'     => $data['result_status'],
                ':nextDate'   => !empty($data['next_action_date']) ? $data['next_action_date'] : null,
                ':comments'   => $data['comments'] ?? null
            ]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()];
        }
    }

    /**
     * Actualizar una interacción existente (corrección de errores)
     */
    public function updateInteraction(int $id, array $data): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE prospect_interactions 
                SET interaction_type = :type, 
                    result_status = :status, 
                    next_action_date = :nextDate, 
                    comments = :comments
                WHERE id = :id
            ");
            $stmt->execute([
                ':type'     => $data['interaction_type'],
                ':status'   => $data['result_status'],
                ':nextDate' => !empty($data['next_action_date']) ? $data['next_action_date'] : null,
                ':comments' => $data['comments'] ?? null,
                ':id'       => $id
            ]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()];
        }
    }

    /**
     * Crear un nuevo prospecto asignando dueño y registrador
     */
    public function createProspect(array $data, int $ownerId, int $registeredById): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO prospects (company_name, contact_name, phone, email, stage, prospect_owner, registered_by, created_at)
                VALUES (:company, :contact, :phone, :email, :stage, :ownerId, :registeredById, NOW())
            ");
            return $stmt->execute([
                ':company'        => $data['company_name'],
                ':contact'        => $data['contact_name'] ?? null,
                ':phone'          => $data['phone'] ?? null,
                ':email'          => $data['email'] ?? null,
                ':stage'          => $data['stage'] ?? 'Nuevo',
                ':ownerId'        => $ownerId,
                ':registeredById' => $registeredById
            ]);
        } catch (PDOException $e) {
            error_log("Error creating prospect: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar si ya existe un prospecto con el mismo nombre de empresa
     */
    public function findDuplicateCompany(string $companyName): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.firstname, u.apellidos 
                FROM prospects p
                LEFT JOIN users u ON u.id = p.prospect_owner
                WHERE LOWER(TRIM(p.company_name)) = LOWER(TRIM(:company))
                LIMIT 1
            ");
            $stmt->execute([':company' => $companyName]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error checking duplicate company: " . $e->getMessage());
            return null;
        }
    }
}