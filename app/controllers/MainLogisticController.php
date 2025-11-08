<?php
class MainLogisticController
{
    public function apiFetchPickups()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';
        $model = new Logistic();
        $data = $model->getPickupOrdersGrouped();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $data]);
    }

    public function apiUpdatePickupStatus()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';
        $payload = json_decode(file_get_contents('php://input'), true);
        $orderId = (int)($payload['supplier_order_id'] ?? 0);
        $newStatus = (int)($payload['id_status'] ?? 0);
        if ($orderId <= 0 || $newStatus <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false]);
            return;
        }
        $model = new Logistic();
        $ok = $model->updatePickupStatus($orderId, $newStatus);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
    }

    public function apiRegisterEvent()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';
        $payload = json_decode(file_get_contents('php://input'), true);
        $orderId = (int)($payload['supplier_order_id'] ?? 0);
        $notes = trim($payload['notes'] ?? '');
        $userId = $_SESSION['user']['id'] ?? null;
        if ($orderId <= 0 || empty($notes)) {
            http_response_code(400);
            echo json_encode(['success' => false]);
            return;
        }
        $model = new Logistic();
        $ok = $model->registerManualEvent($orderId, $notes, $userId);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
    }
}
