<?php
require_once __DIR__ . '/../../config/db.php';

class TrackingSupplierOrder
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }

    /**
     * 🟢 Crear registro de seguimiento (tracking) para un ítem proveedor.
     *
     * $data = [
     *   'id_item_supplier_order' => int,
     *   'tracking_number'        => string|null,
     *   'tracking_company'       => string|null,
     *   'id_branch'              => int|null,
     *   'arrival_date'           => string|null (YYYY-MM-DD)
     * ]
     */
    public function createTracking(array $data): bool
    {
        $sql = "INSERT INTO tracking_supplier_orders
                (id_item_supplier_order, tracking_number, tracking_company, id_branch, arrival_date)
                VALUES (:id_item_supplier_order, :tracking_number, :tracking_company, :id_branch, :arrival_date)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_item_supplier_order' => $data['id_item_supplier_order'],
            ':tracking_number'        => $data['tracking_number'] ?? null,
            ':tracking_company'       => $data['tracking_company'] ?? null,
            ':id_branch'              => $data['id_branch'] ?? null, // 👈 Cambiado de origin_branch a id_branch
            ':arrival_date'           => $data['arrival_date'] ?? null,
        ]);
    }

    /**
     * 🔍 Obtener seguimiento por ID de ítem proveedor.
     */
    public function getTrackingByItem(int $itemId): ?array
{
    $sql = "
        SELECT 
            t.*, 
            sa.branch AS branch_name
        FROM tracking_supplier_orders AS t
        LEFT JOIN supplier_adress AS sa ON sa.id = t.id_branch
        WHERE t.id_item_supplier_order = ?
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$itemId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

    /**
     * 🔄 Actualizar información de seguimiento.
     *
     * $data = [
     *   'id_item_supplier_order' => int,
     *   'tracking_number'        => string|null,
     *   'tracking_company'       => string|null,
     *   'id_branch'              => int|null,
     *   'arrival_date'           => string|null
     * ]
     */
    public function updateTracking(array $data): bool
    {
        $sql = "UPDATE tracking_supplier_orders
                SET tracking_number = :tracking_number,
                    tracking_company = :tracking_company,
                    id_branch = :id_branch,
                    arrival_date = :arrival_date
                WHERE id_item_supplier_order = :id_item_supplier_order";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':tracking_number'        => $data['tracking_number'] ?? null,
            ':tracking_company'       => $data['tracking_company'] ?? null,
            ':id_branch'              => $data['id_branch'] ?? null, // 👈 Cambiado de origin_branch a id_branch
            ':arrival_date'           => $data['arrival_date'] ?? null,
            ':id_item_supplier_order' => $data['id_item_supplier_order'],
        ]);
    }
}