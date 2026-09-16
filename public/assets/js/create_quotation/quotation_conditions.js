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
        previewBtn.addEventListener("click", () => {
            // 1. Recopilar datos generales de la pantalla
            const destName = document.getElementById("search_target").value || "CLIENTE GENERAL";
            const attentionTo = document.getElementById("attention_to_input").value || "SIN ESPECIFICAR";
            const validityText = document.getElementById("validity_text_hidden")?.value || "Validez estándar";
            const paymentTerms = document.getElementById("payment_terms_select")?.value || "Contado";
            const currency = document.getElementById("currency_select")?.value || "MXN";

            // Si es crédito, obtenemos el texto completo de los días de crédito
            let conditionsText = paymentTerms;
            if (paymentTerms === "Credito") {
                conditionsText = document.getElementById("credit_terms_hidden")?.value || "Crédito";
            }

            // 2. Recopilar las partidas de la tabla de productos
            let rowsHtml = "";
            const itemRows = document.querySelectorAll(".item-row");

            itemRows.forEach((row, idx) => {
                const qty = row.querySelector(".qty-input")?.value || "1";
                const partNumber = row.querySelector("input[name*='[part_number]']")?.value || "N/A";
                const description = row.querySelector("input[name*='[description]']")?.value || "Sin descripción";
                const unitPrice = parseFloat(row.querySelector(".price-input")?.value || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                const totalPrice = parseFloat(row.querySelector(".total-price-input")?.value || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                const deliveryTime = row.querySelector("input[name*='[delivery_time_days]']")?.value || "2-4 días";

                rowsHtml += `
                <tr>
                    <td class="text-center border-end py-2">${qty}</td>
                    <td class="border-end py-2 font-monospace">${partNumber}</td>
                    <td class="border-end py-2">${description}</td>
                    <td class="text-end border-end py-2">$ ${unitPrice}</td>
                    <td class="text-end border-end py-2">$ ${totalPrice}</td>
                    <td class="text-center py-2">${deliveryTime}</td>
                </tr>
            `;
            });

            // Rellenar filas vacías opcionales para que simule la hoja de excel formal si son pocas partidas
            for (let i = itemRows.length; i < 6; i++) {
                rowsHtml += `
                <tr>
                    <td class="border-end py-3">&nbsp;</td>
                    <td class="border-end py-3"></td>
                    <td class="border-end py-3"></td>
                    <td class="border-end py-3"></td>
                    <td class="border-end py-3"></td>
                    <td class="py-3"></td>
                </tr>
            `;
            }

            // 3. Totales
            const subtotal = document.getElementById("lbl_subtotal")?.textContent || "$0.00";
            const iva = document.getElementById("lbl_iva")?.textContent || "$0.00";
            const total = document.getElementById("lbl_total")?.textContent || "$0.00";

            // 4. Inyectar HTML estructurado estilo formato formal dentro del modal
            const previewContainer = document.getElementById("quotation_preview_content");
            previewContainer.innerHTML = `
            <div style="font-family: Arial, sans-serif; font-size: 12px; color: #000;">
                <!-- Header -->
                <div class="row border-bottom pb-3 mb-3 align-items-center">
                    <div class="col-6">
                        <h4 class="fw-bold text-danger mb-0" style="letter-spacing: 1px;">ERA COMPUTACIONAL, S.A. DE C.V.</h4>
                        <p class="text-muted mb-0 small">RFC: ECO-100211-SZ1</p>
                        <p class="text-muted mb-0 small">Vista Hermosa 403 Col. Linda Vista, Guadalupe, N.L. C.P. 67123</p>
                        <p class="text-muted mb-0 small">Tel: 81-44-44-65-13 Cel: 81-19-38-10-40</p>
                    </div>
                    <div class="col-6 text-end">
                        <h3 class="fw-bold text-dark fst-italic mb-1">COTIZACIÓN</h3>
                        <h5 class="fw-bold text-danger mb-1">20102-26</h5>
                        <p class="text-muted mb-0">${new Date().toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                    </div>
                </div>

                <!-- Cliente y At'n -->
                <div class="mb-4">
                    <h6 class="fw-bold text-danger text-uppercase mb-1">${destName}</h6>
                    <p class="mb-0"><strong>At'n:</strong> ${attentionTo}</p>
                </div>

                <!-- Tabla de Artículos -->
                <table class="table table-bordered border-dark mb-3" style="font-size: 11px;">
                    <thead class="bg-danger text-white text-center">
                        <tr>
                            <th class="bg-secondary text-white py-2" style="width: 8%;">Cantidad</th>
                            <th class="bg-secondary text-white py-2" style="width: 18%;">Número de Parte</th>
                            <th class="bg-secondary text-white py-2" style="width: 36%;">Descripción</th>
                            <th class="bg-secondary text-white py-2" style="width: 13%;">Precio/Unidad</th>
                            <th class="bg-secondary text-white py-2" style="width: 13%;">Total</th>
                            <th class="bg-secondary text-white py-2" style="width: 12%;">Tiempo de Entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>

                <!-- Fila inferior: Notas y Totales -->
                <div class="row mt-3">
                    <div class="col-7">
                        <p class="text-danger text-decoration-underline fw-bold mb-1" style="font-size: 11px;">${validityText.toUpperCase()}</p>
                        <p class="mb-1"><strong>Condiciones de Pago:</strong> ${conditionsText}</p>
                        <p class="mb-1"><strong>Moneda:</strong> ${currency === 'USD' ? 'Precios en Dolares' : 'Pesos Mexicanos (MXN)'}</p>
                        <p class="mb-1">Precios sujetos a cambio sin previo aviso.</p>
                        <p class="mb-3">Los Precios No Incluyen IVA</p>
                        
                        <div class="border-top pt-2 small">
                            <p class="fw-bold mb-1">Cuenta Bancaria para Depósitos ó Transferencias Electrónicas</p>
                            <div class="row">
                                <div class="col-6">
                                    <span class="text-muted">Banco:</span> <strong>BANREGIO</strong><br>
                                    <span class="text-muted">Sucursal:</span> 042 Pablo Livas<br>
                                    <span class="text-muted">Cuenta:</span> 042918800012<br>
                                    <span class="text-muted">Clabe:</span> 058580429188000127
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">Banco:</span> <strong>BBVA BANCOMER</strong><br>
                                    <span class="text-muted">Sucursal:</span> 4635 - Multiplaza Linda Vista<br>
                                    <span class="text-muted">Cuenta:</span> 0117698313<br>
                                    <span class="text-muted">Clabe:</span> 012580001176983131
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-5">
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                            <span>Sub-Total:</span>
                            <strong>$ ${subtotal.replace('$', '')}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                            <span>I.V.A. 16%:</span>
                            <strong>$ ${iva.replace('$', '')}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-2 fw-bold fs-6">
                            <span>Total:</span>
                            <span class="text-success">$ ${total.replace('$', '')}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        });
    }
});