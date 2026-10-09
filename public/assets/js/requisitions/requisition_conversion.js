document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('itemsContainer');
    let dynamicRowIdx = parseInt(itemsContainer?.dataset.initialCount) || 0;

    // Instancia del Modal de Bootstrap (asumiendo que existe en tu vista el HTML del modal de confirmación)
    const confirmModalEl = document.getElementById('quotationActionConfirmModal');
    const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;

    function showCustomModal(title, text, callback) {
        if (!confirmModal || !confirmModalEl) {
            // Fallback por si el modal no estuviera cargado en alguna vista
            if (confirm(text)) { callback(); }
            return;
        }
        document.getElementById('confirmModalTitle').innerHTML = `<i class="bi bi-shield-exclamation text-warning me-2"></i> ${title}`;
        document.getElementById('confirmModalText').textContent = text;
        
        const confirmBtn = document.getElementById('confirmActionBtn');
        // Limpiar eventos anteriores clonando el botón
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

        newBtn.addEventListener('click', () => {
            confirmModal.hide();
            callback();
        });

        confirmModal.show();
    }

    function setupKeyboardNavigation(inputEl, listEl) {
        let selectedIndex = -1;
        function resetSelection() { selectedIndex = -1; updateHighlight(); }
        function updateHighlight() {
            const items = listEl.querySelectorAll(".list-group-item");
            items.forEach((item, idx) => item.classList.toggle("active", idx === selectedIndex));
        }
        inputEl.addEventListener("keydown", (e) => {
            const items = listEl.querySelectorAll(".list-group-item");
            if (!items.length) return;
            if (e.key === "ArrowDown") {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateHighlight();
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateHighlight();
            } else if (e.key === "Enter") {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                    resetSelection();
                }
            }
        });
        inputEl.addEventListener("input", resetSelection);
    }

    async function loadBranchesForSupplier(row, supplierId, selectedBranchId = null) {
        const branchSelect = row.querySelector('.branch-select');
        if (!branchSelect) return;
        branchSelect.innerHTML = '<option value="">Cargando sucursales...</option>';
        branchSelect.disabled = true;
        try {
            const res = await fetch(`index.php?page=supplier_branches_list&id_supplier=${encodeURIComponent(supplierId)}`);
            const data = await res.json();
            const branches = (data.success && Array.isArray(data.data)) ? data.data : [];
            if (branches.length > 0) {
                branchSelect.innerHTML = '<option value="">Seleccione sucursal...</option>';
                branches.forEach(b => {
                    const isSelected = (selectedBranchId && parseInt(selectedBranchId) === parseInt(b.id)) ? 'selected' : '';
                    branchSelect.innerHTML += `<option value="${b.id}" ${isSelected}>${b.branch}</option>`;
                });
                branchSelect.disabled = false;
            } else {
                branchSelect.innerHTML = '<option value="1">⚠️ Sin sucursales (Default)</option>';
                branchSelect.disabled = false;
            }
        } catch (e) {
            branchSelect.innerHTML = '<option value="1">Error al cargar</option>';
            branchSelect.disabled = false;
        }
    }

    function initSupplierAutocomplete(row) {
        const searchInput = row.querySelector('.supplier-search-input');
        const supIdInput = row.querySelector('.sup-id-val');
        const listContainer = row.querySelector('.supplier-autocomplete-list');
        if (!searchInput || !supIdInput || !listContainer) return;
        let timeout = null;

        searchInput.addEventListener('input', () => {
            supIdInput.value = '0';
            const query = searchInput.value.trim();
            listContainer.innerHTML = "";
            clearTimeout(timeout);
            if (query.length < 2) return;
            timeout = setTimeout(async () => {
                try {
                    const res = await fetch(`index.php?page=suppliers_list&term=${encodeURIComponent(query)}`);
                    const data = await res.json();
                    const suppliers = (data.success && Array.isArray(data.data)) ? data.data : [];
                    renderSupplierList(suppliers, listContainer, searchInput, supIdInput, row);
                } catch (err) { }
            }, 300);
        });
        setupKeyboardNavigation(searchInput, listContainer);
        document.addEventListener('click', (e) => {
            if (!row.contains(e.target)) listContainer.innerHTML = '';
        });
    }

    function renderSupplierList(suppliers, listContainer, searchInput, supIdInput, row) {
        listContainer.innerHTML = "";
        if (!suppliers.length) {
            const item = document.createElement("div");
            item.className = "list-group-item list-group-item-action text-muted small py-2 px-3";
            item.textContent = "No se encontraron proveedores.";
            listContainer.appendChild(item);
            return;
        }
        suppliers.forEach((s) => {
            const div = document.createElement("div");
            div.className = "list-group-item list-group-item-action py-2 px-3 text-wrap";
            div.textContent = s.business_name || s.company_name || "Sin nombre";
            div.addEventListener("click", () => {
                searchInput.value = s.business_name || s.company_name;
                supIdInput.value = s.id;
                listContainer.innerHTML = "";
                loadBranchesForSupplier(row, s.id);
            });
            listContainer.appendChild(div);
        });
    }

    // 🧮 CÁLCULO GENERAL DE TOTALES, IVA, UTILIDAD Y PIEZAS A SURTIR
    function calculateGrandTotals() {
        let totalSale = 0;
        let totalPurchaseCost = 0;
        let totalFreight = 0;
        let currentReqSum = 0;

        document.querySelectorAll('.item-row-selector').forEach(row => {
            const chk = row.querySelector('.item-check');
            if (!chk || !chk.checked) return;

            const qtyInput = row.querySelector('.qty-convert-input');
            const costInput = row.querySelector('.purchase-cost-input');
            const freightInput = row.querySelector('.freight-cost-input');
            const totalCell = row.querySelector('.item-total-cell');

            const val = parseInt(qtyInput?.value) || 0;
            const unitPrice = parseFloat(totalCell?.dataset.unitPrice) || 0;
            const unitCost = parseFloat(costInput?.value) || 0;
            const freightVal = parseFloat(freightInput?.value) || 0;

            totalSale += val * unitPrice;
            totalPurchaseCost += (val * unitCost) + freightVal; 
            totalFreight += freightVal;
            currentReqSum += val;
        });

        const tax = totalSale * 0.16;
        const totalProfit = totalSale - totalPurchaseCost; // Utilidad real considerando costos y fletes

        // Actualizar tarjeta inferior
        document.getElementById('grand-total-sale').textContent = '$' + totalSale.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('grand-total-tax').textContent = '$' + tax.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('grand-total-cost').textContent = '$' + totalPurchaseCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const profitEl = document.getElementById('grand-total-profit');
        if (profitEl) {
            profitEl.textContent = '$' + totalProfit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            profitEl.className = totalProfit < 0 ? 'fs-5 fw-bold text-danger font-monospace' : 'fs-5 fw-bold text-success font-monospace';
        }

        const headerQtyBadge = document.getElementById('header-total-requisition-qty');
        if (headerQtyBadge) headerQtyBadge.textContent = currentReqSum;

        // Guardar estado de pérdida global en el formulario principal para validación al enviar
        const form = document.getElementById('requisitionMasterForm');
        if (form) {
            form.dataset.hasLoss = totalProfit < 0 ? 'true' : 'false';
        }
    }

    // ⚙️ RECALCULO DE FILA INDIVIDUAL Y ALERTA DE PÉRDIDA POR PARTIDA
    function triggerRowRecalc(r) {
        const qInp = r.querySelector('.qty-convert-input');
        const cInp = r.querySelector('.purchase-cost-input');
        const fInp = r.querySelector('.freight-cost-input');
        const tCel = r.querySelector('.item-total-cell');
        const ctCel = r.querySelector('.item-cost-total-cell');
        const pCel = r.querySelector('.item-profit-cell');
        
        const uPrice = parseFloat(tCel.dataset.unitPrice) || 0;
        const qVal = parseInt(qInp.value) || 0;
        const cVal = parseFloat(cInp.value) || 0;
        const fVal = parseFloat(fInp?.value) || 0;

        const subtotalSaleVal = qVal * uPrice;
        const subtotalCostVal = (qVal * cVal) + fVal;

        tCel.textContent = '$' + subtotalSaleVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        ctCel.textContent = '$' + subtotalCostVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        let lProf = subtotalSaleVal - subtotalCostVal;
        if (pCel) {
            pCel.textContent = '$' + lProf.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            pCel.className = lProf < 0
                ? 'text-end fw-bold font-monospace item-profit-cell text-danger'
                : 'text-end fw-bold font-monospace item-profit-cell text-primary';
        }

        // 🚨 ALERTA VISUAL DE PÉRDIDA POR PARTIDA O CANTIDAD EN 0
        let warningContainer = r.querySelector('.row-warnings');
        if (!warningContainer) {
            warningContainer = document.createElement('div');
            warningContainer.className = 'row-warnings mt-1';
            qInp.after(warningContainer);
        }
        warningContainer.innerHTML = '';

        if (qVal === 0) {
            r.classList.add('table-danger', 'opacity-75');
            warningContainer.innerHTML += `<div class="text-danger fs-9 fw-bold">⚠️ Sin Surtir (0 pzas)</div>`;
        } else if (lProf < 0) {
            r.classList.add('table-danger');
            warningContainer.innerHTML += `<div class="text-danger fs-9 fw-bold">⚠️ ¡Partida con Pérdida!</div>`;
        } else {
            r.classList.remove('table-danger', 'opacity-75');
        }

        updateGroupSumHint(r);
    }

    // FUNCIÓN PARA MOSTRAR LA SUMA TOTAL DE LAS PARTIDAS FRACCIONADAS
    function updateGroupSumHint(row) {
        const groupId = row.dataset.groupId;
        const siblingRows = document.querySelectorAll(`.item-row-selector[data-group-id="${groupId}"]`);

        if (siblingRows.length <= 1) return;

        let totalGroupQty = 0;
        siblingRows.forEach(sib => {
            totalGroupQty += parseInt(sib.querySelector('.qty-convert-input')?.value) || 0;
        });

        siblingRows.forEach((sib, index) => {
            const hintDiv = sib.querySelector('.group-total-hint');
            if (!hintDiv) return;

            if (index === 0) {
                hintDiv.textContent = `(Se surtirán ${totalGroupQty} pzas)`;
                hintDiv.classList.remove('d-none');
            } else {
                hintDiv.classList.add('d-none');
            }
        });
    }

    function initRowListeners(row) {
        const chk = row.querySelector('.item-check');
        const qtyInput = row.querySelector('.qty-convert-input');
        const costInput = row.querySelector('.purchase-cost-input');
        const freightInput = row.querySelector('.freight-cost-input');

        if (!row.dataset.groupId) {
            row.dataset.groupId = row.dataset.rowId;
        }

        chk.addEventListener('change', function () {
            if (!this.checked) {
                row.dataset.lastQty = qtyInput.value;
                qtyInput.value = 0;
                row.style.opacity = '0.4';
            } else {
                qtyInput.value = row.dataset.lastQty || row.querySelector('.max-qty-val')?.value || 1;
                row.style.opacity = '1';
            }

            triggerRowRecalc(row);
            calculateGrandTotals();
        });

        qtyInput.addEventListener('input', () => {
            let val = parseInt(qtyInput.value) || 0;
            if (val < 0) { qtyInput.value = 0; }
            triggerRowRecalc(row);
            calculateGrandTotals();
        });

        costInput.addEventListener('input', () => {
            triggerRowRecalc(row);
            calculateGrandTotals();
        });

        if (freightInput) {
            freightInput.addEventListener('input', () => {
                triggerRowRecalc(row);
                calculateGrandTotals();
            });
        }

        const splitBtn = row.querySelector('.split-item-btn');
        if (splitBtn) {
            splitBtn.addEventListener('click', function () {
                const currentQty = parseInt(qtyInput.value) || 0;
                if (currentQty <= 1) {
                    showCustomModal('Acción no permitida', 'No se puede dividir una partida con cantidad menor o igual a 1.');
                    return;
                }

                const halfQty = Math.floor(currentQty / 2);
                const remainingQty = currentQty - halfQty;

                qtyInput.value = halfQty;
                row.classList.add('table-warning');
                row.querySelector('.split-tag')?.classList.remove('d-none');

                const clone = row.cloneNode(true);
                dynamicRowIdx++;

                clone.querySelectorAll('[name]').forEach(el => {
                    el.name = el.name.replace(/items\[\d+\]/, `items[${dynamicRowIdx}]`);
                });

                clone.dataset.rowId = dynamicRowIdx;
                clone.dataset.groupId = row.dataset.groupId;

                const cloneQtyInput = clone.querySelector('.qty-convert-input');
                cloneQtyInput.value = remainingQty;

                const clonedOcCell = clone.querySelector('.total-oc-cell');
                if (clonedOcCell) {
                    const originalMax = row.querySelector('.max-qty-val')?.value || (halfQty + remainingQty);
                    clonedOcCell.innerHTML = `
                        <span class="text-muted fs-8 fst-italic fw-semibold d-block">↳ (Fracción)</span>
                        <input type="hidden" class="max-qty-val" value="${originalMax}">
                    `;
                }

                clone.classList.add('table-warning');
                clone.querySelector('.split-tag')?.classList.remove('d-none');

                const removeBtn = clone.querySelector('.remove-split-btn');
                if (removeBtn) removeBtn.classList.remove('d-none');

                row.after(clone);
                initRowListeners(clone);

                triggerRowRecalc(row);
                triggerRowRecalc(clone);
                calculateGrandTotals();
            });
        }

        const removeSplitBtn = row.querySelector('.remove-split-btn');
        if (removeSplitBtn) {
            removeSplitBtn.addEventListener('click', function () {
                const currentQty = parseInt(qtyInput.value) || 0;
                const groupId = row.dataset.groupId;
                const siblingRows = document.querySelectorAll(`.item-row-selector[data-group-id="${groupId}"]`);
                let targetSibling = Array.from(siblingRows).find(r => r !== row);

                if (targetSibling) {
                    const targetQtyInput = targetSibling.querySelector('.qty-convert-input');
                    let newQty = (parseInt(targetQtyInput.value) || 0) + currentQty;
                    targetQtyInput.value = newQty;
                    triggerRowRecalc(targetSibling);
                }

                row.remove();
                calculateGrandTotals();
            });
        }

        initSupplierAutocomplete(row);
        const initialSupId = row.querySelector('.sup-id-val').value;
        const initialBranchId = row.querySelector('.branch-select')?.value;
        if (initialSupId && initialSupId !== '0') {
            loadBranchesForSupplier(row, initialSupId, initialBranchId);
        }
        triggerRowRecalc(row);
    }

    document.querySelectorAll('.item-row-selector').forEach(row => {
        initRowListeners(row);
    });

    // 🚀 LLAMADA OBLIGATORIA AL CARGAR LA PÁGINA
    calculateGrandTotals();

    document.getElementById('selectAllItems')?.addEventListener('change', function () {
        document.querySelectorAll('.item-check').forEach(chk => {
            chk.checked = this.checked;
            chk.dispatchEvent(new Event('change'));
        });
    });

