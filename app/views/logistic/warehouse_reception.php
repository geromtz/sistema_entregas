<?php
// app/views/logistic/warehouse_reception.php
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            <i class="bi bi-box-seam me-2 text-primary"></i>
            Recepción de Mercancía en Andén
        </h3>
        <span class="badge bg-primary px-3 py-2 shadow-sm">
            <span id="counterDisplay"><?= count($data ?? []) ?></span> Pendientes / Parciales por Recibir
        </span>
    </div>

    <!-- Filtros y Buscador Rápido -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body bg-light border-bottom p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Buscador</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="warehouseSearch" class="form-control border-start-0" placeholder="Proveedor, orden, número de parte...">
                    </div>
                </div>
                <div class="col-md-8 text-end">
                    <button class="btn btn-outline-secondary" onclick="location.reload()" title="Actualizar Lista"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Recepción en Andén -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="warehouseTable">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Orden Proveedor</th>
                        <th>Nº Parte / Producto</th>
                        <th>Progreso Cantidad</th>
                        <th>Chofer Asignado</th>
                        <th>Fecha Recolección</th>
                        <th class="text-center pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No hay mercancía pendiente de recibir en andén por el momento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $index => $item):
                            $supplierName = $item['supplier_name'] ?? 'S/N';
                            $orderNum = $item['supplier_order'] ?? 'S/F';
                            $partNum = $item['part_number'] ?? 'S/N';
                            $description = $item['description'] ?? '';
                            $expectedQty = (int)$item['quantity'];
                            $totalReceived = (int)($item['total_received'] ?? 0);
                            $remainingQty = $expectedQty - $totalReceived;
                            $searchContent = strtolower($supplierName . ' ' . $orderNum . ' ' . $partNum . ' ' . $description);
                        ?>
                            <tr class="warehouse-row" data-search-content="<?= htmlspecialchars($searchContent) ?>">
                                <td class="ps-4 text-center fw-bold text-muted" style="width: 50px;"><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($orderNum) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($supplierName) ?></small>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($partNum) ?></code><br>
                                    <small class="text-dark fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($description) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">Esp: <?= $expectedQty ?></span>
                                    <span class="badge bg-success">Rec: <?= $totalReceived ?></span>
                                    <?php if ($totalReceived > 0): ?>
                                        <span class="badge bg-warning text-dark">Faltan: <?= $remainingQty ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="bi bi-person-badge me-1 text-muted"></i><?= htmlspecialchars($item['driver_name'] ?? 'N/A') ?></td>
                                <td><small class="text-muted"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($item['pickup_date']) ?></small></td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openReceptionModal(
                                        <?= $item['item_id'] ?>, 
                                        <?= $item['logistic_status_id'] ?>, 
                                        <?= $expectedQty ?>, 
                                        <?= $totalReceived ?>,
                                        '<?= htmlspecialchars($partNum, ENT_QUOTES) ?>'
                                    )">
                                        <i class="bi bi-clipboard-check me-1"></i> <?= $totalReceived > 0 ? 'Continuar Recibo' : 'Recibir' ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Recepción Estricto y Sincronizado -->
