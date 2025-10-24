<?php

require_once __DIR__ . '/../models/SupplierOrder.php';
require_once __DIR__ . '/../models/SupplierItem.php';
require_once __DIR__ . '/../models/TrackingSupplierOrder.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';


class PickupsController extends AppController
{
    public function index()
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) session_start();

        // 🔒 1️⃣ Protege la vista: solo choferes (id_position = 6)
        RoleMiddleware::requireRole(6);

        $orders = [];

        try {
            // 2️⃣ Obtener pedidos "en proceso"
            $supplierOrderModel = new SupplierOrder();
            $orders = $supplierOrderModel->getOrdersInProcess();
        } catch (Exception $e) {
            $orders[] = ['error' => "Error al obtener pedidos: " . $e->getMessage()];
        }

        // 3️⃣ Cargar vista (layout principal)
        $view = 'logistic/pickups';
        require __DIR__ . '/../views/layouts/app.php';
    }
}
