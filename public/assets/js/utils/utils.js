// Funciones globales reutilizables
window.confirmWithModal = function(name) {
    return new Promise((resolve) => {
        const modalEl = document.getElementById('confirmCreateModal');
        const confirmBtn = document.getElementById('confirmCreateBtn');
        const modalText = document.getElementById('customerNameToCreate');

        if(!modalEl) {
            console.error("Error: No se encontró el modal 'confirmCreateModal' en el DOM.");
            return resolve(false);
        }

        modalText.textContent = name;
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();

        confirmBtn.onclick = () => {
            bsModal.hide();
            resolve(true);
        };

        modalEl.addEventListener('hidden.bs.modal', () => {
            resolve(false);
        }, { once: true });
    });
};

/**
 * Muestra una alerta visual en el #alertContainer
 * @param {string} msg - Mensaje a mostrar
 * @param {string} type - success, error, warning, info
 */
window.showAlert = function(msg, type = "success") {
    const container = document.getElementById('alertContainer');
    if (!container) return;

    const bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-warning');
    const toastId = 'alert-' + Date.now();

    const html = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    const element = document.getElementById(toastId);
    const toast = new bootstrap.Toast(element, { delay: 3000 });
    toast.show();

    element.addEventListener('hidden.bs.toast', () => element.remove());
};