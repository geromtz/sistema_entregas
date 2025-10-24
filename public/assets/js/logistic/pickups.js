document.addEventListener("DOMContentLoaded", () => {

  /* =============================
      🔹 CARGAR PARTIDAS POR PEDIDO
  ============================== */
  document.querySelectorAll(".pickup-items").forEach(container => {
    const orderId = container.dataset.orderId;

    fetch(`index.php?page=api_get_items_by_order&id=${orderId}`)
      .then(res => res.json())
      .then(items => {
        if (!Array.isArray(items) || !items.length) {
          container.innerHTML = `<div class="text-muted">Sin partidas registradas.</div>`;
          return;
        }

        let html = `<table class="table table-sm align-middle">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Producto</th>
                          <th class="text-center">Cantidad</th>
                          <th>Estatus</th>
                        </tr>
                      </thead>
                      <tbody>`;

        items.forEach(it => {
          html += `<tr data-id="${it.id}">
                    <td>${it.item_number}</td>
                    <td>${it.description_product}</td>
                    <td class="text-center">${it.quantity}</td>
                    <td><span class="badge bg-${statusColor(it.id_status)}">${it.status_name || '—'}</span></td>
                  </tr>`;
        });

        html += `</tbody></table>`;
        container.innerHTML = html;
      })
      .catch(err => {
        console.error(err);
        container.innerHTML = `<div class="text-danger">Error al cargar partidas.</div>`;
      });
  });

  /* =============================
      🔹 FILTROS
  ============================== */
  const applyBtn = document.getElementById("applyFilters");
  const clearBtn = document.getElementById("clearFilters");

  if (applyBtn) {
    applyBtn.addEventListener("click", () => {
      const search = (document.getElementById("filterSearch").value || "").toLowerCase();
      const supplier = (document.getElementById("filterSupplier").value || "").toLowerCase();
      const status = document.getElementById("filterStatus").value;
      const start = document.getElementById("filterDateStart").value;
      const end = document.getElementById("filterDateEnd").value;

      document.querySelectorAll(".pickup-card").forEach(card => {
        const matchSearch =
          card.dataset.supplier.includes(search) ||
          card.textContent.toLowerCase().includes(search);

        const matchSupplier = !supplier || card.dataset.supplier === supplier;
        const matchStatus = !status || card.dataset.status === status;
        const cardDate = card.dataset.date;
        const matchDate =
          (!start && !end) ||
          (cardDate >= start && cardDate <= (end || cardDate));

        card.style.display = (matchSearch && matchSupplier && matchStatus && matchDate)
          ? ""
          : "none";
      });
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      ["filterSearch", "filterSupplier", "filterStatus", "filterDateStart", "filterDateEnd"].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = "";
      });
      const btn = document.getElementById("applyFilters");
      if (btn) btn.click();
    });
  }

/* =============================
    🔹 ACCIÓN: MARCAR PEDIDO COMPLETO
============================= */
document.addEventListener("click", async e => {
  if (!e.target.classList.contains("btn-order-complete")) return;

  const orderId = e.target.dataset.orderId;
  if (!confirm("¿Marcar pedido como COMPLETO?")) return;

  const formData = new FormData();
  formData.append("order_id", orderId);
  formData.append("status", 4); // completo

  try {
    const res = await fetch("index.php?page=update_order_items_status", {
      method: "POST",
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      alert("✅ Pedido marcado como completo.");
      const card = document.querySelector(`.pickup-card[data-order-id='${orderId}']`);
      if (card) card.style.borderLeft = "5px solid #198754";
    } else {
      alert("⚠️ Error: " + (data.error || "No se pudo actualizar"));
    }
  } catch (err) {
    console.error(err);
    alert("⚠️ Error de red al actualizar el pedido.");
  }
});


  /* =============================
      🚨 MODAL INCIDENTE GENERAL
  ============================== */
  document.addEventListener("click", e => {
    if (!e.target.classList.contains("btn-order-incident")) return;
    const orderId = e.target.dataset.orderId;
    document.getElementById("incidentOrderId").value = orderId;
    new bootstrap.Modal(document.getElementById("incidentModal")).show();
  });

  document.getElementById("sendIncident").addEventListener("click", async () => {
    const form = document.getElementById("incidentForm");
    const data = new FormData(form);

    const res = await fetch("index.php?page=report_incident", {
      method: "POST",
      body: data
    });
    const json = await res.json();

    if (json.success) {
      alert("✅ Incidente registrado correctamente.");
      bootstrap.Modal.getInstance(document.getElementById("incidentModal")).hide();
      form.reset();
    } else {
      alert("⚠️ Error: " + (json.error || "No se pudo guardar el incidente."));
    }
  });

  /* =============================
      ⚠️ MODAL PARCIALIDAD
  ============================== */
  document.addEventListener("click", async e => {
    if (!e.target.classList.contains("btn-order-partial")) return;

    const orderId = e.target.dataset.orderId;
    document.getElementById("partialOrderId").value = orderId;
    const container = document.getElementById("partialItemsContainer");

    container.innerHTML = `<div class="text-center text-muted p-3">
        <div class="spinner-border spinner-border-sm"></div> Cargando productos...
    </div>`;

    const res = await fetch(`index.php?page=api_get_items_by_order&id=${orderId}`);
    const items = await res.json();

    if (!items.length) {
      container.innerHTML = `<div class="alert alert-light text-center">Sin partidas.</div>`;
      return;
    }

    let html = `<table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>✔️</th>
                      <th>#</th>
                      <th>Producto</th>
                      <th>Cantidad</th>
                    </tr>
                  </thead>
                  <tbody>`;
    items.forEach(it => {
      html += `<tr>
        <td><input type="checkbox" name="partial_items[]" value="${it.id}"></td>
        <td>${it.item_number}</td>
        <td>${it.description_product}</td>
        <td>${it.quantity}</td>
      </tr>`;
    });
    html += `</tbody></table>`;
    container.innerHTML = html;

    new bootstrap.Modal(document.getElementById("partialModal")).show();
  });

  document.getElementById("sendPartial").addEventListener("click", async () => {
    const form = document.getElementById("partialForm");
    const data = new FormData(form);
    data.append("incident_type", "parcial");

    const res = await fetch("index.php?page=report_incident", {
      method: "POST",
      body: data
    });
    const json = await res.json();

    if (json.success) {
      alert("✅ Parcialidad registrada correctamente.");
      bootstrap.Modal.getInstance(document.getElementById("partialModal")).hide();
      form.reset();
    } else {
      alert("⚠️ Error: " + (json.error || "No se pudo guardar la parcialidad."));
    }
  });
});

/* =============================
    🧩 COLORES DE ESTATUS
============================= */
function statusColor(statusId) {
  switch (parseInt(statusId)) {
    case 3: return "info";     // parcial
    case 4: return "success";  // completo
    default: return "secondary";
  }
}
