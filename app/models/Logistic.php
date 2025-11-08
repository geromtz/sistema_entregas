<?php
require_once __DIR__ . '/../../config/db.php';

class Logistic
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getPickupOrdersGrouped(): array
    {
        $sql = "
            SELECT 
                so.system_number,
                so.system_number_date,
                so.system_order_series,
                c.business_name AS customer,
                s.business_name AS supplier,
                spo.supplier_order AS supplier_order_number,
                iso.description_product AS item_desc,
                spo.id_order_type AS order_type,
                spo.id_status AS supplier_status,
                spo.supplier_order_date AS order_date
            FROM supplier_orders spo
            JOIN customer_orders co ON co.id = spo.id_customer_order
            JOIN system_orders so ON so.id = co.id_system_order
            JOIN customers c ON c.id = co.id_customer
            JOIN suppliers s ON s.id = spo.id_supplier
            JOIN items_supplier_order iso ON iso.id_supplier_order = spo.id
            WHERE spo.id_status IN (1, 2, 3)
            ORDER BY so.system_number DESC, spo.supplier_order_date DESC
        ";

        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];

        foreach ($rows as $r) {
            $key = $r['system_number'];

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'system_number' => $r['system_number'],
                    'system_number_date' => $r['system_number_date'],
                    'system_order_series' => $r['system_order_series'],
                    'customer' => $r['customer'],
                    'orders' => [],
                    'supplier' => $r['supplier'],
                    'supplier_order_number' => $r['supplier_order_number']
                ];
            }

            $grouped[$key]['orders'][] = $r;
        }

        foreach ($grouped as &$g) {
            $statuses = array_column($g['orders'], 'supplier_status');
            $types = array_column($g['orders'], 'order_type');

            $total = count($statuses);
            $completed = count(array_filter($statuses, fn($s) => (int)$s === 3));

            if ($completed === $total) {
                $g['global_status'] = 3; // completo
            } elseif ($completed > 0 && $completed < $total) {
                $g['global_status'] = 2; // parcial
            } else {
                $g['global_status'] = 1; // en proceso
            }

            $g['order_type'] = $types[0] ?? 0;
        }

        return $grouped;
    }

    public function updatePickupStatus(int $supplierOrderId, int $newStatus): bool
    {
        $stmt = $this->db->prepare("
            UPDATE supplier_orders 
            SET id_status = :newStatus,
                supplier_order_last_update = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'newStatus' => $newStatus,
            'id' => $supplierOrderId
        ]);
    }

    public function registerManualEvent(int $orderId, string $notes, ?int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO system_order_audit (entity, entity_id, action, notes, user_id, created_at)
                VALUES ('supplier_orders', ?, 'manual_event', ?, ?, NOW())
            ");
            return $stmt->execute([$orderId, $notes, $userId]);
        } catch (Throwable $e) {
            error_log('registerManualEvent error: ' . $e->getMessage());
            return false;
        }
    }
}
