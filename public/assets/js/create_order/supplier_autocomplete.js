// public/assets/js/create_order/supplier_autocomplete.js
document.addEventListener("DOMContentLoaded", () => {
  // Función reusable
  window.initSupplierAutocomplete = (inputEl, hiddenEl, listEl) => {
    if (!inputEl || !listEl || !hiddenEl) return;

    let timer;
    const MIN = 2;

    const clearList = () => { listEl.innerHTML = ""; };

    inputEl.addEventListener("input", () => {
      const term = inputEl.value.trim();
      hiddenEl.value = "";
      clearTimeout(timer);
      clearList();
      if (term.length < MIN) return;

      timer = setTimeout(async () => {
        try {
          const res  = await fetch(`index.php?page=suppliers_list&term=${encodeURIComponent(term)}`);
          const data = await res.json();
          clearList();
          if (data.success && Array.isArray(data.data)) {
            data.data.forEach(s => {
              const btn = document.createElement("button");
              btn.type = "button";
              btn.className = "list-group-item list-group-item-action";
              btn.textContent = s.business_name || s.company_name || `#${s.id}`;
              btn.addEventListener("click", () => {
                inputEl.value = s.business_name || s.company_name || `#${s.id}`;
                hiddenEl.value = s.id;
                clearList();
              });
              listEl.appendChild(btn);
            });
          }
        } catch (e) {
          console.error("Autocomplete error:", e);
        }
      }, 250);
    });

    // Cerrar lista si clic fuera
    document.addEventListener("click", (ev) => {
      if (!listEl.contains(ev.target) && ev.target !== inputEl) clearList();
    });

    // Crear proveedor al presionar Enter si no hay selección
    inputEl.addEventListener("keydown", async (e) => {
      if (e.key !== "Enter") return;
      if (hiddenEl.value || !inputEl.value.trim()) return;

      e.preventDefault();
      const supplierName = inputEl.value.trim();

      // Si tienes modal, puedes abrirlo aquí. Para base, creamos directo:
      try {
        const res  = await fetch("index.php?page=create_supplier", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `business_name=${encodeURIComponent(supplierName)}`
        });
        const data = await res.json();
        if (data.success && data.id) {
          hiddenEl.value = data.id;
          alert(`✅ Proveedor "${supplierName}" creado.`);
        } else {
          alert(`⚠️ ${data.message || "Error creando proveedor"}`);
        }
      } catch (err) {
        console.error(err);
      }
    });
  };

  // Inicialización automática en el formulario principal (si están los IDs)
  const input   = document.getElementById("supplier");
  const hidden  = document.getElementById("supplier_id");
  const list    = document.getElementById("supplier-list");
  if (input && hidden && list) {
    window.initSupplierAutocomplete(input, hidden, list);
  }
});
