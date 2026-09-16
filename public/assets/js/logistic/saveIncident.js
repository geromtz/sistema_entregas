// Esta función prepara el modal con la info del producto correcto
function reportIncident(id, productDescription) {
    // 1. Inyectamos los valores al modal
    document.getElementById('modal_item_id').value = id;
    document.getElementById('incidentProductText').innerText = productDescription;
    
    // 2. Limpiamos estados anteriores
    document.getElementById('modal_comments').value = "";
    const quickSelect = document.getElementById('quickReasonSelect');
    if (quickSelect) quickSelect.value = ""; // Limpiar select rápido si existe
    
    const releaseCheck = document.getElementById('releaseTaskCheck');
    if (releaseCheck) releaseCheck.checked = true; // Por defecto marcado para liberar si hay incidente

    const feedback = document.getElementById('mensajeFeedback');
    if (feedback) feedback.className = "text-center p-2 rounded d-none";
    
    // 3. Levantamos el modal con la API de Bootstrap
    const myModal = new bootstrap.Modal(document.getElementById('incidentModal'));
    myModal.show();
}

// Función auxiliar para rellenar rápido el comentario con el select
function fillComment(select) {
    if (select.value) {
        document.getElementById('modal_comments').value = select.value;
    }
}

// Función para guardar Incidente vía AJAX
function guardarIncidenteJS() {
    const btn = document.getElementById('btnGuardarIncidente');
    const itemId = document.getElementById('modal_item_id').value;
    const comments = document.getElementById('modal_comments').value;
    const feedback = document.getElementById('mensajeFeedback');

    // 1. Validación básica
    if (!comments.trim()) {
        feedback.className = "text-center p-2 rounded d-block alert-danger mb-3";
        feedback.innerHTML = '<i class="bi bi-x-circle me-1"></i> El comentario o motivo es obligatorio.';
        return;
    }

    // 2. Estado de carga: Bloqueamos botón para evitar duplicados
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
    if (feedback) feedback.className = "d-none"; // Ocultar mensajes previos

    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('comments', comments);
    formData.append('type', 'pickup');

    // Capturar si se marcó la casilla de liberar tarea de la ruta
    const releaseCheck = document.getElementById('releaseTaskCheck');
    formData.append('release_task', releaseCheck && releaseCheck.checked ? '1' : '0');

    // 3. Envío de datos al controlador
    fetch('index.php?page=save_incident_ajax', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            feedback.className = "text-center p-2 rounded d-block alert-success mb-3 fw-bold shadow-sm";
            feedback.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ¡Registro exitoso!';
            
            setTimeout(() => {
                const modalEl = document.getElementById('incidentModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                
                location.reload();
            }, 1200);

        } else {
            feedback.className = "text-center p-2 rounded d-block alert-danger mb-3";
            feedback.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> ' + (data.error || 'No se pudo guardar.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar Reporte';
        }
    })
    .catch(error => {
        console.error('Error Crítico:', error);
        feedback.className = "text-center p-2 rounded d-block alert-danger mb-3";
        feedback.innerHTML = '<i class="bi bi-bug me-1"></i> Error de conexión o respuesta inválida.';
        
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar Reporte';
    });
}