// public/assets/js/create_order/supplier_order_viewer.js
document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("supplier_orders_container");
  let activeEditingCard = null; // 🔹 solo una card editable

  const showToast = (msg, type = "info") =>
    console.log(`[${type.toUpperCase()}] ${msg}`);

  const fmtDateTime = (iso) =>
    new Date(iso).toLocaleString("es-MX", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });

  /* ===========================================================
     📦 CARGAR PEDIDOS
  =========================================================== */
  async function loadSupplierOrders() {
    const customerOrderId = document.getElementById("customer_order_id")?.value;
    if (!customerOrderId) {
      container.innerHTML = `<div class="text-muted">Esperando pedido cliente...</div>`;
      return;
    }

    container.innerHTML = `<div class="text-muted">Cargando pedidos...</div>`;

    try {
      const res = await fetch(
        `index.php?page=get_supplier_orders&customer_order_id=${customerOrderId}`
      );
      const data = await res.json();
      container.innerHTML = "";

      if (data.success && Array.isArray(data.data) && data.data.length) {
        data.data.forEach((order) =>
          container.appendChild(buildOrderCard(order))
        );
      } else {
        container.innerHTML = `<div class="text-muted">No hay pedidos mayoristas guardados.</div>`;
      }
    } catch (e) {
      console.error(e);
      container.innerHTML = `<div class="alert alert-danger">Error al cargar pedidos</div>`;
    }
  }

  /* ===========================================================
     🧱 CONSTRUIR CARD
  =========================================================== */
