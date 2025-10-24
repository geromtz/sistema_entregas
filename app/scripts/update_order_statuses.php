<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../models/StatusManager.php';

echo "<pre style='font-family: monospace; background: #111; color: #0f0; padding: 1rem; border-radius: 8px;'>";
echo "🔄 Iniciando actualización de estados jerárquicos...\n";


try {
    $database = new Database();
    $db = $database->getConnection();
    $statusManager = new StatusManager($db);

    // ==========================================================
    // 1️⃣ SUPPLIER ORDERS
    // ==========================================================
    $supplierIds = $db->query("SELECT id FROM supplier_orders")->fetchAll(PDO::FETCH_COLUMN);
    echo "📦 Proveedores encontrados: " . count($supplierIds) . "\n";

    foreach ($supplierIds as $i => $id) {
        $statusManager->updateSupplierOrderStatus($id);
        if (($i + 1) % 50 === 0) echo "   → Procesados: " . ($i + 1) . "\n";
    }

    // ==========================================================
    // 2️⃣ CUSTOMER ORDERS
    // ==========================================================
    $customerIds = $db->query("SELECT id FROM customer_orders")->fetchAll(PDO::FETCH_COLUMN);
    echo "👤 Pedidos cliente encontrados: " . count($customerIds) . "\n";

    foreach ($customerIds as $i => $id) {
        $statusManager->updateCustomerOrderStatus($id);
        if (($i + 1) % 50 === 0) echo "   → Procesados: " . ($i + 1) . "\n";
    }

    // ==========================================================
    // 3️⃣ SYSTEM ORDERS
    // ==========================================================
    $systemIds = $db->query("SELECT id FROM system_orders")->fetchAll(PDO::FETCH_COLUMN);
    echo "🧾 Pedidos del sistema encontrados: " . count($systemIds) . "\n";

    foreach ($systemIds as $i => $id) {
        $statusManager->updateSystemOrderStatus($id);
        if (($i + 1) % 50 === 0) echo "   → Procesados: " . ($i + 1) . "\n";
    }

    echo "\n✅ Actualización completada correctamente.\n";
} catch (Exception $e) {
    echo "❌ Error durante la actualización: " . $e->getMessage() . "\n";
}

echo "</pre>";
