<?php
require_once __DIR__ . '/../config/db.php';

class LogisticIncidentsController
{
    public function create()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE) session_start();

        $driverId = $_SESSION['user']['id'] ?? null;
        $orderId = $_POST['order_id'] ?? null;
        $type = $_POST['incident_type'] ?? 'otro';
        $desc = $_POST['description'] ?? '';

        if (!$driverId || !$orderId || empty($desc)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        // 📷 Subida de foto (si hay)
        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/incidents/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename = date('Ymd_His') . '_' . basename($_FILES['photo']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                $photoPath = 'public/uploads/incidents/' . $filename;
            }
        }

        // 🧾 Insertar en DB
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("
            INSERT INTO logistic_incidents
                (id_supplier_order, id_driver, incident_type, description, photo_path)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$orderId, $driverId, $type, $desc, $photoPath]);

        echo json_encode(['success' => true, 'photo' => $photoPath]);
        exit;
    }
}
