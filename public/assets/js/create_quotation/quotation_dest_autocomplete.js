document.addEventListener("DOMContentLoaded", () => {
    // 🔹 Elementos clave actualizados para Cotizaciones
    const searchInput = document.getElementById("search_target"); // El input de texto visual
    const searchList = document.getElementById("search_list");     // El contenedor de la lista flotante
    const searchLabel = document.getElementById("search_label");   // La etiqueta dinámica
    const customerIdInput = document.getElementById("id_customer"); // Input oculto ID Cliente
    const prospectIdInput = document.getElementById("id_prospect"); // Input oculto ID Prospecto
    const radios = document.querySelectorAll(".dest-radio");         // Radios de Cliente vs Prospecto

    // Elementos del modal rápido de contactos
    const btnQuickContact = document.getElementById("btn_quick_contact");
    const qcCustomerId = document.getElementById("qc_customer_id");

    // Obtener ID del usuario desde la sesión o contenedor global
    const userId = document.body.dataset.userId || 1;

    let currentEndpoint = "index.php?page=search_customer"; // Por defecto busca clientes
    let timeout = null;
    let currentItems = [];

    // 🔹 DETECCIÓN MODO EDICIÓN / RE-COTIZACIÓN: Si ya viene un cliente o prospecto seleccionado desde PHP
    if (customerIdInput && customerIdInput.value.trim() !== "") {
        document.getElementById("type_customer").checked = true;
        currentEndpoint = "index.php?page=search_customer";
        searchLabel.textContent = "Cliente Seleccionado";
        if (btnQuickContact) btnQuickContact.disabled = false;
        if (qcCustomerId) qcCustomerId.value = customerIdInput.value;
        
        // 🔹 CORRECCIÓN: Cargar los contactos del cliente precargado para que el autocompletado de "Atención a" funcione
        handleDestinoSeleccionado('customer', customerIdInput.value);

    } else if (prospectIdInput && prospectIdInput.value.trim() !== "") {
        document.getElementById("type_prospect").checked = true;
        currentEndpoint = "index.php?page=search_prospect";
        searchLabel.textContent = "Prospecto Seleccionado";
        if (btnQuickContact) btnQuickContact.disabled = true;
        if (qcCustomerId) qcCustomerId.value = "";
    }

    // ==========================================================
    // ⌨️ FUNCIÓN GENÉRICA DE NAVEGACIÓN POR TECLADO (UNIVERSAL)
    // ==========================================================
    function setupKeyboardNavigation(inputEl, listEl) {
        let selectedIndex = -1;

        function resetSelection() {
            selectedIndex = -1;
            updateHighlight();
        }

        function updateHighlight() {
            const items = listEl.querySelectorAll(".list-group-item");
            items.forEach((item, idx) => {
                item.classList.toggle("active", idx === selectedIndex);
            });
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

    // 🚀 ACTIVAR NAVEGACIÓN POR TECLADO EN EL BUSCADOR PRINCIPAL
    setupKeyboardNavigation(searchInput, searchList);

    // ---------------------------
    // 🔀 CAMBIO DINÁMICO ENTRE CLIENTE Y PROSPECTO
    // ---------------------------
    radios.forEach(radio => {
        radio.addEventListener("change", (e) => {
            searchInput.value = "";
            customerIdInput.value = "";
            prospectIdInput.value = "";
            searchList.innerHTML = "";
            currentItems = [];

            if (btnQuickContact) btnQuickContact.disabled = true;

            if (e.target.value === "customer") {
                currentEndpoint = "index.php?page=search_customer";
                searchLabel.textContent = "Seleccionar Cliente Registrado";
                searchInput.placeholder = "Escribe el nombre del cliente...";
            } else {
                currentEndpoint = "index.php?page=search_prospect";
                searchLabel.textContent = "Seleccionar Prospecto del Embudo";
                searchInput.placeholder = "Escribe el nombre del prospecto...";
            }
        });
    });

    // ---------------------------
    // 🔍 AUTOCOMPLETADO INTELIGENTE (PRINCIPAL)
    // ---------------------------
    searchInput.addEventListener("input", () => {
        const query = searchInput.value.trim();
        customerIdInput.value = "";
        prospectIdInput.value = "";

        if (btnQuickContact) btnQuickContact.disabled = true;

        clearTimeout(timeout);
        if (!query) {
            searchList.innerHTML = "";
            currentItems = [];
            return;
        }

        timeout = setTimeout(async () => {
            const results = await fetchResults(query, currentEndpoint);
            currentItems = results;
            renderList(results);
        }, 300);
    });

    // 🔹 Click en la lista flotante principal
    searchList.addEventListener("click", async (e) => {
        const idx = Array.from(searchList.children).indexOf(e.target);
        if (idx >= 0 && currentItems[idx]) {
            await selectItem(currentItems[idx]);
        }
    });

    // ---------------------------
    // 🧠 FUNCIONES AUXILIARES PRINCIPALES
    // ---------------------------
    async function fetchResults(query, endpoint) {
        if (!query) return [];
        try {
            const res = await fetch(`${endpoint}&q=${encodeURIComponent(query)}`);
            return await res.json();
        } catch (err) {
            console.error("❌ Error en búsqueda:", err);
            return [];
        }
    }

    function renderList(items) {
        searchList.innerHTML = "";

        if (!items.length) {
            const item = document.createElement("div");
            item.className = "list-group-item list-group-item-action text-muted small";
            item.textContent = "No se encontraron resultados.";
            searchList.appendChild(item);
            return;
        }

        items.forEach((item) => {
            const div = document.createElement("div");
            div.className = "list-group-item list-group-item-action";
            div.textContent = item.business_name?.trim() || item.company_name?.trim() || "Sin nombre";
            div.dataset.id = item.id;
            searchList.appendChild(div);
        });
    }

    async function selectItem(item) {
        const isCustomerMode = document.getElementById("type_customer").checked;
        const tipo = isCustomerMode ? 'customer' : 'prospect';
        const inputAttention = document.querySelector("input[name='attention_to']");

        if (isCustomerMode) {
            customerIdInput.value = item.id;
            prospectIdInput.value = "";
            searchInput.value = item.business_name;

            if (btnQuickContact) btnQuickContact.disabled = false;
            if (qcCustomerId) qcCustomerId.value = item.id;

            await handleDestinoSeleccionado(tipo, item.id);
        } else {
            prospectIdInput.value = item.id;
            customerIdInput.value = "";
            searchInput.value = item.company_name;

            if (btnQuickContact) btnQuickContact.disabled = true;
            if (qcCustomerId) qcCustomerId.value = "";

            currentClientContacts = [];
            if (inputAttention) {
                inputAttention.value = item.contact_name || "";
            }
        }

        searchList.innerHTML = "";
        currentItems = [];

        await handleDestinoSeleccionado(tipo, item.id);
    }

    async function handleDestinoSeleccionado(tipo, id) {
        const inputAttention = document.querySelector("input[name='attention_to']");

        if (tipo === 'customer') {
            try {
                const res = await fetch(`index.php?page=get_customer_contacts&customer_id=${id}`);
                const contactos = await res.json();

                if (Array.isArray(contactos) && contactos.length > 0) {
                    currentClientContacts = contactos;
                    // Solo sobreescribimos la atención si es nuevo, para no borrar el contacto existente al editar
                    if (!inputAttention.value.trim()) {
                        const principal = contactos.find(c => c.is_primary == 1) || contactos[0];
                        inputAttention.value = principal.contact_name;
                    }
                } else {
                    currentClientContacts = [];
                }
            } catch (err) {
                console.error("Error al cargar contactos del cliente:", err);
            }
        } else {
            currentClientContacts = [];
        }
    }

    // ---------------------------
    // 💾 GUARDAR NUEVO CONTACTO RÁPIDO (MODAL)
    // ---------------------------
    const saveContactBtn = document.getElementById("save_quick_contact_btn");
    if (saveContactBtn) {
        saveContactBtn.addEventListener("click", async () => {
            const form = document.getElementById("quick_contact_form");
            const formData = new FormData(form);

            const nuevoNombre = formData.get("contact_name");
            const nuevoPuesto = formData.get("position");
            const nuevoTelefono = formData.get("phone");
            const nuevoEmail = formData.get("email");

            try {
                const res = await fetch("index.php?page=store_quick_contact", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    attentionInput.value = nuevoNombre;

                    const nuevoContactoObjeto = {
                        id: data.id || Date.now(),
                        contact_name: nuevoNombre,
                        position: nuevoPuesto,
                        phone: nuevoTelefono,
                        email: nuevoEmail,
                        is_primary: 0
                    };

                    currentClientContacts.push(nuevoContactoObjeto);

                    const modalEl = document.getElementById("quickContactModal");
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    form.reset();
                } else {
                    alert("Error al guardar el contacto: " + (data.message || "Desconocido"));
                }
            } catch (err) {
                console.error("Error en petición:", err);
            }
        });
    }

    // ==========================================================
    // 👥 AUTOCOMPLETADO Y NAVEGACIÓN PARA "ATENCIÓN A" (CONTACTOS)
    // ==========================================================
    let currentClientContacts = [];
    const attentionInput = document.getElementById("attention_to_input");
    const contactSearchList = document.getElementById("contact_search_list");

    if (attentionInput && contactSearchList) {
        attentionInput.addEventListener("input", () => {
            const query = attentionInput.value.toLowerCase().trim();
            contactSearchList.innerHTML = "";

            const isCustomerMode = document.getElementById("type_customer")?.checked;
            if (!isCustomerMode || !currentClientContacts.length || !query) return;

            const filtrados = currentClientContacts.filter(c =>
                c.contact_name.toLowerCase().includes(query) ||
                (c.position && c.position.toLowerCase().includes(query))
            );

            if (!filtrados.length) return;

            filtrados.forEach(c => {
                const div = document.createElement("div");
                div.className = "list-group-item list-group-item-action py-1 px-2 small";
                div.innerHTML = `<strong>${c.contact_name}</strong> <span class="text-muted">(${c.position || 'Sin puesto'})</span>`;

                div.addEventListener("click", () => {
                    attentionInput.value = c.contact_name;
                    contactSearchList.innerHTML = "";
                });
                contactSearchList.appendChild(div);
            });
        });

        setupKeyboardNavigation(attentionInput, contactSearchList);

        document.addEventListener("click", (e) => {
            if (!attentionInput.contains(e.target) && !contactSearchList.contains(e.target)) {
                contactSearchList.innerHTML = "";
            }
        });
    }
});