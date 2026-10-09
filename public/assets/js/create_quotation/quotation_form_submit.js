document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('demo_quotation_form');
    if (!form) return;

    // ----------------------------------------------------
    // 1. CAPTURAR EL VALOR DEL BOTÓN EXACTO QUE SE PRESIONÓ
    // ----------------------------------------------------
    let clickedAction = 'save_draft'; // Valor por defecto
    form.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.addEventListener('click', function () {
            clickedAction = this.value; // Lee el value ('save_draft', 'emit', 'update_draft', etc.)
        });
    });

    // ----------------------------------------------------
    // 2. SINCRONIZADORES EN TIEMPO REAL
    // ----------------------------------------------------
    const validityNumberInput = document.getElementById('validity_number');
    const validityTextHidden = document.getElementById('validity_text_hidden');
    if (validityNumberInput && validityTextHidden) {
        validityNumberInput.addEventListener('input', function () {
            const val = this.value.trim() || 4;
            validityTextHidden.value = `${val} días o hasta agotar existencias`;
        });
    }

    const creditDaysNumberInput = document.getElementById('credit_days_number');
    const creditTermsHidden = document.getElementById('credit_terms_hidden');
    if (creditDaysNumberInput && creditTermsHidden) {
        creditDaysNumberInput.addEventListener('input', function () {
            const days = this.value.trim() || 30;
            creditTermsHidden.value = `${days} días de crédito`;
        });
    }

    // ----------------------------------------------------
    // 3. FUNCIÓN DE ENVÍO REAL (TU MISMO FETCH ORIGINAL)
    // ----------------------------------------------------
    async function ejecutarEnvioAjax() {
        const submitBtn = form.querySelector(`button[type="submit"][value="${clickedAction}"]`) || form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

        // Bloquear botón para evitar doble clic
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...`;
        }

        try {
            // Aseguramos que los inputs ocultos tengan la info más fresca antes de empaquetar
            if (validityNumberInput && validityTextHidden) {
                const val = validityNumberInput.value.trim() || 4;
                validityTextHidden.value = `${val} días o hasta agotar existencias`;
            }
            if (creditDaysNumberInput && creditTermsHidden) {
                const days = creditDaysNumberInput.value.trim() || 30;
                creditTermsHidden.value = `${days} días de crédito`;
            }

            const formData = new FormData(form);

            // 🔹 INYECTAMOS LA ACCIÓN CAPTURADA DEL BOTÓN AL FORMDATA
            formData.append('action', clickedAction);

            // Detección dinámica: Si el input 'id' no existe en el DOM o está vacío, creamos una nueva; si existe, actualizamos.
            const quotationIdInput = form.querySelector('input[name="id"]');
            const hasValidId = quotationIdInput && quotationIdInput.value.trim() !== "" && quotationIdInput.value.trim() !== "0";
            const endpoint = hasValidId ? 'index.php?page=update_quotation' : 'index.php?page=store_quotation';

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                window.showAppAlert(result.message || 'Operación realizada correctamente.', 'success');

                setTimeout(() => {
                    // 🔹 Si el controlador confirma que la acción fue 'emit', abrimos el PDF automáticamente en otra pestaña
                    if (result.action === 'emit' && result.quotation_id) {
                        window.open(`index.php?page=print_quotation&id=${result.quotation_id}`, '_blank');
                    }
                    // Redirigimos al listado general
                    window.location.href = `index.php?page=my_quotations`;
                }, 1200);
            } else {
                window.showAppAlert(result.message || 'Ocurrió un error al procesar la solicitud.', 'danger');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            }

        } catch (error) {
            console.error("Error en el envío del formulario:", error);
            window.showAppAlert('Error de conexión con el servidor.', 'danger');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    }

    // ----------------------------------------------------
    // 4. INTERCEPTAR EL SUBMIT PARA PREGUNTAR CON EL MODAL
    // ----------------------------------------------------
    form.addEventListener('submit', function (e) {
        e.preventDefault(); // Detenemos el envío inmediato

        // Validar que exista al menos una partida en el contenedor
        const itemsRows = document.querySelectorAll('.item-row');
        if (itemsRows.length === 0) {
            window.showAppAlert('La cotización requiere al menos una partida.', 'warning');
            return;
        }

        // Validar que se haya seleccionado un cliente o prospecto
        const customerId = document.getElementById('id_customer').value.trim();
        const prospectId = document.getElementById('id_prospect').value.trim();
        if (customerId === '' && prospectId === '') {
            window.showAppAlert('Debes seleccionar un Cliente o un Prospecto de destino.', 'warning');
            document.getElementById('search_target').focus();
            return;
        }

        // 🔹 BUSCAMOS EL MODAL DE BOOTSTRAP EN TU VISTA
        const modalEl = document.getElementById('quotationActionConfirmModal');
        
        // Si por alguna razón el modal no existe en la vista, mandamos el AJAX directo para no bloquearte
        if (!modalEl) {
            ejecutarEnvioAjax();
            return;
        }

        // Personalizamos textos y colores según el botón que se presionó
        const modalTitle = document.getElementById('confirmModalTitle');
        const modalText = document.getElementById('confirmModalText');
        const confirmBtn = document.getElementById('confirmActionBtn');

        if (clickedAction === 'emit') {
            modalTitle.innerHTML = `<i class="bi bi-shield-exclamation text-warning me-2"></i> ⚠️ ¿Emitir y Congelar Cotización?`;
            modalText.textContent = "Una vez emitida formalmente, esta cotización quedará congelada y no se podrá modificar directamente. ¿Deseas enviarla ahora?";
            confirmBtn.className = "btn btn-success btn-sm rounded-pill px-4 fw-semibold shadow-sm";
            confirmBtn.textContent = "Sí, emitir";
        } else if (clickedAction === 'update_draft') {
            modalTitle.innerHTML = `<i class="bi bi-shield-exclamation text-primary me-2"></i> 💾 ¿Actualizar Borrador?`;
            modalText.textContent = "¿Estás seguro de guardar los cambios realizados en este borrador?";
            confirmBtn.className = "btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm";
            confirmBtn.textContent = "Sí, actualizar";
        } else {
            modalTitle.innerHTML = `<i class="bi bi-shield-exclamation text-secondary me-2"></i> 💾 ¿Guardar Borrador?`;
            modalText.textContent = "¿Deseas guardar esta cotización como borrador?";
            confirmBtn.className = "btn btn-secondary btn-sm rounded-pill px-4 fw-semibold shadow-sm";
            confirmBtn.textContent = "Sí, guardar";
        }

        // Mostramos el modal de Bootstrap
        let confirmModal = bootstrap.Modal.getInstance(modalEl);
        if (!confirmModal) {
            confirmModal = new bootstrap.Modal(modalEl);
        }
        confirmModal.show();

        // Limpiamos eventos previos del botón de aceptar para evitar duplicidad de envíos
        const nuevoBtnConfirmar = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(nuevoBtnConfirmar, confirmBtn);

        // Cuando el usuario confirma haciendo clic en el botón del modal
        nuevoBtnConfirmar.addEventListener('click', function () {
            confirmModal.hide(); // Cerramos el modal
            ejecutarEnvioAjax(); // 🚀 Disparamos tu función original de AJAX intacta
        });
    });

    // ----------------------------------------------------
    // 5. INTERCEPTAR EL CLIC EN EL BOTÓN DE RE-COTIZAR (CLONE)
    // ----------------------------------------------------
    const cloneBtn = document.querySelector('a[href*="clone_quotation_version"]');
    if (cloneBtn) {
        cloneBtn.addEventListener('click', function (e) {
            e.preventDefault(); // Detenemos la navegación directa
            const targetUrl = this.href;

            const modalEl = document.getElementById('quotationActionConfirmModal');
            if (!modalEl) {
                // Si por algo no está el modal, dejamos que navegue directo
                window.location.href = targetUrl;
                return;
            }

            const modalTitle = document.getElementById('confirmModalTitle');
            const modalText = document.getElementById('confirmModalText');
            const confirmBtn = document.getElementById('confirmActionBtn');

            // Personalizamos el modal para la acción de re-cotizar
            modalTitle.innerHTML = `<i class="bi bi-shield-exclamation text-warning me-2"></i> 📋 ¿Generar Nueva Versión?`;
            modalText.textContent = "Se creará una copia de esta cotización como una nueva versión comercial para re-cotizar. ¿Deseas proceder?";
            confirmBtn.className = "btn btn-warning btn-sm rounded-pill px-4 fw-semibold shadow-sm text-dark";
            confirmBtn.textContent = "Sí, clonar versión";

            // Mostramos el modal de Bootstrap
            let confirmModal = bootstrap.Modal.getInstance(modalEl);
            if (!confirmModal) {
                confirmModal = new bootstrap.Modal(modalEl);
            }
            confirmModal.show();

            // Limpiamos eventos previos del botón de aceptar
            const nuevoBtnConfirmar = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(nuevoBtnConfirmar, confirmBtn);

            // Cuando el usuario confirma, redirigimos a la URL de clonación
            nuevoBtnConfirmar.addEventListener('click', function () {
                confirmModal.hide();
                window.location.href = targetUrl;
            });
        });
    }
});