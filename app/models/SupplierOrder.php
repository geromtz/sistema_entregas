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
                (id_customer_order, id_status, id_supplier, item_number, supplier_order, id_order_type, created_by, supplier_order_date, supplier_order_last_update)
                VALUES (:id_customer_order, :id_status, :id_supplier, :item_number, :supplier_order, :id_order_type, :created_by, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_customer_order' => $data['id_customer_order'],
            ':id_status'         => $data['id_status'],
            ':id_supplier'       => $data['id_supplier'],
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
            so.supplier_order_date,
            so.supplier_order_last_update
        FROM supplier_orders AS so
        LEFT JOIN status_supplier_order AS ss ON so.id_status = ss.id
        LEFT JOIN supplier_order_types AS sot ON so.id_order_type = sot.id
        LEFT JOIN suppliers AS sp ON so.id_supplier = sp.id
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
            so.supplier_order_date,
            so.created_by,
            so.id_order_type,
            sot.order_type AS order_type_name,
            s.business_name AS supplier_name,
            CONCAT(u.firstname, ' ', u.apellidos) AS created_by_name
        FROM supplier_orders AS so
        INNER JOIN suppliers AS s ON s.id = so.id_supplier
        INNER JOIN supplier_order_types AS sot ON sot.id = so.id_order_type
        LEFT JOIN users AS u ON u.id = so.created_by
        WHERE 
            so.id_status IN (1, 2)
            AND (
                -- 🟢 Local: siempre visible
                so.id_order_type = 1
                
                -- 🟡 Foráneo a sucursal / varias sucursales: mostrar si ya llegó (<= hoy)
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




}
