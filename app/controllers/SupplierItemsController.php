<?php

require_once __DIR__ . '/../models/SupplierItem.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

class SupplierItemsController extends AppController
{
    /**
     * Devuelve en JSON todas las partidas (ítems) de un pedido proveedor,
     * con su estatus y tracking. Solo accesible para choferes (id_position = 6).
     */
    public function getItemsByOrder()
    {
        // 🧩 Asegurar respuesta JSON limpia
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        ob_clean();

        // 🔒 Verificar que el usuario sea chofer (mensajero)
        RoleMiddleware::requireRole(6, true);

        // Obtener el ID del pedido proveedor
        $orderId = $_GET['id'] ?? null;

        if (!$orderId) {
            echo json_encode(['error' => 'Falta el parámetro id']);
            exit;
        }

        try {
            // Obtener las partidas con estatus y tracking
            $itemModel = new SupplierItem();
            $items = $itemModel->getItemsWithStatusAndTracking((int)$orderId);

            echo json_encode($items);
        } catch (Exception $e) {
            // Capturar y devolver error como JSON
            echo json_encode(['error' => 'Error al obtener partidas: ' . $e->getMessage()]);
        }

        exit; // Evita que el layout app.php se cargue
    }

    /**
     * Actualiza el estado de una partida (ítem) individual.
     * Usado por el chofer desde la vista de recolecciones.
     */
    public function updateItemStatus()
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    ob_clean();

    // 🔒 Solo choferes
    RoleMiddleware::requireRole(6, true);

    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$id || !$status) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }

    try {
        $itemModel = new SupplierItem();

        // Actualizar estado
        $updated = $itemModel->updateStatus((int)$id, (int)$status);

        if (!$updated) {
            throw new Exception('No se pudo actualizar el estado del ítem.');
        }

        // Obtener nombre del nuevo estatus
        $newStatusName = $itemModel->getStatusNameById((int)$status);

        echo json_encode([
            'success' => true,
            'new_status_name' => $newStatusName ?: 'Actualizado'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    exit;
}


/**
     * Devuelve una lista de productos en JSON para autocompletar en los pedidos.
     */
    public function searchProducts()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        if (function_exists('ob_clean')) {
            @ob_clean();
        }

        try {
            $term = $_GET['term'] ?? null;

            $itemModel = new SupplierItem();
            $products = $itemModel->searchProducts($term);

            echo json_encode([
                'success' => true,
                'data' => $products
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar productos: ' . $e->getMessage()
            ]);
        }

        exit;
    }

}
