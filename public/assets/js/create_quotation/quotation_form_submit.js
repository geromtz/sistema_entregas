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
    // 3. ENVÍO DEL FORMULARIO POR AJAX (FETCH)
    // ----------------------------------------------------
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

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

        const submitBtn = form.querySelector('button[type="submit"]');
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
    });
});