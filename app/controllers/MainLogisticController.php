<?php

class MainLogisticController
{
    public function apiFetchWholesaler()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';
        $model = new Logistic();

        $data = $model->getWholesalerPending();
        header('Content-Type: application/json');
        echo json_encode(['data' => $data]);
    }

    public function apiAssignBulk()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';
        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$payload || empty($payload['items']) || empty($payload['assigned_to'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            return;
        }

        $model = new Logistic();
        $ok = $model->assignBulk(
            $payload['items'],                 // [{tracking_id, type, scheduled_date}, ...]
            (int)$payload['assigned_to'],
            $payload['notes'] ?? ''
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
    }

    public function apiRegisterEvent()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';
        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$payload || empty($payload['tracking_id']) || empty($payload['status'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            return;
        }

        $model = new Logistic();
        $ok = $model->registerManualEvent(
            (int)$payload['tracking_id'],
            $payload['status'],
            $payload['notes'] ?? ''
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
    }
}
