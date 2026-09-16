<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark"><i class="bi bi-window-sidebar me-2"></i>Editor Avanzado de Menús por Departamento</h2>
            <p class="text-muted">Gestiona la cinta de opciones, crea nuevos módulos y previsualiza el resultado en tiempo real.</p>
        </div>
    </div>

    <!-- Selector de Departamento -->
    <form method="GET" action="index.php" class="row mb-4">
        <input type="hidden" name="page" value="menu_editor">
        <div class="col-md-4">
            <label for="department_id" class="form-label fw-semibold">Departamento a Configurar:</label>
            <select name="department_id" id="department_id" class="form-select shadow-sm border-primary" onchange="this.form.submit()">
                <option value="">-- Elige un departamento --</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= (isset($selectedDepartment) && $selectedDepartment == $dept['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['name_department']) ?> (ID: <?= $dept['id'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if (!empty($selectedDepartment)): ?>
        <?php
            $assignedList = [];
            $availableList = [];

            foreach ($menus as $m) {
                if (in_array($m['id'], $assignedMenus) || $m['is_global'] == 1) {
                    $assignedList[] = $m;
                } else {
                    $availableList[] = $m;
                }
            }
        ?>

        <div class="row g-4">
            <!-- Columna Izquierda: Disponibles + Crear Nuevo Menú -->
            <div class="col-lg-6">
                <!-- Formulario de Creación Rápida de Menú -->
                <div class="card shadow-sm border-0 mb-4 bg-light">
                    <div class="card-header bg-secondary text-white py-2">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-1"></i>Crear Nueva Opción de Menú</h6>
                    </div>
                    <div class="card-body">
                        <form action="index.php?page=create_menu_item" method="POST" class="row g-2">
                            <input type="hidden" name="id_department" value="<?= $selectedDepartment ?>">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nombre:</label>
                                <input type="text" name="name" class="form-control form-control-sm" required placeholder="Ej. Reportes Avanzados">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tipo:</label>
                                <select name="type" class="form-select form-select-sm" id="menuTypeSelect" onchange="toggleViewName(this)">
                                    <option value="menu">Menu (Enlace)</option>
                                    <option value="dropdown">Dropdown (Desplegable)</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="viewNameContainer">
                                <label class="form-label small fw-bold">View Name (Ruta):</label>
                                <input type="text" name="view_name" class="form-control form-control-sm" placeholder="ej. advanced_reports">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Menú Padre (Dropdown):</label>
                                <select name="parent_id" class="form-select form-select-sm">
                                    <option value="">-- Ninguno (Raíz) --</option>
                                    <?php foreach ($menus as $m): ?>
                                        <?php if ($m['type'] === 'dropdown' || $m['view_name'] === '#'): ?>
                                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Orden Numérico:</label>
                                <input type="number" name="order_num" class="form-control form-control-sm" value="1" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_global" value="1" id="isGlobalCheck">
                                    <label class="form-check-label small" for="isGlobalCheck">¿Es Global?</label>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">Crear y Asignar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Disponibles -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-secondary">Menús Disponibles / Restantes</h6>
                    </div>
                    <div class="card-body p-2">
                        <select multiple id="availableMenus" class="form-select border-0 shadow-none font-monospace" style="height: 220px; font-size: 0.85rem;">
                            <?php foreach ($availableList as $menu): ?>
                                <option value="<?= $menu['id'] ?>">
                                    [<?= strtoupper($menu['type']) ?>] <?= htmlspecialchars($menu['name']) ?> <?= $menu['is_global'] == 1 ? '🌐' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Botones centrales -->
            <div class="col-lg-1 d-flex flex-column justify-content-center align-items-center py-3">
                <button type="button" class="btn btn-primary btn-sm w-100 mb-2 shadow-sm" onclick="moveSelected('availableMenus', 'assignedMenus'); updatePreview();" title="Agregar">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm w-100 shadow-sm" onclick="moveSelected('assignedMenus', 'availableMenus'); updatePreview();" title="Quitar">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>

            <!-- Columna Derecha: Asignados + Vista Previa -->
            <div class="col-lg-5">
                <form action="index.php?page=save_department_menus" method="POST" id="menuForm">
                    <input type="hidden" name="id_department" value="<?= $selectedDepartment ?>">

                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header bg-primary text-white py-3">
                            <h6 class="mb-0 fw-bold">Asignados a este Departamento</h6>
                        </div>
                        <div class="card-body p-2">
                            <select multiple id="assignedMenus" name="menus[]" class="form-select border-0 shadow-none font-monospace" style="height: 250px; font-size: 0.85rem;">
                                <?php foreach ($assignedList as $menu): ?>
                                    <option value="<?= $menu['id'] ?>">
                                        [<?= strtoupper($menu['type']) ?>] <?= htmlspecialchars($menu['name']) ?> <?= $menu['is_global'] == 1 ? '🌐' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="card-footer bg-white py-2 text-end">
                            <button type="submit" class="btn btn-success btn-sm px-4 fw-bold" onclick="selectAllOptions()">
                                <i class="bi bi-save me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Vista Previa en Tiempo Real -->
                <div class="card shadow-sm border-0 border-info">
                    <div class="card-header bg-info text-dark py-2">
                        <h6 class="mb-0 fw-bold small"><i class="bi bi-eye me-1"></i>Vista Previa de la Barra (Header)</h6>
                    </div>
                    <div class="card-body bg-light p-2">
                        <nav class="navbar navbar-expand-lg navbar-light bg-white border rounded px-2 py-1">
                            <span class="navbar-brand fw-bold text-muted small me-3">ERA</span>
                            <ul class="navbar-nav flex-row flex-wrap gap-2" id="liveNavbarPreview">
                                <!-- Se llena dinámicamente con JS -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            <div>
                <span>Arquitectura del Sistema de Menús

Tabla menus: Almacena el catálogo completo de navegación del sistema. Campos principales: id, name, parent_id (contiene el ID del menú padre si es un submenú o NULL si es un elemento raíz), view_name (la ruta asociada en routes.php o # si es un contenedor desplegable), type (menu o dropdown), order_num (posición numérica) y is_global (1 si es accesible globalmente para todos, 0 si requiere asignación por departamento).

Tabla department_menus: Tabla intermedia que maneja una relación de muchos a muchos mediante id_department e id_menu. Define qué departamentos tienen acceso a qué secciones específicas.

Regla Crítica de Visualización: Para que un submenú hijo se muestre en la interfaz, su menú padre de tipo dropdown debe estar explícitamente asignado al departamento en la tabla department_menus; de lo contrario, el contenedor principal se oculta y los hijos no se renderizan.

Lógica de Carga y Renderizado (Menu.php)

Método getMenusByDepartment($departmentId): Consulta los menús vinculados al departamento o aquellos que tengan is_global = 1.

Método buildMenuTree(): Toma el arreglo plano de la base de datos y construye de forma recursiva una estructura jerárquica basada en el parent_id.

Método renderMenu(): Recorre el árbol jerárquico generado y escupe el HTML compatible con Bootstrap (nav-item dropdown, dropdown-menu, etc.) para dibujarlo en el header de la aplicación.

Panel de Administración (MenuEditorController y Vista)

Gestionado por MenuEditorController.php y la vista en app/views/system/menu_editor.php.

Permite dar de alta nuevos menús directamente en la tabla menus especificando sus parámetros y asociarlos a los departamentos mediante una interfaz de selección de doble columna, actualizando masivamente la tabla department_menus sin necesidad de inserciones manuales en SQL.</span>
            </div>
        </div>

        <!-- Scripts de interacción y vista previa -->
        <script>
            function moveSelected(sourceId, targetId) {
                let source = document.getElementById(sourceId);
                let target = document.getElementById(targetId);
                for (let i = source.options.length - 1; i >= 0; i--) {
                    let opt = source.options[i];
                    if (opt.selected) {
                        target.appendChild(opt);
                        opt.selected = false;
                    }
                }
            }

            function selectAllOptions() {
                let assigned = document.getElementById('assignedMenus');
                for (let i = 0; i < assigned.options.length; i++) {
                    assigned.options[i].selected = true;
                }
            }

            function toggleViewName(select) {
                let container = document.getElementById('viewNameContainer');
                if (select.value === 'dropdown') {
                    container.style.display = 'none';
                } else {
                    container.style.display = 'block';
                }
            }

            // Simulación de vista previa en tiempo real basada en la columna derecha
            function updatePreview() {
                let assignedSelect = document.getElementById('assignedMenus');
                let previewContainer = document.getElementById('liveNavbarPreview');
                previewContainer.innerHTML = '';

                for (let i = 0; i < assignedSelect.options.length; i++) {
                    let text = assignedSelect.options[i].text.replace(/\[.*?\]/g, '').replace('🌐', '').trim();
                    let isDropdown = assignedSelect.options[i].text.includes('[DROPDOWN]');

                    let li = document.createElement('li');
                    li.className = isDropdown ? 'nav-item dropdown' : 'nav-item';
                    
                    let a = document.createElement('a');
                    a.className = isDropdown ? 'nav-link dropdown-toggle py-0 small fw-semibold text-primary' : 'nav-link py-0 small text-dark';
                    a.href = '#';
                    a.innerText = text;
                    
                    li.appendChild(a);
                    previewContainer.appendChild(li);
                }
            }

            // Ejecutar al cargar la página para pintar la vista previa inicial
            document.addEventListener("DOMContentLoaded", updatePreview);
        </script>
    <?php endif; ?>
</div>