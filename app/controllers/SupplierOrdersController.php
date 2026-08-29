<?php
require_once __DIR__ . '/../models/SupplierOrder.php';
require_once __DIR__ . '/../models/SupplierItem.php';
require_once __DIR__ . '/../models/StatusManager.php'; // 🔹 nuevo: gestión de estados en cascada

class SupplierOrdersController
{
    /**
     * Guarda un pedido proveedor individual con sus ítems asociados.
     * Endpoint: index.php?page=save_single_supplier_order
     */
    public function saveSingleSupplierOrder()
    {
        file_put_contents('debug_post.txt', print_r($_POST, true));



        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user']['id'] ?? null;
        $customerOrderId = $_POST['customer_order_id'] ?? null;

        if (!$userId || !$customerOrderId) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            return;
        }

        try {
            $supplierModel = new SupplierOrder();
            $itemModel = new SupplierItem();
            $statusManager = new StatusManager($supplierModel->getConnection());

            // Obtener siguiente número de pedido proveedor dentro del pedido cliente
            $itemNumber = $supplierModel->getNextItemNumber((int)$customerOrderId);

            $supplierOrderId = $supplierModel->createSupplierOrder([
                'id_customer_order' => $customerOrderId,
                'id_status'         => 1, // pendiente
                'id_supplier'       => $_POST['mayorista'] ?? null,
                'id_branch'         => $_POST['id_branch'] ?? 1, // 🟢 Nuevo campo (valor por defecto = 1)
                'item_number'       => $itemNumber,
                'supplier_order'    => $_POST['id_order'] ?? '',
                'id_order_type'     => $_POST['id_type_order'] ?? 0,
                'created_by'        => $userId
            ]);

            if (!$supplierOrderId) {
                echo json_encode(['success' => false, 'message' => 'Error al crear el pedido proveedor.']);
                return;
            }

            // ==========================================================
            // 🔍 Buscar los ítems según el tipo de formulario activo
            // ==========================================================
            $items = null;
            foreach (['local', 'foraneo_oficina', 'foraneo_usuario_final', 'foraneo_sucursal', 'varias_sucursales'] as $grupo) {
                if (!empty($_POST[$grupo]['items']) && is_array($_POST[$grupo]['items'])) {

                    // ⚠️ Ignorar grupos con productos vacíos (validando que venga el product_id)
                    $items = array_filter($_POST[$grupo]['items'], function ($i) {
                        return !empty(trim($i['product_id'] ?? ''));
                    });

                    if (count($items) > 0) {
                        break;
                    }
                }
            }


            // ==========================================================
            // 💾 Guardar partidas (si existen)
            // ==========================================================
            if (!empty($items)) {
                foreach ($items as $item) {
                    // Evita insertar ítems vacíos
                    if (empty($item['product'])) continue;

                    $nextItemNumber = $itemModel->getNextItemNumber($supplierOrderId);

                    // 🟢 Capturamos si el check de recolección en sucursal viene marcado (1 o 0)
                    $pickupAtBranch = isset($item['pickup_at_branch']) ? 1 : 0;

                    $itemModel->createSupplierItem([
                        'id_status'           => 1,
                        'id_supplier_order'   => $supplierOrderId,
                        'quantity'            => $item['quantity'] ?? 0,
                        'item_number'         => $nextItemNumber,
                        'product_id'          => $item['product_id'] ?? null,
                        'pickup_at_branch'    => $pickupAtBranch, // 👈 Se lo pasamos al modelo
                        'created_by'          => $userId,
                    ]);

                    // 🔹 Obtener el ID del ítem recién insertado
                    $itemId = $itemModel->getConnection()->lastInsertId();

                    // ==========================================================
                    // 🧭 Si el pedido NO es local, insertar seguimiento (tracking)
                    // ==========================================================
                    if ((int)$_POST['id_type_order'] !== 1) {
                        require_once __DIR__ . '/../models/TrackingSupplierOrder.php';
                        $trackingModel = new TrackingSupplierOrder();

                        $trackingModel->createTracking([
                            'id_item_supplier_order' => $itemId,
                            'tracking_number' => $item['guide_number'] ?? null,
                            'tracking_company' => $item['courier'] ?? null,
                            'origin_branch' => $item['origin_branch_id'] ?? $item['origin_branch'] ?? null, // 👈 Asegurar que reciba el ID de la sucursal seleccionada o creada
                            'arrival_date' => $item['eta'] ?? null,
                        ]);
                    }
                }
            } else {
                error_log("⚠️ No se encontraron ítems en la solicitud POST (supplier_order_id: {$supplierOrderId}).");
            }

            // ==========================================================
            // 🚀 NUEVO: DISPARAR LOGÍSTICA AUTOMÁTICA
            // ==========================================================
            require_once __DIR__ . '/../models/LogisticModel.php';
            $logisticModel = new LogisticModel();
            $logisticModel->createFromFullOrder($supplierOrderId);

            // ==========================================================
            // 🔄 Recalcular estado del pedido proveedor y propagar
            // ==========================================================
            $statusManager->updateSupplierOrderStatus($supplierOrderId);

            echo json_encode([
                'success' => true,
                'message' => 'Pedido proveedor guardado correctamente.',
                'supplier_order_id' => $supplierOrderId,
                'item_number' => $itemNumber
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Obtiene todos los pedidos proveedor asociados a un pedido de cliente.
     * Endpoint: index.php?page=get_supplier_orders
     */
    public function getSupplierOrders()
    {
        header('Content-Type: application/json; charset=utf-8');
        $customerOrderId = $_GET['customer_order_id'] ?? null;

        if (!$customerOrderId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID del pedido del cliente.']);
            return;
        }

        try {
            $supplierModel = new SupplierOrder();
            $orders = $supplierModel->getSupplierOrdersByCustomerOrder((int)$customerOrderId);
            echo json_encode(['success' => true, 'data' => $orders]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene los ítems de un pedido proveedor.
     * Endpoint: index.php?page=get_supplier_items
     */
    public function getSupplierItems()
    {
        header('Content-Type: application/json; charset=utf-8');
        $supplierOrderId = $_GET['supplier_order_id'] ?? null;

        if (!$supplierOrderId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID del pedido proveedor.']);
            return;
        }

        try {
            $itemModel = new SupplierItem();
            $items = $itemModel->getItemsBySupplierOrder((int)$supplierOrderId);
            echo json_encode(['success' => true, 'data' => $items]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Cancela un pedido proveedor (cambia estado a 5 = cancelado).
     * Endpoint: index.php?page=cancel_supplier_order
     */
    public function cancelSupplierOrder()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $supplierOrderId = $_POST['supplier_order_id'] ?? null;

        if (!$supplierOrderId) {
            echo json_encode([
                'success' => false,
                'message' => 'Falta el ID del pedido proveedor.'
            ]);
            return;
        }

        try {
            $supplierModel = new SupplierOrder();

            // ✅ Llamar al método completo que cancela pedido + ítems + jerarquía
            $success = $supplierModel->cancelOrderAndItems((int)$supplierOrderId);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => '🛑 Pedido y partidas canceladas correctamente.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se encontró o no se pudo cancelar el pedido.'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al cancelar: ' . $e->getMessage()
            ]);
        }
    }





    public function updateSupplierOrder()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $supplierOrderId = $_POST['supplier_order_id'] ?? null;
        $supplierId      = $_POST['id_supplier'] ?? null;
        $orderNumber     = $_POST['supplier_order'] ?? null;
        $orderTypeId     = $_POST['id_order_type'] ?? null;
        $branchId        = $_POST['id_branch'] ?? null;

        if (!$supplierOrderId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID del pedido proveedor.']);
            return;
        }

        try {
            $supplierModel = new SupplierOrder();
            $conn = $supplierModel->getConnection();

            $sql = "
            UPDATE supplier_orders
            SET 
                id_supplier = :supplier_id,
                supplier_order = :supplier_order,
                id_order_type = :order_type,
                id_branch = :branch_id,
                supplier_order_last_update = NOW()
            WHERE id = :id
        ";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':supplier_id'   => $supplierId ?: null,
                ':supplier_order' => $orderNumber,
                ':order_type'    => $orderTypeId,
                ':branch_id'     => $branchId ?: 1, // ⚙️ “1 = No aplica” por defecto
                ':id'            => $supplierOrderId,
            ]);

            echo json_encode(['success' => true, 'message' => 'Pedido proveedor actualizado correctamente.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }



    public function updateSupplierItem()
    {
        header('Content-Type: application/json; charset=utf-8');
        $itemId = $_POST['item_id'] ?? null;
        $quantity = $_POST['quantity'] ?? null;
        $productId = $_POST['product_id'] ?? null; // 👈 Recibimos el ID del producto

        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Falta ID del ítem.']);
            return;
        }

        try {
            $itemModel = new SupplierItem();
            $conn = $itemModel->getConnection();
            $stmt = $conn->prepare("
            UPDATE items_supplier_order 
            SET quantity = :q, product_id = :p, items_supplier_order_last_update = NOW()
            WHERE id = :id
        ");
            $stmt->execute([
                ':q' => $quantity,
                ':p' => $productId,
                ':id' => $itemId
            ]);

            // ==========================================================
            // 🔄 Actualizar estatus del pedido y jerarquía superior
            // ==========================================================
            $stmt = $conn->prepare("SELECT id_supplier_order FROM items_supplier_order WHERE id = ?");
            $stmt->execute([$itemId]);
            $supplierOrderId = $stmt->fetchColumn();

            if ($supplierOrderId) {
                require_once __DIR__ . '/../models/StatusManager.php';
                $statusManager = new StatusManager($conn);
                $statusManager->updateSupplierOrderStatus($supplierOrderId);
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
