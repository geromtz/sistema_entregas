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

    // Pedidos del mayorista pendientes (sin asignación activa)
public function getWholesalerPending(): array
{
    $sql = "
        SELECT 
            tso.id                     AS tracking_id,
            so.system_number           AS system_number,
            c.business_name            AS customer,
            s.business_name            AS supplier,
            iso.description_product    AS item_desc,
            spo.id_status              AS supplier_status,
            spo.id_order_type          AS order_type,
            tso.tracking_number        AS tracking_number,
            tso.arrival_date           AS arrival_date
        FROM supplier_orders spo
        JOIN customer_orders co         ON co.id = spo.id_customer_order
        JOIN system_orders so           ON so.id = co.id_system_order
        JOIN customers c                ON c.id = co.id_customer
        JOIN suppliers s                ON s.id = spo.id_supplier
        JOIN items_supplier_order iso   ON iso.id_supplier_order = spo.id
        LEFT JOIN tracking_supplier_orders tso 
               ON tso.id_item_supplier_order = iso.id
        LEFT JOIN logistic_assignments la 
               ON la.id_tracking = tso.id 
               AND la.status IN ('pending','in_progress')
        WHERE 
            spo.id_status IN (1,2)               -- pendiente o en progreso
            AND (tso.id IS NOT NULL OR spo.id_order_type IN (2,3,4,5))
            AND (la.id IS NULL OR la.status = 'completed')
        ORDER BY spo.id DESC
        LIMIT 200
    ";

    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


    public function getDriverList(): array
{
    $stmt = $this->db->query("
        SELECT 
            id, 
            CONCAT(firstname, ' ', apellidos) AS fullname
        FROM users
        WHERE id_position IN (SELECT id FROM positions WHERE name LIKE '%Chofer%' OR name LIKE '%Driver%')
           OR parent_id = 4 -- opcional, si quieres limitar a logística
        ORDER BY firstname
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    // items = [ {tracking_id, type, scheduled_date}, ... ]
    public function assignBulk(array $items, int $assignedTo, string $notes): bool
    {
        $this->db->beginTransaction();
        try {
            $ins = $this->db->prepare("
                INSERT INTO logistic_assignments
                    (id_tracking, assigned_to, type, status, scheduled_date, notes, created_by)
                VALUES
                    (?, ?, ?, 'pending', ?, ?, ?)
            ");

            $upd = $this->db->prepare("
                UPDATE tracking_supplier_orders SET status = 'in_progress' WHERE id = ?
            ");

            foreach ($items as $it) {
                $ins->execute([
                    (int)$it['tracking_id'],
                    $assignedTo,
                    $it['type'] ?? 'delivery',
                    $it['scheduled_date'] ?? null,
                    $notes,
                    $_SESSION['user']['id'] ?? null
                ]);

                $upd->execute([(int)$it['tracking_id']]);
            }

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('assignBulk error: '.$e->getMessage());
            return false;
        }
    }

    public function registerManualEvent(int $trackingId, string $status, string $notes): bool
    {
        $this->db->beginTransaction();
        try {
            // Auditoría
            $audit = $this->db->prepare("
                INSERT INTO system_order_audit (entity, entity_id, action, notes, user_id, created_at)
                VALUES ('tracking_supplier_orders', ?, ?, ?, ?, NOW())
            ");
            $audit->execute([$trackingId, $status, $notes, $_SESSION['user']['id'] ?? null]);

            // Actualiza estado actual
            $upd = $this->db->prepare("UPDATE tracking_supplier_orders SET status = ? WHERE id = ?");
            $upd->execute([$status, $trackingId]);

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('registerManualEvent error: '.$e->getMessage());
            return false;
        }
    }
}
