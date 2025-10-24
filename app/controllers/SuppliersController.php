<?php
require_once __DIR__ . '/../models/Supplier.php';

class SuppliersController
{
    public function getSuppliers()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $term = $_GET['term'] ?? null;

            $supplierModel = new Supplier();
            $suppliers = $supplierModel->getSuppliers($term);

            echo json_encode([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function createSupplier()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $userId = $_SESSION['user']['id'] ?? null;
        $businessName = trim($_POST['business_name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            return;
        }

        if ($businessName === '') {
            echo json_encode(['success' => false, 'message' => 'Falta el nombre del proveedor']);
            return;
        }

        require_once __DIR__ . '/../models/Supplier.php';
        $supplierModel = new Supplier();

        $supplierId = $supplierModel->createSupplier($businessName, $companyName, $userId);

        if ($supplierId) {
            echo json_encode(['success' => true, 'id' => $supplierId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el proveedor']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

}
