<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark"><i class="bi bi-journal-text me-2"></i>Auditoría y Monitoreo del Sistema</h2>
            <p class="text-muted">Registro de actividad del sistema totalmente adaptado a lenguaje claro y legible.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" style="font-size: 0.85rem;">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Acción Realizada</th>
                            <th>Módulo / Tabla</th>
                            <th>Reg. ID</th>
                            <th>Detalles de la Operación</th>
                            <th>Fecha y Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <?php 
                                    // Traducción de acciones
                                    $actionLabels = [
                                        'LOGIN_SUCCESS' => '<span class="badge bg-success">Inicio de sesión exitoso</span>',
                                        'LOGIN_FAILED' => '<span class="badge bg-danger">Intento fallido de acceso</span>',
                                        'UPDATE_SUPPLIER_ORDER' => '<span class="badge bg-primary">Modificó orden de proveedor</span>',
                                        'CANCEL_SUPPLIER_ORDER' => '<span class="badge bg-warning text-dark">Canceló orden</span>',
                                        'CREATE_SUPPLIER_ORDER' => '<span class="badge bg-info text-dark">Creó orden de proveedor</span>',
                                        'UPDATE_SUPPLIER_ITEM' => '<span class="badge bg-secondary">Actualizó ítem de orden</span>',
                                        'ASSIGN_DRIVER' => '<span class="badge bg-dark">Asignó chofer</span>',
                                        'SAVE_INCIDENT' => '<span class="badge bg-danger">Registró incidencia</span>',
                                        'MARK_PICKUP' => '<span class="badge bg-info text-dark">Marcó recolección</span>'
                                    ];
                                    $actionHtml = $actionLabels[$log['action_type']] ?? '<span class="badge bg-secondary">' . htmlspecialchars($log['action_type']) . '</span>';

                                    // Diccionario de campos amigables
                                    $fieldLabels = [
                                        'id_supplier' => 'Proveedor',
                                        'supplier_order' => 'Núm. Orden Proveedor',
                                        'id_status' => 'Estado',
                                        'quantity' => 'Cantidad',
                                        'product_id' => 'Producto ID',
                                        'assigned_to' => 'Chofer Asignado',
                                        'id_customer_order' => 'Orden de Cliente ID',
                                        'id_branch' => 'Sucursal',
                                        'id_order_type' => 'Tipo de Orden',
                                        'incident_type' => 'Tipo de Incidencia',
                                        'comment' => 'Comentario',
                                        'pickup_status' => 'Estado de Recolección',
                                        'pickup_by' => 'Recolectado por',
                                        'username' => 'Usuario',
                                        'attempted_username' => 'Usuario Intentado'
                                    ];

                                    $old = json_decode($log['old_data'], true);
                                    $new = json_decode($log['new_data'], true);
                                    
                                    $isLoginAction = in_array($log['action_type'], ['LOGIN_SUCCESS', 'LOGIN_FAILED']);
                                ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($log['username'] ?? 'Sistema / Externo') ?></div>
                                        <small class="text-muted">ID: <?= $log['user_id'] ?></small>
                                    </td>
                                    <td><?= $actionHtml ?></td>
                                    <td><code><?= htmlspecialchars($log['table_affected']) ?></code></td>
                                    <td><?= $log['record_id'] ?></td>
                                    <td>
                                        <?php if ($isLoginAction && is_array($new)): ?>
                                            <span class="text-muted">
                                                <?php if (isset($new['username'])): ?>
                                                    Usuario conectado: <strong><?= htmlspecialchars($new['username']) ?></strong>
                                                <?php elseif (isset($new['attempted_username'])): ?>
                                                    Intento con usuario: <strong class="text-danger"><?= htmlspecialchars($new['attempted_username']) ?></strong>
                                                <?php endif; ?>
                                                <br><small class="text-muted">IP: <?= htmlspecialchars($new['ip'] ?? '::1') ?></small>
                                            </span>
                                        <?php elseif (is_array($new)): ?>
                                            <ul class="list-unstyled mb-0" style="font-size: 0.8rem;">
                                                <?php foreach ($new as $key => $newValue): ?>
                                                    <?php 
                                                        $friendlyKey = $fieldLabels[$key] ?? $key;
                                                        $oldValue = $old[$key] ?? null;
                                                        
                                                        if ($oldValue === $newValue && $oldValue !== null) continue;
                                                    ?>
                                                    <li>
                                                        <?php if ($key === 'product_id'): ?>
                                                            <?php 
                                                                $oldText = isset($log['products_map'][$oldValue]) ? $log['products_map'][$oldValue] : "ID: " . $oldValue;
                                                                $newText = isset($log['products_map'][$newValue]) ? $log['products_map'][$newValue] : "ID: " . $newValue;
                                                            ?>
                                                            <strong><?= htmlspecialchars($friendlyKey) ?>:</strong> 
                                                            <?php if ($oldValue !== null && $oldValue !== ''): ?>
                                                                <span class="text-danger text-decoration-line-through"><?= htmlspecialchars($oldText) ?></span> ➔ 
                                                            <?php endif; ?>
                                                            <span class="text-success fw-bold"><?= htmlspecialchars($newText) ?></span>

                                                        <?php elseif ($key === 'assigned_to' || $key === 'pickup_by'): ?>
                                                            <?php 
                                                                $oldText = isset($log['users_map'][$oldValue]) ? $log['users_map'][$oldValue] : "ID: " . $oldValue;
                                                                $newText = isset($log['users_map'][$newValue]) ? $log['users_map'][$newValue] : "ID: " . $newValue;
                                                            ?>
                                                            <strong><?= htmlspecialchars($friendlyKey) ?>:</strong> 
                                                            <?php if ($oldValue !== null && $oldValue !== ''): ?>
                                                                <span class="text-danger text-decoration-line-through"><?= htmlspecialchars($oldText) ?></span> ➔ 
                                                            <?php endif; ?>
                                                            <span class="text-success fw-bold"><?= htmlspecialchars($newText) ?></span>

                                                        <?php else: ?>
                                                            <strong><?= htmlspecialchars($friendlyKey) ?>:</strong> 
                                                            <?php if ($oldValue !== null && $oldValue !== ''): ?>
                                                                <span class="text-danger text-decoration-line-through"><?= htmlspecialchars(is_array($oldValue) ? json_encode($oldValue) : $oldValue) ?></span> ➔ 
                                                            <?php endif; ?>
                                                            <span class="text-success fw-bold"><?= htmlspecialchars(is_array($newValue) ? json_encode($newValue) : $newValue) ?></span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap"><?= $log['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No hay registros de auditoría disponibles.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>