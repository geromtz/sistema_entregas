// Esta función prepara el modal con la info del producto correcto
function reportIncident(id, productDescription) {
    // 1. Inyectamos los valores al modal
    document.getElementById('modal_item_id').value = id;
    document.getElementById('incidentProductText').innerText = productDescription;
    
    // 2. Limpiamos estados anteriores
    document.getElementById('modal_comments').value = "";
    const feedback = document.getElementById('mensajeFeedback');
    feedback.className = "text-center p-2 rounded d-none";
    
    // 3. Levantamos el modal con la API de Bootstrap
    const myModal = new bootstrap.Modal(document.getElementById('incidentModal'));
    myModal.show();
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
        feedback.innerHTML = '<i class="bi bi-x-circle me-1"></i> El comentario es obligatorio.';
        return;
    }

    // 2. Estado de carga: Bloqueamos botón para evitar duplicados
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
    feedback.className = "d-none"; // Ocultar mensajes previos

    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('comments', comments);
    formData.append('type', 'pickup');

    // 3. Envío de datos al controlador
    fetch('index.php?page=save_incident_ajax', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Verificamos si la respuesta es OK (status 200)
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Éxito visual
            feedback.className = "text-center p-2 rounded d-block alert-success mb-3 fw-bold shadow-sm";
            feedback.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ¡Registro exitoso!';
            
            // Esperar un momento para que el usuario vea el mensaje verde
            setTimeout(() => {
                // Cerrar Modal
                const modalEl = document.getElementById('incidentModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                
                // RECARGAR para que el log aparezca en la tabla automáticamente
                location.reload();
            }, 1200);

        } else {
            // Error reportado por el PHP (ej. fallo en BD)
            feedback.className = "text-center p-2 rounded d-block alert-danger mb-3";
            feedback.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> ' + (data.error || 'No se pudo guardar.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar Reporte';
        }
    })
    .catch(error => {
        // Error de red o respuesta no JSON
        console.error('Error Crítico:', error);
        feedback.className = "text-center p-2 rounded d-block alert-danger mb-3";
        feedback.innerHTML = '<i class="bi bi-bug me-1"></i> Error de conexión o respuesta inválida.';
        
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar Reporte';
    });
}