<?php
require_once __DIR__ . '/../../config/db.php';

class Customer
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Buscar clientes por business_name o company_name y por vendedor (created_by)
     */
    public function searchCustomers(string $term, int $userId): array
    {
        if (!$term || !$userId) return [];

        try {
            $stmt = $this->db->prepare("
            SELECT id, business_name, company_name
            FROM customers
            WHERE (business_name LIKE :term OR company_name LIKE :term)
              AND created_by = :userId
            ORDER BY business_name ASC
            LIMIT 10
        ");
            $stmt->execute([
                ':term' => "%$term%",
                ':userId' => $userId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching customers: " . $e->getMessage());
            return [];
        }
    }

   /**
 * Crear un cliente nuevo.
 * 
 * @param string $businessName
 * @param string $companyName
 * @param int    $userId
 * @return int|null  ID del cliente creado o existente
 * @throws Exception Si ocurre un error al crear
 */
public function createCustomer(string $businessName, string $companyName, int $userId): ?int
{
    try {
        // 1️⃣ Verificar si ya existe un cliente con el mismo nombre
        $stmt = $this->db->prepare("
            SELECT id FROM customers 
            WHERE TRIM(LOWER(business_name)) = TRIM(LOWER(:business_name))
            LIMIT 1
        ");
        $stmt->execute([':business_name' => $businessName]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            // ⚠️ Ya existe → devolver su ID
            return (int)$existingId;
        }

        // 2️⃣ Si no existe, crear nuevo
        $stmt = $this->db->prepare("
            INSERT INTO customers (business_name, company_name, created_by, created_at)
            VALUES (:business_name, :company_name, :created_by, NOW())
        ");
        $stmt->execute([
            ':business_name' => $businessName,
            ':company_name' => $companyName,
            ':created_by' => $userId
        ]);

        return (int)$this->db->lastInsertId();
    } catch (PDOException $e) {
        throw new Exception("Error al crear cliente: " . $e->getMessage());
    }
}



}
