// public/assets/js/create_order/branch_autocomplete.js
document.addEventListener("DOMContentLoaded", () => {
  window.initBranchAutocomplete = (inputEl, hiddenEl, listEl, supplierIdEl) => {
    if (!inputEl || !listEl || !hiddenEl || !supplierIdEl) return;

    let timer;
    let activeIndex = -1; // índice actual
    const MIN = 1;

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

    // Buscar sucursales
    inputEl.addEventListener("input", () => {
      const term = inputEl.value.trim();
      hiddenEl.value = "";
      clearTimeout(timer);
      clearList();

      const supplierId = supplierIdEl.value;
      if (!supplierId || term.length < MIN) return;

      timer = setTimeout(async () => {
        try {
          const res = await fetch(
            `index.php?page=supplier_branches_list&id_supplier=${supplierId}&term=${encodeURIComponent(term)}`
          );
          const data = await res.json();
          clearList();

          if (data.success && Array.isArray(data.data)) {
            data.data.forEach((b) => {
              const btn = document.createElement("button");
              btn.type = "button";
              btn.className = "list-group-item list-group-item-action";
              btn.textContent = b.branch || `Sucursal #${b.id}`;
              btn.dataset.id = b.id; // ✅ guardamos ID
              btn.addEventListener("click", () => {
                inputEl.value = b.branch;
                hiddenEl.value = b.id;
                clearList();
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
          // ✅ Seleccionar sucursal destacada
          const selected = items[activeIndex];
          inputEl.value = selected.textContent;
          hiddenEl.value = selected.dataset.id; // ✅ ahora guarda el ID
          clearList();
          return;
        }

        // Si no hay selección, crear nueva sucursal
        if (hiddenEl.value || !inputEl.value.trim()) return;
        const supplierId = supplierIdEl.value;
        const branchName = inputEl.value.trim();

        if (!supplierId) {
          alert("⚠️ Debes seleccionar primero un proveedor.");
          return;
        }

        try {
          const res = await fetch("index.php?page=create_supplier_branch", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id_supplier=${encodeURIComponent(supplierId)}&branch=${encodeURIComponent(branchName)}`,
          });

          const data = await res.json();
          if (data.success && data.id) {
            hiddenEl.value = data.id;
            alert(`✅ Sucursal "${branchName}" creada y asociada al proveedor.`);
          } else {
            alert(`⚠️ ${data.message || "Error creando sucursal."}`);
          }
        } catch (err) {
          console.error("Error creando sucursal:", err);
        }
      }
    });

    // Cerrar lista si clic fuera
    document.addEventListener("click", (ev) => {
      if (!listEl.contains(ev.target) && ev.target !== inputEl) clearList();
    });
  };

  // Inicialización automática
  const inputBranch = document.getElementById("branch");
  const hiddenBranch = document.getElementById("branch_id");
  const listBranch = document.getElementById("branch-list");
  const supplierHidden = document.getElementById("supplier_id");

  if (inputBranch && hiddenBranch && listBranch && supplierHidden) {
    window.initBranchAutocomplete(inputBranch, hiddenBranch, listBranch, supplierHidden);
  }
});
