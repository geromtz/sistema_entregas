<?php
require_once __DIR__ . '/../models/Product.php';

class ProductsController
{
    private $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    /**
     * Endpoint para el autocompletado en las filas de cotización
     */
    public function searchProducts()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $term = $_GET['term'] ?? '';

        try {
            $products = $this->productModel->searchProducts($term);
            echo json_encode([
                'success' => true,
                'data'    => $products
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ]);
            exit;
        }
    }

    /**
     * Endpoint para guardar el producto rápido desde el modal
     */
    public function createQuickProduct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        try {
            $productId = $this->productModel->createProduct($_POST);

            if ($productId) {
                echo json_encode([
                    'success' => true,
                    'id'      => $productId,
                    'message' => 'Producto registrado correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudo registrar el producto'
                ]);
            }
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}