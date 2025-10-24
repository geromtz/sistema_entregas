<?php
require_once __DIR__ . '/../../config/db.php';

class Supplier
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        // ✅ Crear instancia de Database y obtener conexión PDO
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * 🔹 Permite obtener la conexión PDO fuera del modelo
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }

    /**
     * Devuelve todos los proveedores o filtra por nombre (para autocompletar)
     * 
     * @param string|null $term texto a buscar (opcional)
     * @return array lista de proveedores
     */
    public function getSuppliers(?string $term = null): array
    {
        if ($term) {
            $sql = "
                SELECT id, company_name, business_name
                FROM suppliers
                WHERE business_name LIKE :term OR company_name LIKE :term
                ORDER BY business_name ASC
                LIMIT 15
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':term', "%{$term}%");
        } else {
            $sql = "
                SELECT id, company_name, business_name
                FROM suppliers
                ORDER BY business_name ASC
                LIMIT 15
            ";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo proveedor (mayorista)
     * 
     * @param string $businessName nombre comercial
     * @param string $companyName razón social
     * @param int $userId usuario creador
     * @return int|null ID insertado o null si falla
     */
    public function createSupplier(string $businessName, string $companyName, int $userId): ?int
    {
        try {
            // Verificar si ya existe uno con el mismo nombre comercial
            $stmt = $this->db->prepare("
                SELECT id FROM suppliers 
                WHERE TRIM(LOWER(business_name)) = TRIM(LOWER(:business_name))
                LIMIT 1
            ");
            $stmt->execute([':business_name' => $businessName]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                return (int)$existingId;
            }

            // Crear nuevo proveedor
            $stmt = $this->db->prepare("
                INSERT INTO suppliers (business_name, company_name, created_by, created_at)
                VALUES (:business_name, :company_name, :created_by, NOW())
            ");
            $stmt->execute([
                ':business_name' => $businessName,
                ':company_name' => $companyName,
                ':created_by' => $userId
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error al crear proveedor: " . $e->getMessage());
        }
    }
}
