// public/assets/js/global_alerts.js

window.showAppAlert = function(message, type = 'danger') {
    // Buscamos o autogeneramos el contenedor de alertas global si no existe en la vista actual
    let alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.className = 'position-fixed top-0 end-0 p-3';
        alertContainer.style.zIndex = '1080';
        document.body.appendChild(alertContainer);
    }

    const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-danger text-white');
    const icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-shield-exclamation-fill');

    const alertId = 'alert_' + Date.now();
    const alertHTML = `
        <div id="${alertId}" class="toast align-items-center ${bgClass} border-0 shadow-lg show mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-medium">
                    <i class="bi ${icon} me-2"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    alertContainer.insertAdjacentHTML('beforeend', alertHTML);
    const alertElement = document.getElementById(alertId);

    setTimeout(() => {
        if (alertElement) {
            alertElement.classList.remove('show');
            setTimeout(() => alertElement.remove(), 300);
        }
    }, 3500);
};