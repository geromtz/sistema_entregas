// public/assets/js/create_order/supplier_form_manager.js
document.addEventListener("DOMContentLoaded", () => {
  // Global para que otros módulos sepan cuál form está activo
  window.activeSupplierFormId = null;

  const addSupplierBtn       = document.getElementById("addSupplierOrder");
  const supplierFormWrapper  = document.getElementById("supplier_type_form"); // contenedor visible/oculto
  const supplierTemplate     = document.getElementById("supplier_template");  // card principal
  const tipoPedidoSelect     = document.getElementById("tipo_pedido");

  // IDs de subformularios (contenedores .card con tablas)
  const FORM_IDS = [
    "form-local",
    "form-foraneo-oficina",
    "form-foraneo-usuario",
    "form-foraneo-sucursal",
    "form-varias-sucursales",
  ];

  // Utils UI
  const hideAllForms = () => {
    FORM_IDS.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = "none";
    });
    window.activeSupplierFormId = null;
  };

  const showForm = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = "block";
    el.scrollIntoView({ behavior: "smooth", block: "start" });
    window.activeSupplierFormId = id; // 👈 global
  };

  const resetTemplateInputs = () => {
    if (!supplierTemplate) return;
    supplierTemplate.querySelectorAll("input, select, textarea").forEach(el => {
      if (el.type === "checkbox" || el.type === "radio") el.checked = false;
      else el.value = "";
    });
  };

  // Estado inicial
  hideAllForms();
  if (supplierFormWrapper) supplierFormWrapper.classList.add("d-none");

  // Abrir “nuevo pedido proveedor”
  addSupplierBtn?.addEventListener("click", () => {
    supplierFormWrapper?.classList.remove("d-none");
    resetTemplateInputs();
    hideAllForms();
    // Colocar opción "Seleccionar..." si no existe
    if (tipoPedidoSelect && !tipoPedidoSelect.querySelector('option[value="0"]')) {
      const opt = document.createElement("option");
      opt.value = "0";
      opt.textContent = "Seleccionar...";
      tipoPedidoSelect.prepend(opt);
    }
    if (tipoPedidoSelect) tipoPedidoSelect.value = "0";
    supplierTemplate?.scrollIntoView({ behavior: "smooth", block: "start" });
  });