function buildOrderCard(order) {
  const statusId = Number(order.id_status);
  const canEdit = [1, 2].includes(statusId);

  // 🎨 Colores del badge
  let statusClass = "bg-secondary";
  switch (statusId) {
    case 1: statusClass = "bg-warning text-dark"; break; // pendiente
    case 2: statusClass = "bg-primary"; break; // en proceso
    case 3: statusClass = "bg-info text-dark"; break; // parcial
    case 4: statusClass = "bg-success"; break; // completo
    case 5: statusClass = "bg-danger"; break; // cancelado
  }

  const card = document.createElement("div");
  card.className = `card shadow-sm p-3 mb-3 position-relative transition-all ${statusId === 5 ? "opacity-75" : ""}`;
  card.dataset.orderId = order.id;
  card.dataset.orderType = order.id_order_type;
  card.dataset.statusId = statusId;

  card.innerHTML = `
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="text-primary mb-1">
        Pedido mayorista #${order.item_number}
      </h5>
      <span class="badge ${statusClass}">
        ${order.status_name || "Pendiente"}
      </span>
    </div>

    <div class="editable-fields mt-2">
      <div class="row">
        <div class="col-md-6">
          <p class="mb-1">
            <strong>Mayorista:</strong>
            <span class="value supplier-name">${order.supplier_name || "—"}</span>
          </p>
          <div class="position-relative d-none supplier-edit">
            <input type="text" class="form-control form-control-sm supplier-input"
                   value="${order.supplier_name || ""}"
                   autocomplete="off" placeholder="Buscar proveedor...">
            <input type="hidden" class="supplier-id" value="${order.id_supplier || ""}">
            <div class="supplier-list list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
          </div>
        </div>

        <div class="col-md-6">
          <p class="mb-1">
            <strong>Sucursal:</strong>
            <span class="value branch-name">${order.branch_name || "—"}</span>
          </p>
          <div class="position-relative d-none branch-edit">
            <input type="text" class="form-control form-control-sm branch-input"
                   value="${order.branch_name || ""}"
                   autocomplete="off" placeholder="Sucursal...">
            <input type="hidden" class="branch-id" value="${order.id_branch || ""}">
            <div class="branch-list list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
          </div>
        </div>
      </div>

      <p class="mb-1 mt-2">
        <strong>No. Pedido:</strong>
        <span class="value order-number">${order.supplier_order || "—"}</span>
        <input type="text" class="form-control form-control-sm d-none" 
               name="supplier_order" value="${order.supplier_order || ""}">
      </p>

      <p class="mb-1">
        <strong>Tipo:</strong>
        <span class="value">${order.order_type_name || "—"}</span>
        <select class="form-select form-select-sm d-none" name="id_order_type" disabled>
          ${buildOrderTypeOptions(order.id_order_type)}
        </select>
      </p>
    </div>

    <div class="d-flex justify-content-between text-muted small mt-1">
      <span>Creado: ${fmtDateTime(order.supplier_order_date)}</span>
      <span>Últ. actualización: ${
        order.supplier_order_last_update
          ? fmtDateTime(order.supplier_order_last_update)
          : "—"
      }</span>
    </div>

    <div class="mt-3 d-flex justify-content-end gap-2">
      <button class="btn btn-sm btn-outline-primary btn-view-items">Ver partidas</button>
      ${
        statusId !== 5 && canEdit
          ? `
          <button class="btn btn-sm btn-outline-secondary btn-edit">Editar</button>
          <button class="btn btn-sm btn-success btn-save d-none">Guardar</button>
          <button class="btn btn-sm btn-light btn-cancel-edit d-none">Cancelar</button>
          <button class="btn btn-sm btn-outline-danger btn-cancel">Cancelar</button>
        `
          : "" // 🚫 No mostrar botones si cancelado
      }
    </div>
  `;

  return card;
}




  function buildOrderTypeOptions(selected) {
    const ORDER_TYPES = [
      { id: 1, name: "Local" },
      { id: 2, name: "Foráneo a ERA" },
      { id: 3, name: "Foráneo a Sucursal" },
      { id: 4, name: "Varias Sucursales" },
      { id: 5, name: "Proveedor a Usuario Final" },
    ];
    return ORDER_TYPES.map(
      (t) =>
        `<option value="${t.id}" ${
          String(t.id) === String(selected) ? "selected" : ""
        }>${t.name}</option>`
    ).join("");
  }

  /* ===========================================================
     📋 PARTIDAS
  =========================================================== */
  async function loadItemsIntoCard(card, editable = false, forceReload = false) {
  const existing = card.querySelector("table");

  // 🔹 Solo quitar la tabla si se pide explícitamente recargar
  if (existing && !forceReload) {
    existing.remove();
    return;
  }
  if (existing && forceReload) existing.remove();

  const orderId = card.dataset.orderId;
  try {
    const res = await fetch(`index.php?page=get_supplier_items&supplier_order_id=${orderId}`);
    const data = await res.json();
    if (!data.success) return showToast("No se pudieron obtener partidas.", "danger");

    for (const item of data.data) {
      try {
        const r = await fetch(`index.php?page=get_tracking_by_item&item_id=${item.id}`);
        const t = await r.json();
        if (t.success) item.tracking = t.data;
      } catch {}
    }

    const htmlTable = buildItemsTable(data.data, Number(card.dataset.orderType), editable);
    card.insertAdjacentHTML("beforeend", htmlTable);
  } catch (e) {
    console.error(e);
    showToast("Error al obtener partidas.", "danger");
  }
}


  function buildItemsTable(items, orderType, editable = false) {
    const isLocal = Number(orderType) === 1;
    const head = `
      <tr>
        <th>#</th><th>Cant.</th><th>Descripción</th><th>Estado</th>
        ${
          !isLocal
            ? `<th>No. Guía</th><th>Paquetería / Sucursal</th><th>Arribo</th>`
            : ``
        }
      </tr>
    `;

    const rows = items
      .map((it) => {
        const trk = it.tracking || {};
        const status =
          it.id_status === 1
            ? "Pendiente"
            : it.id_status === 2
            ? "Parcial"
            : it.id_status === 3
            ? "Completo"
            : "Cancelado";

        return `
          <tr data-item-id="${it.id}">
            <td>${it.item_number}</td>
            <td>${
              editable
                ? `<input type="number" class="form-control form-control-sm item-qty" value="${
                    it.quantity ?? ""
                  }">`
                : it.quantity
            }</td>
            <td>${
              editable
                ? `<input type="text" class="form-control form-control-sm item-desc" value="${
<<<<<<< HEAD
                    it.part_number && it.description_product ? it.part_number + ' - ' + it.description_product : (it.description_product ?? '')
                  }">`
                : (it.part_number && it.description_product 
                    ? `<strong>${it.part_number}</strong> - ${it.description_product}` 
                    : (it.description_product ?? "—"))
=======
                    it.description_product ?? ""
                  }">`
                : it.description_product
>>>>>>> 555d25d6f5c3f4b78398f7e0b05baf92d385f547
            }</td>
            <td>${status}</td>
            ${
              !isLocal
                ? `
              <td>${
                editable
                  ? `<input type="text" class="form-control form-control-sm track-num" value="${
                      trk.tracking_number ?? ""
                    }">`
                  : trk.tracking_number ?? "—"
              }</td>
              <td>${
                editable
                  ? `<input type="text" class="form-control form-control-sm track-comp" value="${
                      trk.tracking_company ?? trk.origin_branch ?? ""
                    }">`
                  : trk.tracking_company ?? trk.origin_branch ?? "—"
              }</td>
              <td>${
                editable
                  ? `<input type="date" class="form-control form-control-sm track-date" value="${
                      trk.arrival_date ?? ""
                    }">`
                  : trk.arrival_date ?? "—"
              }</td>
              `
                : ""
            }
          </tr>
        `;
      })
      .join("");

    return `
      <table class="table table-bordered mt-3 fade show">
        <thead class="table-light">${head}</thead>
        <tbody>${rows}</tbody>
      </table>
    `;
  }

 /* ===========================================================
     ✏️ EDICIÓN INLINE
  =========================================================== */
