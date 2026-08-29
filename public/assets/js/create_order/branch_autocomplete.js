// public/assets/js/create_order/branch_autocomplete.js
document.addEventListener("DOMContentLoaded", () => {
    window.initBranchAutocomplete = (inputEl, hiddenEl, listEl, supplierIdEl) => {
        if (!inputEl || !listEl || !hiddenEl || !supplierIdEl) return;

        let timer;
        let activeIndex = -1;
        const MIN = 1;
<<<<<<< HEAD
        let currentBranches = [];
=======
        let currentBranches = []; // Espejo en memoria para validación instantánea
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547

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

<<<<<<< HEAD
=======
        // --- 🔍 BUSCAR SUCURSALES (INPUT) ---
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
        inputEl.addEventListener("input", () => {
            const term = inputEl.value.trim();
            hiddenEl.value = "";
            clearTimeout(timer);

            const supplierId = supplierIdEl.value;
            if (!supplierId || term.length < MIN) {
                clearList();
                currentBranches = [];
                return;
            }

            timer = setTimeout(async () => {
                try {
                    const res = await fetch(
                        `index.php?page=supplier_branches_list&id_supplier=${supplierId}&term=${encodeURIComponent(term)}`
                    );
                    const data = await res.json();
<<<<<<< HEAD
                    currentBranches = (data.success && Array.isArray(data.data)) ? data.data : [];
=======

                    // Actualizamos el espejo en memoria
                    currentBranches = (data.success && Array.isArray(data.data)) ? data.data : [];

>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
                    clearList();

                    if (currentBranches.length > 0) {
                        currentBranches.forEach((b) => {
                            const btn = document.createElement("button");
                            btn.type = "button";
                            btn.className = "list-group-item list-group-item-action";
                            btn.textContent = b.branch || `Sucursal #${b.id}`;
                            btn.dataset.id = b.id;

                            btn.addEventListener("click", () => {
                                inputEl.value = b.branch;
                                hiddenEl.value = b.id;
                                clearList();
<<<<<<< HEAD
=======
                                // Al click, saltamos al siguiente campo lógico
                                document.getElementsByName("id_order")[0]?.focus();
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
                            });
                            listEl.appendChild(btn);
                        });
                        activeIndex = -1;
                    }
                } catch (err) {
                    console.error("Autocomplete error (branch):", err);
                }
            }, 250);
        });

<<<<<<< HEAD
=======
        // --- ⌨️ NAVEGACIÓN Y VALIDACIÓN (KEYDOWN) ---
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
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
<<<<<<< HEAD
                activeIndex = (activeIndex - 1 + total) % total;
                highlightItem(activeIndex);
            } else if (e.key === "Enter" || e.key === "Tab") {
=======
                activeIndex = (activeIndex - 1 + total) % total; // Corregido: antes tenías un menos doble
                highlightItem(activeIndex);
            } else if (e.key === "Enter" || e.key === "Tab") {

>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
                const branchName = inputEl.value.trim();
                const supplierId = supplierIdEl.value;
                if (branchName.length < 1) return;

<<<<<<< HEAD
=======
                // 1️⃣ CASO: Selección manual con flechas
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
                if (activeIndex >= 0 && activeIndex < total) {
                    const selected = items[activeIndex];
                    inputEl.value = selected.textContent;
                    hiddenEl.value = selected.dataset.id;
                    clearList();
<<<<<<< HEAD
                    return;
                }

=======
                    document.getElementsByName("id_order")[0]?.focus();
                    return;
                }

                // 2️⃣ CASO: VALIDACIÓN INSTANTÁNEA (Espejo)
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
                const exactMatch = currentBranches.find(b =>
                    (b.branch || "").toLowerCase() === branchName.toLowerCase()
                );

                if (exactMatch) {
                    hiddenEl.value = exactMatch.id;
                    inputEl.value = exactMatch.branch;
                    clearList();
<<<<<<< HEAD
                    return;
                }

                if (!hiddenEl.value && branchName.length >= MIN) {
                    if (!supplierId) {
                        e.preventDefault();
                        window.showAlert?.("⚠️ Selecciona primero un proveedor.", "warning") || alert("Selecciona primero un proveedor.");
=======
                    if (e.key === "Enter") document.getElementsByName("id_order")[0]?.focus();
                    return;
                }

                // 3️⃣ CASO: CREACIÓN
                if (!hiddenEl.value && branchName.length >= MIN) {
                    if (!supplierId) {
                        e.preventDefault();
                        window.showAlert("⚠️ Selecciona primero un proveedor.", "warning");
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
                        document.getElementById("supplier")?.focus();
                        return;
                    }

                    if (e.key === "Tab") e.preventDefault();

                    const userConfirmed = await confirmWithModal(branchName);

                    if (userConfirmed) {
                        try {
                            const res = await fetch("index.php?page=create_supplier_branch", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: `id_supplier=${encodeURIComponent(supplierId)}&branch=${encodeURIComponent(branchName)}`,
                            });

                            const data = await res.json();
                            if (data.success && data.id) {
                                hiddenEl.value = data.id;
<<<<<<< HEAD
                                window.showAlert?.(`Sucursal "${branchName}" creada.`, "success");
                            } else {
                                alert(`⚠️ ${data.message || "Error al crear."}`);
                            }
                        } catch (err) {
                            alert("Error de conexión.");
=======
                                window.showAlert(`Sucursal "${branchName}" creada.`, "success");
                                document.getElementsByName("id_order")[0]?.focus();
                            } else {
                                window.showAlert(`⚠️ ${data.message || "Error al crear."}`, "error");
                            }
                        } catch (err) {
                            window.showAlert("Error de conexión.", "error");
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
                        }
                    } else {
                        inputEl.value = "";
                        hiddenEl.value = "";
<<<<<<< HEAD
=======
                        inputEl.focus();
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
                    }
                }
            }
        });