<<<<<<< HEAD
// Cambiar tipo de pedido → mostrar subform con validación de Mayorista
  tipoPedidoSelect?.addEventListener("change", (e) => {
    const supplierId = document.getElementById("supplier_id")?.value;
    const val = String(e.target.value || "");

    // 🛑 Si el usuario intenta elegir tipo de pedido sin haber puesto un mayorista:
    if (!supplierId && val !== "0" && val !== "") {
      e.target.value = "0"; // Regresar el select a "Seleccionar..."
      hideAllForms();
      window.showAlert("Debes seleccionar primero un Mayorista.", "warning");
      document.getElementById("supplier")?.focus();
      return;
    }

    hideAllForms();
=======
  // Cambiar tipo de pedido → mostrar subform
  tipoPedidoSelect?.addEventListener("change", (e) => {
    

    hideAllForms();
    const val = String(e.target.value || "");
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
    switch (val) {
      case "1": showForm("form-local"); break;
      case "2": showForm("form-foraneo-oficina"); break;
      case "3": showForm("form-foraneo-sucursal"); break;
      case "4": showForm("form-varias-sucursales"); break;
      case "5": showForm("form-foraneo-usuario"); break;
      default: window.activeSupplierFormId = null; break;
    }


    // 🔹 Habilitar/Deshabilitar campo de Sucursal
      const branchInput = document.getElementById("branch");
      const branchHidden = document.getElementById("branch_id");
      const branchList = document.getElementById("branch-list");

      if (branchInput) {
      const tipo = parseInt(e.target.value, 10);
      if (tipo === 1 || tipo === 3) {
      branchInput.disabled = false;
      branchInput.placeholder = "Nombre de la sucursal...";
      branchInput.classList.remove("bg-light");
        } else {
      branchInput.disabled = true;
      branchInput.value = "";
      branchHidden.value = "";
      if (branchList) branchList.innerHTML = "";
      branchInput.placeholder = "No aplica para este tipo de pedido";
      branchInput.classList.add("bg-light");
  }
}
  });

  // Botón cerrar (X) del template (si quieres agregarlo dinámicamente)
  // — si ya lo tienes en HTML, puedes omitir esta sección —
  if (supplierTemplate && !supplierTemplate.querySelector(".btn-close-form")) {
    const closeBtn = document.createElement("button");
    closeBtn.type = "button";
    closeBtn.className = "btn btn-sm btn-light btn-close-form position-absolute top-0 end-0 m-2";
    closeBtn.textContent = "×";
    supplierTemplate.appendChild(closeBtn);

    closeBtn.addEventListener("click", () => {
      hideAllForms();
      supplierFormWrapper?.classList.add("d-none");
      // Avisar a otros módulos que el form se cerró/reinició
      window.dispatchEvent(new Event("supplierFormReset"));
    });
  }

  // ========== GESTIÓN DE PARTIDAS DINÁMICAS (Add / Delete / Select all) ==========
  document.addEventListener("click", (e) => {
    // ➕ Agregar fila
    if (e.target.classList.contains("add-row-btn")) {
<<<<<<< HEAD
      const targetId = e.target.dataset.target; 
=======
      const targetId = e.target.dataset.target; // debe coincidir con el id del contenedor .card
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
      const formCard = document.getElementById(targetId);
      const tbody = formCard?.querySelector("tbody");
      if (!tbody) return;

      const rows = tbody.querySelectorAll("tr");
      const newIndex = rows.length;
      const newRow = rows[0].cloneNode(true);

<<<<<<< HEAD
      // Limpiar inputs de la fila clonada
      // Limpiar inputs de la fila clonada
      newRow.querySelectorAll("input").forEach((input) => {
        if (input.type === "checkbox") input.checked = false;
        else input.value = "";
        if (input.classList.contains("product-id") || input.classList.contains("branch-id")) input.value = "";
        input.name = input.name.replace(/\[items]\[\d+]/, `[items][${newIndex}]`);
      });

      // 🔹 Limpiar listas desplegables previas de autocompletado (Productos y Sucursales)
      const productListDiv = newRow.querySelector(".product-list");
      if (productListDiv) productListDiv.innerHTML = "";

      const branchListDiv = newRow.querySelector(".branch-list");
      if (branchListDiv) branchListDiv.innerHTML = "";

      // Quitar marcas de inicialización para que los scripts detecten los nuevos inputs
      const searchInput = newRow.querySelector(".product-search");
      if (searchInput) {
        delete searchInput.dataset.autocompleteInitialized;
      }

      const branchSearchInput = newRow.querySelector(".branch-search");
      if (branchSearchInput) {
        delete branchSearchInput.dataset.branchInitialized;
      }

=======
      newRow.querySelectorAll("input").forEach((input) => {
        if (input.type === "checkbox") input.checked = false;
        else input.value = "";
        input.name = input.name.replace(/\[items]\[\d+]/, `[items][${newIndex}]`);
      });
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
      // renumerar columna "#"
      const numCell = newRow.querySelector("td:nth-child(2)");
      if (numCell) numCell.textContent = newIndex + 1;

      tbody.appendChild(newRow);
    }

    // 🗑 Eliminar filas seleccionadas
    if (e.target.classList.contains("delete-row-btn")) {
      const targetId = e.target.dataset.target;
      const formCard = document.getElementById(targetId);
      const tbody = formCard?.querySelector("tbody");
      if (!tbody) return;

      const rows = Array.from(tbody.querySelectorAll("tr"));
      const checked = rows.filter(r => r.querySelector(".row-check")?.checked);

      if (!checked.length) return;

      if (checked.length === rows.length) {
        // deja 1 fila vacía
        rows.forEach((r, i) => i > 0 && r.remove());
        tbody.querySelectorAll("input").forEach(inp => {
          if (inp.type !== "checkbox") inp.value = "";
          else inp.checked = false;
        });
      } else {
        checked.forEach(r => r.remove());
        // renumerar y reindexar names
        tbody.querySelectorAll("tr").forEach((row, i) => {
          const numCell = row.querySelector("td:nth-child(2)");
          if (numCell) numCell.textContent = i + 1;
          row.querySelectorAll("input").forEach(input => {
            if (!input.classList.contains("row-check")) {
              input.name = input.name.replace(/\[items]\[\d+]/, `[items][${i}]`);
            }
          });
        });
      }
    }

    // ☑ seleccionar todo
    if (e.target.classList.contains("select-all")) {
      const table = e.target.closest("table");
      table?.querySelectorAll(".row-check").forEach(cb => cb.checked = e.target.checked);
    }
  });

  /* ===========================================================
 📦 CARGAR TIPOS DE PEDIDO DESDE BACKEND
=========================================================== */
async function loadSupplierOrderTypes() {
  const select = document.getElementById("tipo_pedido");
  if (!select) return;

  // Mostrar siempre la opción por defecto primero
  select.innerHTML = '<option value="0">Seleccionar...</option>';

  try {
    const res  = await fetch("index.php?page=get_supplier_order_types");
    const data = await res.json();

    if (data.success && Array.isArray(data.data)) {
      data.data.forEach((t) => {
        const opt = document.createElement("option");
        opt.value = t.id;
        opt.textContent = t.order_type;
        select.appendChild(opt);
      });
    } else {
      console.warn("⚠️ No se encontraron tipos de pedido válidos, usando fallback.");
      addFallbackOrderTypes(select);
    }
  } catch (err) {
    console.error("❌ Error cargando tipos de pedido:", err);
    addFallbackOrderTypes(select);
  }

  select.value = "0"; // valor inicial
}

<<<<<<< HEAD


// Checkbox maestro para "Recoger en Sucursal" dentro de Varias Sucursales
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('master-pickup-branch')) {
        const table = e.target.closest('table');
        const checkboxes = table.querySelectorAll('.item-pickup-branch');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
    }
});

=======
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
/* ===========================================================
 🧩 FALLBACK LOCAL (por si falla el fetch)
=========================================================== */
function addFallbackOrderTypes(select) {
  const fallback = [
    { id: 1, order_type: "Local" },
    { id: 2, order_type: "Foráneo a ERA" },
    { id: 3, order_type: "Foráneo a Sucursal" },
    { id: 4, order_type: "Varias Sucursales" },
    { id: 5, order_type: "Proveedor a Usuario Final" },
  ];

  fallback.forEach((t) => {
    const opt = document.createElement("option");
    opt.value = t.id;
    opt.textContent = t.order_type;
    select.appendChild(opt);
  });
}

// 🚀 Inicializar tipos de pedido dinámicamente
loadSupplierOrderTypes();



});
