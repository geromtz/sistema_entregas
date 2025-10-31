<?php
// app/models/LogisticSupplierOrder.php
require_once __DIR__ . '/../../config/db.php';

class LogisticSupplierOrder
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
     * 🔹 Obtiene todas las órdenes logísticas con datos de referencia
     * (vinculadas a supplier_orders).
     */
    public function getAll(): array
    {
        $sql = "
            SELECT 
                lso.id AS id_logistic,
                lso.id_supplier_order,
                lso.logistic_status,
                lso.verified_by,
                lso.verified_at,
                lso.notes,
                lso.created_at,
                lso.updated_at,

                -- Datos referenciales
                so.supplier_order,
                so.supplier_order_date,
                sso.status_name AS supplier_status,
                s.business_name AS supplier_name,
                sa.branch AS branch_name,
                CONCAT(u.firstname, ' ', u.apellidos) AS created_by_name,
                sot.order_type AS order_type_name

            FROM logistic_supplier_orders AS lso
            INNER JOIN supplier_orders AS so 
                ON so.id = lso.id_supplier_order
            LEFT JOIN suppliers AS s 
                ON s.id = so.id_supplier
            LEFT JOIN supplier_adress AS sa 
                ON sa.id = so.id_branch
            LEFT JOIN supplier_order_types AS sot 
                ON sot.id = so.id_order_type
            LEFT JOIN status_supplier_order AS sso 
                ON sso.id = so.id_status
            LEFT JOIN users AS u 
                ON u.id = so.created_by
            ORDER BY lso.created_at DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
