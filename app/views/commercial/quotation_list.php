<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-spreadsheet text-primary"></i> Bandeja de Cotizaciones</h2>
        <a href="index.php?page=create_quotation" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nueva Cotización
        </a>
    </div>

    <!-- 🔍 BARRA DE FILTROS AVANZADA -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <div class="row g-3">
                <!-- 1. Búsqueda General (Folio o Cliente) -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Buscar (Folio / Cliente)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="filter_search" class="form-control" placeholder="Escribe folio o nombre...">
                    </div>
                </div>

                <!-- 2. Filtro por Estatus -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Estatus</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">Todas (Sin reemplazadas)</option>
                        <option value="Borrador">Borrador</option>
                        <option value="Enviada">Enviada</option>
                        <option value="Aprobada">Aprobada</option>
                        <option value="Rechazada">Rechazada</option>
                        <option value="Vencida">Vencida</option>
                        <option value="Reemplazada">Reemplazada (Histórico)</option>
                    </select>
                </div>

                <!-- 3. Filtro por Periodo Predefinido -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Periodo</label>
                    <select id="filter_period" class="form-select form-select-sm">
                        <option value="this_month" selected>Este mes</option>
                        <option value="last_3_months">Últimos 3 meses</option>
                        <option value="this_year">Este año</option>
                        <option value="all">Todas</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>

                <!-- 4. Fecha Desde (Oculto/Dinámico según periodo) -->
                <div class="col-md-2 date-custom-container d-none">
                    <label class="form-label small fw-bold text-muted mb-1">Desde</label>
                    <input type="date" id="filter_date_from" class="form-control form-control-sm">
                </div>

                <!-- 5. Fecha Hasta (Oculto/Dinámico según periodo) -->
                <div class="col-md-2 date-custom-container d-none">
                    <label class="form-label small fw-bold text-muted mb-1">Hasta</label>
                    <input type="date" id="filter_date_to" class="form-control form-control-sm">
                </div>

                <!-- 6. Botón de Limpiar Filtros -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" id="clear_filters_btn" class="btn btn-outline-secondary btn-sm w-100" title="Restaurar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE COTIZACIONES -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="quotations_table">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Cliente / Prospecto</th>
                            <th>Atención a</th>
                            <th>Total</th>
                            <th>Estatus</th>
                            <th>Vendedor</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($quotations) && is_array($quotations)): ?>
                            <?php foreach ($quotations as $quot): ?>
                                <tr data-status="<?= htmlspecialchars($quot['status'] ?? 'Borrador') ?>" data-date="<?= htmlspecialchars($quot['quotation_date']) ?>">
                                    <td><strong><?= htmlspecialchars($quot['folio']) ?></strong></td>
                                    <td><?= htmlspecialchars($quot['quotation_date']) ?></td>
                                    <td>
                                        <?php if (!empty($quot['customer_name'])): ?>
                                            <span class="badge bg-success">Cliente</span> <?= htmlspecialchars($quot['customer_name']) ?>
                                        <?php elseif (!empty($quot['prospect_name'])): ?>
                                            <span class="badge bg-info text-dark">Prospecto</span> <?= htmlspecialchars($quot['prospect_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($quot['attention_to']) ?></td>
                                    <td><strong>$<?= number_format($quot['total'], 2) ?> <?= htmlspecialchars($quot['currency']) ?></strong></td>
                                    <td>
                                        <?php
                                        $currentStatus = $quot['status'] ?? 'Borrador';
                                        $badgeBg = 'bg-secondary';
                                        $statusLabel = $currentStatus;

                                        if ($currentStatus === 'Borrador') $badgeBg = 'bg-warning text-dark';
                                        if ($currentStatus === 'Enviada') $badgeBg = 'bg-info text-dark';
                                        if ($currentStatus === 'Aprobada') $badgeBg = 'bg-success';
                                        if ($currentStatus === 'Rechazada') $badgeBg = 'bg-danger';
                                        if ($currentStatus === 'Vencida') $badgeBg = 'bg-secondary';
                                        if ($currentStatus === 'Reemplazada') {
                                            $badgeBg = 'bg-dark text-white';
                                            if (!empty($quot['replacement_folio'])) {
                                                $statusLabel = 'Reemplazada por ' . $quot['replacement_folio'];
                                            }
                                        }
                                        ?>
                                        <span class="badge <?= $badgeBg ?>">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>

                                        <?php if (!empty($quot['replacement_id'])): ?>
                                            <a href="index.php?page=edit_quotation&id=<?= $quot['replacement_id'] ?>" class="d-block fs-8 text-primary text-decoration-none mt-1" title="Ir a la versión sustituta">
                                                <i class="bi bi-box-arrow-up-right"></i> Ver nueva versión
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($quot['firstname'] . ' ' . $quot['apellidos']) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=edit_quotation&id=<?= $quot['id'] ?>" class="btn btn-outline-secondary" title="Ver / Editar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($currentStatus === 'Enviada'): ?>
                                                <a href="index.php?page=print_quotation&id=<?= $quot['id'] ?>" target="_blank" class="btn btn-outline-danger" title="Imprimir PDF">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($currentStatus === 'Aprobada'): ?>
                                                <a href="index.php?page=convert_quotation&id=<?= $quot['id'] ?>" class="btn btn-outline-success" title="Convertir a Pedido">
                                                    <i class="bi bi-arrow-right-square"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="no_results_row">
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2"></i><br>
                                    No hay cotizaciones registradas en tu equipo de trabajo.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div id="empty_search_msg" class="text-center text-muted py-4 d-none">
                    <i class="bi bi-search fs-2"></i><br>
                    No se encontraron cotizaciones que coincidan con los filtros aplicados.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT DE FILTRADO Y PERIODOS INTELIGENTES -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('filter_search');
        const statusSelect = document.getElementById('filter_status');
        const periodSelect = document.getElementById('filter_period');
        const dateFromInput = document.getElementById('filter_date_from');
        const dateToInput = document.getElementById('filter_date_to');
        const dateContainers = document.querySelectorAll('.date-custom-container');
        const clearBtn = document.getElementById('clear_filters_btn');

        const tableRows = document.querySelectorAll('#quotations_table tbody tr:not(#no_results_row)');
        const emptyMsg = document.getElementById('empty_search_msg');

        // Función para calcular rangos de fecha según el selector de Periodo
        function getDateRangeByPeriod(period) {
            const now = new Date();
            let startDate = null;
            let endDate = new Date().toISOString().split('T')[0];

            if (period === 'this_month') {
                startDate = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            } else if (period === 'last_3_months') {
                const d = new Date();
                d.setMonth(d.getMonth() - 3);
                startDate = d.toISOString().split('T')[0];
            } else if (period === 'this_year') {
                startDate = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
            } else if (period === 'all') {
                startDate = null;
                endDate = null;
            }
            return {
                startDate,
                endDate
            };
        }

        // Actualizar visibilidad de inputs de fecha personalizados
        function handlePeriodChange() {
            const val = periodSelect.value;
            if (val === 'custom') {
                dateContainers.forEach(el => el.classList.remove('d-none'));
            } else {
                dateContainers.forEach(el => el.classList.add('d-none'));
                const range = getDateRangeByPeriod(val);
                if (dateFromInput) dateFromInput.value = range.startDate || '';
                if (dateToInput) dateToInput.value = range.endDate || '';
            }
            filterTable();
        }

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedStatus = statusSelect.value;
            const currentPeriod = periodSelect.value;

            // Si el periodo es 'custom', usamos los inputs; si no, calculamos por preset
            let dateFrom = dateFromInput.value;
            let dateTo = dateToInput.value;

            if (currentPeriod !== 'custom') {
                const range = getDateRangeByPeriod(currentPeriod);
                dateFrom = range.startDate;
                dateTo = range.endDate;
            }

            let visibleCount = 0;

            tableRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const rowDate = row.getAttribute('data-date'); // YYYY-MM-DD

                // 1. Búsqueda de texto
                const matchesSearch = rowText.includes(searchTerm);

                // 2. Filtro de Estatus (Por defecto si está vacío, oculta las 'Reemplazada' para limpiar la vista)
                let matchesStatus = true;
                if (selectedStatus !== "") {
                    matchesStatus = (rowStatus === selectedStatus);
                } else {
                    // Si no seleccionó estatus específico, omitimos las reemplazadas por defecto
                    if (rowStatus === 'Reemplazada') matchesStatus = false;
                }

                // 3. Filtro de Fechas
                let matchesDate = true;
                if (rowDate) {
                    if (dateFrom && rowDate < dateFrom) matchesDate = false;
                    if (dateTo && rowDate > dateTo) matchesDate = false;
                }

                if (matchesSearch && matchesStatus && matchesDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0 && tableRows.length > 0) {
                emptyMsg.classList.remove('d-none');
            } else {
                emptyMsg.classList.add('d-none');
            }
        }

        // Event Listeners
        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (statusSelect) statusSelect.addEventListener('change', filterTable);
        if (periodSelect) periodSelect.addEventListener('change', handlePeriodChange);
        if (dateFromInput) dateFromInput.addEventListener('change', filterTable);
        if (dateToInput) dateToInput.addEventListener('change', filterTable);

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                statusSelect.value = '';
                periodSelect.value = 'this_month';
                handlePeriodChange();
            });
        }

        // Inicializar aplicando el filtro de "Este mes" por defecto al cargar la página
        handlePeriodChange();
    });
</script>