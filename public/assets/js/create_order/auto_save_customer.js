document.addEventListener("DOMContentLoaded", () => {
  const customerIdInput = document.getElementById("customer_id");
  const customerNameInput = document.getElementById("customer");
  const titleInput = document.getElementById("order_name");
  const poInput = document.getElementById("order_number");
  const commentsInput = document.querySelector('input[name="comments"]');
  const attnInput = document.querySelector('input[name="receiver"]');

  if (!customerIdInput) {
    console.warn("⚠️ Campo #customer_id no encontrado. Auto-save desactivado.");
    return;
  }

  async function saveCustomer() {
    const systemOrderInput = document.getElementById("system_order_id");
    const systemOrderIdforSeller = systemOrderInput?.value || "";

    if (!customerIdInput.value) {
      console.log("⏸️ Auto-save detenido: cliente aún sin ID.");
      return;
    }

    // // 📋 Diagnóstico: mostrar lo que se enviará
    // console.group("📤 Auto-save cliente");
    // console.log("➡️ system_id:", systemOrderIdforSeller);
    // console.log("➡️ id_customer:", customerIdInput.value);
    // console.log("➡️ customer_name:", customerNameInput?.value);
    // console.log("➡️ name_order:", titleInput?.value);
    // console.log("➡️ purchase_order:", poInput?.value);
    // console.log("➡️ comments:", commentsInput?.value);
    // console.log("➡️ attn:", attnInput?.value);
    // console.groupEnd();

    const formData = new FormData();
    formData.append("system_id", systemOrderIdforSeller);
    formData.append("id_customer", customerIdInput.value || "");
    formData.append("customer_name", customerNameInput?.value || "");
    formData.append("name_order", titleInput?.value || "");
    formData.append("purchase_order", poInput?.value || "");
    formData.append("comments", commentsInput?.value || "");
    formData.append("attn", attnInput?.value || "");

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
        console.log("✅ Pedido guardado.");

        // ✅ Asignar ID del pedido cliente al input oculto
        if (data.data && data.data.customer_order_id) {
          const customerOrderIdInput =
            document.getElementById("customer_order_id");
          if (customerOrderIdInput) {
            customerOrderIdInput.value = data.data.customer_order_id;
            console.log(
              "📌 ID del pedido cliente actualizado:",
              data.data.customer_order_id
            );
          } else {
            console.warn(
              "⚠️ No se encontró el campo #customer_order_id en el DOM."
            );
          }
        }

        // 🟢 Si el backend devolvió serie y número, actualizamos el DOM
        if (data.data) {
          const { order_seller_series, order_seller_number } = data.data;

          const seriesEl = document.getElementById("order_seller_series");
          const numberEl = document.getElementById("order_seller_number");

          if (seriesEl && order_seller_series) {
            seriesEl.textContent = order_seller_series;
          }
          if (numberEl && order_seller_number) {
            numberEl.textContent = order_seller_number;
          }

          console.log("📦 Serie/Número del pedido cliente actualizados:", {
            order_seller_series,
            order_seller_number,
          });
        }
      } else {
        console.warn(
          "⚠️ Error al guardar pedido:",
          data.message || "Respuesta vacía del servidor."
        );
      }
    } catch (error) {
      console.error("❌ Error en autosave:", error);
    }
  }

  // Escucha cambios o salidas de campo (blur o change)
  [
    customerIdInput,
    customerNameInput,
    titleInput,
    poInput,
    commentsInput,
    attnInput,
  ].forEach((el) => {
    if (el) {
      el.addEventListener("blur", saveCustomer);
      el.addEventListener("change", saveCustomer);
    }
  });

  // 🚀 Auto-save inmediato cuando se asigna un cliente
  const observer = new MutationObserver(() => {
    if (customerIdInput.value) {
      console.log("🆕 Cliente asignado, ejecutando auto-save inmediato...");
      saveCustomer();
      observer.disconnect(); // Detenemos el observer para que no se repita
    }
  });

  // Detectar cambios en atributos o contenido del input (para autocompletes, etc.)
  observer.observe(customerIdInput, {
    attributes: true,
    attributeFilter: ["value"],
  });
});
