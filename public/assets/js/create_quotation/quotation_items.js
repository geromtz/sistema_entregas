document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = 0;
    const itemsContainer = document.getElementById('items_container');
    const template = document.getElementById('item_row_template');

    if (!itemsContainer || !template) return;

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

                                // 🚀 AQUÍ "ENCENDEMOS" LA CARGA DE SUCURSALES EN EL SELECT
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

    // Función para cargar las sucursales vía AJAX al <select>
    async function loadBranchesIntoSelect(row, supplierId) {
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
            // Asignamos el nombre de la sucursal seleccionada al input hidden para que no viaje vacío
            branchNameHidden.value = (branchSelect.value !== "") ? selectedOption.textContent.trim() : "";

            // Limpiamos clase de error si la tuviera
            branchSelect.classList.remove('is-invalid', 'border-danger');
        });
    }

    // --- 🛠️ CARGAR PARTIDA EXISTENTE (MODO EDICIÓN) ---
    function addExistingItemRow(itemData) {
        const clone = template.content.cloneNode(true);
        const rowEl = clone.querySelector('.item-row');

        // Reemplazamos el INDEX por el índice actual de la fila
        rowEl.innerHTML = rowEl.innerHTML.replaceAll('INDEX', rowIndex);
        itemsContainer.appendChild(rowEl); // 👈 Esto es lo que dibuja físicamente la fila en la pantalla

        const newRow = itemsContainer.lastElementChild;

        // Inicializamos todos los eventos y autocompletados de esta fila
        initRowEvents(newRow);
        initProductAutocomplete(newRow);
        initSupplierAutocompleteForRow(newRow);
        initBranchValidation(newRow);
        initProductFieldWatcher(newRow);
        initBranchSelectEvents(newRow);

        // Asignamos número de partida
        const itemNumInput = newRow.querySelector('.item-number-input');
        if (itemNumInput) itemNumInput.value = itemData.item_number || (rowIndex + 1);

        const rowNumBadge = newRow.querySelector('.row-number');
        if (rowNumBadge) rowNumBadge.textContent = `#${itemData.item_number || (rowIndex + 1)}`;

        // Rellenar Producto
        const partInput = newRow.querySelector('.product-search-part');
        const descInput = newRow.querySelector('.product-search-desc');
        const productIdInput = newRow.querySelector('.product-id');

        if (partInput) partInput.value = itemData.product_part_number || itemData.part_number || '';
        if (descInput) {
            descInput.value = itemData.product_description || itemData.description || '';
            descInput.disabled = false;
        }
        if (productIdInput) productIdInput.value = itemData.product_id || '';

        // Rellenar Cantidad y Costos Financieros
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

        // Rellenar Proveedor y Sucursal
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

        // Tiempos de entrega
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

    // --- GESTIÓN DE FILAS ---
    function addItemRow() {
        const clone = template.content.cloneNode(true);
        const rowEl = clone.querySelector('.item-row');

        rowEl.innerHTML = rowEl.innerHTML.replaceAll('INDEX', rowIndex);

        itemsContainer.appendChild(rowEl);

        const newRow = itemsContainer.lastElementChild;
        initRowEvents(newRow);
        initProductAutocomplete(newRow);
        initSupplierAutocompleteForRow(newRow);
        initBranchValidation(newRow);
        initProductFieldWatcher(newRow)
        initBranchSelectEvents(newRow)
        rowIndex++;
        updateRowNumbers();
        calculateAll();
    }

    function updateRowNumbers() {
        const rows = itemsContainer.querySelectorAll('.item-row');
        rows.forEach((row, idx) => {
            row.querySelector('.row-number').textContent = `#${idx + 1}`;
            row.querySelector('.item-number-input').value = idx + 1;
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
                alert('La cotización requiere al menos una partida.');
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
        const freight = parseFloat(row.querySelector('.freight-input').value) || 0; // Flete único por partida
        let profitPct = parseFloat(row.querySelector('.profit-input').value) || 0;

        const priceInput = row.querySelector('.price-input');
        const totalPriceInput = row.querySelector('.total-price-input');
        const netProfitInput = row.querySelector('.net-profit-input');
        const netProfitDisplay = row.querySelector('.net-profit-display');

        let unitPrice = 0;
        let netProfitPerUnit = 0;

        if (!userChangedPrice) {
            // 1. Calculamos el precio unitario limpio del producto (sin flete)
            const productProfit = cost * (profitPct / 100);
            unitPrice = cost + productProfit;

            priceInput.value = unitPrice.toFixed(2);
        } else {
            // Si el usuario modifica el precio unitario manualmente
            unitPrice = parseFloat(priceInput.value) || 0;

            if (cost > 0) {
                profitPct = ((unitPrice - cost) / cost) * 100;
                row.querySelector('.profit-input').value = profitPct.toFixed(2);
            }
        }

        // 2. La utilidad neta unitaria (basada en el producto, el flete es costo logístico)
        netProfitPerUnit = unitPrice - cost;

        // 3. EL CAMBIO CLAVE: El total de la partida es (Precio Unitario * Cantidad) + Flete ÚNICO
        const totalPrice = (unitPrice * qty) + freight;
        const totalNetProfit = netProfitPerUnit * qty; // La utilidad sigue siendo de las piezas vendidas

        totalPriceInput.value = totalPrice.toFixed(2);
        netProfitInput.value = totalNetProfit.toFixed(2);
        netProfitDisplay.value = `$${totalNetProfit.toFixed(2)}`;
    }

    function calculateAll() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const totalPrice = parseFloat(row.querySelector('.total-price-input').value) || 0;
            subtotal += totalPrice;

        });

        const iva = subtotal * 0.16;
        const total = subtotal + iva;

        document.getElementById('lbl_subtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('input_subtotal').value = subtotal.toFixed(2);

        document.getElementById('lbl_iva').textContent = `$${iva.toFixed(2)}`;
        document.getElementById('input_iva').value = iva.toFixed(2);

        document.getElementById('lbl_total').textContent = `$${total.toFixed(2)}`;
        document.getElementById('input_total').value = total.toFixed(2);
    }

    const addItemBtn = document.getElementById('add_item_btn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => addItemRow());
    }

    // --- 🏢 APERTURA INTELIGENTE DEL MODAL DE SUCURSAL ---
    document.addEventListener('click', function (e) {
        if (e.target && (e.target.id === 'btn_quick_branch' || e.target.closest('#btn_quick_branch'))) {

            // Intentamos detectar si el usuario dio clic en un botón asociado a una fila específica
            const row = e.target.closest('.item-row');
            const supplierIdInput = row ? row.querySelector('.supplier-id') : null;
            const supplierSearchInput = row ? row.querySelector('.supplier-search') : null;

            const modalSupplierIdInput = document.getElementById('modal_id_supplier');
            const modalSupplierSearchInput = document.getElementById('modal_supplier_search');

            // Si la fila tiene un proveedor ya seleccionado, lo pre-cargamos en el modal por comodidad
            if (supplierIdInput && supplierIdInput.value.trim() !== '') {
                modalSupplierIdInput.value = supplierIdInput.value.trim();
                modalSupplierSearchInput.value = supplierSearchInput.value.trim();
            } else {
                // Si la fila está vacía, dejamos limpio para que busquen libremente
                modalSupplierIdInput.value = '';
                modalSupplierSearchInput.value = '';
            }

            // Abrimos el modal de manera segura con Bootstrap
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

    // ==========================================
    // 1. GUARDAR PROVEEDOR RÁPIDO (AJAX)
    // ==========================================
    const saveSupplierBtn = document.getElementById('saveQuickSupplierBtn');
    if (saveSupplierBtn) {
        saveSupplierBtn.addEventListener('click', async () => {
            const form = document.getElementById('quickSupplierForm');
            const formData = new FormData(form);

            try {
                // Apunta a la ruta 'create_supplier' que ya tienes registrada en app/routes.php
                const res = await fetch('index.php?page=create_supplier', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success && data.id) {
                    window.showAppAlert('Proveedor creado exitosamente.', 'success');

                    // Cerramos el modal de forma segura usando Bootstrap
                    const modalEl = document.getElementById('quickSupplierModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();

                    form.reset();

                    // Opcional inteligente: Si el usuario estaba escribiendo en la fila activa, 
                    // podemos inyectar el nuevo proveedor directamente en el input de la fila actual.
                    const activeRow = document.querySelector('.item-row');
                    if (activeRow) {
                        const suppInput = activeRow.querySelector('.supplier-search');
                        const suppIdInput = activeRow.querySelector('.supplier-id');
                        const branchInput = activeRow.querySelector('.branch-search');

                        if (suppInput && suppIdInput) {
                            // Tomamos el nombre comercial ingresado
                            const businessName = formData.get('business_name');
                            suppInput.value = businessName;
                            suppIdInput.value = data.id;

                            // Habilitamos el campo de sucursal para que pueda escribirle sucursal si gusta
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

    // ==========================================
    // 2. GUARDAR SUCURSAL RÁPIDA (AJAX)
    // ==========================================
    const saveBranchBtn = document.getElementById('saveQuickBranchBtn');
    if (saveBranchBtn) {
        saveBranchBtn.addEventListener('click', async () => {
            const form = document.getElementById('quickBranchForm');
            const formData = new FormData(form);

            try {
                // Apunta a la ruta 'create_supplier_branch' que ya tienes registrada en app/routes.php
                const res = await fetch('index.php?page=create_supplier_branch', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success && data.id) {
                    window.showAppAlert('Sucursal creada exitosamente.', 'success');

                    // Cerramos el modal de forma segura
                    const modalEl = document.getElementById('quickBranchModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();

                    // Guardamos el nombre de la sucursal que acaban de escribir antes de resetear
                    const createdBranchName = formData.get('branch');
                    const createdBranchId = data.id;
                    const supplierIdUsed = formData.get('id_supplier');

                    form.reset();

                    // Opcional inteligente: Inyectar esta nueva sucursal en la fila activa del proveedor correspondiente
                    const activeRow = document.querySelector('.item-row');
                    if (activeRow) {
                        const rowSuppId = activeRow.querySelector('.supplier-id')?.value;
                        // Validamos que la fila pertenezca al mismo proveedor al que se le creó la sucursal
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

    // Validación inteligente para el <select> de sucursal al salir del campo (blur) o cambiar de elemento
    function initBranchValidation(row) {
        const supplierIdInput = row.querySelector('.supplier-id');
        const branchSelect = row.querySelector('.branch-select');

        if (!branchSelect || !supplierIdInput) return;

        branchSelect.addEventListener('blur', () => {
            setTimeout(() => {
                const supplierId = supplierIdInput.value.trim();
                const branchValue = branchSelect.value.trim();

                // CASO 1: Hay un proveedor seleccionado...
                if (supplierId !== '') {

                    // ...PERO el select sigue en valor vacío ("Seleccione sucursal...")
                    if (branchValue === '') {
                        branchSelect.classList.add('is-invalid', 'border-danger');

                        window.showAppAlert(
                            'Sucursal obligatoria: Si es la única sucursal en Monterrey, favor de registrarla con "+ Sucursal" o especificarla.',
                            'warning'
                        );

                        branchSelect.focus();

                        setTimeout(() => {
                            branchSelect.classList.remove('is-invalid');
                        }, 3000);
                    }
                    // CASO 2: El usuario SÍ seleccionó una sucursal válida del desplegable
                    else {
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
            // Si modifican o vacían el texto, limpiamos por completo el ID y la descripción
            if (productIdInput) productIdInput.value = '';
            if (descInput) {
                descInput.value = '';
                descInput.disabled = true;
            }
        });
    }

// --- 🚀 INICIALIZACIÓN INTELIGENTE (NUEVO VS EDICIÓN / RE-COTIZACIÓN) ---
    const quotationIdInput = document.querySelector('input[name="id"]');
    const existingRows = document.querySelectorAll('.item-row');

    // Si estamos editando o recotizando y PHP ya pintó filas en el HTML:
    if (existingRows.length > 0) {
        existingRows.forEach((row, index) => {
            row.dataset.index = index;

            // Inicializamos eventos y autocompletados
            initRowEvents(row);
            initProductAutocomplete(row);
            initSupplierAutocompleteForRow(row);
            initBranchValidation(row);
            initProductFieldWatcher(row);
            initBranchSelectEvents(row);

            // 1. Sincronizamos los valores que PHP trajo en el HTML a los inputs activos
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

            // 2. 🔹 CORRECCIÓN CLAVE: Calculamos matemáticamente el total de esta partida 
            // con base en sus valores reales restaurados para que el input oculto no vaya vacío o erróneo.
            const qty = parseFloat(qtyInput?.value) || 0;
            const unitPrice = parseFloat(priceInput?.value) || 0;
            const freight = parseFloat(freightInput?.value) || 0;
            const cost = parseFloat(costInput?.value) || 0;

            const totalPrice = (unitPrice * qty) + freight;
            const netProfitPerUnit = unitPrice - cost;
            const totalNetProfit = netProfitPerUnit * qty;

            // Actualizamos los campos ocultos y de despliegue de la partida
            const totalPriceInput = row.querySelector('.total-price-input');
            const netProfitInput = row.querySelector('.net-profit-input');
            const netProfitDisplay = row.querySelector('.net-profit-display');

            if (totalPriceInput) totalPriceInput.value = totalPrice.toFixed(2);
            if (netProfitInput) netProfitInput.value = totalNetProfit.toFixed(2);
            if (netProfitDisplay) netProfitDisplay.value = `$${totalNetProfit.toFixed(2)}`;
        });

        // 3. Actualizamos numeración y ahora sí calculamos los totales generales de forma perfecta
        updateRowNumbers();
        calculateAll();

        rowIndex = existingRows.length;

    } else {
        // Si es una cotización nueva, arrancamos con una fila vacía
        if (typeof addItemRow === 'function') {
            addItemRow();
        }
    }
});