document.addEventListener("DOMContentLoaded", async () => {
  const container = document.getElementById("pickupsContainer");

  // 💡 1️⃣ Inicializa registros logísticos antes de mostrar pedidos
  await initializeLogistics();

  // 💡 2️⃣ Cargar pedidos logísticos
  await loadLogisticOrders(container);
});

// ─────────────── Función para inicializar logística ───────────────
async function initializeLogistics() {
  try {
    const res = await fetch("index.php?page=api_logistic_initialize");
    const data = await res.json();
    if (!data.success) throw new Error(data.message);
    console.log(`🧩 Inicialización logística completada:`, data);
  } catch (err) {
    console.error("⚠️ Error al inicializar logística:", err.message);
  }
}

// ─────────────── Cargar pedidos logísticos ───────────────
async function loadLogisticOrders(container) {
  container.innerHTML = `
    <div class="text-center text-muted py-5">
      <div class="spinner-border text-primary"></div>
      <div class="mt-2">Cargando pedidos logísticos...</div>
    </div>`;

  try {
    const res = await fetch("index.php?page=api_list_supplier_orders");
    const data = await res.json();
    if (!data.success) throw new Error(data.message);
    const orders = data.data;

    // Si no hay pedidos logísticos
    if (!orders.length) {
      container.innerHTML = `
        <div class="alert alert-info text-center">
          No hay pedidos logísticos registrados.
          <br><button id="btnReload" class="btn btn-primary mt-3">
            🔄 Reintentar carga
          </button>
        </div>`;
      document
        .getElementById("btnReload")
        .addEventListener("click", () => loadLogisticOrders(container));
      return;
    }

    // Render principal con filtros + botón de actualización
    container.innerHTML = `
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body bg-light rounded-top">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label fw-semibold">Buscar</label>
              <input type="text" id="searchInput" class="form-control" placeholder="Proveedor o pedido...">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Estatus</label>
              <select id="filterStatus" class="form-select">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="en proceso">En proceso</option>
                <option value="parcial">Parcial</option>
                <option value="completo">Completo</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Proveedor</label>
              <select id="filterSupplier" class="form-select">
                <option value="">Todos</option>
                ${[...new Set(orders.map((o) => o.supplier_name))]
                  .map((s) => `<option value="${s}">${s}</option>`)
                  .join("")}
              </select>
            </div>
            <div class="col-md-3 text-end">
              <button id="btnInitialize" class="btn btn-success mt-3">
                🔄 Actualizar pedidos logísticos
              </button>
              <button id="clearFilters" class="btn btn-outline-secondary mt-3">Limpiar filtros</button>
            </div>
          </div>
        </div>
      </div>

      <div class="accordion" id="ordersAccordion">
        ${orders.map((o, i) => buildAccordionItem(o, i)).join("")}
      </div>
    `;

    // 🧩 Botón de actualización manual
    document
      .getElementById("btnInitialize")
      .addEventListener("click", async () => {
        const btn = document.getElementById("btnInitialize");
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Actualizando...`;
        await initializeLogistics();
        await loadLogisticOrders(container);
      });

    // Filtros interactivos
    const searchInput = document.getElementById("searchInput");
    const filterStatus = document.getElementById("filterStatus");
    const filterSupplier = document.getElementById("filterSupplier");
    const clearBtn = document.getElementById("clearFilters");

    function applyFilters() {
      const text = searchInput.value.toLowerCase();
      const status = filterStatus.value.toLowerCase();
      const supplier = filterSupplier.value;

      document.querySelectorAll(".accordion-item").forEach((item) => {
        const matchText = item.dataset.search.includes(text);
        const matchStatus = !status || item.dataset.status === status;
        const matchSupplier = !supplier || item.dataset.supplier === supplier;
        item.style.display =
          matchText && matchStatus && matchSupplier ? "" : "none";
      });
    }

    [searchInput, filterStatus, filterSupplier].forEach((el) =>
      el.addEventListener("input", applyFilters)
    );
    clearBtn.addEventListener("click", () => {
      searchInput.value = "";
      filterStatus.value = "";
      filterSupplier.value = "";
      applyFilters();
    });
  } catch (err) {
    container.innerHTML = `<div class="alert alert-danger">❌ ${err.message}</div>`;
  }
}

// ─────────────── Helpers ───────────────

function buildAccordionItem(o, i) {
  const statusClass =
    {
      pendiente: "bg-warning text-dark",
      en_proceso: "bg-primary text-white",
      parcial_aspel: "bg-info text-dark",
      completo_aspel: "bg-success text-white",
      listo_para_entrega: "bg-success text-white",
      cerrado: "bg-secondary text-white",
      cancelado: "bg-danger text-white",
    }[o.logistic_status?.toLowerCase()] || "bg-dark text-white";

  const collapseId = `collapse-${i}`;

  return `
  <div class="accordion-item shadow-sm border-0 mb-2"
       data-id-supplier-order="${o.id_supplier_order}"
       data-search="${(o.supplier_name + " " + o.supplier_order).toLowerCase()}"
       data-status="${o.status_name?.toLowerCase()}"
       data-supplier="${o.supplier_name}">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed d-flex justify-content-between align-items-center"
              type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
        <div class="flex-grow-1">
          <div class="fw-bold text-primary">Pedido #${o.supplier_order}</div>
          <small class="text-muted">${o.supplier_name} — ${
    o.branch_name || "Sin sucursal"
  }</small>
        </div>
        <span class="badge ${statusClass}">${o.logistic_status}</span>
      </button>
    </h2>
    <div id="${collapseId}" class="accordion-collapse collapse">
      <div class="accordion-body bg-light">
        <div class="small mb-2 text-muted">
          <strong>Tipo:</strong> ${o.order_type_name} &nbsp; | &nbsp;
          <strong>Fecha:</strong> ${
            o.supplier_order_date || "-"
          } &nbsp; | &nbsp;
          <strong>Creado por:</strong> ${o.created_by_name || "—"}
        </div>
        <div class="items-container text-center text-muted border-top pt-2">
          <div class="spinner-border spinner-border-sm"></div> Cargando partidas...
        </div>
      </div>
    </div>
  </div>`;
}

// ─────────────── Cargar ítems al abrir ───────────────
document.addEventListener("show.bs.collapse", async (e) => {
  const collapse = e.target;
  const body = collapse.querySelector(".items-container");
  const parent = collapse.closest(".accordion-item");
  const supplierOrderId = parent.dataset.idSupplierOrder;

  body.innerHTML = `
    <div class="text-center text-muted border-top pt-2">
      <div class="spinner-border spinner-border-sm"></div> Cargando partidas...
    </div>`;

  try {
    const res = await fetch(
      `index.php?page=api_list_logistic_items&supplier_order_id=${supplierOrderId}`
    );
    const data = await res.json();
    if (!data.success) throw new Error(data.message);
    body.innerHTML = buildItemsTable(data.data);
  } catch (err) {
    body.innerHTML = `<div class="alert alert-danger small mt-2">❌ ${err.message}</div>`;
  }
});

// ─────────────── Render tabla de ítems ───────────────
function buildItemsTable(items) {
  if (!items.length)
    return `<div class="text-muted small mt-2">No se encontraron partidas registradas en logística.</div>`;

  return `
    <div class="table-responsive mt-2">
      <table class="table table-sm table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Producto</th>
            <th class="text-center">Pedido</th>
            <th class="text-center">Recibido</th>
            <th class="text-center">Diferencia</th>
            <th class="text-center">Estado</th>
          </tr>
        </thead>
        <tbody>
          ${items
            .map((it) => {
              const status = it.status?.toLowerCase() || "pendiente";

              // 🎨 Mapa de colores por estatus
              const statusClass = {
                pendiente: "bg-warning text-dark",
                parcial: "bg-info text-dark",
                completo: "bg-success text-white",
                cancelado: "bg-danger text-white",
              }[status] || "bg-secondary text-white";

              // Etiqueta más legible (opcional)
              const statusLabel = {
                pendiente: "Pendiente",
                parcial: "Parcial",
                completo: "Completo",
                cancelado: "Cancelado",
              }[status] || status;

              return `
                <tr>
                  <td>${it.item_number}</td>
                  <td>${it.description_product}</td>
                  <td class="text-center">${it.ordered_quantity}</td>
                  <td class="text-center">${it.received_quantity}</td>
                  <td class="text-center">${it.difference_quantity ?? "-"}</td>
                  <td class="text-center">
                    <span class="badge ${statusClass}">${statusLabel}</span>
                  </td>
                </tr>`;
            })
            .join("")}
        </tbody>
      </table>
    </div>`;
}