<div class="modal fade" id="receptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="receptionForm" onsubmit="submitReception(event)">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down me-2"></i>Validación de Entrada - <span id="modalPartNumber"></span></h5>
                    <button type="button" class="btn-close btn-close-white" onclick="confirmCloseModal()"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="modalItemId" name="item_id">
                    <input type="hidden" id="modalLogisticStatusId" name="logistic_status_id">
                    <input type="hidden" id="modalExpectedQuantity" value="0">
                    <input type="hidden" id="modalAlreadyReceived" value="0">

                    <!-- Panel de Control Visual en Vivo -->
                    <div class="alert alert-info py-2 px-3 d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="fw-bold small text-uppercase">Total Esperado:</span> <span id="lblExpected" class="badge bg-dark">0</span>
                            <span class="fw-bold small text-uppercase ms-2">Ya Recibido:</span> <span id="lblAlreadyReceived" class="badge bg-secondary">0</span>
                            <span class="fw-bold small text-uppercase ms-2">Este Ingreso:</span> <span id="lblPhysical" class="badge bg-primary">0</span>
                            <span class="fw-bold small text-uppercase ms-2">Capturados:</span> <span id="lblCaptured" class="badge bg-success">0</span>
                        </div>
                        <div>
                            <span class="fw-bold small text-uppercase text-danger" id="lblDifferenceWarning" style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i><span id="warningText">Faltan piezas por escanear</span>
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-uppercase small text-muted">Cantidad a Recibir en este Ingreso</label>
                            <input type="number" class="form-control" id="modalQuantity" name="received_quantity" required oninput="updateReceptionProgress()">
                            <small class="text-muted" id="maxAllowedHint"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-uppercase small text-muted">Condición del Producto</label>
                            <select class="form-select" name="condition_status" id="conditionStatus" onchange="checkConditionNote(this)" required>
                                <option value="good">Bueno / Íntegro (Caja y producto OK)</option>
                                <option value="damaged">Dañado / Caja maltratada (Aceptado por urgencia)</option>
                                <option value="incomplete">Incompleto / Faltante en origen</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campo para justificar anomalías -->
                    <div class="mb-3" id="conditionNoteContainer" style="display: none;">
                        <label class="form-label fw-bold text-uppercase small text-warning">Motivo / Observación de la anomalía</label>
                        <input type="text" class="form-control form-control-sm" name="condition_note" placeholder="Ej: Caja aplastada por transporte, aceptado bajo autorización...">
                    </div>

                    <!-- Checkbox opcional para productos sin serie -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="noSerialToggle" onchange="toggleSerialInputs(this)">
                        <label class="form-check-label small fw-bold text-muted" for="noSerialToggle">
                            Este producto no requiere número de serie (Artículo a granel / genérico)
                        </label>
                    </div>

                    <!-- Contenedor Dinámico de Series -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-uppercase small text-muted">Números de Serie Escaneados</label>
                        <div id="serialInputsContainer" class="border p-3 rounded bg-light" style="max-height: 180px; overflow-y: auto;">
                            <div class="input-group mb-2 serial-row">
                                <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" class="form-control serial-input" name="serials[]" placeholder="Escanee serie y presione Enter..." onkeydown="handleSerialKeydown(event, this)" oninput="updateReceptionProgress()">
                                <button type="button" class="btn btn-outline-danger" onclick="removeSerialRow(this)"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <small class="text-muted">Debe escanear exactamente la misma cantidad de series que la cantidad ingresada.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="confirmCloseModal()">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-4 shadow-sm"><i class="bi bi-check-circle me-1"></i>Confirmar Ingreso</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Contenedor para Alertas Flotantes -->
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