async function enterEdit(card) {
  // cerrar otras tarjetas en edición
  if (activeEditingCard && activeEditingCard !== card)
    exitEdit(activeEditingCard);
  activeEditingCard = card;

  card.dataset.editing = "1";
  card.classList.add("bg-light", "fade-slide-in");

  // ocultar spans y mostrar inputs/selects
  card.querySelectorAll(".value").forEach((v) => v.classList.add("d-none"));
  card.querySelectorAll("input, select").forEach((el) => {
    el.classList.remove("d-none");
    // evitar edición del tipo de pedido (según tu requerimiento)
    if (el.name === "id_order_type") el.disabled = true;
  });

  card.querySelector(".btn-edit")?.classList.add("d-none");
  card.querySelector(".btn-save")?.classList.remove("d-none");
  card.querySelector(".btn-cancel")?.classList.add("d-none");
  card.querySelector(".btn-cancel-edit")?.classList.remove("d-none");

  /* ==========================================================
     🧾 AUTOCOMPLETADO DE PROVEEDOR
  ========================================================== */
  const supplierInput = card.querySelector(".supplier-input");
  const supplierHidden = card.querySelector(".supplier-id");
  const supplierList = card.querySelector(".supplier-list");
  const supplierBox = card.querySelector(".supplier-edit");
  if (supplierBox) supplierBox.classList.remove("d-none");

  if (window.initSupplierAutocomplete && supplierInput && supplierHidden && supplierList) {
    window.initSupplierAutocomplete(supplierInput, supplierHidden, supplierList);
  }

  /* ==========================================================
     🏢 AUTOCOMPLETADO DE SUCURSAL
  ========================================================== */
  const branchInput = card.querySelector(".branch-input");
  const branchHidden = card.querySelector(".branch-id");
  const branchList = card.querySelector(".branch-list");
  const branchBox = card.querySelector(".branch-edit");
  if (branchBox) branchBox.classList.remove("d-none");

  if (window.initBranchAutocomplete && branchInput && branchHidden && branchList) {
    const supplierHiddenForBranch = card.querySelector(".supplier-id");
    window.initBranchAutocomplete(branchInput, branchHidden, branchList, supplierHiddenForBranch);
  }

  // cargar partidas en modo editable
  await loadItemsIntoCard(card, true, true);
}





  function exitEdit(card) {
    card.dataset.editing = "0";
    card.classList.remove("bg-light", "fade-slide-in");
    activeEditingCard = null;

    card
      .querySelectorAll(".value")
      .forEach((v) => v.classList.remove("d-none"));
    card
      .querySelectorAll("input, select")
      .forEach((el) => el.classList.add("d-none"));
    card.querySelector(".btn-edit")?.classList.remove("d-none");
    card.querySelector(".btn-save")?.classList.add("d-none");
    card.querySelector(".btn-cancel")?.classList.remove("d-none");
    card.querySelector(".btn-cancel-edit")?.classList.add("d-none");

    const tbl = card.querySelector("table");
    if (tbl) tbl.remove();
  }

 async function saveEdit(card) {
  const supplierOrderId = card.dataset.orderId;
  const supplierId = card.querySelector(".supplier-id")?.value || "";
  const supplierOrd = card.querySelector('[name="supplier_order"]')?.value || "";
  const idOrderType = card.querySelector('[name="id_order_type"]')?.value || "";
  const branchId = card.querySelector(".branch-id")?.value || "";

  try {
    const fd = new FormData();
    
    fd.append("supplier_order_id", supplierOrderId);
    fd.append("id_supplier", supplierId);
    fd.append("supplier_order", supplierOrd);
    fd.append("id_order_type", idOrderType);
    fd.append("id_branch", branchId);

    

    await fetch("index.php?page=update_supplier_order", {
      method: "POST",
      body: fd,
    });

    // actualizar partidas
    const rows = card.querySelectorAll("table tbody tr");
    for (const row of rows) {
      const itemId = row.dataset.itemId;
      if (!itemId) continue;

      const qty = row.querySelector(".item-qty")?.value;
      const desc = row.querySelector(".item-desc")?.value;
      const tNum = row.querySelector(".track-num")?.value;
      const tComp = row.querySelector(".track-comp")?.value;
      const tDate = row.querySelector(".track-date")?.value;

      const fdi = new FormData();
      fdi.append("item_id", itemId);
      fdi.append("quantity", qty);
      fdi.append("description_product", desc);
      await fetch("index.php?page=update_supplier_item", {
        method: "POST",
        body: fdi,
      });

      const ft = new FormData();
      ft.append("id_item_supplier_order", itemId);
      ft.append("tracking_number", tNum);
      ft.append("tracking_company", tComp);
      ft.append("arrival_date", tDate);
      await fetch("index.php?page=update_tracking", {
        method: "POST",
        body: ft,
      });
    }

    showToast("✅ Cambios guardados correctamente.", "success");
    await loadSupplierOrders();
  } catch (err) {
    console.error(err);
    showToast("Error al guardar los cambios.", "danger");
  }
}

