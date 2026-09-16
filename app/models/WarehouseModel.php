<?php
require_once __DIR__ . '/../../config/db.php';

class WarehouseModel
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getPendingReceptionItems()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    iso.id as item_id,
                    iso.quantity,
                    iso.product_id,
                    p.part_number,
                    p.description,
                    so.supplier_order,
                    s.business_name as supplier_name,
                    ls.id as logistic_status_id,
                    ls.pickup_date,
                    ls.pickup_by,
                    CONCAT(u.firstname, ' ', u.apellidos) as driver_name,
                    COALESCE(SUM(wr.received_quantity), 0) as total_received
                FROM items_supplier_order iso
                JOIN logistic_status ls ON ls.id_item_supplier_order = iso.id
                JOIN products p ON p.id = iso.product_id
                JOIN supplier_orders so ON so.id = iso.id_supplier_order
                LEFT JOIN suppliers s ON s.id = so.id_supplier
                LEFT JOIN users u ON u.id = ls.pickup_by
                LEFT JOIN warehouse_reception wr ON wr.id_item_supplier_order = iso.id
                WHERE ls.pickup_status = 1 
                GROUP BY iso.id
                HAVING total_received < iso.quantity
                ORDER BY ls.pickup_date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPendingReceptionItems: " . $e->getMessage());
            return [];
        }
    }

    public function processReception(int $itemId, int $logisticStatusId, int $quantity, string $condition, array $serials, int $userId): bool
    {
        try {
            $this->db->beginTransaction();

            // 0. Validación previa de series duplicadas en la base de datos global
            if (!empty($serials)) {
                foreach ($serials as $serial) {
                    $serialClean = trim($serial);
                    if (!empty($serialClean)) {
                        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM item_serials WHERE serial_number = ?");
                        $checkStmt->execute([$serialClean]);
                        if ($checkStmt->fetchColumn() > 0) {
                            throw new Exception("La serie '{$serialClean}' ya se encuentra registrada en el sistema.");
                        }
                    }
                }
            }

            // 1. Registrar la recepción formal en el andén
            $stmt = $this->db->prepare("
                INSERT INTO warehouse_reception (id_item_supplier_order, id_logistic_status, received_quantity, condition_status, received_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$itemId, $logisticStatusId, $quantity, $condition, $userId]);
            $receptionId = $this->db->lastInsertId();

            // 2. Si vienen números de serie, guardarlos de forma masiva en la tabla normalizada
            if (!empty($serials)) {
                // Obtenemos el product_id del ítem una sola vez antes del ciclo
                $prodQuery = $this->db->prepare("SELECT product_id FROM items_supplier_order WHERE id = ?");
                $prodQuery->execute([$itemId]);
                $productId = $prodQuery->fetchColumn();

                $insertSerial = $this->db->prepare("
                    INSERT INTO item_serials (warehouse_reception_id, item_supplier_order_id, product_id, serial_number, status)
                    VALUES (?, ?, ?, ?, 'in_stock')
                ");

                foreach ($serials as $serial) {
                    $serialClean = trim($serial);
                    if (!empty($serialClean)) {
                        $insertSerial->execute([$receptionId, $itemId, $productId, $serialClean]);
                    }
                }
            }

            // 3. Actualizar el estatus logístico o cerrar el ciclo de tránsito si es necesario
            $updateLogistic = $this->db->prepare("
                UPDATE logistic_status 
                SET updated_at = NOW() 
                WHERE id = ?
            ");
            $updateLogistic->execute([$logisticStatusId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) { // Cambiado a Exception genérica para atrapar nuestras alertas personalizadas y PDOExceptions
            $this->db->rollBack();
            error_log("Error in processReception: " . $e->getMessage());
            
            // Opcional: Si quieres que el controlador imprima exactamente el mensaje de la excepción:
            $_SESSION['reception_error_message'] = $e->getMessage(); 
            
            return false;
        }
    }

    public function getCustomerOrdersForStockAudit()
{
    try {
        $stmt = $this->db->prepare("
            SELECT 
                co.id as customer_order_id,
                co.purchase_order,
                co.customer_order_date,
                co.order_pdf_path,
                c.business_name as customer_name,
                so.id as supplier_order_id,
                so.supplier_order as supplier_folio,
                sup.business_name as supplier_name,
                iso.id as item_id,
                p.part_number,
                p.description,
                iso.quantity as ordered_quantity,
                COALESCE(SUM(wr.received_quantity), 0) as received_quantity
            FROM customer_orders co
            LEFT JOIN customers c ON c.id = co.id_customer
            JOIN supplier_orders so ON so.id_customer_order = co.id
            LEFT JOIN suppliers sup ON sup.id = so.id_supplier
            JOIN items_supplier_order iso ON iso.id_supplier_order = so.id
            JOIN products p ON p.id = iso.product_id
            LEFT JOIN warehouse_reception wr ON wr.id_item_supplier_order = iso.id
            GROUP BY iso.id
            ORDER BY co.id DESC, so.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getCustomerOrdersForStockAudit: " . $e->getMessage());
        return [];
    }
}

    public function getDbConnection() {
        return $this->db;
    }
}