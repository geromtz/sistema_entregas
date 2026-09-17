document.addEventListener('DOMContentLoaded', function () {
    // ----------------------------------------------------
    // ⏳ VIGENCIA DE PRECIOS DINÁMICA (DÍA / DÍAS)
    // ----------------------------------------------------
    const validityNumberInput = document.getElementById("validity_number");
    const validityTextHidden = document.getElementById("validity_text_hidden");
    const validityLabel = document.getElementById("validity_label");

    if (validityNumberInput && validityTextHidden && validityLabel) {
        validityNumberInput.addEventListener("input", () => {
            let val = parseInt(validityNumberInput.value.trim(), 10);
            if (isNaN(val) || val < 1) val = 1;

            const leyenda = (val === 1) ? "día o hasta agotar existencias" : "días o hasta agotar existencias";
            validityLabel.textContent = leyenda;
            validityTextHidden.value = `${val} ${leyenda}`;
        });
    }

    // ----------------------------------------------------
    // 💳 CONDICIONES DE PAGO Y PLAZOS DE CRÉDITO
    // ----------------------------------------------------
    const paymentTermsSelect = document.getElementById("payment_terms_select");
    const creditDaysContainer = document.getElementById("credit_days_container");
    const creditDaysNumber = document.getElementById("credit_days_number");
    const creditDaysLabel = document.getElementById("credit_days_label");
    const creditTermsHidden = document.getElementById("credit_terms_hidden");

    if (paymentTermsSelect && creditDaysContainer) {
        function toggleCreditDays() {
            if (paymentTermsSelect.value === "Credito") {
                creditDaysContainer.classList.remove("d-none");
                creditDaysNumber.required = true;
                updateCreditText();
            } else {
                creditDaysContainer.classList.add("d-none");
                creditDaysNumber.required = false;
                creditTermsHidden.value = "Contado";
            }
        }

        function updateCreditText() {
            let val = parseInt(creditDaysNumber.value.trim(), 10);
            if (isNaN(val) || val < 1) val = 1;

            const leyenda = (val === 1) ? "día de crédito" : "días de crédito";
            creditDaysLabel.textContent = leyenda;
            creditTermsHidden.value = `${val} ${leyenda}`;
        }

        paymentTermsSelect.addEventListener("change", toggleCreditDays);
        if (creditDaysNumber) {
            creditDaysNumber.addEventListener("input", updateCreditText);
        }

        toggleCreditDays(); // Estado inicial
    }

    // ----------------------------------------------------
    // 💱 CONTROL DE MONEDA Y TIPO DE CAMBIO
    // ----------------------------------------------------
    const currencySelect = document.getElementById("currency_select");
    const exchangeRateContainer = document.getElementById("exchange_rate_container");
    const exchangeRateInput = document.getElementById("exchange_rate_input");

    if (currencySelect && exchangeRateContainer) {
        currencySelect.addEventListener("change", () => {
            if (currencySelect.value === "USD") {
                exchangeRateContainer.classList.remove("d-none");
                exchangeRateInput.required = true;
            } else {
                exchangeRateContainer.classList.add("d-none");
                exchangeRateInput.required = false;
                exchangeRateInput.value = "";
            }
        });
    }

    const previewBtn = document.getElementById("preview_quotation_btn");
    if (previewBtn) {
        previewBtn.addEventListener("click", (e) => {
            e.preventDefault();

            // 1. Obtener el TEXTO VISIBLE del cliente
            const searchTargetInput = document.getElementById("search_target");
            let clientName = searchTargetInput && searchTargetInput.value.trim() !== ""
                ? searchTargetInput.value
                : "No especificado";

            const attentionInput = document.getElementById("attention_to_input");
            const attentionTo = attentionInput ? attentionInput.value : "N/A";

            // Capturar Moneda y Plazo de Crédito del formulario
            const currencySelect = document.querySelector('select[name*="currency"], #currency_select');
            const currencyText = currencySelect ? currencySelect.options[currencySelect.selectedIndex].text : "Pesos Mexicanos";

            // Capturar el texto dinámico real de los hidden inputs que ya creamos en las condiciones
            const creditHidden = document.getElementById("credit_terms_hidden");
            const creditTerms = creditHidden ? creditHidden.value : "30 días de crédito";

            // Asignarlas al modal
            document.getElementById("prev_client_name").innerText = clientName;
            document.getElementById("prev_attention_to").innerText = attentionTo;
            document.getElementById("prev_currency").innerText = currencyText;
            document.getElementById("prev_credit_terms").innerText = creditTerms;

            // 2. Extraer las partidas de la interfaz de manera dinámica
            const targetTbody = document.getElementById("prev_items_tbody");
            targetTbody.innerHTML = "";

            let calculatedSubtotal = 0;
            let itemsFound = 0;

            // Buscamos los contenedores de las filas (.item-row) para evaluarlas limpiamente
            const itemRows = document.querySelectorAll('.item-row');

            if (itemRows.length > 0) {
                itemRows.forEach((row) => {
                    const partInput = row.querySelector('input[name*="part_number"]');
                    const partNum = partInput ? partInput.value.trim() : "";

                    const totalPriceInput = row.querySelector('.total-price-input');
                    const totalPriceVal = totalPriceInput ? (parseFloat(totalPriceInput.value) || 0) : 0;

                    // 🛑 FILTRO CLAVE: Si la fila no tiene número de parte o su total es 0, LA IGNORAMOS por completo.
                    if (partNum === '' || totalPriceVal === 0) return;

                    const descInput = row.querySelector('input[name*="description"]');
                    const description = descInput ? descInput.value : "Sin descripción";

                    const qtyInput = row.querySelector('.qty-input');
                    const qty = qtyInput ? (parseFloat(qtyInput.value) || 1) : 1;

                    const priceInput = row.querySelector('.price-input');
                    const unitPrice = priceInput ? (parseFloat(priceInput.value) || 0) : 0;

                    const totalRow = qty * unitPrice;
                    calculatedSubtotal += totalRow;
                    itemsFound++;

                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td class="text-center">${qty}</td>
                        <td><strong>${partNum}</strong></td>
                        <td>${description}</td>
                        <td class="text-end">$ ${unitPrice.toFixed(2)}</td>
                        <td class="text-end">$ ${totalRow.toFixed(2)}</td>
                    `;
                    targetTbody.appendChild(tr);
                });
            }

            if (itemsFound === 0) {
                targetTbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No hay partidas agregadas todavía.</td></tr>`;
            }

            // 3. Totales
            const iva = calculatedSubtotal * 0.16;
            const total = calculatedSubtotal + iva;

            document.getElementById("prev_subtotal").innerText = `$ ${calculatedSubtotal.toFixed(2)}`;
            document.getElementById("prev_iva").innerText = `$ ${iva.toFixed(2)}`;
            document.getElementById("prev_total").innerText = `$ ${total.toFixed(2)}`;

            // 4. Apertura segura del modal
            const modalEl = document.getElementById("quotationPreviewModal");
            if (modalEl) {
                let existingModal = bootstrap.Modal.getInstance(modalEl);
                if (existingModal) {
                    existingModal.dispose();
                }
                const myModal = new bootstrap.Modal(modalEl);
                myModal.show();
            }
        });
    }


});