<?php
require_once __DIR__ . '/../../config/db.php';

/**
 * StatusManager
 * 🔄 Gestiona la propagación de estados jerárquicos:
 * items_supplier_order → supplier_orders → customer_orders → system_orders
 */
class StatusManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /* ===========================================================
       🔹 1. Actualiza estado del supplier_order según sus ítems
       =========================================================== */
    public function updateSupplierOrderStatus($supplierOrderId)
    {
        // Contar ítems por estado
        $sql = "SELECT 
                    SUM(CASE WHEN id_status = 3 THEN 1 ELSE 0 END) AS completos,
                    SUM(CASE WHEN id_status = 2 THEN 1 ELSE 0 END) AS parciales,
                    SUM(CASE WHEN id_status = 1 THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN id_status = 4 THEN 1 ELSE 0 END) AS cancelados,
                    COUNT(*) AS total
                FROM items_supplier_order
                WHERE id_supplier_order = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$supplierOrderId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$counts) return false;
        if ($counts['total'] == 0) return true; // 🧩 sin ítems aún → mantener estado actual

        $newStatus = 1; // pendiente

        if ($counts['cancelados'] == $counts['total']) {
            $newStatus = 5; // cancelado
        } elseif ($counts['completos'] == $counts['total']) {
            $newStatus = 4; // completo
        } elseif ($counts['parciales'] > 0 || ($counts['completos'] > 0 && $counts['pendientes'] > 0)) {
            $newStatus = 3; // parcial
        } elseif ($counts['pendientes'] > 0) {
            $newStatus = 2; // en proceso
        }

        $update = $this->db->prepare("
            UPDATE supplier_orders 
            SET id_status = ?, supplier_order_last_update = NOW() 
            WHERE id = ?
        ");
        $update->execute([$newStatus, $supplierOrderId]);

        // Obtener el id_customer_order relacionado
        $getParent = $this->db->prepare("SELECT id_customer_order FROM supplier_orders WHERE id = ?");
        $getParent->execute([$supplierOrderId]);
        $parent = $getParent->fetchColumn();

        // Propagar cambio hacia arriba
        if ($parent) $this->updateCustomerOrderStatus($parent);

        return true;
    }

    /* ===========================================================
       🔹 2. Actualiza estado del customer_order según suppliers
       =========================================================== */
    public function updateCustomerOrderStatus($customerOrderId)
    {
        $sql = "SELECT 
                    SUM(CASE WHEN id_status = 4 THEN 1 ELSE 0 END) AS completos,
                    SUM(CASE WHEN id_status = 3 THEN 1 ELSE 0 END) AS parciales,
                    SUM(CASE WHEN id_status = 1 THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN id_status = 5 THEN 1 ELSE 0 END) AS cancelados,
                    COUNT(*) AS total
                FROM supplier_orders
                WHERE id_customer_order = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerOrderId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$counts) return false;
        if ($counts['total'] == 0) return true; // sin proveedores aún

        $newStatus = 1; // en proceso

        if ($counts['cancelados'] == $counts['total']) {
            $newStatus = 4; // cancelado
        } elseif ($counts['completos'] == $counts['total']) {
            $newStatus = 3; // completado
        } elseif ($counts['parciales'] > 0 || ($counts['completos'] > 0 && $counts['pendientes'] > 0)) {
            $newStatus = 2; // parcial
        }

        $update = $this->db->prepare("
            UPDATE customer_orders 
            SET id_status = ?, co_last_update_date = NOW() 
            WHERE id = ?
        ");
        $update->execute([$newStatus, $customerOrderId]);

        // Obtener el id_system_order relacionado
        $getParent = $this->db->prepare("SELECT id_system_order FROM customer_orders WHERE id = ?");
        $getParent->execute([$customerOrderId]);
        $parent = $getParent->fetchColumn();

        if ($parent) $this->updateSystemOrderStatus($parent);

        return true;
    }

    /* ===========================================================
       🔹 3. Actualiza estado del system_order según customer_orders
       =========================================================== */
    public function updateSystemOrderStatus($systemOrderId)
    {
        $sql = "SELECT 
                    SUM(CASE WHEN id_status = 3 THEN 1 ELSE 0 END) AS completados,  -- customer completado
                    SUM(CASE WHEN id_status = 2 THEN 1 ELSE 0 END) AS parciales,   -- customer parcial
                    SUM(CASE WHEN id_status = 1 THEN 1 ELSE 0 END) AS en_proceso,  -- customer en proceso
                    SUM(CASE WHEN id_status = 4 THEN 1 ELSE 0 END) AS cancelados,  -- customer cancelado
                    COUNT(*) AS total
                FROM customer_orders
                WHERE id_system_order = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$systemOrderId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$counts) return false;
        if ($counts['total'] == 0) return true; // sin pedidos cliente aún

        $newStatus = 6; // Por llenar (por defecto)

        if ($counts['cancelados'] == $counts['total']) {
            $newStatus = 4; // Cancelado
        } elseif ($counts['completados'] == $counts['total']) {
            $newStatus = 2; // Finalizado
        } elseif ($counts['parciales'] > 0 || ($counts['completados'] > 0 && $counts['en_proceso'] > 0)) {
            $newStatus = 3; // Incompleto
        } elseif ($counts['en_proceso'] > 0) {
            $newStatus = 1; // Abierto / En proceso
        }

        $update = $this->db->prepare("
            UPDATE system_orders 
            SET id_status = ?, system_order_last_update = NOW()
            WHERE id = ?
        ");
        $update->execute([$newStatus, $systemOrderId]);

        return true;
    }
}


/*
|--------------------------------------------------------------------------
| 🔄 DISPARADORES DE ACTUALIZACIÓN DE ESTADOS (FUTURA ETAPA LOGÍSTICA)
|--------------------------------------------------------------------------
| Estos métodos deben activarse cuando se realicen cambios en la parte
| logística: recepción, envío, surtido parcial o completo de pedidos.
| 
| Los métodos propagan el estado en cascada:
|   items_supplier_order → supplier_orders → customer_orders → system_orders
| 
| 🔧 Para habilitarlos: descomentar y asegurarse de requerir:
|   require_once __DIR__ . '/../models/StatusManager.php';
|   $statusManager = new StatusManager($this->db);
|--------------------------------------------------------------------------
*/

/* ===========================================================
   📦 1. Cuando se actualiza un ítem de proveedor
   =========================================================== */
// $statusManager->updateSupplierOrderStatus($supplierOrderId);

/* ===========================================================
   🚚 2. Cuando se completa o actualiza un pedido de proveedor
   =========================================================== */
// $statusManager->updateCustomerOrderStatus($customerOrderId);

/* ===========================================================
   🧾 3. Cuando cambia el estado de un pedido de cliente
   =========================================================== */
// $statusManager->updateSystemOrderStatus($systemOrderId);

/* ===========================================================
   🧠 4. Flujo completo típico
   ===========================================================
   // Después de actualizar un ítem:
   $statusManager->updateSupplierOrderStatus($supplierOrderId);

   // Esto automáticamente:
   //   - recalcula estado del pedido proveedor
   //   - actualiza pedido del cliente
   //   - actualiza pedido del sistema
*/
