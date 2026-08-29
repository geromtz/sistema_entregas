// public/assets/js/create_order/supplier_autocomplete.js
document.addEventListener("DOMContentLoaded", () => {
    window.initSupplierAutocomplete = (inputEl, hiddenEl, listEl) => {
        if (!inputEl || !listEl || !hiddenEl) return;

        let timer;
        let activeIndex = -1;
        const MIN = 2;
        let currentSuppliers = []; // Espejo en memoria local al scope de la función

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

                    // Actualizamos el espejo en memoria
                    currentSuppliers = (data.success && Array.isArray(data.data)) ? data.data : [];

                    clearList();

                    if (currentSuppliers.length > 0) {
                        currentSuppliers.forEach((s) => {
                            const btn = document.createElement("button");
                            btn.type = "button";
                            btn.className = "list-group-item list-group-item-action";
                            // Priorizamos business_name, luego company_name
                            btn.textContent = s.business_name || s.company_name || `#${s.id}`;
                            btn.dataset.id = s.id;

                            btn.addEventListener("click", () => {
                                inputEl.value = s.business_name || s.company_name || `#${s.id}`;
                                hiddenEl.value = s.id;
                                clearList();
                                // Al hacer click, movemos foco a sucursal
                                document.getElementById("branch")?.focus();
                            });
                            listEl.appendChild(btn);
                        });
                    }
                } catch (e) {
                    console.error("Autocomplete error:", e);
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

                // 1️⃣ CASO: Selección manual con flechas
                if (activeIndex >= 0 && activeIndex < total) {
                    const selected = items[activeIndex];
                    inputEl.value = selected.textContent;
                    hiddenEl.value = selected.dataset.id;
                    clearList();
                    // Movemos foco manualmente si es necesario
                    document.getElementById("branch")?.focus();
                    return;
                }

                // 2️⃣ CASO: VALIDACIÓN INSTANTÁNEA (El "Espejo")
                const exactMatch = currentSuppliers.find(s => {
                    const name = (s.business_name || s.company_name || "").toLowerCase();
                    return name === supplierName.toLowerCase();
                });

                if (exactMatch) {
                    hiddenEl.value = exactMatch.id;
                    inputEl.value = exactMatch.business_name || exactMatch.company_name;
                    clearList();
                    // Importante: No detenemos el TAB para que el navegador cambie de campo
                    // Pero si es Enter, forzamos el foco
                    if (e.key === "Enter") document.getElementById("branch")?.focus();
                    return;
                }

                // 3️⃣ CASO: CREACIÓN
                if (!hiddenEl.value) {
                    // Si es Tab, detenemos la navegación para obligar a confirmar
                    if (e.key === "Tab") e.preventDefault();

                    const userConfirmed = await confirmWithModal(supplierName);

                    if (userConfirmed) {
                        try {
                            const res = await fetch("index.php?page=create_supplier", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: `business_name=${encodeURIComponent(supplierName)}`,
                            });
                            const data = await res.json();

                            if (data.success && data.id) {
                                hiddenEl.value = data.id;
                                window.showAlert(`Proveedor "${supplierName}" creado.`, "success");
                                document.getElementById("branch")?.focus();
                            } else {
                                window.showAlert(data.message || "Error al crear.", "error");
                            }
                        } catch (err) {
                            window.showAlert("Error de conexión.", "error");
                        }
                    } else {
                        inputEl.value = "";
                        hiddenEl.value = "";
                        inputEl.focus();
                    }
                }
            }
        });

        /**
         * Lógica del Modal (Dentro del scope para evitar conflictos)
         */
        function confirmWithModal(name) {
            return new Promise((resolve) => {
                const modalEl = document.getElementById('confirmCreateModal');
                const confirmBtn = document.getElementById('confirmCreateBtn');
                const modalText = document.getElementById('customerNameToCreate');

                if (!modalEl || !confirmBtn) return resolve(false);

                modalText.textContent = name;
                const bsModal = new bootstrap.Modal(modalEl);
                bsModal.show();

                confirmBtn.onclick = () => {
                    bsModal.hide();
                    resolve(true);
                };

                modalEl.addEventListener('hidden.bs.modal', () => {
                    resolve(false);
                }, { once: true });
            });
        }

        // Cerrar lista si clic fuera
        document.addEventListener("click", (ev) => {
            if (!listEl.contains(ev.target) && ev.target !== inputEl) clearList();
        });
    };

    // Inicialización automática
    const input = document.getElementById("supplier");
    const hidden = document.getElementById("supplier_id");
    const list = document.getElementById("supplier-list");

    if (input && hidden && list) {
        window.initSupplierAutocomplete(input, hidden, list);
    }
});