<?php

class MainLogisticController
{
    /**
     * ==========================================================
     *   API – OBTENER LISTADO O DETALLE (ASPEL PARCIAL)
     * ==========================================================
     */
    public function apiFetchPickups()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';
        $model = new Logistic();

        header(header: 'Content-Type: application/json; charset=utf-8');

        /**
         * ======================================
         * 1) MODO DETALLE (ALTA ASPEL)
         *    GET supplier_order_id=###
         * ======================================
         */
        if (!empty($_GET['supplier_order_id'])) {

            $id = (int) $_GET['supplier_order_id'];

            $order = $model->getPickupBySupplierOrderId($id);

            echo json_encode([
                'success' => $order !== null,
                'data'    => $order ?: []
            ], JSON_UNESCAPED_UNICODE);

            return; // IMPORTANTÍSIMO
        }


        /**
         * ======================================
         * 2) MODO LISTADO DE RECOLECCIONES
         * ======================================
         */
        $filters = [
            'startDate' => $_GET['startDate'] ?? null,
            'endDate'   => $_GET['endDate'] ?? null,
            'type'      => $_GET['type'] ?? null,
            'supplier'  => trim($_GET['supplier'] ?? ''),
            'search'    => trim($_GET['search'] ?? '')
        ];

        try {
            $data = $model->getPickupOrdersGroupedFiltered($filters);

            echo json_encode([
                'success' => true,
                'data'    => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {

            error_log('apiFetchPickups error: ' . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener datos.'
            ]);
        }
    }



    /**
     * ==========================================================
     *   API – ACTUALIZAR ESTATUS DE supplier_orders
     * ==========================================================
     */
    public function apiUpdatePickupStatus()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';

        $payload = json_decode(file_get_contents('php://input'), true);

        $orderId   = (int)($payload['supplier_order_id'] ?? 0);
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



    /**
     * ==========================================================
     *   API – REGISTRAR EVENTO MANUAL (AUDITORÍA)
     * ==========================================================
     */
    public function apiRegisterEvent()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';

        $payload = json_decode(file_get_contents('php://input'), true);

        $orderId = (int)($payload['supplier_order_id'] ?? 0);
        $notes   = trim($payload['notes'] ?? '');
        $userId  = $_SESSION['user']['id'] ?? null;

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

    public function apiRegisterAspel()
    {
        AuthMiddleware::checkDepartment([4]);
        require_once __DIR__ . '/../models/Logistic.php';
        $model = new Logistic();

        $supplierOrderId = (int)($_POST['supplier_order_id'] ?? 0);
        $type            = trim($_POST['type'] ?? '');
        $notes           = trim($_POST['notes'] ?? '');
        $itemsJson       = $_POST['items'] ?? null;

        if ($supplierOrderId <= 0 || empty($type)) {
            $this->jsonFail("Datos incompletos.");
            return;
        }

        try {
            if ($type === "completa") {
                $ok = $this->saveAspelComplete($model, $supplierOrderId, $notes);
            } elseif ($type === "parcial") {
                $ok = $this->saveAspelPartial($model, $supplierOrderId, $notes, $itemsJson);
            } else {
                $this->jsonFail("Tipo de alta inválido.");
                return;
            }

            if (!$ok) {
                $this->jsonFail("No se pudo registrar el alta.");
                return;
            }

            $this->jsonSuccess("Alta registrada correctamente.");
        } catch (Throwable $e) {
            error_log("apiRegisterAspel error: " . $e->getMessage());
            $this->jsonFail("Error interno.");
        }
    }

    private function saveAspelComplete(Logistic $model, int $orderId, string $notes): bool
    {
        // 1) Actualizar status a COMPLETO (3)
        $ok = $model->updatePickupStatus($orderId, 3);
        if (!$ok) return false;

        // 2) Registrar evento
        $ok2 = $model->registerManualEvent($orderId, $notes ?: "Alta completa ASPEL", $_SESSION['user']['id'] ?? null);

        return $ok2;
    }

    private function saveAspelPartial(
        Logistic $model,
        int $supplierOrderId,
        string $notes,
        ?string $itemsJson
    ): bool {
        if (!$itemsJson) return false;

        $items = json_decode($itemsJson, true);
        if (!is_array($items) || !count($items)) return false;

        // 1) Registrar evento
        $ok = $model->registerManualEvent(
            $supplierOrderId,
            $notes ?: "Alta parcial ASPEL",
            $_SESSION['user']['id'] ?? null
        );
        if (!$ok) return false;

        // 2) Guardar productos parciales (tu tabla la definimos)
        $ok2 = $model->registerPartialItems($supplierOrderId, $items);
        if (!$ok2) return false;

        // 3) Verificar si ya están todos completados
        $order = $model->getPickupBySupplierOrderId($supplierOrderId);
        if (!$order) return false;

        $totalItems = count($order['items']);
        $completed  = 0;

        foreach ($order['items'] as $it) {
            if ($it['supplier_status'] == 3) {
                $completed++;
            }
        }

        // 4) Actualizar estado global
        if ($completed >= $totalItems) {
            $model->updatePickupStatus($supplierOrderId, 3); // completo
        } else {
            $model->updatePickupStatus($supplierOrderId, 2); // parcial
        }

        return true;
    }

    private function jsonSuccess($msg)
    {
        echo json_encode(['success' => true, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    }

    private function jsonFail($msg)
    {
        echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    }
}
