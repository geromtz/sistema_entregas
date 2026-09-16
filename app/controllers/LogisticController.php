<?php
class LogisticController
{   
    

    private $model;

    public function __construct()
    {
        AuthMiddleware::checkLogin();
        $this->model = new LogisticModel();
    }

    public function myPickups()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);


        $userId   = $_SESSION['user']['id'] ?? 0;
        $isLeader = ($_SESSION['user']['is_leader'] ?? 0) == 1;

        // 1. Traer datos de recolecciones
        $data = $this->model->getPendingPickups($userId, $isLeader);

        // 2. Traer TODOS los usuarios de la DB para el modal de asignación
        $drivers = $this->model->getAvailableDrivers();

        $view = 'logistic/my_pickups';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function myDeliverys()
    {
        $data = $this->model->getPendingDeliveries();

        $view = 'logistic/my_deliverys';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function markPickup()
    {
        $itemId = $_POST['item_id'];
        $userId = $_SESSION['user']['id'];

        $this->model->markPickup($itemId, $userId);

        header("Location: index.php?page=my_pickups");
    }

    public function markDelivery()
    {
        $itemId = $_POST['item_id'];
        $userId = $_SESSION['user']['id'];

        $this->model->markDelivery($itemId, $userId);

        header("Location: index.php?page=my_deliverys");
    }

    public function mainLogistic()
    {
        $data = $this->model->getLogisticMonitor();
        $view = 'logistic/main_logistic';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function salesLogistic()
    {
        $sellerId = $_SESSION['user']['id'];

        $data = $this->model->getSalesOrdersStatus($sellerId);

        $view = 'logistic/sales_status';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function saveIncidentAjax()
    {
        // Aseguramos que la sesión esté iniciada para poder leer los datos
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $itemId  = $_POST['item_id'] ?? null;
            $comment = $_POST['comments'] ?? null;
            $type    = $_POST['type'] ?? 'pickup';
            $releaseTask = $_POST['release_task'] ?? '0';

            $userId  = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                throw new Exception("Error de sesión: Usuario no identificado.");
            }

            if (empty($itemId) || empty($comment)) {
                throw new Exception("El comentario y el ID son obligatorios.");
            }

            $statusId = $this->model->getOrCreateStatus($itemId);

            if (!$statusId) {
                throw new Exception("No se pudo establecer el estado logístico.");
            }

            $result = $this->model->insertIncident($statusId, $comment, $userId, $type);

            if ($result) {
                // Si el chofer indicó liberar la tarea por falta de tiempo o proveedor cerrado
                if ($releaseTask == '1') {
                    $this->model->removeAssignment($itemId);
                }

                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Error al guardar en base de datos.");
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function assignDriver()
    {
        // Limpiamos cualquier buffer para que no se cuele HTML accidental
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $driverId = $_POST['driver_id'] ?? null;
            $itemsRaw = $_POST['selected_items'] ?? '';

            if (!$driverId || empty($itemsRaw)) {
                throw new Exception("Faltan datos (ID de chofer o ítems).");
            }

            $itemIds = explode(',', $itemsRaw);
            foreach ($itemIds as $itemId) {
                $this->model->updateAssignment(trim($itemId), $driverId);
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit; 
    }


    public function unassignDriver()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $itemId = $_POST['item_id'] ?? null;

            if (!$itemId) {
                throw new Exception("ID de ítem faltante.");
            }

            // Llamamos al método correcto en el modelo
            $result = $this->model->removeAssignment($itemId);

            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Error al limpiar la asignación.");
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function logisticPickups()
    {
        // 1. Traer TODOS los datos de recolecciones para el panel de logística sin filtros restrictivos
        $data = $this->model->getAllPickupsForLogistics();

        // 2. Traer la lista de choferes para el modal de asignación
        $drivers = $this->model->getAvailableDrivers();

        // 3. Renderizar la vista específica de recolecciones logísticas
        $view = 'logistic/logistic_pickups';
        require __DIR__ . '/../views/layouts/app.php';
    }


    public function logisticDeliveries()
    {
        // 1. Traer datos de órdenes de clientes y su estatus de entrega/recolección
        $data = $this->model->getAllDeliveriesForLogistics();

        // 2. Renderizar la vista específica de entregas
        $view = 'logistic/logistic_delivery';
        require __DIR__ . '/../views/layouts/app.php';
    }
}
