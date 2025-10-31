<?php
require_once __DIR__ . '/../models/LogisticInit.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

class LogisticInitController
{
    public function initialize()
    {
        RoleMiddleware::requireRole([9], true); // Solo logística/facturista
        header('Content-Type: application/json; charset=utf-8');

        try {
            $model = new LogisticInit();
            $result = $model->initializeFromSupplierOrders();

            echo json_encode([
                'success' => true,
                'message' => 'Registros logísticos creados correctamente.',
                'data' => $result
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al inicializar: ' . $e->getMessage()
            ]);
        }
    }
}
