<?php
$isLeader = (($_SESSION['user']['is_leader'] ?? 0) == 1);
?>

<?php
function renderArriboLogistica($arrivalInfo)
{
    if ($arrivalInfo === 'Producto Local') {
        return '<span class="badge arrival-bg bg-primary-subtle text-primary">Producto Local</span>';
    }

    $fechaArribo = DateTime::createFromFormat('d/m/Y', $arrivalInfo);

    if (!$fechaArribo) {
        return '<span class="badge bg-light text-dark border">' . htmlspecialchars($arrivalInfo) . '</span>';
    }

    $fechaArribo->setTime(0, 0, 0);
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);

    $esUrgente = ($fechaArribo < $hoy);
    $badgeClass = $esUrgente ? 'urgente-blink text-danger fw-bold' : 'text-dark';
    $iconClass = $esUrgente ? 'bi-exclamation-triangle-fill' : 'bi-calendar3';

    $html = '<div class="arrival-badge ' . $badgeClass . '">';
    $html .= '<i class="bi ' . $iconClass . ' me-1"></i>' . htmlspecialchars($arrivalInfo);

    if ($esUrgente) {
        $intervalo = $hoy->diff($fechaArribo);
        $diasAtraso = $intervalo->days > 0 ? $intervalo->days : 1;
        $txtDia = ($diasAtraso == 1) ? 'día' : 'días';
        $html .= '<span class="d-block small text-uppercase" style="font-size: 0.65rem; mt-n1">';
        $html .= "Atrasado $diasAtraso $txtDia</span>";
    }

    $html .= '</div>';
    return $html;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            <i class="bi bi-box-arrow-in-down-right me-2 text-primary"></i>
            Panel de Control de Recolecciones (Logística)
        </h3>
        <span class="badge bg-primary px-3 py-2 shadow-sm">
            <span id="counterDisplay"><?= count($data) ?></span> Pendientes por Asignar
        </span>
    </div>

    <!-- Filtros y Acciones -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body bg-light border-bottom p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Buscador</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="logisticsSearch" class="form-control border-start-0" placeholder="Proveedor, pedido...">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="hideAssigned" checked>
                        <label class="form-check-label small fw-bold text-primary" for="hideAssigned">Ocultar asignados</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Agrupar por</label>
                    <select id="groupBySelect" class="form-select">
                        <option value="none">Sin agrupar</option>
                        <option value="supplier">Proveedor</option>
                        <option value="type">Tipo</option>
                    </select>
                </div>

                <div class="col-md-5 d-flex justify-content-end gap-2">
                    <div id="massActions" class="d-none">
                        <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#assignmentModal" onclick="prepareAssignment()">
                            <i class="bi bi-person-plus-fill"></i> Asignar Chofer (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                    <button class="btn btn-outline-secondary" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Recolecciones -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="mainLogisticsTable">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th class="ps-4 text-center" style="width: 50px;">
                            <input type="checkbox" class="form-check-input border-white" id="selectAllItems">
                        </th>
                        <th>Origen / Sucursal</th>
                        <th>Arribo / Estatus</th>
                        <th>Pedido</th>
                        <th>Asignado A</th>
                        <th>Producto</th>
                        <th class="text-center pe-4">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row):
                        $supplierName = $row['supplier_name'] ?? 'S/N';
                        $isLocal = ($row['arrival_info'] === 'Producto Local');
                        $itemId = $row['item_id'];
                    ?>
                        <tr class="logistics-row"
                            data-supplier="<?= htmlspecialchars($supplierName) ?>"
                            data-type="<?= $isLocal ? 'Local' : 'Foráneo' ?>"
                            data-search-content="<?= strtolower(htmlspecialchars($supplierName . ' ' . $row['system_number'] . ' ' . $row['description_product'])) ?>">

                            <td class="ps-4 text-center" style="width: 50px;">
                                <input type="checkbox" class="form-check-input item-checkbox" value="<?= $itemId ?>">
                            </td>

                            <td>
                                <div class="fw-bold text-dark text-uppercase"><?= htmlspecialchars($supplierName) ?></div>
                                <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($row['pickup_location'] ?? 'N/A') ?></small>
                            </td>

                            <td>
                                <span class="badge <?= $isLocal ? 'bg-primary-subtle text-primary' : 'bg-light text-dark border' ?>">
                                    <?= renderArriboLogistica($row['arrival_info']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="badge bg-light text-dark border">#<?= htmlspecialchars($row['system_number']) ?></div>
                            </td>

                            <td>
                                <div id="assign-container-<?= $itemId ?>">
                                    <?php if (!empty($row['assigned_driver_name'])): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-info text-dark">
                                                <?= htmlspecialchars($row['assigned_driver_name']) ?>
                                            </span>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger border-0 p-0 px-1"
                                                onclick="unassignDriver(<?= $itemId ?>)"
                                                title="Quitar asignación">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Pendiente</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <div class="fw-bold text-dark"><?= (int)$row['quantity'] ?> pz</div>
                                <small class="text-dark d-block fw-bold" style="line-height: 1.2; font-size: 0.85rem;">
                                    <?= htmlspecialchars($row['description_product']) ?>
                                </small>
                            </td>

                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm border rounded bg-white">
                                    <form method="POST" action="index.php?page=mark_pickup" onsubmit="return confirm('¿Confirmas la recolección de este ítem?');">
                                        <input type="hidden" name="item_id" value="<?= $itemId ?>">
                                        <button type="submit" class="btn btn-white btn-sm text-success border-end" title="Marcar Recolectado"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <button class="btn btn-white btn-sm text-warning" onclick="reportIncident(<?= $itemId ?>, '<?= htmlspecialchars($row['description_product']) ?>')" title="Reportar Incidente">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Asignación de Chofer -->
<div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Asignar Chofer a Recolección</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small">Items seleccionados: <strong id="modalSelectedCount">0</strong></p>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="switchApoyo">
                        <label class="form-check-label small text-muted" for="switchApoyo">Mostrar Personal de Apoyo</label>
                    </div>
                    <input type="hidden" name="selected_items" id="selectedItemsInput">
                    <select name="driver_id" id="selectChofer" class="form-select border-primary shadow-sm" required>
                        <option value="">-- Seleccionar Chofer --</option>
                    </select>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">Confirmar Asignación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Incidentes -->
<div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-octagon-fill text-warning me-2"></i>Reportar Incidente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-secondary border-0 mb-3">
                    <small class="text-muted d-block text-uppercase fw-bold mb-1">Producto / Ítem:</small>
                    <span id="incidentProductText" class="fw-bold text-dark">---</span>
                </div>
                <form id="formIncidente">
                    <input type="hidden" name="item_id" id="modal_item_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Detalle del Problema:</label>
                        <textarea name="comments" id="modal_comments" class="form-control" rows="4" placeholder="¿Por qué no se pudo recolectar?" style="resize: none;"></textarea>
                    </div>
                    <div id="mensajeFeedback" class="text-center p-2 rounded d-none"></div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarIncidente" onclick="guardarIncidenteJS()" class="btn btn-warning fw-bold shadow-sm">
                    <i class="bi bi-save me-1"></i> Guardar Reporte
                </button>
            </div>
        </div>
    </div>
</div>

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
        const formAsignacion = document.querySelector('#assignmentModal form');

        const IS_LEADER = true; // Vista exclusiva de administración/logística

        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (groupBySelect) groupBySelect.addEventListener('change', applyFilters);
        if (hideAssignedSwitch) hideAssignedSwitch.addEventListener('change', applyFilters);

        function applyFilters() {
            const term = searchInput.value.toLowerCase();
            const groupBy = groupBySelect.value;
            const hideAssigned = hideAssignedSwitch ? hideAssignedSwitch.checked : false;
            const rows = Array.from(document.querySelectorAll('.logistics-row'));

            document.querySelectorAll('.group-header').forEach(h => h.remove());

            rows.forEach(row => {
                const content = row.getAttribute('data-search-content') || '';
                const cellAsignado = row.cells[4] ? row.cells[4].innerText : '';
                const isAssigned = row.classList.contains('is-assigned') || !cellAsignado.includes('Pendiente');

                const matchesSearch = content.includes(term);
                const matchesAssignedFilter = !hideAssigned || !isAssigned;

                if (matchesSearch && matchesAssignedFilter) {
                    row.style.display = '';
                    if (isAssigned && !hideAssigned) {
                        row.style.opacity = '0.7';
                        row.classList.add('table-light');
                    } else {
                        row.style.opacity = '1';
                        row.classList.remove('table-light');
                    }
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
                    header.className = 'group-header table-secondary align-middle';

                    header.innerHTML = `
                        <td class="ps-4 text-center">
                            <input type="checkbox" class="group-selector form-check-input border-dark">
                        </td>
                        <td colspan="6" class="py-2 ps-3 small text-uppercase fw-bold text-dark">
                            ${groupName} <span class="badge bg-dark ms-2">${groups[groupName].length}</span>
                        </td>
                    `;

                    tableBody.insertBefore(header, groups[groupName][0]);

                    header.querySelector('.group-selector').addEventListener('change', function() {
                        groups[groupName].forEach(row => {
                            const cb = row.querySelector('.item-checkbox');
                            if (cb) cb.checked = this.checked;
                        });
                        updateMassActions();
                    });
                });
            }
            updateCounter();
        }

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
                const display = document.getElementById('selectedCount');
                if (display) display.innerText = count;
            }
        }

        function updateCounter() {
            const counter = document.getElementById('counterDisplay');
            if (counter) {
                const visibleCount = document.querySelectorAll('.logistics-row:not([style*="display: none"])').length;
                counter.innerText = visibleCount;
            }
        }

        if (formAsignacion) {
            formAsignacion.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;

                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
                btn.disabled = true;

                const formData = new FormData(this);
                const selectedIds = formData.get('selected_items').split(',');
                const choferNombre = selectChofer.options[selectChofer.selectedIndex].text;

                fetch('index.php?page=assign_driver', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const modalEl = document.getElementById('assignmentModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();

                        selectedIds.forEach(id => {
                            const cb = document.querySelector(`.item-checkbox[value="${id}"]`);
                            if (cb) {
                                const row = cb.closest('tr');
                                if (row.cells[4]) {
                                    row.cells[4].innerHTML = `<span class="badge bg-info text-dark">${choferNombre}</span>`;
                                }
                                row.classList.add('is-assigned');
                                row.classList.add('row-assign-out');
                                setTimeout(() => applyFilters(), 500);
                            }
                        });

                        this.reset();
                        if (selectAll) selectAll.checked = false;
                        updateMassActions();
                    } else {
                        alert("Error: " + (data.error || "No se pudo completar la operación"));
                    }
                })
                .catch(err => alert("Error de conexión con el servidor"))
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        }

        function actualizarListaChoferes() {
            if (!selectChofer) return;
            const mostrarApoyo = switchApoyo ? switchApoyo.checked : false;
            selectChofer.innerHTML = '<option value="">-- Seleccionar Chofer --</option>';

            const usuarios = window.todosLosUsuarios || [];
            usuarios.forEach(user => {
                if (mostrarApoyo || user.id_position == 6) {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.firstname} ${user.apellidos}`.trim();
                    selectChofer.appendChild(option);
                }
            });
        }

        if (switchApoyo) switchApoyo.addEventListener('change', actualizarListaChoferes);

        window.todosLosUsuarios = <?= json_encode($drivers ?? []) ?>;
        actualizarListaChoferes();
        applyFilters();
    });

    function prepareAssignment() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);
        document.getElementById('selectedItemsInput').value = ids.join(',');
        document.getElementById('modalSelectedCount').innerText = ids.length;
    }

    function reportIncident(id, productName) {
        document.getElementById('modal_item_id').value = id;
        document.getElementById('incidentProductText').innerText = productName;
        const modalEl = new bootstrap.Modal(document.getElementById('incidentModal'));
        modalEl.show();
    }

    function unassignDriver(itemId) {
        if (!confirm('¿Quitar asignación de este producto?')) return;

        const container = document.getElementById(`assign-container-${itemId}`);
        const formData = new FormData();
        formData.append('item_id', itemId);

        fetch('index.php?page=unassign_driver', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = '<span class="badge bg-light text-muted border">Pendiente</span>';
                if (typeof applyFilters === "function") applyFilters();
            } else {
                alert(data.error);
            }
        });
    }
</script>

<script src="public/assets/js/logistic/saveIncident.js"></script>