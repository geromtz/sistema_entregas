<?php
require_once __DIR__ . '/../../config/db.php';

class SupplierOrder
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * 🔹 Permite obtener la conexión PDO fuera del modelo (sin romper encapsulación)
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }

    /**
     * Crea un nuevo pedido de proveedor.
     * $data = [
     *   'id_customer_order' => int,
     *   'id_status'         => int,   // p.ej. 1 = pendiente
     *   'id_supplier'       => int|null,
     *   'item_number'       => int,   // consecutivo dentro del customer_order
     *   'supplier_order'    => string,
     *   'id_order_type'     => int,   // 1..5
     *   'created_by'        => int
     * ]
     * @return int ID generado
     */
    public function createSupplierOrder(array $data): int
    {
        $sql = "INSERT INTO supplier_orders
            (id_customer_order, id_status, id_supplier, id_branch, item_number, supplier_order, id_order_type, created_by, supplier_order_date, supplier_order_last_update)
            VALUES (:id_customer_order, :id_status, :id_supplier, :id_branch, :item_number, :supplier_order, :id_order_type, :created_by, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_customer_order' => $data['id_customer_order'],
            ':id_status'         => $data['id_status'],
            ':id_supplier'       => $data['id_supplier'],
            ':id_branch'         => $data['id_branch'] ?? 1, // 🟢 Nuevo campo con valor por defecto
            ':item_number'       => $data['item_number'],
            ':supplier_order'    => $data['supplier_order'],
            ':id_order_type'     => $data['id_order_type'],
            ':created_by'        => $data['created_by'],
        ]);

        return (int)$this->db->lastInsertId();
    }


    /**
     * Siguiente número de pedido proveedor (item_number) dentro de un customer_order.
     */
    public function getNextItemNumber(int $customerOrderId): int
    {
        $sql = "SELECT COALESCE(MAX(item_number), 0) + 1
                FROM supplier_orders
                WHERE id_customer_order = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerOrderId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Trae los supplier_orders de un customer_order, con su estado y tipo.
     */
    public function getSupplierOrdersByCustomerOrder(int $customerOrderId): array
    {
        $sql = "
        SELECT 
            so.id,
            so.item_number,
            so.id_status,
            ss.status_name,
            so.supplier_order,
            so.id_order_type,
            sot.order_type AS order_type_name,
            so.id_supplier,
            sp.business_name AS supplier_name,
            sa.branch AS branch_name, -- 🟢 Nueva columna
            so.supplier_order_date,
            so.supplier_order_last_update
        FROM supplier_orders AS so
        LEFT JOIN status_supplier_order AS ss ON so.id_status = ss.id
        LEFT JOIN supplier_order_types AS sot ON so.id_order_type = sot.id
        LEFT JOIN suppliers AS sp ON so.id_supplier = sp.id
        LEFT JOIN supplier_adress AS sa ON sa.id = so.id_branch -- 🟢 Nuevo JOIN
        WHERE so.id_customer_order = ?
        ORDER BY so.item_number ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerOrderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function getOrdersInProcess()
{
    $sql = "
        SELECT 
            so.id,
            so.supplier_order,
            so.id_status,
            sso.status_name,                                  -- ✅ Agregado
            so.supplier_order_date,
            so.created_by,
            so.id_order_type,
            sot.order_type AS order_type_name,
            s.business_name AS supplier_name,
            sa.branch AS branch_name,                         -- 🟢 Sucursal del proveedor
            CONCAT(u.firstname, ' ', u.apellidos) AS created_by_name
        FROM supplier_orders AS so
        INNER JOIN suppliers AS s ON s.id = so.id_supplier
        LEFT JOIN supplier_adress AS sa ON sa.id = so.id_branch
        INNER JOIN supplier_order_types AS sot ON sot.id = so.id_order_type
        INNER JOIN status_supplier_order AS sso ON sso.id = so.id_status -- ✅ Aquí unimos el estatus
        LEFT JOIN users AS u ON u.id = so.created_by
        WHERE 
            so.id_status IN (1, 2)
            AND (
                so.id_order_type = 1
                OR (
                    so.id_order_type IN (3, 4)
                    AND EXISTS (
                        SELECT 1
                        FROM tracking_supplier_orders tso
                        INNER JOIN items_supplier_order iso 
                            ON iso.id = tso.id_item_supplier_order
                        WHERE iso.id_supplier_order = so.id
                        AND DATE(tso.arrival_date) <= CURDATE()
                    )
                )
            )
        ORDER BY so.supplier_order_date DESC
    ";

    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    public function cancelOrder(int $supplierOrderId): bool
    {
        $sql = "UPDATE supplier_orders
            SET id_status = 5,
                supplier_order_last_update = NOW()
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $supplierOrderId]);

        return $stmt->rowCount() > 0;
    }

    public function cancelOrderAndItems(int $supplierOrderId): bool
    {
        // 1️⃣ Cancelar pedido
        $stmt = $this->db->prepare("
        UPDATE supplier_orders
        SET id_status = 5, supplier_order_last_update = NOW()
        WHERE id = ?
    ");
        $stmt->execute([$supplierOrderId]);

        if ($stmt->rowCount() === 0) return false;

        // 2️⃣ Cancelar todas las partidas del pedido
        $updateItems = $this->db->prepare("
        UPDATE items_supplier_order
        SET id_status = 4, items_supplier_order_last_update = NOW()
        WHERE id_supplier_order = ?
    ");
        $updateItems->execute([$supplierOrderId]);

        // 3️⃣ Propagar actualización a niveles superiores
        require_once __DIR__ . '/StatusManager.php';
        $statusManager = new StatusManager($this->db);
        $statusManager->updateSupplierOrderStatus($supplierOrderId);

        return true;
    }
}
