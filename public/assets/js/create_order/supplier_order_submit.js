// public/assets/js/create_order/supplier_order_submit.js
document.addEventListener("DOMContentLoaded", () => {
  const saveBtn = document.getElementById("save-supplier-btn");
  const supplierFormWrapper = document.getElementById("supplier_type_form");
  const supplierTemplate = document.getElementById("supplier_template");

  /* ===========================================================
    🕒 Esperar ID del pedido cliente (por si se genera dinámicamente)
   =========================================================== */
  const waitCustomerOrderId = async () => {
    let id = document.getElementById("customer_order_id")?.value?.trim();
    let tries = 0;
    while ((!id || id === "") && tries < 15) {
      await new Promise((r) => setTimeout(r, 200));
      id = document.getElementById("customer_order_id")?.value?.trim();
      tries++;
    }
    return id || "";
  };

  /* ===========================================================
    🔔 Alertas Visuales (Conectadas con alertContainer)
   =========================================================== */
  const showToast = (msg, type = "info") => {
    if (window.showAlert) {
      window.showAlert(
        msg,
        type === "warning" ? "warning" : type === "error" ? "error" : "success",
      );
    } else {
      console.log(`[${type.toUpperCase()}] ${msg}`);
      alert(msg);
    }
  };

  /* ===========================================================
    💾 Guardar pedido proveedor
   =========================================================== */
  saveBtn?.addEventListener("click", async () => {
    // 1️⃣ Determinar el subform activo
    const formId = window.activeSupplierFormId;
    if (!formId) {
      showToast("Abre un tipo de pedido para continuar.", "warning");
      return;
    }

    const activeForm = document.getElementById(formId);
    if (!activeForm) {
      showToast("No se encontró el formulario activo.", "danger");
      return;
    }

    // 2️⃣ Capturar campos principales
    const supplierId =
      document.getElementById("supplier_id")?.value?.trim() || "";
    const orderNumber =
      supplierTemplate.querySelector('[name="id_order"]')?.value?.trim() || "";
    const tipoPedido = document.getElementById("tipo_pedido")?.value || "0";
    const tipoNum = Number(tipoPedido);

    // 3️⃣ Validaciones básicas
    if (!orderNumber || !tipoNum) {
      showToast("Completa número de pedido y tipo de pedido.", "warning");
      return;
    }

    const tiposConFechaObligatoria = [2, 3, 4, 5];

    if (tiposConFechaObligatoria.includes(tipoNum)) {
      const dateInputs = activeForm.querySelectorAll('input[type="date"]');
      let fechasIncompletas = false;

      dateInputs.forEach((input) => {
        if (!input.value || input.value === "0000-00-00") {
          fechasIncompletas = true;
          input.classList.add("is-invalid");
        } else {
          input.classList.remove("is-invalid");
        }
      });

      if (fechasIncompletas) {
        showToast(
          "Favor de insertar fecha de arribo (o un aproximado) para continuar.",
          "warning",
        );
        return;
      }
    }

    // Regla: exigir proveedor salvo Local(1) y Varias Sucursales(4)
    if (tipoNum !== 1 && !supplierId) {
      showToast("Por favor, ingrese un proveedor para continuar.", "warning");
      return;
    }

    // 4️⃣ Asegurar que existe un pedido cliente
    const customerOrderId = await waitCustomerOrderId();
    if (!customerOrderId) {
      showToast("No hay pedido cliente activo.", "danger");
      return;
    }

    // 🔒 🚫 BLOQUEAR BOTÓN PARA EVITAR DOBLE ENVÍO / MULTIPLES REGISTROS
    saveBtn.disabled = true;
    const originalBtnText = saveBtn.textContent;
    saveBtn.textContent = "Guardando...";

    try {
      // 5️⃣ Construir FormData desde el subform activo
      let fd;
      if (activeForm instanceof HTMLFormElement) {
        fd = new FormData(activeForm);
      } else {
        fd = new FormData();
        activeForm.querySelectorAll("input, select, textarea").forEach((el) => {
          if (el.name && !el.disabled) {
            fd.append(el.name, el.value);
          }
        });
      }

      // 5.1️⃣ Agregar datos de cabecera esperados por el backend
      fd.append("customer_order_id", customerOrderId);
      fd.append("mayorista", supplierId);
      fd.append("id_branch", document.getElementById("branch_id")?.value || 1);
      fd.append("id_order", orderNumber);
      fd.append("id_type_order", String(tipoNum));

      // 6️⃣ Enviar al backend
      const res = await fetch("index.php?page=save_single_supplier_order", {
        method: "POST",
        body: fd,
      });
      const data = await res.json();

      if (!data.success) {
        showToast(data.message || "No se pudo guardar el pedido.", "danger");
        return;
      }

      // 7️⃣ Éxito → limpiar UI
      showToast("✅ Pedido mayorista guardado correctamente.", "success");

      supplierTemplate
        ?.querySelectorAll("input, select, textarea")
        .forEach((el) => {
          if (el.type === "checkbox" || el.type === "radio") el.checked = false;
          else el.value = "";
        });

      supplierFormWrapper?.classList.add("d-none");

      // 8️⃣ Notificar a otros módulos
      window.dispatchEvent(new Event("supplierFormReset"));
      window.dispatchEvent(new Event("supplierFormSaved"));
    } catch (err) {
      console.error("❌ Error en guardado:", err);
      showToast("Error al guardar el pedido.", "danger");
    } finally {
      // 🔓 RESTAURAR BOTÓN PASE LO QUE PASE
      saveBtn.disabled = false;
      saveBtn.textContent = originalBtnText;
    }
  });
});