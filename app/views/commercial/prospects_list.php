<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-people-fill text-primary"></i> Gestión de Prospectos y KPIs</h2>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newProspectModal">
            <i class="bi bi-plus-lg"></i> Nuevo Prospecto
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Empresa / Negocio</th>
                            <th>Contacto</th>
                            <th>Teléfono / Correo</th>
                            <th>Etapa (Funnel)</th>
                            <th>Vendedor</th>
                            <th class="text-center">Acciones / Bitácora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prospects)): ?>
                            <?php foreach ($prospects as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['company_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['contact_name'] ?? '-') ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($p['phone'] ?? '') ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars($p['email'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($p['stage']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($p['firstname'] . ' ' . $p['apellidos']) ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-secondary btn-sm open-interaction-modal"
                                            data-id="<?= $p['id'] ?>"
                                            data-company="<?= htmlspecialchars($p['company_name']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#interactionModal">
                                            <i class="bi bi-journal-text"></i> Bitácora (<span><?= $p['total_interactions'] ?></span>)
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2"></i><br>
                                    Aún no hay prospectos registrados. Da clic en "+ Nuevo Prospecto" para comenzar.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL: NUEVO PROSPECTO ================= -->
<div class="modal fade" id="newProspectModal" tabindex="-1" aria-labelledby="newProspectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="newProspectModalLabel"><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Prospecto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="newProspectForm">
                <div class="modal-body g-3">
                    <!-- Modifica el campo de empresa en el modal de nuevo prospecto -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Empresa / Negocio:</label>
                        <input type="text" class="form-control form-control-sm" id="inputCompanyName" name="company_name" required placeholder="Ej. Era Computacional">
                        <!-- Contenedor dinámico para la animación y la alerta -->
                        <div id="duplicateAlertContainer" class="form-text mt-1" style="font-size: 0.85rem;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Persona de Contacto:</label>
                        <input type="text" class="form-control form-control-sm" name="contact_name" placeholder="Ej. Juan Pérez (Compras)">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Teléfono:</label>
                            <input type="text" class="form-control form-control-sm" name="phone" placeholder="81XXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Correo Electrónico:</label>
                            <input type="email" class="form-control form-control-sm" name="email" placeholder="correo@empresa.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Etapa Inicial:</label>
                        <select class="form-select form-select-sm" name="stage">
                            <option value="Nuevo">Nuevo</option>
                            <option value="Contactado">Contactado</option>
                            <option value="En Negociación">En Negociación</option>
                            <option value="Cotizado">Cotizado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">Guardar Prospecto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL: BITÁCORA E INTERACCIONES ================= -->
<div class="modal fade" id="interactionModal" tabindex="-1" aria-labelledby="interactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="interactionModalLabel">Bitácora de Seguimiento: <span id="modalCompanyName" class="fw-bold">---</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Formulario estructurado para registrar nueva interacción -->
                <form id="interactionForm" class="card p-3 bg-light border-0 mb-4">
                    <input type="hidden" id="modalProspectId" name="prospect_id">
                    <h6 class="text-secondary fw-bold mb-3"><i class="bi bi-plus-circle"></i> Registrar Nueva Interacción</h6>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Canal / Tipo de Interacción:</label>
                            <select class="form-select form-select-sm" name="interaction_type" required>
                                <option value="Llamada saliente">Llamada saliente</option>
                                <option value="Llamada entrante">Llamada entrante</option>
                                <option value="Correo enviado">Correo enviado</option>
                                <option value="WhatsApp / Mensaje">WhatsApp / Mensaje</option>
                                <option value="Reunión / Cita presencial">Reunión / Cita presencial</option>
                                <option value="Videoconferencia">Videoconferencia</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Resultado (KPI):</label>
                            <select class="form-select form-select-sm" name="result_status" required>
                                <option value="Contacto exitoso">Contacto exitoso</option>
                                <option value="No contesta / Buzón">No contesta / Buzón</option>
                                <option value="Agendó cita / Seguimiento">Agendó cita / Seguimiento</option>
                                <option value="Solicitó cotización">Solicitó cotización</option>
                                <option value="Rechazado / Sin interés">Rechazado / Sin interés</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Próxima llamada / Cita (Compromiso):</label>
                            <input type="datetime-local" class="form-control form-control-sm" name="next_action_date">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nota o comentario breve:</label>
                            <input type="text" class="form-control form-control-sm" name="comments" placeholder="Ej. Pidió cotizar 3 impresoras...">
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-success btn-sm px-4">Guardar Seguimiento</button>
                        </div>
                    </div>
                </form>

                <!-- Historial Cronológico -->
                <h6 class="text-secondary fw-bold mb-3"><i class="bi bi-clock-history"></i> Historial de Seguimiento</h6>
                <div id="interactionsTimeline" class="list-group overflow-auto" style="max-height: 250px;">
                    <div class="text-center text-muted py-3">Cargando historial...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Guardar Nuevo Prospecto
        document.getElementById('newProspectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('index.php?page=store_prospect', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Recarga para ver el nuevo prospecto en la tabla
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        });

        // 2. Manejar Modal de Bitácora
        const interactionModal = document.getElementById('interactionModal');
        interactionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const prospectId = button.getAttribute('data-id');
            const companyName = button.getAttribute('data-company');

            document.getElementById('modalCompanyName').textContent = companyName;
            document.getElementById('modalProspectId').value = prospectId;

            loadInteractions(prospectId);
        });

        // 3. Enviar nueva interacción por AJAX
        document.getElementById('interactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('index.php?page=save_interaction', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const currentProspectId = formData.get('prospect_id');
                        this.reset();
                        document.getElementById('modalProspectId').value = currentProspectId;
                        loadInteractions(currentProspectId);
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        });
    });

    function loadInteractions(prospectId) {
        const timeline = document.getElementById('interactionsTimeline');
        timeline.innerHTML = '<div class="text-center text-muted py-3">Cargando historial...</div>';

        fetch(`index.php?page=get_interactions&prospect_id=${prospectId}`)
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(item => {
                        html += `
                            <div class="list-group-item list-group-item-action flex-column align-items-start mb-3 border rounded shadow-sm p-3">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h5 class="mb-0 text-primary fw-bold">
                                        <i class="bi bi-chat-left-text"></i> ${item.interaction_type} 
                                        <span class="badge bg-secondary text-white fs-6 fw-normal ms-2">${item.result_status}</span>
                                    </h5>
                                    <span class="text-muted fw-semibold" style="font-size: 0.9rem;">${item.created_at}</span>
                                </div>
                                <p class="mb-2 text-dark fs-6">${item.comments ? item.comments : '<i class="text-muted">Sin comentarios adicionales.</i>'}</p>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 text-muted" style="font-size: 0.85rem;">
                                    <span><i class="bi bi-person-badge"></i> Registrado por: <strong>${item.firstname} ${item.apellidos}</strong></span>
                                    ${item.next_action_date ? `<span class="text-danger fw-bold"><i class="bi bi-alarm"></i> Próximo contacto: ${item.next_action_date}</span>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    timeline.innerHTML = html;
                } else {
                    timeline.innerHTML = '<div class="text-center text-muted py-4">No hay interacciones registradas para este prospecto.</div>';
                }
            });
    }

    // 🔍 Validación en tiempo real al salir del campo de texto de la empresa
    const companyInput = document.getElementById('inputCompanyName');
    const alertContainer = document.getElementById('duplicateAlertContainer');
    const submitBtn = document.getElementById('saveProspectBtn'); // Asegúrate de ponerle id="saveProspectBtn" a tu botón de guardar

    if (companyInput) {
        companyInput.addEventListener('blur', function() {
            const companyName = this.value.trim();
            if (companyName.length < 2) {
                alertContainer.innerHTML = '';
                return;
            }

            // Animación ligera de "Consultando..."
            alertContainer.innerHTML = `<span class="text-muted spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Consultando prospectos...`;

            fetch(`index.php?page=check_duplicate_prospect&company_name=${encodeURIComponent(companyName)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exists) {
                        alertContainer.innerHTML = `<div class="alert alert-warning py-1 px-2 mb-0 mt-1">${data.message}</div>`;
                        // Opcional: si quieres deshabilitar el botón de guardar para evitar duplicados
                        // if(submitBtn) submitBtn.disabled = true;
                    } else {
                        alertContainer.innerHTML = `<span class="text-success"><i class="bi bi-check-circle"></i> Empresa disponible.</span>`;
                        // if(submitBtn) submitBtn.disabled = false;
                    }
                })
                .catch(err => {
                    alertContainer.innerHTML = '';
                });
        });
    }
</script>