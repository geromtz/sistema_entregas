document.addEventListener("DOMContentLoaded", () => {
  // 🔹 Elementos clave
  const customerInput = document.getElementById("customer");
  const customerList = document.getElementById("customer-list");
  const customerIdInput = document.getElementById("customer_id");
  const systemOrderId = document.getElementById("system_order_id")?.value || 0;
  const userId = document.getElementById("system_order_id")?.dataset.userId || 0;
  const addSupplierBtn = document.getElementById("addSupplierOrder");

  // 🔹 Campos del formulario (excepto cliente y PDF)
  const inputs = document.querySelectorAll(
    "input:not(#customer):not(#customer_id):not(#pdf_order_file), textarea, select"
  );

  let timeout = null;
  let selectedIndex = -1;
  let currentItems = [];
  let isSelectingFromList = false;

   // ---------------------------
  // 🔒 BLOQUEAR / DESBLOQUEAR CAMPOS SEGÚN CLIENTE
  // ---------------------------
  function toggleFields(hasCustomer) {
    inputs.forEach((input) => (input.disabled = !hasCustomer));
    if (addSupplierBtn) addSupplierBtn.disabled = !hasCustomer;
  }

  // Al cargar la página, verifica si ya hay cliente asignado
  const hasCustomerAtStart = !!customerIdInput.value && customerIdInput.value !== "0";
  toggleFields(hasCustomerAtStart);

  // ---------------------------
  // 🔍 AUTOCOMPLETADO DE CLIENTES
  // ---------------------------
  customerInput.addEventListener("input", () => {
    const query = customerInput.value.trim();
    customerIdInput.value = ""; // limpiar id al escribir
    selectedIndex = -1;

    clearTimeout(timeout);
    if (!query) {
      customerList.innerHTML = "";
      currentItems = [];
      return;
    }

    timeout = setTimeout(async () => {
      const results = await fetchCustomers(query);
      currentItems = results;
      renderCustomerList(results);
    }, 300);
  });

  // 🔽 Navegación con teclado
  customerInput.addEventListener("keydown", async (e) => {
    if (!currentItems.length) return;

    if (e.key === "ArrowDown") {
      e.preventDefault();
      selectedIndex = (selectedIndex + 1) % currentItems.length;
      highlightItem();
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      selectedIndex =
        (selectedIndex - 1 + currentItems.length) % currentItems.length;
      highlightItem();
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (selectedIndex >= 0) {
        await selectCustomer(currentItems[selectedIndex]);
      } else {
        const name = customerInput.value.trim();
        const hasId = !!customerIdInput.value;
        if (name && !hasId) await handleClienteNoExistente(name);
      }
      simulateTab(customerInput);
    }
  });

  // 🔹 Click en la lista
  customerList.addEventListener("mousedown", () => (isSelectingFromList = true));
  customerList.addEventListener("mouseup", () =>
    setTimeout(() => (isSelectingFromList = false), 200)
  );

  customerList.addEventListener("click", async (e) => {
    const idx = Array.from(customerList.children).indexOf(e.target);
    if (idx >= 0 && currentItems[idx]) await selectCustomer(currentItems[idx]);
  });

  // ---------------------------
  // 🧠 FUNCIONES AUXILIARES
  // ---------------------------
  async function fetchCustomers(query) {
    if (!query) return [];
    const res = await fetch(
      `index.php?page=search_customer&q=${encodeURIComponent(query)}`
    );
    return await res.json();
  }

  function renderCustomerList(customers) {
    customerList.innerHTML = "";

    if (!customers.length) {
      const item = document.createElement("div");
      item.className = "list-group-item list-group-item-action";
      item.textContent =
        "No encontrado, escribe el nombre y cambia de campo para crear";
      item.dataset.newCustomer = "true";
      customerList.appendChild(item);
      currentItems = [{ newCustomer: true, name: customerInput.value }];
      return;
    }

    customers.forEach((c) => {
      const item = document.createElement("div");
      item.className = "list-group-item list-group-item-action";
      item.textContent = c.business_name?.trim() || "Cliente sin nombre";
      item.dataset.id = c.id;
      customerList.appendChild(item);
    });
  }

  function highlightItem() {
    const items = customerList.querySelectorAll(".list-group-item");
    items.forEach((item, idx) =>
      item.classList.toggle("active", idx === selectedIndex)
    );
  }

  async function selectCustomer(customer) {
    if (customer.newCustomer) {
      const newId = await createCustomer(customer.name);
      if (newId) {
        customerIdInput.value = newId;
        customerInput.value = customer.name;
      }
    } else {
      customerIdInput.value = customer.id;
      customerInput.value = customer.business_name;
    }

    customerList.innerHTML = "";
    currentItems = [];
    selectedIndex = -1;

    await handleCustomerSelected();
    simulateTab(customerInput);
  }

  async function createCustomer(name) {
    const formData = new FormData();
    formData.append("business_name", name);
    formData.append("company_name", "");
    formData.append("created_by", userId);

    try {
      const res = await fetch("index.php?page=create_customer", {
        method: "POST",
        body: formData,
      });

      const text = await res.text();
      console.log("📥 Respuesta cruda del servidor:", text);

      const data = JSON.parse(text);
      if (data.success && data.id) {
        showAlert(`🆕 Cliente creado: ${name}`, "success");
        return data.id;
      } else {
        showAlert("⚠️ No se pudo crear el cliente.", "warning");
        return 0;
      }
    } catch (err) {
      console.error("❌ Error al crear cliente:", err);
      showAlert("❌ Error al crear cliente. Revisa la consola.", "danger");
      return 0;
    }
  }

  // ---------------------------
  // 💾 GUARDAR CLIENTE EN PEDIDO
  // ---------------------------
  async function handleCustomerSelected() {
    const hasCustomer = !!customerIdInput.value;
    inputs.forEach((input) => (input.disabled = !hasCustomer));
    if (addSupplierBtn) addSupplierBtn.disabled = !hasCustomer;

    if (!hasCustomer) return;

    const formData = new FormData();
    formData.append("system_id", systemOrderId);
    formData.append("id_customer", customerIdInput.value);

    console.group("📤 Enviando auto_save_customer");
    console.log("➡️ system_id:", systemOrderId);
    console.log("➡️ id_customer:", customerIdInput.value);
    console.groupEnd();

    try {
      const res = await fetch("index.php?page=auto_save_customer", {
        method: "POST",
        body: formData,
      });

      const text = await res.text();
      console.group("📥 Respuesta del servidor (auto_save_customer)");
      console.log(text);
      console.groupEnd();

      const data = JSON.parse(text);
      if (data.success) {
        console.log("✅ Pedido guardado correctamente.");
      } else {
        showAlert(`⚠️ ${data.message || "Error al guardar pedido."}`, "warning");
      }
    } catch (err) {
      console.error("❌ Error al guardar cliente:", err);
      showAlert("❌ Error inesperado al guardar cliente.", "danger");
    }
  }

  // ---------------------------
  // 🧩 CREACIÓN DE CLIENTE NUEVO (MODAL)
  // ---------------------------
  async function handleClienteNoExistente(name) {
    const confirmar = await confirmarYCrearCliente(name);
    if (!confirmar) {
      showAlert("❌ Creación cancelada por el usuario.", "secondary");
      return;
    }

    showAlert("🆕 Creando cliente...", "info");

    const newId = await createCustomer(name);
    if (newId) {
      customerIdInput.value = newId;
      await handleCustomerSelected();
      showAlert("✅ Cliente creado correctamente.", "success");
    } else {
      showAlert("❌ No se pudo crear el cliente.", "danger");
    }
  }

  function confirmarYCrearCliente(name) {
    return new Promise((resolve) => {
      const modalEl = document.getElementById("confirmCreateModal");
      const modal = new bootstrap.Modal(modalEl);
      const nameEl = document.getElementById("customerNameToCreate");
      const confirmBtn = document.getElementById("confirmCreateBtn");

      nameEl.textContent = name;
      modal.show();

      modalEl.addEventListener(
        "shown.bs.modal",
        () => confirmBtn.focus(),
        { once: true }
      );

      const confirmHandler = () => {
        modal.hide();
        resolve(true);
      };

      confirmBtn.addEventListener("click", confirmHandler, { once: true });

      modalEl.addEventListener(
        "keydown",
        (e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            confirmHandler();
          }
        },
        { once: true }
      );

      modalEl.addEventListener(
        "hidden.bs.modal",
        () => resolve(false),
        { once: true }
      );
    });
  }

  // ---------------------------
  // 🧰 UTILIDADES
  // ---------------------------
  function showAlert(message, type = "info", timeout = 4000) {
    const container = document.getElementById("alertContainer");
    if (!container) return;

    const wrapper = document.createElement("div");
    wrapper.innerHTML = `
      <div class="alert alert-${type} alert-dismissible fade show shadow-sm mb-2" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    `;
    container.appendChild(wrapper);

    setTimeout(() => {
      const alert = bootstrap.Alert.getOrCreateInstance(
        wrapper.querySelector(".alert")
      );
      alert.close();
    }, timeout);
  }

  function simulateTab(element) {
    const focusable = Array.from(
      document.querySelectorAll(
        'input, select, textarea, button, [tabindex]:not([tabindex="-1"])'
      )
    ).filter(
      (el) =>
        !el.disabled &&
        el.offsetParent !== null &&
        el.type !== "hidden"
    );

    const currentIndex = focusable.indexOf(element);
    const next = focusable[currentIndex + 1];
    if (next) next.focus();
  }
});
