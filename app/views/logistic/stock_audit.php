<?php
// app/views/logistic/stock_audit.php
// Interfaz de Auditoría de Stock agrupada por Pedido de Cliente (OC) con diseño alternado (even/odd)

// Agrupamos los datos en PHP por orden de compra de cliente por defecto
$groupedOrders = [];
if (!empty($ordersAudit)) {
    foreach ($ordersAudit as $row) {
        $ocKey = $row['purchase_order'] ?? 'S-OC-' . ($row['customer_order_id'] ?? 'General');
        if (!isset($groupedOrders[$ocKey])) {
            $groupedOrders[$ocKey] = [
                'customer_order_id' => $row['customer_order_id'],
                'purchase_order' => $row['purchase_order'] ?? 'S/OC',
                'customer_name' => $row['customer_name'] ?? 'Cliente General',
                'customer_order_date' => $row['customer_order_date'] ?? date('Y-m-d'),
                'order_pdf_path' => $row['order_pdf_path'] ?? null,
                'items' => []
            ];
        }
        $groupedOrders[$ocKey]['items'][] = $row;
    }
}
?>

<div class="container-fluid px-4 py-4">
    <!-- Encabezado Principal -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark m-0"><i class="bi bi-boxes me-2 text-primary"></i>Auditoría de Stock por Pedido de Cliente</h2>
            <p class="text-muted small mb-0">Visualización jerárquica de órdenes de cliente, sus proveedores y partidas listas para facturar.</p>
        </div>
        <div>
            <a href="index.php?page=main_logistic" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Tablero
            </a>
        </div>
    </div>

    <!-- Barra de Filtros Rápidos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-4 mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchOCInput" class="form-control border-start-0 ps-0" placeholder="Buscar por OC, Cliente o Producto...">
                    </div>
                </div>
                <div class="col-md-8 text-md-end">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="bi bi-filter me-1 text-primary"></i> Mostrando órdenes agrupadas por Cliente / OC con distinción visual
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal en List Group / Cards -->
    <div class="row" id="ordersContainer">
        <?php if (!empty($groupedOrders)): ?>
            <?php $cardIndex = 0; ?>
            <?php foreach ($groupedOrders as $oc => $orderData): ?>
                <?php
                $cardIndex++;
                $isEven = ($cardIndex % 2 === 0);
                $cardBgClass = $isEven ? 'bg-white border-start border-4 border-primary' : 'bg-light border-start border-4 border-secondary';
                $collapseId = 'collapseOrder_' . $cardIndex;

                // Calcular estatus global del pedido
                $totalItemsInOC = count($orderData['items']);
                $completedItemsCount = 0;
                $hasAnyStock = false;

                foreach ($orderData['items'] as $it) {
                    $ordered = (int)$it['ordered_quantity'];
                    $received = (int)$it['received_quantity'];
                    if ($received >= $ordered) {
                        $completedItemsCount++;
                    }
                    if ($received > 0) {
                        $hasAnyStock = true;
                    }
                }

                $isAllComplete = ($completedItemsCount === $totalItemsInOC);
                $orderBadgeClass = $isAllComplete ? 'bg-success' : ($completedItemsCount > 0 ? 'bg-warning text-dark' : 'bg-secondary');
                $orderStatusLabel = $isAllComplete ? 'Completo (Listo para Facturar)' : ($completedItemsCount > 0 ? 'Surtimiento Parcial' : 'Pendiente de Stock');
                ?>
                <div class="col-12 mb-4 order-card-item" data-search-text="<?= strtolower($orderData['purchase_order'] . ' ' . $orderData['customer_name']) ?>">
                    <div class="card shadow-sm <?= $cardBgClass ?>">

                        <!-- Cabecera de la Orden del Cliente -->
                        <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">

                            <!-- Bloque izquierdo -->
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="text-uppercase fs-7 text-muted fw-bold d-block">Pedido de Cliente / OC</span>
                                    <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <!-- Zona que controla el collapse exclusivamente -->
                                        <span class="text-primary" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" style="cursor: pointer;" title="Clic para expandir/contraer">
                                            <i class="bi bi-file-earmark-text"></i> <?= htmlspecialchars($orderData['purchase_order']) ?>
                                            <i class="bi bi-chevron-down small text-muted ms-1"></i>
                                        </span>

                                        <!-- Botón de PDF -->
                                        <?php if (!empty($orderData['order_pdf_path'])): ?>
                                            <a href="index.php?page=download_purchase_order&system_order_id=<?= $orderData['customer_order_id'] ?>"
                                                target="_blank"
                                                class="btn btn-sm btn-outline-danger shadow-sm ms-2">
                                                <i class="bi bi-file-earmark-pdf-fill"></i> VER ORDEN DE COMPRA
                                            </a>
                                        <?php endif; ?>
                                    </h4>
                                    <small class="text-muted fw-semibold"><i class="bi bi-building me-1"></i> <?= htmlspecialchars($orderData['customer_name']) ?></small>
                                </div>
                            </div>

                            <!-- Bloque derecho: Estatus y Botón Único de Liberación en Cabecera -->
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge <?= $orderBadgeClass ?> fs-6 px-3 py-2">
                                    <?= $orderStatusLabel ?> (<?= $completedItemsCount ?>/<?= $totalItemsInOC ?> partidas)
                                </span>

                                <?php if ($hasAnyStock): ?>
                                    <?php if ($isAllComplete): ?>
                                        <!-- BOTÓN 1: MODAL BÁSICO (Pedido 100% Completo) -->
                                        <button class="btn btn-sm btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalComplete_<?= $cardIndex ?>">
                                            <i class="bi bi-check2-all me-1"></i> Liberar Pedido
                                        </button>
                                    <?php else: ?>
                                        <!-- BOTÓN 2: MODAL AVANZADO / PARCIAL (Faltan piezas o hay excepciones) -->
                                        <button class="btn btn-sm btn-warning text-dark shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalPartial_<?= $cardIndex ?>">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Gestionar Parcialidad
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light text-muted border" disabled>
                                        Sin Existencias
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- MODAL BÁSICO: Pedido Completo -->
                        <?php if ($hasAnyStock && $isAllComplete): ?>
                            <div class="modal fade text-start" id="modalComplete_<?= $cardIndex ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="index.php?page=process_release_order" method="POST">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title fs-6"><i class="bi bi-check-circle me-2"></i>Liberar Orden Completa</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="customer_order_id" value="<?= $orderData['customer_order_id'] ?>">
                                                <input type="hidden" name="release_type" value="complete">

                                                <p class="mb-2 text-dark">La orden <strong><?= htmlspecialchars($orderData['purchase_order']) ?></strong> del cliente <strong><?= htmlspecialchars($orderData['customer_name']) ?></strong> está 100% surtida en stock.</p>
                                                <div class="alert alert-success small mb-3">
                                                    Al confirmar, se enviará el paquete completo directamente al departamento de facturación administrativa.
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small">Comentarios opcionales para Facturación:</label>
                                                    <textarea name="logistic_comments" class="form-control form-control-sm" rows="2" placeholder="Notas adicionales..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-success btn-sm">Confirmar y Enviar a Facturación</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- MODAL AVANZADO: Control detallado por partida para Envío Parcial -->
                        <div class="modal fade text-start" id="modalPartial_<?= $cardIndex ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <form action="index.php?page=process_release_partial_order" method="POST">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title fs-6"><i class="bi bi-sliders me-2"></i>Gestión de Envío Parcial por Partida - OC: <?= htmlspecialchars($orderData['purchase_order']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="customer_order_id" value="<?= $orderData['customer_order_id'] ?>">

                                            <p class="text-muted small mb-3">El cliente autorizó un envío parcial. Ajusta o marca a continuación exactamente qué cantidades de cada partida se despachan en esta remesa y cuáles se quedan en pendiente:</p>

                                            <!-- Tabla interactiva de partidas -->
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Producto</th>
                                                            <th class="text-center">Solicitado</th>
                                                            <th class="text-center">En Stock</th>
                                                            <th class="text-center" style="width: 150px;">A Enviar Ahora</th>
                                                            <th>Estatus / Destino del Faltante</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($orderData['items'] as $idx => $item): ?>
                                                            <?php
                                                            $ordered = (int)$item['ordered_quantity'];
                                                            $received = (int)$item['received_quantity'];
                                                            // Por defecto proponemos enviar lo que hay disponible (mínimo entre lo pedido y lo físico)
                                                            $suggestedSend = min($received, $ordered);
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <input type="hidden" name="items[<?= $idx ?>][item_id]" value="<?= $item['item_id'] ?>">
                                                                    <code><?= htmlspecialchars($item['part_number']) ?></code><br>
                                                                    <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                                                </td>
                                                                <td class="text-center fw-bold"><?= $ordered ?></td>
                                                                <td class="text-center text-primary fw-bold"><?= $received ?></td>
                                                                <td class="text-center">
                                                                    <!-- Cantidad exacta que logística decide despachar en este envío -->
                                                                    <input type="number"
                                                                        name="items[<?= $idx ?>][quantity_to_send]"
                                                                        class="form-control form-control-sm text-center"
                                                                        value="<?= $suggestedSend ?>"
                                                                        min="0"
                                                                        max="<?= $received ?>" required>
                                                                </td>
                                                                <td>
                                                                    <!-- Switch o selector individual por producto -->
                                                                    <select name="items[<?= $idx ?>][action_on_missing]" class="form-select form-select-sm">
                                                                        <option value="backorder">Dejar pendiente (Backorder)</option>
                                                                        <option value="hold_all">Retener y no enviar esta partida aún</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="mb-3 mt-3">
                                                <label class="form-label fw-bold small">Comentarios y bitácora del acuerdo con el cliente:</label>
                                                <textarea name="logistic_comments" class="form-control form-control-sm" rows="2" placeholder="Comentarios..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-warning text-dark btn-sm fw-semibold">Autorizar y Enviar a Facturación</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Cuerpo con Collapse de Bootstrap (Partidas en modo lectura limpia) -->
                        <div class="collapse" id="<?= $collapseId ?>">
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush bg-transparent">
                                    <?php foreach ($orderData['items'] as $item): ?>
                                        <?php
                                        $ordered = (int)$item['ordered_quantity'];
                                        $received = (int)$item['received_quantity'];
                                        $itemComplete = $received >= $ordered;
                                        $itemBadge = $itemComplete ? 'bg-success' : ($received > 0 ? 'bg-warning text-dark' : 'bg-secondary');
                                        $itemText = $itemComplete ? 'Completo' : ($received > 0 ? 'Parcial' : 'Pendiente');
                                        ?>
                                        <div class="list-group-item p-3 item-row bg-transparent border-bottom" data-item-search="<?= strtolower($item['part_number'] . ' ' . $item['description'] . ' ' . $item['supplier_folio']) ?>">
                                            <div class="row align-items-center">
                                                <!-- Columna Proveedor / Folio -->
                                                <div class="col-md-4">
                                                    <span class="text-muted fs-7 d-block">Proveedor / Folio Compra:</span>
                                                    <strong class="text-dark"><?= htmlspecialchars($item['supplier_name'] ?? 'Sin Proveedor') ?></strong>
                                                    <span class="badge bg-white text-primary border ms-1"><?= htmlspecialchars($item['supplier_folio'] ?? 'S/F') ?></span>
                                                </div>

                                                <!-- Columna Producto / Descripción -->
                                                <div class="col-md-5">
                                                    <span class="text-muted fs-7 d-block">Producto:</span>
                                                    <code><?= htmlspecialchars($item['part_number']) ?></code>
                                                    <span class="text-dark small d-block"><?= htmlspecialchars($item['description']) ?></span>
                                                </div>

                                                <!-- Columna Cantidades -->
                                                <div class="col-md-3 text-end">
                                                    <span class="text-muted fs-7 d-block">Cant. Física en Bodega</span>
                                                    <span class="fw-bold fs-6"><?= $received ?> / <?= $ordered ?> pz</span>
                                                    <div class="mt-1">
                                                        <span class="badge <?= $itemBadge ?>"><?= $itemText ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-folder2-open display-4 text-muted mb-3"></i>
                        <h5 class="text-muted">No hay pedidos pendientes de auditar en stock.</h5>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .fs-7 {
        font-size: 0.75rem;
    }
</style>

<script>
    // Script de filtrado en tiempo real por OC o cliente
    document.getElementById('searchOCInput')?.addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let cards = document.querySelectorAll('.order-card-item');

        cards.forEach(card => {
            let text = card.getAttribute('data-search-text');
            if (text.includes(filter)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>