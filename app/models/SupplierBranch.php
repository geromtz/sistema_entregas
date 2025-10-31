<?php
require_once __DIR__ . '/../../config/db.php';

class SupplierBranch
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * 🔹 Obtener sucursales por proveedor (filtrado por término opcional)
     */
    public function getBranchesBySupplier(int $supplierId, string $term = ''): array
    {
        $sql = "
            SELECT id, branch
            FROM supplier_adress
            WHERE id_supplier = :id_supplier
              AND branch LIKE :term
            ORDER BY branch ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_supplier' => $supplierId,
            ':term'        => "%{$term}%",
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 🔹 Crear una nueva sucursal (si no existe)
     */
    public function createBranch(int $supplierId, string $branchName): int
    {
        // Evitar duplicados
        $check = $this->db->prepare("
            SELECT id 
            FROM supplier_adress 
            WHERE id_supplier = ? AND branch = ?
        ");
        $check->execute([$supplierId, $branchName]);

        if ($check->fetch()) {
            throw new Exception("La sucursal '{$branchName}' ya existe para este proveedor.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO supplier_adress (id_supplier, branch) 
            VALUES (:id_supplier, :branch)
        ");
        $stmt->execute([
            ':id_supplier' => $supplierId,
            ':branch'      => $branchName,
        ]);

        return (int)$this->db->lastInsertId();
    }
}