// 🛑 FILTRO OBLIGATORIO DE MODAL PARA CUALQUIER ACCIÓN (DRAFT O EMIT)
    const masterForm = document.getElementById('requisitionMasterForm');
    if (masterForm) {
        const btnDraft = document.getElementById('btnDraft');
        const btnEmit = document.getElementById('btnEmit');

        let selectedAction = '';

        function triggerModalWorkflow(actionVal, title, text, btnClass) {
            selectedAction = actionVal;
            
            // Personalizamos el modal dinámicamente según la acción
            const modalTitleEl = document.getElementById('confirmModalTitle');
            const modalTextEl = document.getElementById('confirmModalText');
            const confirmBtn = document.getElementById('confirmActionBtn');

            if (modalTitleEl) modalTitleEl.innerHTML = `<i class="bi bi-shield-exclamation text-warning me-2"></i> ${title}`;
            if (modalTextEl) modalTextEl.textContent = text;
            if (confirmBtn) confirmBtn.className = `btn btn-sm rounded-pill px-4 fw-semibold shadow-sm ${btnClass}`;

            // Mostramos el modal de Bootstrap
            const confirmModalEl = document.getElementById('quotationActionConfirmModal');
            if (confirmModalEl) {
                const bootstrapModal = new bootstrap.Modal(confirmModalEl);
                bootstrapModal.show();
            }
        }

        // Clic en Borrador -> Pasa por el modal
        if (btnDraft) {
            btnDraft.addEventListener('click', function () {
                triggerModalWorkflow(
                    'draft',
                    'CONFIRMAR GUARDAR BORRADOR',
                    '¿Estás seguro de guardar esta requisición como borrador?',
                    'btn-secondary'
                );
            });
        }

        // Clic en Enviar a Compras -> Pasa por el modal (validando pérdida financiera)
        if (btnEmit) {
            btnEmit.addEventListener('click', function () {
                const hasLoss = masterForm.dataset.hasLoss === 'true';
                let msg = '¿Estás seguro de enviar esta requisición a compras? Una vez enviada ya no podrá modificarse.';
                
                if (hasLoss) {
                    msg = '⚠️ ADVERTENCIA: Esta requisición presenta PÉRDIDA FINANCIERA (el costo supera al precio de venta). Al continuar, autorizarás esta operación bajo tu responsabilidad.';
                }

                triggerModalWorkflow(
                    'emit',
                    hasLoss ? '⚠️ ADVERTENCIA DE PÉRDIDA' : 'CONFIRMAR ENVÍO A COMPRAS',
                    msg,
                    hasLoss ? 'btn-danger' : 'btn-success'
                );
            });
        }

        // 3. Cuando el usuario hace clic en "Sí, continuar" dentro del modal
        const confirmBtn = document.getElementById('confirmActionBtn');
        if (confirmBtn) {
            // Clonamos el botón para evitar acumulación de eventos
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

            newConfirmBtn.addEventListener('click', function () {
                // Ocultar modal
                const confirmModalEl = document.getElementById('quotationActionConfirmModal');
                const modalInstance = bootstrap.Modal.getInstance(confirmModalEl);
                if (modalInstance) modalInstance.hide();

                // Evaluar si hubo pérdida para mandar la bandera de auditoría
                const hasLoss = masterForm.dataset.hasLoss === 'true';
                let inputAccept = masterForm.querySelector('input[name="loss_accepted"]');
                if (!inputAccept) {
                    inputAccept = document.createElement('input');
                    inputAccept.type = 'hidden';
                    inputAccept.name = 'loss_accepted';
                    masterForm.appendChild(inputAccept);
                }
                inputAccept.value = (hasLoss && selectedAction === 'emit') ? '1' : '0';

                // Inyectar la acción seleccionada
                let inputAction = masterForm.querySelector('input[name="action"][type="hidden"]');
                if (!inputAction) {
                    inputAction = document.createElement('input');
                    inputAction.type = 'hidden';
                    inputAction.name = 'action';
                    masterForm.appendChild(inputAction);
                }
                inputAction.value = selectedAction;

                // Envío definitivo del formulario
                masterForm.submit();
            });
        }
    }
});