<!-- Modal de Confirmación Estilizado -->
<div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark py-2">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Atención</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="mb-0 text-secondary" id="customConfirmMessage">¿Está seguro que quiere salir? Se eliminarán los registros ingresados.</p>
            </div>
            <div class="modal-footer bg-light justify-content-center py-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm px-3 fw-bold" id="customConfirmBtn">Sí, salir</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showAlert(message, type = 'success') {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;

        const alertId = 'alert-' + Date.now();
        const icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
        
        const alertHTML = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show shadow-sm d-flex align-items-center mb-2" role="alert" style="min-width: 280px; max-width: 400px;">
                <i class="bi ${icon} fs-5 me-2"></i>
                <div class="small flex-grow-1">${message}</div>
                <button type="button" class="btn-close btn-close-sm ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        alertContainer.insertAdjacentHTML('beforeend', alertHTML);

        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                const bsAlert = new bootstrap.Alert(alertElement);
                bsAlert.close();
            }
        }, 4000);
    }

    function openReceptionModal(itemId, logisticStatusId, quantity, totalReceived, partNumber) {
        const remaining = quantity - totalReceived;

        document.getElementById('modalItemId').value = itemId;
        document.getElementById('modalLogisticStatusId').value = logisticStatusId;
        document.getElementById('modalQuantity').value = remaining;
        document.getElementById('modalExpectedQuantity').value = quantity;
        document.getElementById('modalAlreadyReceived').value = totalReceived;
        document.getElementById('modalPartNumber').innerText = partNumber;
        
        document.getElementById('lblExpected').innerText = quantity;
        document.getElementById('lblAlreadyReceived').innerText = totalReceived;
        document.getElementById('lblPhysical').innerText = remaining;
        document.getElementById('lblCaptured').innerText = '0';
        document.getElementById('maxAllowedHint').innerText = `Máximo pendiente por recibir: ${remaining} pz`;
        document.getElementById('lblDifferenceWarning').style.display = 'none';

        document.getElementById('conditionStatus').value = 'good';
        document.getElementById('conditionNoteContainer').style.display = 'none';
        document.getElementById('noSerialToggle').checked = false;

        resetSerialContainer();
        updateReceptionProgress();

        var myModal = new bootstrap.Modal(document.getElementById('receptionModal'));
        myModal.show();
    }

    function handleSerialKeydown(event, input) {
        if (event.key === 'Enter') {
            event.preventDefault(); 
            
            const value = input.value.trim();
            if (value === '') return; 

            const physicalQty = parseInt(document.getElementById('modalQuantity').value) || 0;
            const currentFilled = getFilledSerialsCount();

            if (currentFilled > physicalQty) {
                showAlert('Ya se alcanzó el límite de series para la cantidad ingresada (' + physicalQty + ').', 'danger');
                input.value = '';
                return;
            }

            // Verificar duplicados locales dentro del mismo modal
            const allInputs = document.querySelectorAll('.serial-input');
            let isDuplicate = false;

            allInputs.forEach(item => {
                if (item !== input && item.value.trim().toLowerCase() === value.toLowerCase()) {
                    isDuplicate = true;
                }
            });

            if (isDuplicate) {
                showAlert('¡No se permite duplicar serie! El código <strong>"' + value + '"</strong> ya fue ingresado.', 'danger');
                input.value = ''; 
                input.focus();
                return;
            }

            // Opcional: Validación preventiva por AJAX o consulta rápida si deseas verificar duplicados históricos globales en vivo
            // Por ahora, al delegarlo al backend, la excepción que configuramos en el modelo capturará 'aaa' y lanzará la alerta exacta.

            if (currentFilled < physicalQty) {
                addSerialRow();
            }
            updateReceptionProgress();
        }
    }

    function addSerialRow(value = '') {
        const container = document.getElementById('serialInputsContainer');
        const div = document.createElement('div');
        div.className = 'input-group mb-2 serial-row';
        div.innerHTML = `
            <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
            <input type="text" class="form-control serial-input" name="serials[]" value="${value}" placeholder="Escanee serie y presione Enter..." onkeydown="handleSerialKeydown(event, this)" oninput="updateReceptionProgress()">
            <button type="button" class="btn btn-outline-danger" onclick="removeSerialRow(this)"><i class="bi bi-trash"></i></button>
        `;
        container.appendChild(div);

        const newInput = div.querySelector('.serial-input');
        newInput.focus();
    }

    function removeSerialRow(button) {
        const row = button.closest('.serial-row');
        const container = document.getElementById('serialInputsContainer');
        if (container.querySelectorAll('.serial-row').length > 1) {
            row.remove();
        } else {
            row.querySelector('.serial-input').value = '';
        }
        updateReceptionProgress();
    }

    function resetSerialContainer() {
        const container = document.getElementById('serialInputsContainer');
        if (container) {
            container.innerHTML = `
                <div class="input-group mb-2 serial-row">
                    <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" class="form-control serial-input" name="serials[]" placeholder="Escanee serie y presione Enter..." onkeydown="handleSerialKeydown(event, this)" oninput="updateReceptionProgress()">
                    <button type="button" class="btn btn-outline-danger" onclick="removeSerialRow(this)"><i class="bi bi-trash"></i></button>
                </div>
            `;
        }
    }

    function getFilledSerialsCount() {
        const capturedInputs = document.querySelectorAll('.serial-input');
        let count = 0;
        capturedInputs.forEach(input => {
            if (input.value.trim() !== '') count++;
        });
        return count;
    }

    function updateReceptionProgress() {
        const expected = parseInt(document.getElementById('modalExpectedQuantity').value) || 0;
        const alreadyReceived = parseInt(document.getElementById('modalAlreadyReceived').value) || 0;
        const physicalQty = parseInt(document.getElementById('modalQuantity').value) || 0;
        const maxAllowed = expected - alreadyReceived;
        const filledCount = getFilledSerialsCount();
        const isBulk = document.getElementById('noSerialToggle').checked;

        document.getElementById('lblPhysical').innerText = physicalQty;
        document.getElementById('lblCaptured').innerText = isBulk ? 'N/A' : filledCount;
        
        const warningEl = document.getElementById('lblDifferenceWarning');
        const warningText = document.getElementById('warningText');

        if (physicalQty > maxAllowed) {
            warningEl.style.display = 'inline-block';
            warningText.innerText = `Excede el pendiente (Máx: ${maxAllowed})`;
        } else if (physicalQty <= 0) {
            warningEl.style.display = 'inline-block';
            warningText.innerText = `Cantidad inválida`;
        } else if (!isBulk && filledCount < physicalQty) {
            warningEl.style.display = 'inline-block';
            warningText.innerText = `Faltan series por escanear (${filledCount}/${physicalQty})`;
        } else if (!isBulk && filledCount > physicalQty) {
            warningEl.style.display = 'inline-block';
            warningText.innerText = `Exceso de series (${filledCount} de ${physicalQty})`;
        } else {
            warningEl.style.display = 'none';
        }
    }

    function checkConditionNote(select) {
        const noteContainer = document.getElementById('conditionNoteContainer');
        if (select.value === 'damaged' || select.value === 'incomplete') {
            noteContainer.style.display = 'block';
        } else {
            noteContainer.style.display = 'none';
        }
    }

    function toggleSerialInputs(checkbox) {
        const container = document.getElementById('serialInputsContainer');
        const inputs = container.querySelectorAll('.serial-input');
        
        if (checkbox.checked) {
            container.style.opacity = '0.5';
            inputs.forEach(input => {
                input.value = '';
                input.disabled = true;
            });
        } else {
            container.style.opacity = '1';
            inputs.forEach(input => {
                input.disabled = false;
            });
        }
        updateReceptionProgress();
    }

    async function confirmCloseModal() {
        const serialInputs = document.querySelectorAll('.serial-input');
        let hasData = false;

        serialInputs.forEach(input => {
            if (input.value.trim() !== '') hasData = true;
        });

        if (hasData || document.getElementById('modalQuantity').value.trim() !== '') {
            // Llamamos a nuestro modal pro en lugar del confirm() feo del navegador
            const confirmed = await showConfirmModal('¿Está seguro que quiere salir? Se eliminarán los registros ingresados.');
            if (confirmed) {
                resetAndCloseModal();
            }
        } else {
            resetAndCloseModal();
        }
    }

    function resetAndCloseModal() {
        document.getElementById('receptionForm').reset();
        resetSerialContainer();

        const modalElement = document.getElementById('receptionModal');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }

    function submitReception(event) {
        event.preventDefault();

        const expected = parseInt(document.getElementById('modalExpectedQuantity').value) || 0;
        const alreadyReceived = parseInt(document.getElementById('modalAlreadyReceived').value) || 0;
        const physicalQty = parseInt(document.getElementById('modalQuantity').value) || 0;
        const maxAllowed = expected - alreadyReceived;
        const isBulk = document.getElementById('noSerialToggle').checked;
        const serialInputs = document.querySelectorAll('.serial-input');
        const enteredSerials = new Set();
        let filledCount = 0;
        let hasDuplicate = false;

        if (physicalQty <= 0 || physicalQty > maxAllowed) {
            showAlert(`Error: La cantidad ingresada (${physicalQty}) no es válida o excede el máximo pendiente (${maxAllowed}).`, 'danger');
            return;
        }

        serialInputs.forEach(input => input.classList.remove('is-invalid'));

        if (!isBulk) {
            for (let input of serialInputs) {
                const val = input.value.trim();
                if (val !== '') {
                    filledCount++;
                    if (enteredSerials.has(val)) {
                        hasDuplicate = true;
                        input.classList.add('is-invalid');
                        input.focus();
                        break;
                    }
                    enteredSerials.add(val);
                }
            }

            if (hasDuplicate) {
                showAlert('¡Atención! Hay números de serie duplicados en la lista.', 'danger');
                return;
            }

            if (filledCount !== physicalQty) {
                showAlert(`Error: La cantidad física recibida es ${physicalQty}, pero ha escaneado ${filledCount} series. Deben coincidir exactamente.`, 'danger');
                return;
            }
        }

        const formData = new FormData(document.getElementById('receptionForm'));

        fetch('index.php?page=process_warehouse_entry', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Ocurrió un error al procesar la recepción.', 'danger');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('warehouseSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                const rows = document.querySelectorAll('.warehouse-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const content = row.getAttribute('data-search-content') || '';
                    if (content.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const counter = document.getElementById('counterDisplay');
                if (counter) counter.innerText = visibleCount;
            });
        }
    });


    // Función reutilizable para mostrar un modal de confirmación profesional
    function showConfirmModal(message) {
        return new Promise((resolve) => {
            const modalEl = document.getElementById('customConfirmModal');
            const messageEl = document.getElementById('customConfirmMessage');
            const confirmBtn = document.getElementById('customConfirmBtn');
            
            messageEl.innerText = message;
            
            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();

            // Limpiamos eventos anteriores clonando el botón para evitar múltiples ejecuciones acumuladas
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

            newConfirmBtn.addEventListener('click', () => {
                bsModal.hide();
                resolve(true); // El usuario aceptó
            });

            modalEl.addEventListener('hidden.bs.modal', () => {
                resolve(false); // El usuario canceló o cerró la ventana
            }, { once: true });
        });
    }
</script>