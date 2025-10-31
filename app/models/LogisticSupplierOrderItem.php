<?php
// app/models/LogisticSupplierOrderItem.php
require_once __DIR__ . '/../../config/db.php';

class LogisticSupplierOrderItem
{
    private PDO $db;

    public function __construct($db = null)
    {
        if ($db instanceof PDO) {
            $this->db = $db;
        } else {
            $database = new Database();
            /** @var PDO */
            $this->db = $database->getConnection();
        }
    }

    /**
     * 🔹 Trae todas las partidas logísticas asociadas a un pedido.
     */
    public function getBySupplierOrder(int $supplierOrderId): array
    {
        $sql = "
            SELECT 
                lsi.id,
                lsi.item_number,
                lsi.description_product,
                lsi.ordered_quantity,
                lsi.received_quantity,
                lsi.difference_quantity,
                lsi.status,
                lsi.incident,
                lsi.created_at,
                lsi.last_update
            FROM logistic_supplier_order_items AS lsi
            INNER JOIN logistic_supplier_orders AS lso 
                ON lso.id = lsi.id_logistic_supplier_order
            WHERE lso.id_supplier_order = :supplierOrderId
            ORDER BY lsi.item_number ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':supplierOrderId' => $supplierOrderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
