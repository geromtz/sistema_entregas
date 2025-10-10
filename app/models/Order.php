<?php
require_once __DIR__ . '/../../config/db.php';

class Order {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Obtener pedidos visibles para los IDs de usuario dados
     */
    public function getOrdersByUsers(array $userIds): array {
        if (empty($userIds)) return [];

        // Creamos placeholders para PDO
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        $sql = "
            SELECT 
                so.id AS system_id,
                so.system_number,
                so.system_number_date,
                so.id_status AS system_status,
                co.id AS customer_order_id,
                co.purchase_order,
                co.name_order,
                co.attn,
                co.customer_order_date,
                co.id_status AS customer_status,
                co.created_by,
                u.firstname,
                u.apellidos,
                GROUP_CONCAT(DISTINCT s.company_name SEPARATOR ', ') AS suppliers
            FROM system_orders so
            LEFT JOIN customer_orders co ON co.id_system_order = so.id
            LEFT JOIN users u ON u.id = co.created_by
            LEFT JOIN supplier_orders so2 ON so2.id_customer_order = co.id
            LEFT JOIN suppliers s ON s.id = so2.id_supplier
            WHERE so.created_by IN ($placeholders)
            GROUP BY so.id
            ORDER BY so.system_number_date DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($userIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Para depuración en la vista
            return [['error' => "Error al obtener pedidos: " . $e->getMessage()]];
        }
    }
}
