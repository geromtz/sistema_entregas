<?php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/LogisticSupplierOrder.php';

class LogisticPickupsController extends AppController
{
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // 🔒 Acceso restringido al área de logística/facturación
        RoleMiddleware::requireRole(9);

        $view = 'logistic/pickups';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function api_list_supplier_orders()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        require_once __DIR__ . '/../models/LogisticSupplierOrder.php';
        $model = new LogisticSupplierOrder();
        $orders = $model->getAll();

        echo json_encode(['success' => true, 'data' => $orders]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener pedidos logísticos: ' . $e->getMessage()
        ]);
    }
}


public function api_list_logistic_items()
{
    header('Content-Type: application/json; charset=utf-8');

    $supplierOrderId = $_GET['supplier_order_id'] ?? null;

    if (!$supplierOrderId) {
        echo json_encode(['success' => false, 'message' => 'Falta el ID del pedido.']);
        return;
    }

    try {
        require_once __DIR__ . '/../models/LogisticSupplierOrderItem.php';
        $model = new LogisticSupplierOrderItem();
        $items = $model->getBySupplierOrder((int)$supplierOrderId);

        echo json_encode(['success' => true, 'data' => $items]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener partidas: ' . $e->getMessage()
        ]);
    }
}


}
