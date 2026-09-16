<?php
// app/models/MainLogisticModel.php

require_once __DIR__ . '/../../config/db.php';

class MainLogisticModel
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Obtiene los contadores reales para cada una de las 7 etapas del embudo logístico
     */
    public function getStagesCounts(): array
    {
        try {
            $counts = [
                'recolecciones' => 0,
                'transito_bodega' => 0,
                'stock' => 0,
                'facturacion' => 0,
                'listos_entregar' => 0,
                'asignados_entrega' => 0,
                'entregados' => 0
            ];

            $stmt1 = $this->db->prepare("
                SELECT COUNT(DISTINCT iso.id) 
                FROM items_supplier_order iso 
                LEFT JOIN logistic_status ls ON ls.id_item_supplier_order = iso.id 
                WHERE (ls.pickup_status = 0 OR ls.pickup_status IS NULL)
            ");
            $stmt1->execute();
            $counts['recolecciones'] = (int)$stmt1->fetchColumn();

            // 2. En tránsito a bodega (Andén - reutilizando la lógica de pendientes en bodega)
            $stmt2 = $this->db->prepare("
                SELECT COUNT(DISTINCT sub.item_id) FROM (
                    SELECT iso.id as item_id, iso.quantity, COALESCE(SUM(wr.received_quantity), 0) as total_received
                    FROM items_supplier_order iso
                    JOIN logistic_status ls ON ls.id_item_supplier_order = iso.id
                    LEFT JOIN warehouse_reception wr ON wr.id_item_supplier_order = iso.id
                    WHERE ls.pickup_status = 1
                    GROUP BY iso.id
                    HAVING total_received < iso.quantity
                ) sub
            ");
            $stmt2->execute();
            $counts['transito_bodega'] = (int)$stmt2->fetchColumn();

            // 3. Pedidos en Stock (Recepción total completada en andén)
            $stmt3 = $this->db->prepare("
                SELECT COUNT(DISTINCT sub.item_id) FROM (
                    SELECT iso.id as item_id, iso.quantity, COALESCE(SUM(wr.received_quantity), 0) as total_received
                    FROM items_supplier_order iso
                    JOIN logistic_status ls ON ls.id_item_supplier_order = iso.id
                    JOIN warehouse_reception wr ON wr.id_item_supplier_order = iso.id
                    WHERE ls.pickup_status = 1
                    GROUP BY iso.id
                    HAVING total_received >= iso.quantity
                ) sub
            ");
            $stmt3->execute();
            $counts['stock'] = (int)$stmt3->fetchColumn();

            // De momento dejamos las demás etapas con consulta base o conteo adaptativo
           
            $counts['facturacion'] = 0;
            $counts['listos_entregar'] = 0;
            $counts['asignados_entrega'] = 0;
            $counts['entregados'] = 0;

            return $counts;
        } catch (PDOException $e) {
            error_log("Error in getStagesCounts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los registros detallados de la etapa seleccionada
     */
    public function getItemsByStage(string $stage): array
    {
        try {
            switch ($stage) {
                case 'recolecciones':
                    $stmt = $this->db->prepare("
                        SELECT 
                            iso.id as item_id,
                            so.supplier_order as folio,
                            s.business_name as provider_or_client,
                            p.part_number,
                            p.description,
                            iso.quantity,
                            COALESCE(iso.items_supplier_order_date, NOW()) as created_at,
                            CONCAT(u.firstname, ' ', u.apellidos) as responsible
                        FROM items_supplier_order iso
                        JOIN supplier_orders so ON so.id = iso.id_supplier_order
                        JOIN products p ON p.id = iso.product_id
                        LEFT JOIN suppliers s ON s.id = so.id_supplier
                        LEFT JOIN logistic_status ls ON ls.id_item_supplier_order = iso.id
                        LEFT JOIN users u ON u.id = ls.pickup_by
                        WHERE (ls.pickup_status = 0 OR ls.pickup_status IS NULL)
                        ORDER BY iso.id DESC
                    ");
                    $stmt->execute();
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);

                case 'transito_bodega':
                    $warehouseModel = new WarehouseModel();
                    $pendingItems = $warehouseModel->getPendingReceptionItems();
                    
                    $formatted = [];
                    foreach ($pendingItems as $item) {
                        $formatted[] = [
                            'item_id' => $item['item_id'],
                            'folio' => $item['supplier_order'],
                            'provider_or_client' => $item['supplier_name'],
                            'part_number' => $item['part_number'],
                            'description' => $item['description'],
                            'quantity' => $item['quantity'] - $item['total_received'],
                            'created_at' => $item['pickup_date'] ?? date('Y-m-d H:i:s'),
                            'responsible' => $item['driver_name']
                        ];
                    }
                    return $formatted;

                case 'stock':
                    $stmt = $this->db->prepare("
                        SELECT 
                            iso.id as item_id,
                            so.supplier_order as folio,
                            s.business_name as provider_or_client,
                            p.part_number,
                            p.description,
                            iso.quantity,
                            MAX(wr.created_at) as created_at,
                            CONCAT(u.firstname, ' ', u.apellidos) as responsible
                        FROM items_supplier_order iso
                        JOIN logistic_status ls ON ls.id_item_supplier_order = iso.id
                        JOIN supplier_orders so ON so.id = iso.id_supplier_order
                        JOIN products p ON p.id = iso.product_id
                        LEFT JOIN suppliers s ON s.id = so.id_supplier
                        LEFT JOIN users u ON u.id = ls.pickup_by
                        JOIN warehouse_reception wr ON wr.id_item_supplier_order = iso.id
                        WHERE ls.pickup_status = 1
                        GROUP BY iso.id
                        HAVING SUM(wr.received_quantity) >= iso.quantity
                        ORDER BY iso.id DESC
                    ");
                    $stmt->execute();
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);

                default:
                    return [];
            }
        } catch (PDOException $e) {
            error_log("Error in getItemsByStage: " . $e->getMessage());
            return [];
        }
    }
}