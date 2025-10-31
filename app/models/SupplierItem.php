<?php
require_once __DIR__ . '/../../config/db.php';

class SupplierItem
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
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
     * Crea una partida (ítem) dentro de un supplier_order.
     * $data = [
     *   'id_status'         => int,
     *   'id_supplier_order' => int,
     *   'quantity'          => float|int,
     *   'item_number'       => int,
     *   'description_product' => string,
     *   'created_by'        => int
     * ]
     */
    public function createSupplierItem(array $data): bool
    {
        $sql = "INSERT INTO items_supplier_order
                (supplier_item_date, id_status, id_supplier_order, quantity, item_number, description_product, created_by, items_supplier_order_date, items_supplier_order_last_update)
                VALUES (NOW(), :id_status, :id_supplier_order, :quantity, :item_number, :description_product, :created_by, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_status'           => $data['id_status'],
            ':id_supplier_order'   => $data['id_supplier_order'],
            ':quantity'            => $data['quantity'],
            ':item_number'         => $data['item_number'],
            ':description_product' => $data['description_product'],
            ':created_by'          => $data['created_by'],
        ]);
    }

    /**
     * Obtiene el siguiente número de partida (item_number) dentro de un supplier_order.
     */
    public function getNextItemNumber(int $supplierOrderId): int
    {
        $sql = "SELECT COALESCE(MAX(item_number), 0) + 1
                FROM items_supplier_order
                WHERE id_supplier_order = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$supplierOrderId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Devuelve todas las partidas de un pedido proveedor.
     */
    public function getItemsBySupplierOrder(int $supplierOrderId): array
    {
        $sql = "SELECT *
                FROM items_supplier_order
                WHERE id_supplier_order = ?
                ORDER BY item_number ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$supplierOrderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Devuelve todas las partidas de un pedido proveedor. (fines logisticos)
     */

    public function getItemsWithStatusAndTracking(int $supplierOrderId): array
{
    $sql = "SELECT 
                i.id,
                i.item_number,
                i.description_product,
                i.quantity,
                i.id_status,
                ss.status_name,
                t.tracking_number,
                t.tracking_company,
                t.origin_branch,
                t.arrival_date
            FROM items_supplier_order i
            LEFT JOIN status_supplier_item ss ON i.id_status = ss.id
            LEFT JOIN tracking_supplier_orders t ON t.id_item_supplier_order = i.id
            WHERE i.id_supplier_order = ?
            ORDER BY i.item_number ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$supplierOrderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function updateStatus(int $itemId, int $newStatus): bool
{
    $sql = "UPDATE items_supplier_order
            SET id_status = ?, items_supplier_order_last_update = NOW()
            WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$newStatus, $itemId]);
}

public function getStatusNameById(int $statusId): ?string
{
    $stmt = $this->db->prepare("SELECT status_name FROM status_supplier_item WHERE id = ?");
    $stmt->execute([$statusId]);
    return $stmt->fetchColumn() ?: null;
}


public function cancelOrderAndItems(int $supplierOrderId): bool
{
    // 1️⃣ Cancelar el pedido principal
    $stmt = $this->db->prepare("
        UPDATE supplier_orders
        SET id_status = 5, supplier_order_last_update = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$supplierOrderId]);

    if ($stmt->rowCount() === 0) {
        return false; // no se actualizó nada
    }

    // 2️⃣ Cancelar todas sus partidas (items)
    $updateItems = $this->db->prepare("
        UPDATE items_supplier_order
        SET id_status = 4, items_supplier_order_last_update = NOW()
        WHERE id_supplier_order = ?
    ");
    $updateItems->execute([$supplierOrderId]);

    // 3️⃣ Actualizar jerarquía superior (cliente y sistema)
    require_once __DIR__ . '/StatusManager.php';
    $statusManager = new StatusManager($this->db);
    $statusManager->updateSupplierOrderStatus($supplierOrderId);

    return true;
}




    
}
