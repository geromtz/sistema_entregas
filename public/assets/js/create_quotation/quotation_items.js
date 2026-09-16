document.addEventListener('DOMContentLoaded', function() {
    // ----------------------------------------------------
    // 📦 MOTOR DE PARTIDAS & CÁLCULOS EN VIVO (FLETES Y MÁRGENES)
    // ----------------------------------------------------
    let rowIndex = 0;
    const itemsContainer = document.getElementById('items_container');
    const template = document.getElementById('item_row_template');

    if (!itemsContainer || !template) return;

    function addItemRow() {
        const clone = template.content.cloneNode(true);
        const rowEl = clone.querySelector('.item-row');

        rowEl.innerHTML = rowEl.innerHTML.replaceAll('INDEX', rowIndex);

        itemsContainer.appendChild(rowEl);
        initRowEvents(itemsContainer.lastElementChild);
        rowIndex++;
        updateRowNumbers();
        calculateAll();
    }

    function updateRowNumbers() {
        const rows = itemsContainer.querySelectorAll('.item-row');
        rows.forEach((row, idx) => {
            row.querySelector('.row-number').textContent = `#${idx + 1}`;
            row.querySelector('.item-number-input').value = idx + 1;
        });
    }

    function initRowEvents(row) {
        const qtyInput = row.querySelector('.qty-input');
        const costInput = row.querySelector('.cost-input');
        const profitInput = row.querySelector('.profit-input');
        const freightInput = row.querySelector('.col-md-6 .freight-input') || row.querySelector('input[name*="[freight_cost]"]');
        const priceInput = row.querySelector('.price-input');
        const removeBtn = row.querySelector('.remove-item-btn');

        [qtyInput, costInput, profitInput, freightInput, priceInput].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    computeRow(row, input === priceInput);
                    calculateAll();
                });
            }
        });

        removeBtn.addEventListener('click', () => {
            if (itemsContainer.querySelectorAll('.item-row').length > 1) {
                row.remove();
                updateRowNumbers();
                calculateAll();
            } else {
                alert('La cotización requiere al menos una partida.');
            }
        });

        if (rowIndex === 0) {
            qtyInput.value = 1;
            costInput.value = 100.00;
            profitInput.value = 40.00;
            computeRow(row);
        }
    }

    function computeRow(row, userChangedPrice = false) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
        const freight = parseFloat(row.querySelector('input[name*="[freight_cost]"]').value) || 0;
        let profitPct = parseFloat(row.querySelector('.profit-input').value) || 0;

        const priceInput = row.querySelector('.price-input');
        const totalPriceInput = row.querySelector('.total-price-input');
        const netProfitInput = row.querySelector('.net-profit-input');
        const netProfitDisplay = row.querySelector('.net-profit-display');

        let unitPrice = 0;
        let netProfitPerUnit = 0;

        if (!userChangedPrice) {
            const productProfit = cost * (profitPct / 100);
            const unitProductPrice = cost + productProfit;
            unitPrice = Math.round(unitProductPrice + freight);

            priceInput.value = unitPrice.toFixed(2);
            netProfitPerUnit = (unitPrice - freight) - cost;
        } else {
            unitPrice = parseFloat(priceInput.value) || 0;
            netProfitPerUnit = (unitPrice - freight) - cost;

            if (cost > 0) {
                profitPct = (netProfitPerUnit / cost) * 100;
                row.querySelector('.profit-input').value = profitPct.toFixed(2);
            }
        }

        const totalPrice = unitPrice * qty;
        const totalNetProfit = netProfitPerUnit * qty;

        totalPriceInput.value = totalPrice.toFixed(2);
        netProfitInput.value = totalNetProfit.toFixed(2);
        netProfitDisplay.value = `$${totalNetProfit.toFixed(2)}`;
    }

    function calculateAll() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const totalPrice = parseFloat(row.querySelector('.total-price-input').value) || 0;
            subtotal += totalPrice;
        });

        const iva = subtotal * 0.16;
        const total = subtotal + iva;

        document.getElementById('lbl_subtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('input_subtotal').value = subtotal.toFixed(2);

        document.getElementById('lbl_iva').textContent = `$${iva.toFixed(2)}`;
        document.getElementById('input_iva').value = iva.toFixed(2);

        document.getElementById('lbl_total').textContent = `$${total.toFixed(2)}`;
        document.getElementById('input_total').value = total.toFixed(2);
    }

    const addItemBtn = document.getElementById('add_item_btn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => addItemRow());
    }
    
    // Iniciar con la primera fila por defecto
    addItemRow();

    // Enviar formulario general por AJAX
    const quotationForm = document.getElementById('demo_quotation_form');
    if (quotationForm) {
        quotationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            fetch('index.php?page=store_quotation', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('¡Éxito! ' + data.message + ' (ID Cotización: ' + data.quotation_id + ')');
                    window.location.href = 'index.php?page=my_quotations';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error('Error:', err));
        });
    }
});