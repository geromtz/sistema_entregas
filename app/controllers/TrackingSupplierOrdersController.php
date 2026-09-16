<?php
require_once __DIR__ . '/../models/TrackingSupplierOrder.php';

/**
 * Controlador: Gestión de seguimiento (tracking) de pedidos proveedor.
 * ------------------------------------------------------------
 * Endpoints:
 *   - index.php?page=get_tracking_by_item
 *   - index.php?page=update_tracking
 */
class TrackingSupplierOrdersController
{
    /**
     * 🔍 Obtener datos de seguimiento por ID de ítem proveedor.
     * Endpoint: index.php?page=get_tracking_by_item&item_id=123
     */
    public function getTrackingByItem()
    {
        header('Content-Type: application/json; charset=utf-8');
        $itemId = $_GET['item_id'] ?? null;

        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID del ítem.']);
            return;
        }

        try {
            $trackingModel = new TrackingSupplierOrder();
            $data = $trackingModel->getTrackingByItem((int)$itemId);

            if ($data) {
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontró seguimiento para este ítem.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 🔄 Actualizar datos de seguimiento de un ítem proveedor.
     * Endpoint: index.php?page=update_tracking
     * 
     * Espera POST con:
     *  - id_item_supplier_order
     *  - tracking_number
     *  - tracking_company
     *  - arrival_date
     */
    public function updateTracking()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user']['id'] ?? null;
        $itemId = $_POST['id_item_supplier_order'] ?? null;

        if (!$itemId || !$userId) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios o sesión no iniciada.']);
            return;
        }

        try {
            $trackingModel = new TrackingSupplierOrder();
            $conn = $trackingModel->getConnection(); // Asegúrate de que tu modelo tenga este método de conexión PDO

            // 1️⃣ Consultar los datos ANTES de modificar el tracking
            $stmtOld = $conn->prepare("SELECT * FROM tracking_supplier_orders WHERE id_item_supplier_order = ?");
            $stmtOld->execute([$itemId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            // 2️⃣ Datos nuevos recibidos
            $newData = [
                'tracking_number'  => $_POST['tracking_number'] ?? null,
                'tracking_company' => $_POST['tracking_company'] ?? null,
                'arrival_date'     => $_POST['arrival_date'] ?? null,
            ];

            $success = $trackingModel->updateTracking(array_merge($newData, [
                'id_item_supplier_order' => $itemId,
                'id_branch' => $oldData['id_branch'] ?? 1
            ]));

            if ($success) {
                // 3️⃣ 📝 REGISTRAR EN LA BITÁCORA DE AUDITORÍA
                require_once __DIR__ . '/../models/AuditModel.php';
                $auditModel = new AuditModel($conn);
                $auditModel->log(
                    (int)$userId,
                    'UPDATE_TRACKING',
                    'tracking_supplier_orders',
                    (int)($oldData['id'] ?? $itemId),
                    $oldData,
                    $newData
                );

                echo json_encode(['success' => true, 'message' => 'Seguimiento actualizado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el seguimiento.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
