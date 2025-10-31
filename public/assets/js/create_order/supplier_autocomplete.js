// public/assets/js/create_order/supplier_autocomplete.js
document.addEventListener("DOMContentLoaded", () => {
  window.initSupplierAutocomplete = (inputEl, hiddenEl, listEl) => {
    if (!inputEl || !listEl || !hiddenEl) return;

    let timer;
    let activeIndex = -1;
    const MIN = 2;

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

    // Buscar proveedores
    inputEl.addEventListener("input", () => {
      const term = inputEl.value.trim();
      hiddenEl.value = "";
      clearTimeout(timer);
      clearList();
      if (term.length < MIN) return;

      timer = setTimeout(async () => {
        try {
          const res = await fetch(`index.php?page=suppliers_list&term=${encodeURIComponent(term)}`);
          const data = await res.json();
          clearList();

          if (data.success && Array.isArray(data.data)) {
            data.data.forEach((s) => {
              const btn = document.createElement("button");
              btn.type = "button";
              btn.className = "list-group-item list-group-item-action";
              btn.textContent = s.business_name || s.company_name || `#${s.id}`;
              btn.dataset.id = s.id; // ✅ Guardamos ID
              btn.addEventListener("click", () => {
                inputEl.value = s.business_name || s.company_name || `#${s.id}`;
                hiddenEl.value = s.id;
                clearList();
              });
              listEl.appendChild(btn);
            });
            activeIndex = -1;
          }
        } catch (e) {
          console.error("Autocomplete error:", e);
        }
      }, 250);
    });

    // Navegación con teclado
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
      } else if (e.key === "Enter") {
        if (activeIndex >= 0 && activeIndex < total) {
          const selected = items[activeIndex];
          inputEl.value = selected.textContent;
          hiddenEl.value = selected.dataset.id; // ✅ Asignamos ID correctamente
          clearList();
          return;
        }

        // 🏗️ Crear proveedor si no existe
        if (hiddenEl.value || !inputEl.value.trim()) return;
        const supplierName = inputEl.value.trim();

        try {
          const res = await fetch("index.php?page=create_supplier", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `business_name=${encodeURIComponent(supplierName)}`,
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
      }
    });

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
