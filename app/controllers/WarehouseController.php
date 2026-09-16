<?php
require_once __DIR__ . '/../models/WarehouseModel.php';
require_once __DIR__ . '/../models/AuditModel.php';

class WarehouseController
{
    private $warehouseModel;
    private $auditModel;

    public function __construct()
    {
        // Aseguramos que el usuario haya iniciado sesión siguiendo el estándar del sistema
        if (class_exists('AuthMiddleware')) {
            AuthMiddleware::checkLogin();
        }

        $this->warehouseModel = new WarehouseModel();
        $this->auditModel = new AuditModel();
    }

    public function index()
    {
        $data = $this->warehouseModel->getPendingReceptionItems();
        $view = 'logistic/warehouse_reception';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function storeEntry()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = $_POST['item_id'] ?? null;
            $logisticStatusId = $_POST['logistic_status_id'] ?? null;
            $quantity = $_POST['received_quantity'] ?? 0;
            $condition = $_POST['condition_status'] ?? 'good';

            $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;

            $rawSerials = $_POST['serials'] ?? [];
            $serials = [];
            if (!empty($rawSerials)) {
                if (is_array($rawSerials)) {
                    foreach ($rawSerials as $chunk) {
                        $lines = explode("\n", $chunk);
                        foreach ($lines as $line) {
                            $clean = trim($line);
                            if (!empty($clean)) $serials[] = $clean;
                        }
                    }
                }
            }

            if ($itemId && $logisticStatusId) {
                // Capturamos cualquier mensaje de error personalizado de la sesión generado en el modelo
                if (isset($_SESSION['reception_error_message'])) {
                    unset($_SESSION['reception_error_message']);
                }

                $success = $this->warehouseModel->processReception(
                    (int)$itemId,
                    (int)$logisticStatusId,
                    (int)$quantity,
                    $condition,
                    $serials,
                    (int)$userId
                );

                if ($success) {
                    $this->auditModel->log(
                        (int)$userId,
                        'WAREHOUSE_RECEPTION',
                        'warehouse_reception',
                        (int)$itemId,
                        null,
                        [
                            'status' => 'Received at warehouse',
                            'quantity' => $quantity,
                            'condition' => $condition,
                            'serials_count' => count($serials)
                        ]
                    );

                    echo json_encode(['success' => true, 'message' => 'Recepción de andén registrada correctamente.']);
                    exit;
                } else {
                    // Extraemos el error específico generado por la excepción del modelo
                    $errorMsg = $_SESSION['reception_error_message'] ?? 'Error al procesar la recepción en la base de datos.';
                    unset($_SESSION['reception_error_message']);
                    
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                }
            }

            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios para procesar la recepción.']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    public function stockAudit()
    {
        // 1. Obtenemos los datos del modelo
        $ordersAudit = $this->warehouseModel->getCustomerOrdersForStockAudit();
        
        // 2. Definimos la variable de la vista que consumirá el layout global
        $view = 'logistic/stock_audit';
        
        // 3. Requerimos la plantilla principal tal como lo hace el método index()
        require_once __DIR__ . '/../views/layouts/app.php';
    }
}
