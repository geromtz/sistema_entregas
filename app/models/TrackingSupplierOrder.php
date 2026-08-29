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
     *   'origin_branch'          => string|null,
     *   'arrival_date'           => string|null (YYYY-MM-DD)
     * ]
     */
    public function createTracking(array $data): bool
    {
        $sql = "INSERT INTO tracking_supplier_orders
                (id_item_supplier_order, tracking_number, tracking_company, origin_branch, arrival_date)
                VALUES (:id_item_supplier_order, :tracking_number, :tracking_company, :origin_branch, :arrival_date)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_item_supplier_order' => $data['id_item_supplier_order'],
            ':tracking_number'        => $data['tracking_number'] ?? null,
            ':tracking_company'       => $data['tracking_company'] ?? null,
            ':origin_branch'          => $data['origin_branch'] ?? null,
            ':arrival_date'           => $data['arrival_date'] ?? null,
        ]);
    }

    /**
     * 🔍 Obtener seguimiento por ID de ítem proveedor.
     */
    public function getTrackingByItem(int $itemId): ?array
    {
        $sql = "SELECT id, id_item_supplier_order, tracking_number, tracking_company, origin_branch, arrival_date
                FROM tracking_supplier_orders
                WHERE id_item_supplier_order = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * 🔄 Actualizar información de seguimiento.
     *
     * $data = [
     *   'id_item_supplier_order' => int,
     *   'tracking_number'        => string|null,
     *   'tracking_company'       => string|null,
     *   'origin_branch'          => string|null,
     *   'arrival_date'           => string|null
     * ]
     */
    public function updateTracking(array $data): bool
    {
        $sql = "UPDATE tracking_supplier_orders
                SET tracking_number = :tracking_number,
                    tracking_company = :tracking_company,
                    origin_branch = :origin_branch,
                    arrival_date = :arrival_date
                WHERE id_item_supplier_order = :id_item_supplier_order";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':tracking_number'        => $data['tracking_number'] ?? null,
            ':tracking_company'       => $data['tracking_company'] ?? null,
            ':origin_branch'          => $data['origin_branch'] ?? null,
            ':arrival_date'           => $data['arrival_date'] ?? null,
            ':id_item_supplier_order' => $data['id_item_supplier_order'],
        ]);
    }
}
