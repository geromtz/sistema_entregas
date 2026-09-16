<?php
// app/views/logistic/logistic_pickups.php
$isLeader = (($_SESSION['user']['is_leader'] ?? 0) == 1);
?>
<style>
    .table-warning-subtle {
        background-color: rgba(255, 193, 7, 0.08) !important;
        border-left: 4px solid #ffc107;
    }
</style>

<div class="container-fluid px-4 mt-4">
    <!-- Encabezado y Métricas Pro -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-kanban text-primary me-2"></i>Centro de Control de Recolecciones
            </h2>
            <p class="text-muted small mb-0">Gestión global, asignación de rutas y monitoreo de carga para proveedores.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm shadow-sm" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Tarjetas de Resumen (KPIs) -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-primary border-4">
                <div class="card-body">
                    <span class="text-muted small text-uppercase fw-bold">Total en Pantalla</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1" id="kpiTotal"><?= count($data) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-warning border-4">
                <div class="card-body">
                    <span class="text-muted small text-uppercase fw-bold">Sin Asignar (Pendientes)</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1" id="kpiUnassigned">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-info border-4">
                <div class="card-body">
                    <span class="text-muted small text-uppercase fw-bold">Asignados a Ruta</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1" id="kpiAssigned">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-success border-4">
                <div class="card-body">
                    <span class="text-muted small text-uppercase fw-bold">Completados</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1">--</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Herramientas y Filtros Avanzados -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light rounded p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="logisticsSearch" class="form-control border-start-0" placeholder="Buscar por proveedor, orden, producto...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="groupBySelect" class="form-select shadow-sm">
                        <option value="none">-- Agrupar filas por --</option>
                        <option value="supplier">Proveedor</option>
                        <option value="type">Tipo de Arribo</option>
                        <option value="order">Pedido Mayorista</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" id="hideAssigned">
                        <label class="form-check-label small fw-bold text-secondary" for="hideAssigned">Ocultar asignados</label>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <div id="massActions" class="d-none">
                        <button type="button" class="btn btn-primary fw-bold shadow-sm w-100" data-bs-toggle="modal" data-bs-target="#assignmentModal" onclick="prepareAssignment()">
                            <i class="bi bi-truck me-1"></i> Asignar Selección (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Principal de Gestión -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="mainLogisticsTable">
                <thead class="table-dark text-uppercase fs-7">
                    <tr>
                        <th class="ps-4 text-center" style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="selectAllItems">
                        </th>
                        <th>Proveedor / Ubicación</th>
                        <th>Tipo / Arribo</th>
                        <th>Pedido Mayorista</th>
                        <th>Chofer / Responsable</th>
                        <th>Detalle de Producto</th>
                        <th class="text-center pe-4">Acciones de Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row):
                        $supplierName = $row['supplier_name'] ?? 'S/N';
                        $isLocal = ($row['arrival_info'] === 'Producto Local');
                        $itemId = $row['item_id'];
                        $isAssigned = !empty($row['assigned_driver_name']);
                        $hasIncidents = (!empty($row['has_incident']) && $row['has_incident'] == 1) || !empty($row['incident_history']);

                        // Determinamos el estilo de la fila: si está asignado o si tiene un incidente de rebote
                        $rowClass = '';
                        if ($isAssigned) {
                            $rowClass = 'table-light text-muted';
                        } elseif ($hasIncidents) {
                            $rowClass = 'table-warning-subtle'; // Tono sutil amarillo para alertas de rebote
                        }
                    ?>
                        <tr class="logistics-row <?= $rowClass ?>"
                            data-supplier="<?= htmlspecialchars($supplierName) ?>"
                            data-type="<?= $isLocal ? 'Local' : 'Foráneo' ?>"
                            data-order="<?= htmlspecialchars($row['system_number']) ?>"
                            data-assigned="<?= $isAssigned ? '1' : '0' ?>"
                            data-search-content="<?= strtolower(htmlspecialchars($supplierName . ' ' . $row['system_number'] . ' ' . $row['description_product'])) ?>">

                            <td class="ps-4 text-center">
                                <input type="checkbox" class="form-check-input item-checkbox" value="<?= $itemId ?>">
                            </td>
                            <td>
                                <div class="fw-bold text-dark text-uppercase"><?= htmlspecialchars($supplierName) ?></div>
                                <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                    <span class="badge bg-light text-secondary border"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['pickup_location'] ?? 'Matriz') ?></span>

                                    <!-- Alerta visual e historial si fue rebotado por el chofer -->
                                    <?php if ($hasIncidents): ?>
                                        <span class="badge bg-warning text-dark border border-warning"
                                            data-bs-toggle="popover"
                                            data-bs-html="true"
                                            data-bs-trigger="hover focus"
                                            title="<i class='bi bi-exclamation-triangle-fill me-1'></i> Historial de Incidentes"
                                            data-bs-content="<?= htmlspecialchars($row['incident_history']) ?>"
                                            style="cursor: pointer;">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Con Incidente
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($isLocal): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Local</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Foráneo (<?= htmlspecialchars($row['arrival_info']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold text-dark">#<?= htmlspecialchars($row['system_number']) ?></span>
                            </td>
                            <td>
                                <div id="assign-container-<?= $itemId ?>">
                                    <?php if (!empty($row['pickup_status']) && $row['pickup_status'] == 1): ?>
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 mb-1 w-fit">
                                                <i class="bi bi-check-circle-fill me-1"></i>Recolectado
                                            </span>
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                Por: <strong><?= htmlspecialchars($row['pickup_user_name'] ?? 'N/D') ?></strong><br>
                                                El: <?= htmlspecialchars($row['pickup_date']) ?>
                                            </small>
                                        </div>
                                    <?php elseif ($isAssigned): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                                <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($row['assigned_driver_name']) ?>
                                            </span>
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" onclick="unassignDriver(<?= $itemId ?>)" title="Quitar asignación">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">Sin Asignar</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= (int)$row['quantity'] ?> pz</div>
                                <span class="small text-muted"><?= htmlspecialchars($row['description_product']) ?></span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm">
                                    <button type="button" class="btn btn-white btn-sm border text-primary" data-bs-toggle="modal" data-bs-target="#assignmentModal" onclick="quickAssign(<?= $itemId ?>)" title="Asignar individual o excepción">
                                        <i class="bi bi-person-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-white btn-sm border text-danger" onclick="reportIncident(<?= $itemId ?>, '<?= htmlspecialchars($row['description_product']) ?>')" title="Ver / Reportar Incidente o Excepción">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>



    <!-- Modal de Asignación / Excepciones (Vacaciones o Emergencias) -->
    <div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form action="" method="POST" id="formAsignacionModal">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title"><i class="bi bi-truck-front me-2"></i>Asignación de Ruta / Cobertura</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-primary py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i> Partidas seleccionadas: <strong id="modalSelectedCount">0</strong>
                        </div>
                        <!-- Switch clave para coberturas o personal temporal -->
                        <div class="form-check form-switch mb-3 bg-light p-2 rounded border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="switchApoyo">
                            <label class="form-check-label small fw-bold text-dark" for="switchApoyo">
                                <i class="bi bi-shield-exclamation text-warning me-1"></i> Habilitar personal de apoyo (Vacaciones / Emergencia)
                            </label>
                        </div>
                        <input type="hidden" name="selected_items" id="selectedItemsInput">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Asignar a Responsable:</label>
                            <select name="driver_id" id="selectChofer" class="form-select border-primary shadow-sm" required>
                                <option value="">-- Seleccionar Responsable --</option>
                            </select>
                            <div class="form-text text-muted small mt-1">Si no hay choferes, usa personal interno para evitar vueltas huérfanas y respalda con una nota de incidente si es necesario.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Guardar Asignación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts de control pro -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('logisticsSearch');
            const groupBySelect = document.getElementById('groupBySelect');
            const hideAssignedSwitch = document.getElementById('hideAssigned');
            const tableBody = document.querySelector('#mainLogisticsTable tbody');
            const selectAll = document.getElementById('selectAllItems');
            const massActions = document.getElementById('massActions');
            const switchApoyo = document.getElementById('switchApoyo');
            const selectChofer = document.getElementById('selectChofer');
            const formAsignacion = document.getElementById('formAsignacionModal');

            function updateKPIs() {
                const rows = document.querySelectorAll('.logistics-row');
                let unassigned = 0;
                let assigned = 0;
                rows.forEach(r => {
                    if (r.getAttribute('data-assigned') === '1') assigned++;
                    else unassigned++;
                });
                document.getElementById('kpiUnassigned').innerText = unassigned;
                document.getElementById('kpiAssigned').innerText = assigned;
            }

            function applyFilters() {
                const term = searchInput.value.toLowerCase();
                const groupBy = groupBySelect.value;
                const hideAssigned = hideAssignedSwitch.checked;
                const rows = Array.from(document.querySelectorAll('.logistics-row'));

                document.querySelectorAll('.group-header').forEach(h => h.remove());

                rows.forEach(row => {
                    const content = row.getAttribute('data-search-content') || '';
                    const isAssigned = row.getAttribute('data-assigned') === '1';

                    const matchesSearch = content.includes(term);
                    const matchesAssigned = !hideAssigned || !isAssigned;

                    if (matchesSearch && matchesAssigned) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (groupBy !== 'none') {
                    const visibleRows = rows.filter(r => r.style.display !== 'none');
                    const groups = {};

                    visibleRows.forEach(row => {
                        const val = row.getAttribute(`data-${groupBy}`) || 'Otros';
                        if (!groups[val]) groups[val] = [];
                        groups[val].push(row);
                    });

                    Object.keys(groups).sort().forEach(groupName => {
                        const header = document.createElement('tr');
                        header.className = 'group-header table-secondary fw-bold';
                        header.innerHTML = `
                        <td colspan="7" class="py-2 ps-3 small text-uppercase text-dark">
                            <i class="bi bi-folder2-open me-2"></i>${groupName} <span class="badge bg-dark ms-2">${groups[groupName].length}</span>
                        </td>
                    `;
                        tableBody.insertBefore(header, groups[groupName][0]);
                    });
                }
                updateKPIs();
            }

            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (groupBySelect) groupBySelect.addEventListener('change', applyFilters);
            if (hideAssignedSwitch) hideAssignedSwitch.addEventListener('change', applyFilters);

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.item-checkbox').forEach(cb => {
                        const row = cb.closest('tr');
                        if (row && row.style.display !== 'none') cb.checked = this.checked;
                    });
                    updateMassActions();
                });
            }

            if (tableBody) {
                tableBody.addEventListener('change', (e) => {
                    if (e.target.classList.contains('item-checkbox')) updateMassActions();
                });
            }

            function updateMassActions() {
                const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
                const count = checkedBoxes.length;
                if (massActions) {
                    massActions.classList.toggle('d-none', count === 0);
                    document.getElementById('selectedCount').innerText = count;
                }
            }

            // Carga dinámica de choferes y personal de apoyo (vacaciones/emergencias)
            window.todosLosUsuarios = <?= json_encode($drivers ?? []) ?>;

            function actualizarListaChoferes() {
                if (!selectChofer) return;
                const mostrarApoyo = switchApoyo ? switchApoyo.checked : false;
                selectChofer.innerHTML = '<option value="">-- Seleccionar Responsable --</option>';

                window.todosLosUsuarios.forEach(user => {
                    // Si el switch está activo, mostramos también posiciones de apoyo/admin/logística
                    if (mostrarApoyo || user.id_position == 6) {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = `${user.firstname} ${user.apellidos}`.trim();
                        selectChofer.appendChild(option);
                    }
                });
            }

            if (switchApoyo) switchApoyo.addEventListener('change', actualizarListaChoferes);
            actualizarListaChoferes();
            updateKPIs();

            // AJAX de envío para asignación limpia
            if (formAsignacion) {
                formAsignacion.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    fetch('index.php?page=assign_driver', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert("Error: " + (data.error || "No se pudo completar la asignación"));
                            }
                        })
                        .catch(() => alert("Error de conexión con el servidor"));
                });
            }
        });

        function prepareAssignment() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            document.getElementById('selectedItemsInput').value = ids.join(',');
            document.getElementById('modalSelectedCount').innerText = ids.length;
        }

        function quickAssign(itemId) {
            document.getElementById('selectedItemsInput').value = itemId;
            document.getElementById('modalSelectedCount').innerText = '1 (Individual)';
        }

        function unassignDriver(itemId) {
            if (!confirm('¿Deseas retirar la asignación a este ítem?')) return;
            const formData = new FormData();
            formData.append('item_id', itemId);
            fetch('index.php?page=unassign_driver', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.error);
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
    </script>