// CANCELAR PEDIDO
let orderToCancel = null;

async function cancelOrder(card) {
  orderToCancel = card;

  // 🧾 Obtener número de pedido y proveedor
  const orderNumber = card.querySelector(".order-number")?.textContent?.trim() || "—";
  const supplierName = card.querySelector(".supplier-name")?.textContent?.trim() || "—";

  // 🖋️ Insertar datos en el modal
  document.getElementById("cancelOrderNumber").textContent = orderNumber;
  document.getElementById("cancelSupplierName").textContent = supplierName;

  // Mostrar modal
  const modal = new bootstrap.Modal(document.getElementById("cancelSupplierModal"));
  modal.show();

  // Confirmación
  const confirmBtn = document.getElementById("confirmCancelSupplierBtn");

  const handler = async () => {
    const orderId = orderToCancel?.dataset.orderId;
    if (!orderId) return;

    confirmBtn.disabled = true;

    try {
      const fd = new FormData();
      fd.append("supplier_order_id", orderId);

      const res = await fetch("index.php?page=cancel_supplier_order", {
        method: "POST",
        body: fd,
      });

      const data = await res.json();

      if (data.success) {
        showToast(`🛑 Pedido ${orderNumber} cancelado correctamente.`, "success");
        bootstrap.Modal.getInstance(document.getElementById("cancelSupplierModal")).hide();
        await loadSupplierOrders();
      } else {
        showToast(data.message || "No se pudo cancelar el pedido.", "danger");
      }
    } catch (err) {
      console.error("Error al cancelar pedido:", err);
      showToast("Error al cancelar pedido.", "danger");
    } finally {
      confirmBtn.disabled = false;
      confirmBtn.removeEventListener("click", handler);
    }
  };

  confirmBtn.removeEventListener("click", handler);
  confirmBtn.addEventListener("click", handler);
}




  /* ===========================================================
     🎛️ EVENTOS
  =========================================================== */
  container?.addEventListener("click", async (e) => {
    const card = e.target.closest(".card");
    if (!card) return;

    if (e.target.classList.contains("btn-view-items"))
      await loadItemsIntoCard(card);
    if (e.target.classList.contains("btn-cancel")) await cancelOrder(card);
    if (e.target.classList.contains("btn-edit")) enterEdit(card);
    if (e.target.classList.contains("btn-cancel-edit")) exitEdit(card);
    if (e.target.classList.contains("btn-save")) await saveEdit(card);
  });

  window.addEventListener("supplierFormSaved", loadSupplierOrders);
  (async () => await loadSupplierOrders())();
});
