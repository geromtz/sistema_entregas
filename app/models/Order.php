<?php
require_once __DIR__ . '/../../config/db.php';

class Order
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Obtener pedidos visibles para los IDs de usuario dados
     */
    public function getOrdersByUsers(array $userIds): array
    {
        if (empty($userIds)) return [];

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        $sql = "
        SELECT 
            so.id AS system_id,
            CONCAT(co.order_seller_series, '-', co.order_seller_number) AS order_code, -- ✅ Serie + número del cliente
            co.customer_order_date,
            so.id_status AS system_status_id,
            s.name AS system_status,
            co.id AS customer_order_id,
            co.purchase_order,
            co.name_order,
            co.attn,
            co.id_status AS customer_status,
            co.created_by,
            u.firstname,
            u.apellidos,
            c.business_name AS customer_name,  -- ✅ nombre del cliente
            GROUP_CONCAT(DISTINCT sup.company_name SEPARATOR ', ') AS suppliers
        FROM system_orders so
        LEFT JOIN customer_orders co ON co.id_system_order = so.id
        LEFT JOIN customers c ON c.id = co.id_customer
        LEFT JOIN users u ON u.id = co.created_by
        LEFT JOIN supplier_orders so2 ON so2.id_customer_order = co.id
        LEFT JOIN suppliers sup ON sup.id = so2.id_supplier
        LEFT JOIN system_order_status s ON s.id = so.id_status
        WHERE so.created_by IN ($placeholders)
        AND so.id_status NOT IN (5)
        GROUP BY so.id
        ORDER BY co.customer_order_date DESC
    ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($userIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [['error' => "Error al obtener pedidos: " . $e->getMessage()]];
        }
    }



    /**
     * Obtener un system_order por su ID
     */
    public function getSystemOrderById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM system_orders WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            return $order ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching system order by ID: " . $e->getMessage());
            return null;
        }
    }


    public function getCustomerOrderBySystemId(int $systemOrderId): ?array
    {
        try {
            $stmt = $this->db->prepare("
            SELECT co.*, c.business_name
            FROM customer_orders co
            LEFT JOIN customers c ON c.id = co.id_customer
            WHERE co.id_system_order = :systemOrderId
            LIMIT 1
        ");
            $stmt->execute([':systemOrderId' => $systemOrderId]);
            $customerOrder = $stmt->fetch(PDO::FETCH_ASSOC);
            return $customerOrder ?: null;
        } catch (PDOException $e) {
            error_log('Error fetching customer order: ' . $e->getMessage());
            return null;
        }
    }



    /**
     * Cambiar una orden de Temporal (5) a Abierto (1)
     */
    public function activateSystemOrder(int $orderId): bool
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE system_orders 
            SET id_status = 1 
            WHERE id = :id AND id_status = 5
        ");
            return $stmt->execute([':id' => $orderId]);
        } catch (PDOException $e) {
            error_log("Error activating system order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener el pedido del sistema activo o temporal de un usuario.
     * Devuelve el pedido con estatus "Abierto (1)" o "Temporal (5)" más reciente.
     */
    public function getActiveSystemOrder(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("
            SELECT * 
            FROM system_orders
            WHERE created_by = :userId
              AND id_status IN (1, 5)
            ORDER BY system_number_date DESC
            LIMIT 1
        ");
            $stmt->execute([':userId' => $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            return $order ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching active/temporary system order: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear un nuevo pedido del sistema con estatus "Temporal".
     * Este pedido se convierte en "Abierto" cuando el usuario empieza a llenarlo.
     */
    public function createSystemOrder(int $userId): array
    {
        try {
            // Calcular el siguiente número incremental según la serie 'SO'
            $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(system_number), 0) + 1 AS next_number
            FROM system_orders
            WHERE system_order_series = 'SO'
        ");
            $stmt->execute();
            $nextNumber = $stmt->fetchColumn();

            // Crear pedido con estatus TEMPORAL (5)
            $stmt = $this->db->prepare("
            INSERT INTO system_orders (system_order_series, system_number, system_number_date, id_status, created_by)
            VALUES ('SO', :number, NOW(), 5, :userId)
        ");
            $stmt->execute([
                ':number' => $nextNumber,
                ':userId' => $userId
            ]);

            // Retornar el pedido recién creado
            $stmt = $this->db->prepare("
            SELECT * 
            FROM system_orders
            WHERE created_by = :userId
              AND id_status = 5
            ORDER BY system_number_date DESC
            LIMIT 1
        ");
            $stmt->execute([':userId' => $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            return $order ?: [];
        } catch (PDOException $e) {
            error_log("Error creating temporary system order: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Eliminar pedidos temporales vacíos de un usuario
     */
    public function cleanupEmptyTemporaryOrders(int $userId): int
    {
        try {
            $stmt = $this->db->prepare("
            DELETE FROM system_orders
            WHERE created_by = :userId
              AND id_status = 5
              AND has_data = 0
        ");
            $stmt->execute([':userId' => $userId]);
            return $stmt->rowCount(); // Retorna cuántos registros se eliminaron
        } catch (PDOException $e) {
            error_log("Error cleaning temporary orders: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Marcar un pedido como que tiene datos (has_data = 1)
     */
    public function markOrderHasData(int $orderId): bool
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE system_orders 
            SET has_data = 1 
            WHERE id = :id
        ");
            return $stmt->execute([':id' => $orderId]);
        } catch (PDOException $e) {
            error_log("Error marking order has_data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mover un pedido a estatus Pendiente (6)
     */
    public function setOrderPending(int $orderId): bool
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE system_orders
            SET id_status = 6
            WHERE id = :id
        ");
            return $stmt->execute([':id' => $orderId]);
        } catch (PDOException $e) {
            error_log("Error setting order to pending: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mover un pedido a estatus Eliminado (7)
     */
    public function setOrderDeleted(int $orderId): bool
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE system_orders
            SET id_status = 7
            WHERE id = :id
        ");
            return $stmt->execute([':id' => $orderId]);
        } catch (PDOException $e) {
            error_log("Error setting order to deleted: " . $e->getMessage());
            return false;
        }
    }





    /**
     * Obtener pedidos temporales (5) o pendientes (6) de un usuario
     */
    public function getTemporaryOrPendingOrders(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
            SELECT * 
            FROM system_orders
            WHERE created_by = :userId
              AND id_status IN (5, 6)
            ORDER BY system_number_date DESC
        ");
            $stmt->execute([':userId' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching temporary/pending orders: " . $e->getMessage());
            return [];
        }
    }


    public function getCustomerOrderBySystemOrderId(int $systemOrderId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM customer_orders WHERE id_system_order = :id LIMIT 1");
            $stmt->execute([':id' => $systemOrderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            return $order ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching customer_order by system_order_id: " . $e->getMessage());
            return null;
        }
    }


    public function createCustomerOrder(int $systemOrderId, int $userId, ?int $customerId = null): int
    {
        try {
            // Obtener series del usuario
            $stmt = $this->db->prepare("SELECT series_code FROM user_series WHERE id_user = :userId LIMIT 1");
            $stmt->execute([':userId' => $userId]);
            $series = $stmt->fetchColumn() ?? 'DEFAULT';

            // Calcular siguiente order_seller_number para esa serie
            $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(order_seller_number), 0) + 1 AS next_number
            FROM customer_orders
            WHERE order_seller_series = :series
        ");
            $stmt->execute([':series' => $series]);
            $nextNumber = $stmt->fetchColumn() ?? 1;

            // Insertar registro
            $stmt = $this->db->prepare("
            INSERT INTO customer_orders
            (id_system_order, order_seller_series, order_seller_number, id_customer, created_by, customer_order_date, co_last_update_date, id_status)
            VALUES
            (:systemOrderId, :series, :number, :customerId, :userId, NOW(), NOW(), 1)
        ");
            $stmt->execute([
                ':systemOrderId' => $systemOrderId,
                ':series' => $series,
                ':number' => $nextNumber,
                ':customerId' => $customerId,
                ':userId' => $userId
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating customer_order: " . $e->getMessage());
            return 0;
        }
    }


    public function getUserSeries(int $userId): ?string
    {
        try {
            $stmt = $this->db->prepare("SELECT series_code FROM user_series WHERE id_user = :userId LIMIT 1");
            $stmt->execute([':userId' => $userId]);
            $series = $stmt->fetchColumn();
            return $series ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching user series: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Obtener el siguiente número consecutivo para una serie de usuario
     */
    public function getNextOrderNumber(string $seriesCode): int
    {
        try {
            $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(order_seller_number), 0) + 1 AS next_number
            FROM customer_orders
            WHERE order_seller_series = :seriesCode
        ");
            $stmt->execute([':seriesCode' => $seriesCode]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching next order number: " . $e->getMessage());
            return 1; // si falla, empezamos en 1
        }
    }


    public function createCustomerOrderForSystemOrder(int $systemOrderId, int $userId): ?int
    {
        try {
            // 1️⃣ Verificar si ya existe customer_order para este system_order
            $stmt = $this->db->prepare("SELECT id FROM customer_orders WHERE id_system_order = :systemId LIMIT 1");
            $stmt->execute([':systemId' => $systemOrderId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return (int)$existing['id']; // ya existe
            }

            // 2️⃣ Obtener series del usuario
            $stmt = $this->db->prepare("SELECT series_code FROM user_series WHERE id_user = :userId LIMIT 1");
            $stmt->execute([':userId' => $userId]);
            $series = $stmt->fetchColumn();
            if (!$series) {
                throw new Exception("No se encontró serie para el usuario $userId");
            }

            // 3️⃣ Calcular siguiente order_seller_number para esa serie
            $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(order_seller_number), 0) + 1 
            FROM customer_orders
            WHERE order_seller_series = :series
        ");
            $stmt->execute([':series' => $series]);
            $nextNumber = (int)$stmt->fetchColumn();

            // 4️⃣ Insertar nuevo customer_order
            $stmt = $this->db->prepare("
            INSERT INTO customer_orders
            (id_system_order, order_seller_series, order_seller_number, created_by, customer_order_date, co_last_update_date, id_status)
            VALUES
            (:systemOrderId, :series, :number, :userId, NOW(), NOW(), 1)
        ");
            $stmt->execute([
                ':systemOrderId' => $systemOrderId,
                ':series' => $series,
                ':number' => $nextNumber,
                ':userId' => $userId
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating customer order: " . $e->getMessage());
            return null;
        }
    }


    public function saveCustomerOrderAndMarkPending(int $systemOrderId, array $data, int $userId): bool
{
    try {
        // 1️⃣ Validar cliente existente (si viene un id_customer)
        $idCustomer = !empty($data['id_customer']) ? (int)$data['id_customer'] : null;
        if ($idCustomer) {
            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM customers WHERE id = :id");
            $stmtCheck->execute([':id' => $idCustomer]);
            if ($stmtCheck->fetchColumn() == 0) {
                $idCustomer = null; // cliente inválido, lo tratamos como null
            }
        }

        // 2️⃣ Verificar si ya existe un registro en customer_orders
        $stmt = $this->db->prepare("
            SELECT id FROM customer_orders WHERE id_system_order = :systemOrderId LIMIT 1
        ");
        $stmt->execute([':systemOrderId' => $systemOrderId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 🔁 Actualizar registro existente
            $stmt = $this->db->prepare("
                UPDATE customer_orders
                SET id_customer = :id_customer,
                    name_order = :name_order,
                    purchase_order = :purchase_order,
                    attn = :attn,
                    comments = :comments,
                    co_last_update_date = NOW()
                WHERE id_system_order = :systemOrderId
            ");

            $stmt->execute([
                ':systemOrderId'  => $systemOrderId,
                ':id_customer'    => $idCustomer,
                ':name_order'     => $data['name_order'] ?? '',
                ':purchase_order' => $data['purchase_order'] ?? '',
                ':attn'           => $data['attn'] ?? '',
                ':comments'       => $data['comments'] ?? ''
            ]);
        } else {
            // 🆕 Insertar nuevo registro (cuando el usuario empieza a llenar)
            
            // 2.1️⃣ Obtener la serie del usuario
            $series = $this->getUserSeries($userId) ?? 'DEFAULT';
            
            // 2.2️⃣ Calcular el siguiente número consecutivo
            $nextNumber = $this->getNextOrderNumber($series);

            // 2.3️⃣ Insertar registro con serie y número asignados
            $stmt = $this->db->prepare("
                INSERT INTO customer_orders
                (id_system_order, order_seller_series, order_seller_number, id_customer, name_order, purchase_order, attn, comments, created_by, customer_order_date, co_last_update_date, id_status)
                VALUES
                (:systemOrderId, :series, :number, :id_customer, :name_order, :purchase_order, :attn, :comments, :userId, NOW(), NOW(), 1)
            ");

            $stmt->execute([
                ':systemOrderId'  => $systemOrderId,
                ':series'         => $series,
                ':number'         => $nextNumber,
                ':id_customer'    => $idCustomer,
                ':name_order'     => $data['name_order'] ?? '',
                ':purchase_order' => $data['purchase_order'] ?? '',
                ':attn'           => $data['attn'] ?? '',
                ':comments'       => $data['comments'] ?? '',
                ':userId'         => $userId
            ]);
        }

        // 3️⃣ Si tiene cliente válido, marcar la orden del sistema como Pendiente
        if ($idCustomer) {
            $stmt2 = $this->db->prepare("
                UPDATE system_orders
                SET has_data = 1,
                    id_status = 6
                WHERE id = :systemOrderId
            ");
            $stmt2->execute([':systemOrderId' => $systemOrderId]);
        }

        return true;
    } catch (PDOException $e) {
        error_log("❌ Error SQL en saveCustomerOrderAndMarkPending(): " . $e->getMessage());
        throw new Exception("Error SQL: " . $e->getMessage());
    }
}

public function getSupplierOrderTypes()
{
    $stmt = $this->db->query("SELECT id, order_type FROM supplier_order_types ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getConnection()
{
    return $this->db;
}


}
