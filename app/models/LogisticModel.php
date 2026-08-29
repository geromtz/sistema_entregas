<?php
require_once __DIR__ . '/../../config/db.php';

class LogisticModel
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Crea registro en logistic_status cuando se crea un item
     */
    public function createFromItem($itemId)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO logistic_status (id_item_supplier_order)
                VALUES (:itemId)
            ");
            $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating logistic entry: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener recolecciones pendientes
     */
    public function getPendingPickups($userId, $isLeader)
    {
        try {
            date_default_timezone_set('America/Monterrey');
            $hoy = date('Y-m-d');

            // Definimos si es admin (Líder o Temo con ID 6)
            $esAdmin = ($isLeader || $userId == 6);

            $query = "SELECT 
                i.id AS item_id, 
                CONCAT(p_prod.part_number, ' - ', p_prod.description) AS description_product, 
                i.quantity,
                COALESCE(s.supplier_order, 'S/F') as system_number,
                COALESCE(cust.business_name, co.name_order, 'Stock / General') as name_order,
                p.business_name as supplier_name,
                s.id_order_type,
                COALESCE(st.order_type, 'Normal') AS type_name,
                CONCAT(u_drv.firstname, ' ', u_drv.apellidos) as assigned_driver_name,
                CASE 
                    WHEN s.id_order_type IN (1, 2) THEN 'Producto Local'
                    ELSE COALESCE(DATE_FORMAT(t.arrival_date, '%d/%m/%Y'), 'Sin fecha')
                END AS arrival_info,
                CASE 
                    WHEN s.id_order_type IN (3, 4, 5) THEN COALESCE(t.origin_branch, 'Pendiente')
                    ELSE COALESCE(ad_order.branch, 'Matriz')
                END AS pickup_location,
                (SELECT GROUP_CONCAT(
                    CONCAT('• ', li.comment, ' - ', u.firstname, ' ', u.apellidos, ' (', DATE_FORMAT(li.created_at, '%d/%m/%Y'), ')') 
                    ORDER BY li.created_at DESC SEPARATOR '<br>') 
                 FROM logistic_incidents li 
                 INNER JOIN users u ON li.created_by = u.id
                 WHERE li.id_logistic_status = l.id) as incident_history
            FROM items_supplier_order i
            INNER JOIN supplier_orders s ON i.id_supplier_order = s.id
            INNER JOIN suppliers p ON s.id_supplier = p.id
            LEFT JOIN products p_prod ON i.product_id = p_prod.id
            LEFT JOIN customer_orders co ON s.id_customer_order = co.id
            LEFT JOIN customers cust ON co.id_customer = cust.id
            LEFT JOIN supplier_order_types st ON st.id = s.id_order_type
            LEFT JOIN supplier_adress ad_order ON s.id_branch = ad_order.id
            LEFT JOIN tracking_supplier_orders t ON t.id_item_supplier_order = i.id
            LEFT JOIN logistic_status l ON l.id_item_supplier_order = i.id
            LEFT JOIN users u_drv ON l.assigned_to = u_drv.id
            
            WHERE (l.pickup_status IS NULL OR l.pickup_status = 0)
            
            AND (
                " . ($esAdmin ? "1=1" : "l.assigned_to = :userId") . "
            )

            AND (
                s.id_order_type IN (1, 2)
                OR 
                (s.id_order_type IN (3, 4, 5) AND (t.arrival_date IS NULL OR DATE(t.arrival_date) <= :hoy))
                OR (" . ($esAdmin ? "1=1" : "0=1") . ") 
            )
            ORDER BY i.id DESC";

            $stmt = $this->db->prepare($query);
            $params = ['hoy' => $hoy];

            if (!$esAdmin) {
                $params['userId'] = $userId;
            }

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getPendingPickups: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener entregas pendientes
     */
    public function getPendingDeliveries()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    so.system_number,
                    c.name_order,
                    i.id AS item_id,
                    CONCAT(p_prod.part_number, ' - ', p_prod.description) AS description_product,
                    i.quantity,
                    l.pickup_status,
                    l.delivery_status
                FROM items_supplier_order i
                JOIN supplier_orders sup 
                    ON sup.id = i.id_supplier_order
                JOIN customer_orders c 
                    ON c.id = sup.id_customer_order
                JOIN system_orders so 
                    ON so.id = c.id_system_order
                JOIN logistic_status l 
                    ON l.id_item_supplier_order = i.id
                LEFT JOIN products p_prod 
                    ON i.product_id = p_prod.id
                WHERE l.pickup_status = 1
                AND l.delivery_status = 0
                ORDER BY so.system_number DESC
            ");

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching deliveries: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marcar recolección
     */
    public function markPickup($itemId, $userId)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE logistic_status
                SET 
                    pickup_status = 1,
                    pickup_date = NOW(),
                    pickup_by = :userId
                WHERE id_item_supplier_order = :itemId
                AND pickup_status = 0
            ");

            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking pickup: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar entrega
     */
    public function markDelivery($itemId, $userId)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE logistic_status
                SET 
                    delivery_status = 1,
                    delivery_date = NOW(),
                    delivery_by = :userId
                WHERE id_item_supplier_order = :itemId
                AND delivery_status = 0
            ");

            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking delivery: " . $e->getMessage());
            return false;
        }
    }

    public function getLogisticMonitor()
    {
        try {
            $query = "SELECT
            sup.supplier_order_date AS fecha_pedido,
            sup.supplier_order AS pedido_mayorista,
            prov.business_name AS proveedor_name,
            st.order_type AS tipo_pedido_nombre,
            cust.business_name AS cliente_name,
            c.purchase_order,
            CONCAT(u3.firstname, ' ', u3.apellidos) AS vendedor_name,
            p_prod.part_number AS part_number,
            p_prod.description AS articulos,
            l.pickup_status,
            l.pickup_date,
            l.delivery_status,
            l.delivery_date,
            CONCAT(u1.firstname, ' ', u1.apellidos) AS pickup_user,
            CONCAT(u2.firstname, ' ', u2.apellidos) AS delivery_user,
            (SELECT GROUP_CONCAT(
                CONCAT('• ', li.comment, ' - ', u.firstname, ' ', u.apellidos, ' (', DATE_FORMAT(li.created_at, '%d/%m/%Y'), ')') 
                ORDER BY li.created_at DESC SEPARATOR '<br>') 
               FROM logistic_incidents li 
               INNER JOIN users u ON li.created_by = u.id
               WHERE li.id_logistic_status = l.id) as incident_history
            
        FROM logistic_status l
        INNER JOIN items_supplier_order i ON i.id = l.id_item_supplier_order
        LEFT JOIN products p_prod ON i.product_id = p_prod.id
        INNER JOIN supplier_orders sup ON sup.id = i.id_supplier_order
        INNER JOIN suppliers prov ON prov.id = sup.id_supplier
        INNER JOIN supplier_order_types st ON st.id = sup.id_order_type
        INNER JOIN customer_orders c ON c.id = sup.id_customer_order
        INNER JOIN customers cust ON cust.id = c.id_customer
        INNER JOIN system_orders so ON so.id = c.id_system_order
        LEFT JOIN users u1 ON u1.id = l.pickup_by
        LEFT JOIN users u2 ON u2.id = l.delivery_by
        LEFT JOIN users u3 ON u3.id = c.created_by
        ORDER BY l.id DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error en SQL Monitor: " . $e->getMessage());
        }
    }

    public function getSalesOrdersStatus($sellerId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT
                so.system_number,
                c.name_order,

                MAX(l.pickup_status) AS any_pickup,
                MIN(l.delivery_status) AS all_delivered

            FROM customer_orders c
            JOIN system_orders so 
                ON so.id = c.id_system_order
            JOIN supplier_orders sup 
                ON sup.id_customer_order = c.id
            JOIN items_supplier_order i 
                ON i.id_supplier_order = sup.id
            JOIN logistic_status l 
                ON l.id_item_supplier_order = i.id

            WHERE c.created_by = :sellerId

            GROUP BY so.system_number, c.name_order
            ORDER BY so.system_number DESC
        ");

            $stmt->bindParam(':sellerId', $sellerId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching sales logistic view: " . $e->getMessage());
            return [];
        }
    }

    public function createFromFullOrder($supplierOrderId)
    {
        $query = "INSERT IGNORE INTO logistic_status (id_item_supplier_order, created_at, updated_at)
              SELECT id, NOW(), NOW() 
              FROM items_supplier_order 
              WHERE id_supplier_order = :supplierOrderId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':supplierOrderId', $supplierOrderId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Asegura la existencia del registro padre en logistic_status
     */
    public function getOrCreateStatus($itemId)
    {
        $stmt = $this->db->prepare("SELECT id FROM logistic_status WHERE id_item_supplier_order = ?");
        $stmt->execute([$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['id'];
        } else {
            $stmt = $this->db->prepare("INSERT INTO logistic_status (id_item_supplier_order, pickup_status, created_at) VALUES (?, 0, NOW())");
            $stmt->execute([$itemId]);
            return $this->db->lastInsertId();
        }
    }

    /**
     * Inserta el log del incidente con 4 parámetros obligatorios
     */
    public function insertIncident($statusId, $comment, $userId, $type)
    {
        $sql = "INSERT INTO logistic_incidents 
            (id_logistic_status, incident_type, comment, created_by, created_at) 
            VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $statusId,
            $type,
            $comment,
            $userId
        ]);
    }

    /**
     * Obtiene la lista de usuarios para asignar recolecciones.
     */
    public function getAvailableDrivers()
    {
        try {
            $query = "SELECT id, firstname, apellidos, id_position FROM users ORDER BY firstname ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateAssignment($itemId, $driverId)
    {
        try {
            $statusId = $this->getOrCreateStatus($itemId);
            $adminId  = $_SESSION['user']['id'] ?? null;

            $query = "UPDATE logistic_status 
                     SET assigned_to = :driverId, 
                         assigned_at = NOW(), 
                         assigned_by = :adminId,
                         updated_at = NOW() 
                     WHERE id = :statusId";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                'driverId' => $driverId,
                'adminId'  => $adminId,
                'statusId' => $statusId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function removeAssignment($itemId)
    {
        try {
            $statusId = $this->getOrCreateStatus($itemId);

            $query = "UPDATE logistic_status 
                     SET assigned_to = NULL, 
                         assigned_at = NULL, 
                         assigned_by = NULL,
                         updated_at = NOW() 
                     WHERE id = :statusId";

            $stmt = $this->db->prepare($query);
            return $stmt->execute(['statusId' => $statusId]);
        } catch (PDOException $e) {
            error_log("Error unassigning: " . $e->getMessage());
            return false;
        }
    }
}