<<<<<<< HEAD
=======
        /**
         * Lógica del Modal (Consistente con Supplier)
         */
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
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

<<<<<<< HEAD
=======
        // Cerrar lista si clic fuera
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
        document.addEventListener("click", (ev) => {
            if (!listEl.contains(ev.target) && ev.target !== inputEl) clearList();
        });
    };

<<<<<<< HEAD
    // 🔄 Inicializador global para la sucursal principal y las filas dinámicas de Varias Sucursales
    const initAllBranches = () => {
        const supplierHidden = document.getElementById("supplier_id");

        // 1. Sucursal principal del form general
        const inputBranch = document.getElementById("branch");
        const hiddenBranch = document.getElementById("branch_id");
        const listBranch = document.getElementById("branch-list");

        if (inputBranch && hiddenBranch && listBranch && supplierHidden) {
            window.initBranchAutocomplete(inputBranch, hiddenBranch, listBranch, supplierHidden);
        }

        // 2. Sucursales de origen dinámicas en las filas de Varias Sucursales
        document.querySelectorAll(".branch-search").forEach(input => {
            if (input.dataset.branchInitialized) return;
            input.dataset.branchInitialized = "true";

            const td = input.closest("td");
            if (!td) return;

            const hidden = td.querySelector(".branch-id");
            const list = td.querySelector(".branch-list");

            if (input && hidden && list && supplierHidden) {
                window.initBranchAutocomplete(input, hidden, list, supplierHidden);
            }
        });
    };

    // Ejecutar al cargar
    initAllBranches();

    // Vigilar cuando agreguen nuevas filas con el botón para inicializarlas también
    document.addEventListener("click", (e) => {
        if (e.target && e.target.classList.contains("add-row-btn")) {
            setTimeout(initAllBranches, 100);
        }
    });
=======
    // Inicialización automática
    const inputBranch = document.getElementById("branch");
    const hiddenBranch = document.getElementById("branch_id");
    const listBranch = document.getElementById("branch-list");
    const supplierHidden = document.getElementById("supplier_id");

    if (inputBranch && hiddenBranch && listBranch && supplierHidden) {
        window.initBranchAutocomplete(inputBranch, hiddenBranch, listBranch, supplierHidden);
    }
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
});