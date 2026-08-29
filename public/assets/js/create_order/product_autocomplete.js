document.addEventListener("DOMContentLoaded", () => {
    // Función reutilizable para inicializar el autocompletado en un input de producto
    window.initProductAutocomplete = (inputEl, hiddenEl, listEl) => {
        if (!inputEl || !listEl || !hiddenEl) return;

        let timer;
        let activeIndex = -1;
        const MIN = 2;
        let currentProducts = []; // Espejo local de productos encontrados

        const clearList = () => {
            listEl.innerHTML = "";
            activeIndex = -1;
        };

        const highlightItem = (index) => {
            const items = listEl.querySelectorAll(".list-group-item");
            items.forEach((item, i) => {
                item.classList.toggle("active", i === index);
                if (i === index) item.scrollIntoView({ block: "nearest" });
            });
        };

        // --- 🔍 BUSCAR PRODUCTOS (INPUT) ---
        inputEl.addEventListener("input", () => {
            const term = inputEl.value.trim();
            hiddenEl.value = ""; // Limpiamos el ID oculto si edita el texto
            clearTimeout(timer);

            if (term.length < MIN) {
                clearList();
                currentProducts = [];
                return;
            }

            timer = setTimeout(async () => {
                try {
                    const res = await fetch(`index.php?page=search_products&term=${encodeURIComponent(term)}`);
                    const data = await res.json();

                    currentProducts = (data.success && Array.isArray(data.data)) ? data.data : [];
                    clearList();

                    if (currentProducts.length > 0) {
                        currentProducts.forEach((p) => {
                            const btn = document.createElement("button");
                            btn.type = "button";
                            btn.className = "list-group-item list-group-item-action product-item";
                            
                            // Formato visual de la lista desplegable: [Número de parte] - Descripción (Stock: X)
                            btn.innerHTML = `<strong>${p.part_number}</strong> - ${p.description} <small class="text-muted">(Stock: ${p.stock} ${p.unit_measure || 'pz'})</small>`;
                            
                            btn.dataset.id = p.id;
                            btn.dataset.part = p.part_number;
                            btn.dataset.description = p.description;

                            // Evento Click en la opción
                            btn.addEventListener("click", () => {
                                selectProduct(p);
                            });

                            listEl.appendChild(btn);
                        });
                    }
                } catch (e) {
                    console.error("Product autocomplete error:", e);
                }
            }, 250);
        });

        // Función para asignar el valor seleccionado al input y oculto
        const selectProduct = (product) => {
            inputEl.value = `${product.part_number} - ${product.description}`;
            hiddenEl.value = product.id;
            clearList();
        };

        // --- ⌨️ NAVEGACIÓN Y VALIDACIÓN (KEYDOWN) ---
        inputEl.addEventListener("keydown", (e) => {
            const items = listEl.querySelectorAll(".list-group-item");
            const total = items.length;

            if (["ArrowDown", "ArrowUp", "Enter"].includes(e.key) && total > 0) {
                e.preventDefault();
            }

            if (e.key === "ArrowDown") {
                activeIndex = (activeIndex + 1) % total;
                highlightItem(activeIndex);
            } else if (e.key === "ArrowUp") {
                activeIndex = (activeIndex - 1 + total) % total;
                highlightItem(activeIndex);
            } else if (e.key === "Enter" || e.key === "Tab") {
                // 1️⃣ CASO: Selección manual mediante flechas de teclado
                if (activeIndex >= 0 && activeIndex < total) {
                    const selected = items[activeIndex];
                    const prodId = selected.dataset.id;
                    const prodPart = selected.dataset.part;
                    const prodDesc = selected.dataset.description;

                    inputEl.value = `${prodPart} - ${prodDesc}`;
                    hiddenEl.value = prodId;
                    clearList();
                    return;
                }

                // 2️⃣ CASO: Validación instantánea por coincidencia exacta en el texto escrito
                const typedText = inputEl.value.trim();
                if (typedText.length < MIN) return;

                const exactMatch = currentProducts.find(p => {
                    const fullName = `${p.part_number} - ${p.description}`.toLowerCase();
                    return fullName === typedText.toLowerCase() || p.part_number.toLowerCase() === typedText.toLowerCase();
                });

                if (exactMatch) {
                    hiddenEl.value = exactMatch.id;
                    inputEl.value = `${exactMatch.part_number} - ${exactMatch.description}`;
                    clearList();
                }
            }
        });

        // Cerrar lista si se hace clic fuera del campo o contenedor
        document.addEventListener("click", (ev) => {
            if (!listEl.contains(ev.target) && ev.target !== inputEl) {
                clearList();
            }
        });
    };

    // --- 🔄 INICIALIZACIÓN GLOBAL Y DINÁMICA (Para filas nuevas) ---
    // Función para enganchar el autocompletado a cualquier input de producto presente o futuro
    const bindProductInputs = () => {
        document.querySelectorAll(".product-search").forEach(input => {
            // Evitar inicializar dos veces el mismo elemento
            if (input.dataset.autocompleteInitialized) return;
            input.dataset.autocompleteInitialized = "true";

            const tdContainer = input.closest("td");
            if (!tdContainer) return;

            const hiddenEl = tdContainer.querySelector(".product-id");
            const listEl = tdContainer.querySelector(".product-list");

            if (input && hiddenEl && listEl) {
                window.initProductAutocomplete(input, hiddenEl, listEl);
            }
        });
    };

    // Inicializar los que ya existen al cargar la página
    bindProductInputs();

    // Vigilar si el usuario hace clic en "Agregar partida" para inicializar los nuevos inputs automáticamente
    document.addEventListener("click", (e) => {
        if (e.target && e.target.classList.contains("add-row-btn")) {
            // Damos un pequeño respiro de tiempo para que la fila nueva se pinte en el DOM
            setTimeout(bindProductInputs, 100);
        }
    });
});