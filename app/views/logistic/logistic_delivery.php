<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark"><i class="bi bi-truck me-2"></i>Centro de Control de Entregas</h2>
            <p class="text-muted">Gestión de despachos a clientes finales y control de surtimiento parcial o total.</p>
        </div>
    </div>

    <!-- Filtros y Agrupación Pro -->
    <div class="row mb-3 g-3 align-items-center">
        <div class="col-md-4">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="logisticsSearch" class="form-control border-start-0" placeholder="Buscar por cliente, OC, producto...">
            </div>
        </div>
        <div class="col-md-3">
            <select id="groupBySelect" class="form-select shadow-sm">
                <option value="none">-- Agrupar filas por --</option>
                <option value="order" selected>Orden de Compra / Cliente</option>
                <option value="supplier">Proveedor</option>
                <option value="seller">Vendedor</option>
            </select>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="mainLogisticsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Orden Cliente / Proyecto</th>
                            <th>Cliente</th>
                            <th>Producto / Partida</th>
                            <th>Proveedor</th>
                            <th>Estado Recolección</th>
                            <th>Estado Entrega</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data)): ?>
                            <?php foreach ($data as $row): ?>
                                <?php 
                                    $supplierName = $row['supplier_name'] ?? 'S/N';
                                    $orderKey = !empty($row['purchase_order']) ? $row['purchase_order'] : $row['customer_order_name'];
                                    $sellerName = $row['seller_name'] ?? 'Sin Vendedor';
                                    $itemId = $row['item_id'];
                                    $isCollected = (($row['pickup_status'] ?? 0) == 1);
                                    $isDelivered = (($row['delivery_status'] ?? 0) == 1);
                                ?>
                                <tr class="logistics-row"
                                    data-order="<?= htmlspecialchars($orderKey) ?>"
                                    data-supplier="<?= htmlspecialchars($supplierName) ?>"
                                    data-seller="<?= htmlspecialchars($sellerName) ?>"
                                    data-search-content="<?= strtolower(htmlspecialchars($supplierName . ' ' . $row['customer_order_name'] . ' ' . ($row['purchase_order'] ?? '') . ' ' . $row['description_product'] . ' ' . ($row['customer_name'] ?? ''))) ?>">
                                    
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['customer_order_name']) ?></div>
                                        <small class="text-muted">
                                            OC: <?= htmlspecialchars($row['purchase_order'] ?? 'S/N') ?> | Sis: #<?= htmlspecialchars($row['system_number']) ?><br>
                                            Vendedor: <span class="text-primary fw-semibold"><?= htmlspecialchars($sellerName) ?></span>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($row['customer_name'] ?? 'Público General') ?></td>
                                    <td>
                                        <div class="fw-bold"><?= (int)$row['quantity'] ?> pz</div>
                                        <small class="text-muted"><?= htmlspecialchars($row['description_product']) ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($supplierName) ?></span></td>
                                    <td>
                                        <?php if ($isCollected): ?>
                                            <span class="badge bg-success-subtle text-success border">Recolectado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border">Pendiente Proveedor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isDelivered): ?>
                                            <span class="badge bg-success text-white">Entregado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border">Por Entregar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Gestionar Entrega">
                                            <i class="bi bi-box-arrow-right"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No hay entregas pendientes o registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('logisticsSearch');
        const groupBySelect = document.getElementById('groupBySelect');
        const tableBody = document.querySelector('#mainLogisticsTable tbody');
        
        // Guardamos las filas originales en su orden inicial al cargar la página
        const originalRows = Array.from(document.querySelectorAll('.logistics-row'));

        function applyFilters() {
            const term = searchInput ? searchInput.value.toLowerCase() : '';
            const groupBy = groupBySelect ? groupBySelect.value : 'none';

            // Limpiar cabeceras de grupo anteriores
            document.querySelectorAll('.group-header').forEach(h => h.remove());

            // Primero filtramos cuáles filas deben verse
            originalRows.forEach(row => {
                const content = row.getAttribute('data-search-content') || '';
                const matchesSearch = content.includes(term);
                row.style.display = matchesSearch ? '' : 'none';
            });

            const visibleRows = originalRows.filter(r => r.style.display !== 'none');

            if (groupBy !== 'none') {
                const groups = {};

                visibleRows.forEach(row => {
                    const val = row.getAttribute(`data-${groupBy}`) || 'Otros';
                    if (!groups[val]) groups[val] = [];
                    groups[val].push(row);
                });

                // Insertamos ordenadamente según el grupo correspondiente
                Object.keys(groups).sort().forEach(groupName => {
                    const header = document.createElement('tr');
                    header.className = 'group-header table-secondary fw-bold';
                    header.innerHTML = `
                        <td colspan="7" class="py-2 ps-3 small text-uppercase text-dark">
                            <i class="bi bi-folder2-open me-2"></i>${groupName} <span class="badge bg-dark ms-2">${groups[groupName].length}</span>
                        </td>
                    `;
                    
                    // Añadimos la cabecera antes del primer elemento del grupo y reordenamos las filas visualmente
                    tableBody.appendChild(header);
                    groups[groupName].forEach(row => {
                        tableBody.appendChild(row);
                    });
                });
            } else {
                // Si no hay agrupación, devolvemos las filas visibles a su contenedor en orden
                visibleRows.forEach(row => {
                    tableBody.appendChild(row);
                });
            }
        }

        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (groupBySelect) groupBySelect.addEventListener('change', applyFilters);

        // Aplicar agrupación por defecto al cargar la página
        applyFilters();
    });
</script>