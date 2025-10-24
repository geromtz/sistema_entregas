document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("other-trip-form");

  // 🟢 Registrar nueva actividad
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = new FormData(form);

    try {
      const response = await fetch("index.php?page=api_other_trips_store", {
        method: "POST",
        body: data
      });
      const result = await response.json();

      if (result.success) {
        form.reset();
        location.reload();
      } else {
        alert("❌ Error al registrar: " + (result.error || "Desconocido"));
      }
    } catch (err) {
      console.error(err);
      alert("⚠️ Error de conexión con el servidor");
    }
  });

  // ✏️ Edición inline
  document.querySelectorAll(".btn-edit-trip").forEach((btn) => {
    btn.addEventListener("click", () => {
      const item = btn.closest(".list-group-item");
      const id = btn.dataset.id;

      const titleEl = item.querySelector(".fw-bold");
      const descEl = item.querySelector(".text-muted");

      const oldTitle = titleEl.textContent.trim();
      const oldDesc = descEl.textContent.trim();

      // Crear campos de edición
      const inputTitle = document.createElement("input");
      inputTitle.type = "text";
      inputTitle.className = "form-control form-control-sm mb-2";
      inputTitle.value = oldTitle;

      const textareaDesc = document.createElement("textarea");
      textareaDesc.className = "form-control form-control-sm";
      textareaDesc.rows = 2;
      textareaDesc.value = oldDesc;

      // Reemplazar contenido actual
      titleEl.replaceWith(inputTitle);
      descEl.replaceWith(textareaDesc);

      // Ocultar botón Editar y añadir Guardar/Cancelar
      btn.style.display = "none";

      const btnSave = document.createElement("button");
      btnSave.className = "btn btn-success btn-sm me-2";
      btnSave.innerHTML = '<i class="bi bi-check2"></i> Guardar';

      const btnCancel = document.createElement("button");
      btnCancel.className = "btn btn-outline-secondary btn-sm";
      btnCancel.innerHTML = "Cancelar";

      const actionContainer = document.createElement("div");
      actionContainer.className = "mt-2";
      actionContainer.appendChild(btnSave);
      actionContainer.appendChild(btnCancel);

      item.appendChild(actionContainer);

      // Guardar cambios
      btnSave.addEventListener("click", async () => {
        const newTitle = inputTitle.value.trim();
        const newDesc = textareaDesc.value.trim();

        if (!newTitle) {
          alert("⚠️ El título no puede estar vacío.");
          return;
        }

        const data = new FormData();
        data.append("id", id);
        data.append("title", newTitle);
        data.append("description", newDesc);

        try {
          const res = await fetch("index.php?page=api_other_trips_update", {
            method: "POST",
            body: data,
          });
          const result = await res.json();

          if (result.success) {
            // Actualizar DOM sin recargar
            const newTitleEl = document.createElement("div");
            newTitleEl.className = "fw-bold text-primary";
            newTitleEl.textContent = newTitle;

            const newDescEl = document.createElement("small");
            newDescEl.className = "text-muted";
            newDescEl.textContent = newDesc || "Sin descripción";

            inputTitle.replaceWith(newTitleEl);
            textareaDesc.replaceWith(newDescEl);
            btn.style.display = "inline-block";
            actionContainer.remove();
          } else {
            alert("❌ Error al actualizar: " + (result.error || "Desconocido"));
          }
        } catch (err) {
          console.error(err);
          alert("⚠️ Error de conexión al actualizar.");
        }
      });

      // Cancelar edición
      btnCancel.addEventListener("click", () => {
        const revertTitle = document.createElement("div");
        revertTitle.className = "fw-bold text-primary";
        revertTitle.textContent = oldTitle;

        const revertDesc = document.createElement("small");
        revertDesc.className = "text-muted";
        revertDesc.textContent = oldDesc;

        inputTitle.replaceWith(revertTitle);
        textareaDesc.replaceWith(revertDesc);
        btn.style.display = "inline-block";
        actionContainer.remove();
      });
    });
  });
});
