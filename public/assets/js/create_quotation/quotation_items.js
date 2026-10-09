document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = 0;
    const itemsContainer = document.getElementById('items_container');
    const template = document.getElementById('item_row_template');

    if (!itemsContainer || !template) return;

    // --- 💰 FUNCIÓN AUXILIAR PARA FORMATEAR MONEDA CON COMAS DE MILES ---
    function formatMoney(amount) {
        return parseFloat(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // --- 🔍 MOTOR DE AUTOCOMPLETADO DE PRODUCTOS ---
    function initProductAutocomplete(row) {
        const partInput = row.querySelector('.product-search-part');
        const descInput = row.querySelector('.product-search-desc');
        const hiddenId = row.querySelector('.product-id');
        const partList = row.querySelector('.product-list-part');
        const descList = row.querySelector('.product-list-desc');

        if (!partInput || !descInput || !hiddenId) return;

        let timer;
        let currentProducts = [];

        const clearLists = () => {
            if (partList) partList.innerHTML = '';
            if (descList) descList.innerHTML = '';
        };

        const applyProduct = (p) => {
            partInput.value = p.part_number;
            descInput.value = p.description;
            hiddenId.value = p.id;

            // Blindamos la descripción para que no se edite a mano
            descInput.disabled = true;

            clearLists();
        };

        // Búsqueda genérica reutilizable
        const fetchProductsquery = (query, listContainer) => {
            clearTimeout(timer);
            if (query.length < 2) {
                clearLists();
                return;
            }

            timer = setTimeout(async () => {
                try {
                    const res = await fetch(`index.php?page=search_products&term=${encodeURIComponent(query)}`);
                    const data = await res.json();
                    currentProducts = (data.success && Array.isArray(data.data)) ? data.data : [];

                    listContainer.innerHTML = '';
                    if (currentProducts.length > 0) {
                        currentProducts.forEach(p => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action product-item';
                            btn.innerHTML = `<strong>${p.part_number}</strong> - ${p.description} <small class="text-muted">(Stock: ${p.stock || 0})</small>`;

                            btn.addEventListener('click', () => applyProduct(p));
                            listContainer.appendChild(btn);
                        });
                    }
                } catch (e) {
                    console.error("Autocomplete error:", e);
                }
            }, 250);
        };

        partInput.addEventListener('input', () => {
            hiddenId.value = '';
            fetchProductsquery(partInput.value.trim(), partList);
        });

        descInput.addEventListener('input', () => {
            hiddenId.value = '';
            fetchProductsquery(descInput.value.trim(), descList);
        });

        // Cerrar listas al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!row.contains(e.target)) clearLists();
        });
    }

    // --- 🚚 MOTOR DE AUTOCOMPLETADO DE PROVEEDORES Y SUCURSALES (DINÁMICO POR FILA) ---
    function initSupplierAutocompleteForRow(row) {
        const inputEl = row.querySelector('.supplier-search');
        const hiddenEl = row.querySelector('.supplier-id');
        const listEl = row.querySelector('.supplier-list');

        const branchInput = row.querySelector('.branch-search');
        const branchIdInput = row.querySelector('.branch-id');
        const branchList = row.querySelector('.branch-list');

        if (!inputEl || !listEl || !hiddenEl) return;

        let timer;
        let activeIndex = -1;
        const MIN = 2;
        let currentSuppliers = [];

        const clearList = () => {
            listEl.innerHTML = "";
            activeIndex = -1;
        };

        const highlightItem = (index) => {
            const items = listEl.querySelectorAll(".list-group-item");
            items.forEach((item, i) => {
                item.classList.toggle("active", i === index);
                if (i === index) item.scrollIntoView({ block: "nearest" });
            });
        };

        // --- 🔍 BUSCAR PROVEEDORES (INPUT) ---
        inputEl.addEventListener("input", () => {
            const term = inputEl.value.trim();
            hiddenEl.value = "";
            if (branchInput) {
                branchInput.value = "";
                branchInput.disabled = true;
            }
            if (branchIdInput) branchIdInput.value = "";

            clearTimeout(timer);

            if (term.length < MIN) {
                clearList();
                currentSuppliers = [];
                return;
            }

            timer = setTimeout(async () => {
                try {
                    const res = await fetch(`index.php?page=suppliers_list&term=${encodeURIComponent(term)}`);
                    const data = await res.json();

                    currentSuppliers = (data.success && Array.isArray(data.data)) ? data.data : [];
                    clearList();

                    if (currentSuppliers.length > 0) {
                        currentSuppliers.forEach((s) => {
                            const btn = document.createElement("button");
                            btn.type = "button";
                            btn.className = "list-group-item list-group-item-action";
                            btn.textContent = s.business_name || s.company_name || `#${s.id}`;
                            btn.dataset.id = s.id;

                            btn.addEventListener("click", () => {
                                inputEl.value = s.business_name || s.company_name || `#${s.id}`;
                                hiddenEl.value = s.id;
                                clearList();

                                loadBranchesIntoSelect(row, s.id);

                                const branchSelectEl = row.querySelector('.branch-select');
                                if (branchSelectEl) {
                                    branchSelectEl.disabled = false;
                                    branchSelectEl.focus();
                                }
                            });
                            listEl.appendChild(btn);
                        });
                    }
                } catch (e) {
                    console.error("Supplier autocomplete error:", e);
                }
            }, 250);
        });

        // --- ⌨️ NAVEGACIÓN Y VALIDACIÓN (KEYDOWN) ---
        inputEl.addEventListener("keydown", async (e) => {
            const items = listEl.querySelectorAll(".list-group-item");
            const total = items.length;

            if (["ArrowDown", "ArrowUp", "Enter"].includes(e.key) && total > 0) {
                e.preventDefault();
            }

            if (e.key === "ArrowDown") {
                activeIndex = (activeIndex + 1) % total;
                highlightItem(activeIndex);
            } else if (e.key === "ArrowUp") {
                activeIndex = (activeIndex - 1 + total) % total;
                highlightItem(activeIndex);
            } else if (e.key === "Enter" || e.key === "Tab") {
                const supplierName = inputEl.value.trim();
                if (supplierName.length < MIN) return;

                if (activeIndex >= 0 && activeIndex < total) {
                    const selected = items[activeIndex];
                    inputEl.value = selected.textContent;
                    hiddenEl.value = selected.dataset.id;
                    clearList();

                    loadBranchesIntoSelect(row, selected.dataset.id);

                    const branchSelectEl = row.querySelector('.branch-select');
                    if (branchSelectEl) {
                        branchSelectEl.disabled = false;
                        if (e.key === "Enter") branchSelectEl.focus();
                    }
                    return;
                }

                const exactMatch = currentSuppliers.find(s => {
                    const name = (s.business_name || s.company_name || "").toLowerCase();
                    return name === supplierName.toLowerCase();
                });

                if (exactMatch) {
                    hiddenEl.value = exactMatch.id;
                    inputEl.value = exactMatch.business_name || exactMatch.company_name;
                    clearList();

                    loadBranchesIntoSelect(row, exactMatch.id);

                    const branchSelectEl = row.querySelector('.branch-select');
                    if (branchSelectEl) {
                        branchSelectEl.disabled = false;
                        if (e.key === "Enter") branchSelectEl.focus();
                    }
                    return;
                }
            }
        });

        document.addEventListener("click", (ev) => {
            if (!listEl.contains(ev.target) && ev.target !== inputEl) clearList();
            if (branchList && !branchList.contains(ev.target) && ev.target !== branchInput) branchList.innerHTML = "";
        });
    }

    // --- 🚚 FUNCIÓN MEJORADA PARA CARGAR SUCURSALES Y PRESELECCIONAR ---
    async function loadBranchesIntoSelect(row, supplierId, selectedBranchId = null) {
        const branchSelect = row.querySelector('.branch-select');
        const branchNameHidden = row.querySelector('.branch-search');

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
                    branchSelect.innerHTML += `<option value="${b.id}">${b.branch}</option>`;
                });
                branchSelect.disabled = false;

                if (selectedBranchId) {
                    branchSelect.value = selectedBranchId;
                    if (branchNameHidden) {
                        const selectedOption = branchSelect.options[branchSelect.selectedIndex];
                        branchNameHidden.value = (branchSelect.value !== "") ? selectedOption.textContent.trim() : "";
                    }
                }
            } else {
                branchSelect.innerHTML = '<option value="">⚠️ Sin sucursales registradas</option>';
                branchSelect.disabled = false;
                window.showAppAlert('Este proveedor no tiene sucursales. Usa "+ Sucursal" arriba para registrar una.', 'warning');
            }
        } catch (e) {
            console.error("Error cargando sucursales:", e);
            branchSelect.innerHTML = '<option value="">Error al cargar</option>';
            branchSelect.disabled = false;
        }
    }

    // Función para sincronizar el texto seleccionado en el input oculto (.branch-search)
    function initBranchSelectEvents(row) {
        const branchSelect = row.querySelector('.branch-select');
        const branchNameHidden = row.querySelector('.branch-search');

        if (!branchSelect || !branchNameHidden) return;

        branchSelect.addEventListener('change', () => {
            const selectedOption = branchSelect.options[branchSelect.selectedIndex];
            branchNameHidden.value = (branchSelect.value !== "") ? selectedOption.textContent.trim() : "";
            branchSelect.classList.remove('is-invalid', 'border-danger');
        });
    }

    // --- 🛠️ CARGAR PARTIDA EXISTENTE (MODO EDICIÓN) ---
    function addExistingItemRow(itemData) {
        const clone = template.content.cloneNode(true);
        const rowEl = clone.querySelector('.item-row');

        rowEl.innerHTML = rowEl.innerHTML.replaceAll('INDEX', rowIndex);
        
        // 🔹 Restaurado a append para respetar el orden de arriba hacia abajo
        itemsContainer.appendChild(rowEl);

        const newRow = itemsContainer.lastElementChild;

        initRowEvents(newRow);
        initProductAutocomplete(newRow);
        initSupplierAutocompleteForRow(newRow);
        initBranchValidation(newRow);
        initProductFieldWatcher(newRow);
        initBranchSelectEvents(newRow);

        const itemNumInput = newRow.querySelector('.item-number-input');
        if (itemNumInput) itemNumInput.value = itemData.item_number || (rowIndex + 1);

        const rowNumBadge = newRow.querySelector('.row-number');
        if (rowNumBadge) rowNumBadge.textContent = `#${itemData.item_number || (rowIndex + 1)}`;

        const partInput = newRow.querySelector('.product-search-part');
        const descInput = newRow.querySelector('.product-search-desc');
        const productIdInput = newRow.querySelector('.product-id');

        if (partInput) partInput.value = itemData.product_part_number || itemData.part_number || '';
        if (descInput) {
            descInput.value = itemData.product_description || itemData.description || '';
            descInput.disabled = false;
        }
        if (productIdInput) productIdInput.value = itemData.product_id || '';

        const qtyInput = newRow.querySelector('.qty-input');
        const costInput = newRow.querySelector('.cost-input');
        const profitInput = newRow.querySelector('.profit-input');
        const priceInput = newRow.querySelector('.price-input');
        const freightInput = newRow.querySelector('.freight-input');

        if (qtyInput) qtyInput.value = itemData.quantity || 1;
        if (costInput) costInput.value = itemData.purchase_unit_cost || 0.00;
        if (profitInput) profitInput.value = itemData.profit_percentage || 0.00;
        if (priceInput) priceInput.value = itemData.unit_price || 0.00;
        if (freightInput) freightInput.value = itemData.freight_cost || 0.00;

        const supplierSearch = newRow.querySelector('.supplier-search');
        const supplierId = newRow.querySelector('.supplier-id');
        const branchSelect = newRow.querySelector('.branch-select');

        if (supplierSearch) supplierSearch.value = itemData.supplier_name || '';
        if (supplierId) supplierId.value = itemData.id_supplier || '';

        if (itemData.id_supplier) {
            loadBranchesIntoSelect(newRow, itemData.id_supplier).then(() => {
                if (branchSelect && itemData.id_branch) {
                    branchSelect.value = itemData.id_branch;
                }
            });
        }

        const deliveryDaysNumber = newRow.querySelector('.delivery-days-number');
        const deliveryTermsHidden = newRow.querySelector('.delivery-terms-hidden');
        if (deliveryDaysNumber && itemData.delivery_time_days) {
            const numMatch = itemData.delivery_time_days.match(/\d+/);
            if (numMatch) deliveryDaysNumber.value = numMatch[0];
            if (deliveryTermsHidden) deliveryTermsHidden.value = itemData.delivery_time_days;
        }

        rowIndex++;
        updateRowNumbers();
        computeRow(newRow, true);
        calculateAll();
    }

    window.addExistingItemRow = addExistingItemRow;

    // --- GESTIÓN DE FILAS (NUEVAS PARTIDAS HACIA ABAJO) ---
    function addItemRow() {
        const clone = template.content.cloneNode(true);
        const rowEl = clone.querySelector('.item-row');

        rowEl.innerHTML = rowEl.innerHTML.replaceAll('INDEX', rowIndex);
        
        // 🔹 Restaurado a append para agregar al final (abajo)
        itemsContainer.appendChild(rowEl);

        const newRow = itemsContainer.lastElementChild;
        initRowEvents(newRow);
        initProductAutocomplete(newRow);
        initSupplierAutocompleteForRow(newRow);
        initBranchValidation(newRow);
        initProductFieldWatcher(newRow);
        initBranchSelectEvents(newRow);
        rowIndex++;
        updateRowNumbers();
        calculateAll();

        // Auto-scroll al final para ver la nueva fila abajo
        const scrollWrapper = document.getElementById('items_scroll_wrapper');
        if (scrollWrapper) {
            scrollWrapper.scrollTop = scrollWrapper.scrollHeight;
        }
    }

    function updateRowNumbers() {
        const rows = itemsContainer.querySelectorAll('.item-row');
        rows.forEach((row, idx) => {
            row.querySelector('.row-number').textContent = `#${idx + 1}`;
            row.querySelector('.item-number-input').value = idx + 1;

            row.classList.remove('row-odd', 'row-even');
            if (idx % 2 === 0) {
                row.classList.add('row-odd');
            } else {
                row.classList.add('row-even');
            }
        });
    }

    function initRowEvents(row) {
        const qtyInput = row.querySelector('.qty-input');
        const costInput = row.querySelector('.cost-input');
        const profitInput = row.querySelector('.profit-input');
        const freightInput = row.querySelector('.freight-input');
        const priceInput = row.querySelector('.price-input');

        const deliveryDaysNumber = row.querySelector('.delivery-days-number');
        const deliveryDaysLabel = row.querySelector('.delivery-days-label');
        const deliveryTermsHidden = row.querySelector('.delivery-terms-hidden');
        const removeBtn = row.querySelector('.remove-item-btn');

        function updateDeliveryText() {
            if (!deliveryDaysNumber || !deliveryDaysLabel || !deliveryTermsHidden) return;
            let val = parseInt(deliveryDaysNumber.value.trim(), 10);
            if (isNaN(val) || val < 1) val = 1;

            const leyenda = (val === 1) ? "DÍA" : "DÍAS";
            deliveryDaysLabel.textContent = leyenda;
            deliveryTermsHidden.value = `${val} ${leyenda}`;
        }

        if (deliveryDaysNumber) {
            deliveryDaysNumber.addEventListener('input', updateDeliveryText);
            updateDeliveryText();
        }

        [qtyInput, costInput, profitInput, freightInput, priceInput].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    computeRow(row, input === priceInput);
                    calculateAll();
                });
            }
        });

        removeBtn.addEventListener('click', () => {
            if (itemsContainer.querySelectorAll('.item-row').length > 1) {
                row.remove();
                updateRowNumbers();
                calculateAll();
            } else {
                window.showAppAlert('La cotización requiere al menos una partida.', 'warning');
            }
        });

        if (rowIndex === 0) {
            qtyInput.value = 1;
            costInput.value = 0;
            profitInput.value = 0;
            if (deliveryDaysNumber) deliveryDaysNumber.value = 2;
            updateDeliveryText();
            computeRow(row);
        }
    }

    function computeRow(row, userChangedPrice = false) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
        const freight = parseFloat(row.querySelector('.freight-input').value) || 0;
        let profitPct = parseFloat(row.querySelector('.profit-input').value) || 0;

        const priceInput = row.querySelector('.price-input');
        const totalPriceInput = row.querySelector('.total-price-input');
        const netProfitInput = row.querySelector('.net-profit-input');
        const netProfitDisplay = row.querySelector('.net-profit-display');

        let unitPrice = 0;
        let netProfitPerUnit = 0;

        if (!userChangedPrice) {
            const productProfit = cost * (profitPct / 100);
            unitPrice = cost + productProfit;
            priceInput.value = unitPrice.toFixed(2);
        } else {
            unitPrice = parseFloat(priceInput.value) || 0;
            if (cost > 0) {
                profitPct = ((unitPrice - cost) / cost) * 100;
                row.querySelector('.profit-input').value = profitPct.toFixed(2);
            }
        }

        netProfitPerUnit = unitPrice - cost;
        const totalPrice = (unitPrice * qty) + freight;
        const totalNetProfit = netProfitPerUnit * qty;

        totalPriceInput.value = totalPrice.toFixed(2);
        netProfitInput.value = totalNetProfit.toFixed(2);

        if (netProfitDisplay) {
            netProfitDisplay.value = `$${formatMoney(totalNetProfit)}`;
        }
    }

    function calculateAll() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const totalPrice = parseFloat(row.querySelector('.total-price-input').value) || 0;
            subtotal += totalPrice;
        });

        const iva = subtotal * 0.16;
        const total = subtotal + iva;

        document.getElementById('lbl_subtotal').textContent = `$${formatMoney(subtotal)}`;
        document.getElementById('input_subtotal').value = subtotal.toFixed(2);

        document.getElementById('lbl_iva').textContent = `$${formatMoney(iva)}`;
        document.getElementById('input_iva').value = iva.toFixed(2);

        document.getElementById('lbl_total').textContent = `$${formatMoney(total)}`;
        document.getElementById('input_total').value = total.toFixed(2);
    }

    const addItemBtn = document.getElementById('add_item_btn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => addItemRow());
    }

    // --- 🏢 APERTURA INTELIGENTE DEL MODAL DE SUCURSAL ---
    document.addEventListener('click', function (e) {
        if (e.target && (e.target.id === 'btn_quick_branch' || e.target.closest('#btn_quick_branch'))) {
            const row = e.target.closest('.item-row');
            const supplierIdInput = row ? row.querySelector('.supplier-id') : null;
            const supplierSearchInput = row ? row.querySelector('.supplier-search') : null;

            const modalSupplierIdInput = document.getElementById('modal_id_supplier');
            const modalSupplierSearchInput = document.getElementById('modal_supplier_search');

            if (supplierIdInput && supplierIdInput.value.trim() !== '') {
                modalSupplierIdInput.value = supplierIdInput.value.trim();
                modalSupplierSearchInput.value = supplierSearchInput.value.trim();
            } else {
                modalSupplierIdInput.value = '';
                modalSupplierSearchInput.value = '';
            }

            const branchModalEl = document.getElementById('quickBranchModal');
            if (branchModalEl) {
                let branchModal = bootstrap.Modal.getInstance(branchModalEl);
                if (!branchModal) {
                    branchModal = new bootstrap.Modal(branchModalEl);
                }
                branchModal.show();
            }
        }
    });

    // --- 🔍 AUTOCOMPLETADO DE PROVEEDOR DENTRO DEL MODAL DE SUCURSAL ---
    const modalSuppSearch = document.getElementById('modal_supplier_search');
    const modalSuppId = document.getElementById('modal_id_supplier');
    const modalSuppList = document.getElementById('modal_supplier_list');

    if (modalSuppSearch && modalSuppId && modalSuppList) {
        let modalTimer;
        modalSuppSearch.addEventListener('input', () => {
            modalSuppId.value = '';
            const term = modalSuppSearch.value.trim();
            clearTimeout(modalTimer);

            if (term.length < 2) {
                modalSuppList.innerHTML = '';
                return;
            }

            modalTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`index.php?page=suppliers_list&term=${encodeURIComponent(term)}`);
                    const data = await res.json();
                    const suppliers = (data.success && Array.isArray(data.data)) ? data.data : [];

                    modalSuppList.innerHTML = '';
                    if (suppliers.length > 0) {
                        suppliers.forEach(s => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action';
                            btn.textContent = s.business_name || s.company_name;
                            btn.addEventListener('click', () => {
                                modalSuppSearch.value = s.business_name || s.company_name;
                                modalSuppId.value = s.id;
                                modalSuppList.innerHTML = '';
                            });
                            modalSuppList.appendChild(btn);
                        });
                    }
                } catch (e) {
                    console.error("Error buscando proveedor en modal:", e);
                }
            }, 250);
        });
    }

    // 3. GUARDAR PRODUCTO RÁPIDO
    const saveProductBtn = document.getElementById('saveQuickProductBtn');
    if (saveProductBtn) {
        saveProductBtn.addEventListener('click', async () => {
            const form = document.getElementById('quickProductForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('index.php?page=create_quick_product', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    window.showAppAlert('Producto registrado correctamente.', 'success');
                    const modalEl = document.getElementById('quickProductModal');
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                    form.reset();
                } else {
                    window.showAppAlert(data.message || 'Error al registrar el producto.', 'danger');
                }
            } catch (err) {
                console.error(err);
                window.showAppAlert('Error de conexión con el servidor.', 'danger');
            }
        });
    }

    // 1. GUARDAR PROVEEDOR RÁPIDO (AJAX)
    const saveSupplierBtn = document.getElementById('saveQuickSupplierBtn');
    if (saveSupplierBtn) {
        saveSupplierBtn.addEventListener('click', async () => {
            const form = document.getElementById('quickSupplierForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('index.php?page=create_supplier', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success && data.id) {
                    window.showAppAlert('Proveedor creado exitosamente.', 'success');
                    const modalEl = document.getElementById('quickSupplierModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                    form.reset();

                    const activeRow = document.querySelector('.item-row');
                    if (activeRow) {
                        const suppInput = activeRow.querySelector('.supplier-search');
                        const suppIdInput = activeRow.querySelector('.supplier-id');
                        const branchInput = activeRow.querySelector('.branch-search');

                        if (suppInput && suppIdInput) {
                            const businessName = formData.get('business_name');
                            suppInput.value = businessName;
                            suppIdInput.value = data.id;

                            if (branchInput) {
                                branchInput.disabled = false;
                            }
                        }
                    }
                } else {
                    window.showAppAlert(data.message || 'Error al crear el proveedor.', 'danger');
                }
            } catch (err) {
                console.error("Error en proveedor rápido:", err);
                window.showAppAlert('Error de conexión con el servidor.', 'danger');
            }
        });
    }

    // 2. GUARDAR SUCURSAL RÁPIDA (AJAX)
    const saveBranchBtn = document.getElementById('saveQuickBranchBtn');
    if (saveBranchBtn) {
        saveBranchBtn.addEventListener('click', async () => {
            const form = document.getElementById('quickBranchForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('index.php?page=create_supplier_branch', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success && data.id) {
                    window.showAppAlert('Sucursal creada exitosamente.', 'success');
                    const modalEl = document.getElementById('quickBranchModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();

                    const createdBranchName = formData.get('branch');
                    const createdBranchId = data.id;
                    const supplierIdUsed = formData.get('id_supplier');

                    form.reset();

                    const activeRow = document.querySelector('.item-row');
                    if (activeRow) {
                        const rowSuppId = activeRow.querySelector('.supplier-id')?.value;
                        if (rowSuppId === supplierIdUsed) {
                            const branchInput = activeRow.querySelector('.branch-search');
                            const branchIdInput = activeRow.querySelector('.branch-id');

                            if (branchInput && branchIdInput) {
                                branchInput.value = createdBranchName;
                                branchIdInput.value = createdBranchId;
                            }
                        }
                    }
                } else {
                    window.showAppAlert(data.message || 'Error al crear la sucursal.', 'danger');
                }
            } catch (err) {
                console.error("Error en sucursal rápida:", err);
                window.showAppAlert('Error de conexión con el servidor.', 'danger');
            }
        });
    }

    function initBranchValidation(row) {
        const supplierIdInput = row.querySelector('.supplier-id');
        const branchSelect = row.querySelector('.branch-select');

        if (!branchSelect || !supplierIdInput) return;

        branchSelect.addEventListener('blur', (e) => {
            const relatedTarget = e.relatedTarget;
            if (relatedTarget && (
                relatedTarget.id === 'btn_quick_branch' ||
                relatedTarget.closest('#btn_quick_branch') ||
                relatedTarget.closest('.modal')
            )) {
                return;
            }

            setTimeout(() => {
                const supplierId = supplierIdInput.value.trim();
                const branchValue = branchSelect.value.trim();

                if (supplierId !== '') {
                    if (branchValue === '') {
                        branchSelect.classList.add('is-invalid', 'border-danger');
                        window.showAppAlert('Sucursal obligatoria: Si es la única sucursal, favor de registrarla con "+ Sucursal".', 'warning');
                        setTimeout(() => {
                            branchSelect.classList.remove('is-invalid');
                        }, 3000);
                    } else {
                        branchSelect.classList.remove('is-invalid', 'border-danger');
                    }
                }
            }, 200);
        });
    }

    function initProductFieldWatcher(row) {
        const partInput = row.querySelector('.product-search-part');
        const descInput = row.querySelector('.product-search-desc');
        const productIdInput = row.querySelector('.product-id');

        if (!partInput) return;

        partInput.addEventListener('input', () => {
            if (productIdInput) productIdInput.value = '';
            if (descInput) {
                descInput.value = '';
                descInput.disabled = true;
            }
        });
    }

    // --- 🚀 INICIALIZACIÓN INTELIGENTE (NUEVO VS EDICIÓN / RE-COTIZACIÓN) ---
    const existingRows = document.querySelectorAll('.item-row');

    if (existingRows.length > 0) {
        existingRows.forEach((row, index) => {
            row.dataset.index = index;

            initRowEvents(row);
            initProductAutocomplete(row);
            initSupplierAutocompleteForRow(row);
            initBranchValidation(row);
            initProductFieldWatcher(row);
            initBranchSelectEvents(row);

            const qtyInput = row.querySelector('.qty-input');
            const costInput = row.querySelector('.cost-input');
            const profitInput = row.querySelector('.profit-input');
            const priceInput = row.querySelector('.price-input');
            const freightInput = row.querySelector('.freight-input');

            if (qtyInput && qtyInput.getAttribute('value')) qtyInput.value = qtyInput.getAttribute('value');
            if (costInput && costInput.getAttribute('value')) costInput.value = costInput.getAttribute('value');
            if (profitInput && profitInput.getAttribute('value')) profitInput.value = profitInput.getAttribute('value');
            if (priceInput && priceInput.getAttribute('value')) priceInput.value = priceInput.getAttribute('value');
            if (freightInput && freightInput.getAttribute('value')) freightInput.value = freightInput.getAttribute('value');

            const qty = parseFloat(qtyInput?.value) || 0;
            const unitPrice = parseFloat(priceInput?.value) || 0;
            const freight = parseFloat(freightInput?.value) || 0;
            const cost = parseFloat(costInput?.value) || 0;

            const totalPrice = (unitPrice * qty) + freight;
            const netProfitPerUnit = unitPrice - cost;
            const totalNetProfit = netProfitPerUnit * qty;

            const totalPriceInput = row.querySelector('.total-price-input');
            const netProfitInput = row.querySelector('.net-profit-input');
            const netProfitDisplay = row.querySelector('.net-profit-display');

            if (totalPriceInput) totalPriceInput.value = totalPrice.toFixed(2);
            if (netProfitInput) netProfitInput.value = totalNetProfit.toFixed(2);
            if (netProfitDisplay) netProfitDisplay.value = `$${formatMoney(totalNetProfit)}`;

            // 🔹 4. CARGA ASÍNCRONA DE SUCURSALES
            const supplierIdInput = row.querySelector('.supplier-id');
            const branchSelect = row.querySelector('.branch-select');

            if (supplierIdInput && supplierIdInput.value.trim() !== '') {
                const savedBranchId = branchSelect ? branchSelect.value : null;
                loadBranchesIntoSelect(row, supplierIdInput.value.trim(), savedBranchId);
            }
        });

        updateRowNumbers();
        calculateAll();
        rowIndex = existingRows.length;

    } else {
        // 🔹 Cotización nueva arranca con su fila #1 limpia por defecto
        if (typeof addItemRow === 'function') {
            addItemRow();
        }
    }
});