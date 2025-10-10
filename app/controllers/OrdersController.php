<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Order.php';

class OrdersController {

    public function myOrders() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $orders = [];

        try {
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                throw new Exception("Usuario no logueado.");
            }

            $userModel = new User();
            $orderModel = new Order();

            $teamIds = $userModel->getTeamUserIds($userId);
            $orders = $orderModel->getOrdersByUsers($teamIds);

            if (empty($orders)) {
                $orders[] = ['error' => 'No se encontraron pedidos para los IDs: ' . implode(', ', $teamIds)];
            }

        } catch (Exception $e) {
            $orders[] = ['error' => "Error al obtener pedidos: " . $e->getMessage()];
        }

        // ✅ Esta vista se carga aquí (ya con $orders disponible)
        require __DIR__ . '/../views/system/my_system_orders.php';
    }
}
