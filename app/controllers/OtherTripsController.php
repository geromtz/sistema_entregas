<?php
require_once __DIR__ . '/../models/OtherTrip.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/AppController.php';

class OtherTripsController extends AppController
{
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $trips = [];
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            echo "<div class='alert alert-danger mt-4'>No se pudo identificar al usuario.</div>";
            return;
        }

        try {
            $model = new OtherTrip();
            $trips = $model->getAll($userId);
        } catch (Exception $e) {
            $trips[] = ['error' => "Error al obtener actividades: " . $e->getMessage()];
        }

        $view = 'logistic/other_trips';
        require __DIR__ . '/../views/layouts/app.php';
    }


    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }

        $data = [
            'id_driver' => $_SESSION['user']['id'] ?? null,
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        if (empty($data['id_driver']) || empty($data['title'])) {
            echo json_encode(['success' => false, 'error' => 'Campos requeridos faltantes']);
            return;
        }

        try {
            $model = new OtherTrip();
            $result = $model->create($data);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 🔹 Chofer actualiza estado
     */
    public function updateStatus()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }

        $data = [
            'id' => $_POST['id'] ?? null,
            'status' => $_POST['status'] ?? null,
            'driver_comment' => trim($_POST['driver_comment'] ?? ''),
            'collected_by' => $_SESSION['user']['id'] ?? null
        ];

        if (empty($data['id']) || empty($data['status'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        try {
            $model = new OtherTrip();
            $result = $model->updateStatus($data);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 🔹 Usuario actualiza título o descripción
     */
    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }

        $data = [
            'id' => $_POST['id'] ?? null,
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        if (empty($data['id']) || empty($data['title'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        try {
            $model = new OtherTrip();
            $result = $model->update($data);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
