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
        header('Content-Type: application/json; charset=utf-8');

        $itemId = $_POST['id_item_supplier_order'] ?? null;
        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID del ítem.']);
            return;
        }

        try {
            $trackingModel = new TrackingSupplierOrder();

            $success = $trackingModel->updateTracking([
                'id_item_supplier_order' => $itemId,
                'tracking_number'        => $_POST['tracking_number'] ?? null,
                'tracking_company'       => $_POST['tracking_company'] ?? null,
                'origin_branch'          => $_POST['origin_branch'] ?? null,
                'arrival_date'           => $_POST['arrival_date'] ?? null,
            ]);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Seguimiento actualizado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el seguimiento.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
