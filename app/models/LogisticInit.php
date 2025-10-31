<?php
// app/models/LogisticInit.php
require_once __DIR__ . '/../../config/db.php';

class LogisticInit
{
    private PDO $db;

    public function __construct($db = null)
    {
        if ($db instanceof PDO) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }

    /**
     * 🔹 Crea registros iniciales en logística basados en supplier_orders.
     * Solo vincula IDs, no duplica datos.
     */
public function initializeFromSupplierOrders(): array
{
    $insertedOrders = 0;
    $insertedItems = 0;

    // 1️⃣ Traer todos los supplier_orders con su status
    $orders = $this->db->query("
        SELECT id, id_status
        FROM supplier_orders
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        $idSupplierOrder = $order['id'];
        $idStatus = $order['id_status'];

        // 🔹 Mapa de estados entre supplier_orders → logística
        $statusMap = [
            1 => 'pendiente',             // Pedido nuevo o por procesar
            2 => 'en_proceso',            // En proceso
            3 => 'parcial_aspel',         // Parcial desde Aspel
            4 => 'completo_aspel',        // Completo desde Aspel
            5 => 'cancelado',             // ❗ Cancelado
            6 => 'listo_para_entrega',    // Listo para entrega
            7 => 'cerrado'                // Cerrado/finalizado
        ];

        $logisticStatus = $statusMap[$idStatus] ?? 'pendiente';

        // 2️⃣ Verificar si ya existe en logística
        $check = $this->db->prepare("
            SELECT id FROM logistic_supplier_orders WHERE id_supplier_order = ?
        ");
        $check->execute([$idSupplierOrder]);
        $exists = $check->fetchColumn();

        if (!$exists) {
            // 🔹 Crear registro logístico con el estatus correspondiente
            $stmt = $this->db->prepare("
                INSERT INTO logistic_supplier_orders
                (id_supplier_order, logistic_status, created_at)
                VALUES (:id_supplier_order, :logistic_status, NOW())
            ");
            $stmt->execute([
                ':id_supplier_order' => $idSupplierOrder,
                ':logistic_status'   => $logisticStatus
            ]);
            $insertedOrders++;
        }

        // Obtener el ID logístico recién creado (o existente)
        $stmt = $this->db->prepare("
            SELECT id FROM logistic_supplier_orders WHERE id_supplier_order = ?
        ");
        $stmt->execute([$idSupplierOrder]);
        $logisticId = $stmt->fetchColumn();

        // 3️⃣ Insertar ítems asociados si no existen
        $items = $this->db->prepare("
            SELECT id, item_number, description_product, quantity 
            FROM items_supplier_order 
            WHERE id_supplier_order = ?
        ");
        $items->execute([$idSupplierOrder]);

        // 🔸 Si el pedido está cancelado, los ítems también deben estar cancelados
        $itemStatus = ($logisticStatus === 'cancelado') ? 'cancelado' : 'pendiente';

        while ($row = $items->fetch(PDO::FETCH_ASSOC)) {
            $checkItem = $this->db->prepare("
                SELECT id FROM logistic_supplier_order_items WHERE id_item_supplier_order = ?
            ");
            $checkItem->execute([$row['id']]);
            if (!$checkItem->fetchColumn()) {
                $insertItem = $this->db->prepare("
                    INSERT INTO logistic_supplier_order_items
                    (id_logistic_supplier_order, id_item_supplier_order, item_number, description_product, ordered_quantity, received_quantity, difference_quantity, status, created_at)
                    VALUES (:id_logistic_supplier_order, :id_item_supplier_order, :item_number, :description_product, :ordered_quantity, 0, 0, :status, NOW())
                ");
                $insertItem->execute([
                    ':id_logistic_supplier_order' => $logisticId,
                    ':id_item_supplier_order'     => $row['id'],
                    ':item_number'                => $row['item_number'],
                    ':description_product'        => $row['description_product'],
                    ':ordered_quantity'           => $row['quantity'],
                    ':status'                     => $itemStatus
                ]);
                $insertedItems++;
            }
        }
    }

    return [
        'orders_created' => $insertedOrders,
        'items_created' => $insertedItems
    ];
}


}
