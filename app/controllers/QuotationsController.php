<?php
require_once __DIR__ . '/../models/Quotation.php';
require_once __DIR__ . '/../models/AuditModel.php';
require_once __DIR__ . '/../models/User.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class QuotationsController
{
    private $auditModel;
    private $quotationModel;

    public function __construct()
    {
        $this->auditModel = new AuditModel();
        $this->quotationModel = new Quotation(); // 👈 CORREGIDO: Inicializado correctamente aquí
    }

    /**
     * Muestra el listado de cotizaciones del equipo (Ruta: my_quotations)
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $userModel = new User();
        // Obtener IDs de usuarios del equipo (jerarquía comercial)
        $teamIds = $userModel->getTeamUserIds($userId);

        // Obtener cotizaciones del equipo
        $quotations = $this->quotationModel->getQuotationsByUsers($teamIds);

        // Definir la vista comercial
        $view = 'commercial/quotation_list';
        require __DIR__ . '/../views/layouts/app.php';
    }

    /**
     * Muestra el formulario para crear una nueva cotización (Ruta: create_quotation)
     */
    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        // Calcular el siguiente número consecutivo de preview para este vendedor
        $series = 'COT';
        $nextNumber = $this->quotationModel->getNextQuotationNumber($series, $userId);
        $previewFolio = "{$series}-{$userId}-" . date('Ymd') . "-{$nextNumber}";

        $formData = [
            'user_id'       => $userId,
            'user_fullname' => $_SESSION['user']['fullname'] ?? '',
            'date'          => date('Y-m-d'),
            'preview_folio' => $previewFolio
        ];

        $view = 'forms/create_quotation';
        require __DIR__ . '/../views/layouts/app.php';
    }

    /**
     * Guarda la cotización, sus partidas y registra la auditoría (Ruta: store_quotation)
     */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Aseguramos que la serie por defecto sea COT
        $_POST['quotation_series'] = 'COT';

        // 🔹 NUEVO: Capturamos la acción del botón para definir el estatus comercial
        $action = $_POST['action'] ?? 'save_draft';
        if ($action === 'emit') {
            $_POST['status'] = 'Enviada'; // Se emite formalmente y se congela
        } else {
            $_POST['status'] = 'Borrador'; // Se queda en borrador para edición libre
        }

        // 1. Crear cabecera (El modelo calculará el consecutivo por vendedor y armará el folio semántico)
        $quotationId = $this->quotationModel->createQuotation($_POST, $userId);

        if (!$quotationId) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la cabecera de la cotización']);
            exit;
        }

        // 2. Crear partidas si existen
        $items = $_POST['items'] ?? [];
        foreach ($items as $index => $item) {
            $item['item_number'] = $index + 1;
            $this->quotationModel->addItem($quotationId, $item, $userId);
        }

        // 3. Registrar en Auditoría (Estándar Pro)
        $this->auditModel->log(
            (int)$userId,
            $action === 'emit' ? 'QUOTATION_EMIT' : 'QUOTATION_CREATE', // Opcional: distinguimos en auditoría si se emitió de golpe
            'quotations',
            (int)$quotationId,
            null,
            $_POST
        );

        echo json_encode([
            'success'      => true,
            'message'      => $action === 'emit' ? 'Cotización emitida exitosamente' : 'Borrador de cotización guardado exitosamente',
            'quotation_id' => $quotationId,
            'action'       => $action // Devolvemos la acción para que el JS sepa si debe redirigir al PDF o mantenerse
        ]);
        exit;
    }

    /**
     * Procesa la clonación como nueva versión (Ruta: clone_quotation_version)
     */
    public function cloneVersion()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $quotationId = $_GET['id'] ?? 0;
        if (!$quotationId) {
            header('Location: index.php?page=my_quotations');
            exit;
        }

        // Ejecutar la clonación en el modelo
        $newQuotationId = $this->quotationModel->cloneAsNewVersion((int)$quotationId, (int)$userId);

        if ($newQuotationId) {
            // Registrar auditoría opcional si gustas
            // Redirigir al formulario de edición de la nueva versión creada
            header("Location: index.php?page=edit_quotation&id={$newQuotationId}");
            exit;
        } else {
            // Manejar error de clonación
            header('Location: index.php?page=my_quotations&error=clone_failed');
            exit;
        }
    }

    /**
     * Muestra el formulario de cotización en modo Edición (Ruta: edit_quotation)
     */
    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $quotationId = $_GET['id'] ?? 0;

        // 1. Cabecera
        $quotation = $this->quotationModel->getQuotationById($quotationId);
        if (!$quotation) {
            header('Location: index.php?page=my_quotations');
            exit;
        }

        // 2. 🔍 ESTO ES LO CLAVE: Recuperar las partidas asociadas a este ID
        $items = $this->quotationModel->getItemsByQuotationId($quotationId);

        $formData = [
            'user_id'       => $userId,
            'user_fullname' => $_SESSION['user']['fullname'] ?? '',
            'preview_folio' => $quotation['folio']
        ];

        $isEdit = true;
        $view = 'forms/create_quotation';
        require __DIR__ . '/../views/layouts/app.php';
    }

    /**
     * Procesa la actualización de la cotización por AJAX (Ruta: update_quotation)
     */
    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $quotationId = $_POST['id'] ?? 0;
        if (!$quotationId) {
            echo json_encode(['success' => false, 'message' => 'ID de cotización no válido']);
            exit;
        }

        // 🔹 1. VALIDACIÓN DE SEGURIDAD COMERCIAL: Verificar el estatus actual en BD
        $currentQuotation = $this->quotationModel->getQuotationById((int)$quotationId);
        if (!$currentQuotation) {
            echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
            exit;
        }

        // Si ya fue emitida o cerrada, no se permite sobreescribir directamente
        if ($currentQuotation['status'] !== 'Borrador') {
            echo json_encode([
                'success' => false,
                'message' => 'Esta cotización ya fue emitida o procesada. No se puede modificar directamente; requiere una nueva versión.'
            ]);
            exit;
        }

        // 🔹 2. CAPTURAR LA ACCIÓN: ¿Solo se actualiza el borrador o se emite formalmente?
        $action = $_POST['action'] ?? 'update_draft';
        if ($action === 'emit') {
            $_POST['status'] = 'Enviada'; // Cambia de borrador a enviada (se congela)
        } else {
            $_POST['status'] = 'Borrador'; // Se mantiene como borrador
        }

        // 3. Actualizar cabecera con el estatus correspondiente
        $updated = $this->quotationModel->updateQuotation((int)$quotationId, $_POST, $userId);

        if (!$updated) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la cabecera de la cotización']);
            exit;
        }

        // 4. Reemplazar partidas (Borrando las anteriores y creando las nuevas del DOM)
        $this->quotationModel->deleteItemsByQuotationId((int)$quotationId);

        $items = $_POST['items'] ?? [];
        foreach ($items as $index => $item) {
            $item['item_number'] = $index + 1;
            $this->quotationModel->addItem((int)$quotationId, $item, $userId);
        }

        // 5. Registrar auditoría de actualización o emisión
        $auditAction = ($action === 'emit') ? 'QUOTATION_EMIT_FROM_DRAFT' : 'QUOTATION_UPDATE';
        $this->auditModel->log(
            (int)$userId,
            $auditAction,
            'quotations',
            (int)$quotationId,
            $currentQuotation, // Guardamos los datos previos gracias a que la consultamos arriba
            $_POST
        );

        echo json_encode([
            'success'      => true,
            'message'      => ($action === 'emit') ? 'Cotización emitida y congelada exitosamente' : 'Borrador actualizado exitosamente',
            'quotation_id' => $quotationId,
            'action'       => $action
        ]);
        exit;
    }

    public function getCustomerContacts()
    {
        header('Content-Type: application/json');
        $customerId = $_GET['customer_id'] ?? 0;

        $contacts = $this->quotationModel->getContactsByCustomer($customerId);

        echo json_encode($contacts);
        exit;
    }

    public function searchProspect()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        $term = $_GET['q'] ?? '';
        $userId = $_SESSION['user']['id'] ?? 1; // 👈 CORREGIDO: Estándar correcto para la sesión

        $prospects = $this->quotationModel->searchProspects($term, $userId);
        echo json_encode($prospects);
        exit;
    }

    public function storeQuickContact()
    {
        header('Content-Type: application/json');

        $customerId = $_POST['customer_id'] ?? 0;
        $contactName = $_POST['contact_name'] ?? '';
        $position = $_POST['position'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';

        if (!$customerId || !$contactName) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            exit;
        }

        try {
            $success = $this->quotationModel->saveQuickContact([
                'customer_id'  => $customerId,
                'contact_name' => $contactName,
                'position'     => $position,
                'phone'        => $phone,
                'email'        => $email
            ]);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Contacto guardado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar en la base de datos.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function printPdf()
    {
        $quotationId = $_GET['id'] ?? 0;
        if (!$quotationId) {
            die('ID de cotización no especificado.');
        }

        // 1. Obtener la cotización real con datos del cliente/prospecto y del vendedor
        $quotation = $this->quotationModel->getQuotationById((int)$quotationId);
        if (!$quotation) {
            die('Cotización no encontrada.');
        }

        // 2. Obtener las partidas (items) reales asociadas
        $items = $this->quotationModel->getItemsByQuotationId((int)$quotationId);

        // Definir nombre del cliente o prospecto para mostrar
        $destName = !empty($quotation['customer_name']) ? $quotation['customer_name'] : ($quotation['prospect_name'] ?? 'PÚBLICO EN GENERAL');
        $attentionTo = !empty($quotation['attention_to']) ? $quotation['attention_to'] : ($quotation['prospect_contact'] ?? 'N/A');

        // Nombre completo del vendedor responsable
        $vendedorNombre = trim(($quotation['firstname'] ?? '') . ' ' . ($quotation['apellidos'] ?? 'Asesor Comercial'));

        // 3. Configurar Dompdf (Carga manual de dependencias que ya tienes)
        $baseDompdfDir = dirname(dirname(__DIR__)) . '/public/assets/libs/dompdf/';
        require_once $baseDompdfDir . 'lib/Cpdf.php';
        require_once $baseDompdfDir . 'lib/HTML5.php';

        $dompdfSrcDir = $baseDompdfDir . 'src/';
        spl_autoload_register(function ($class) use ($dompdfSrcDir) {
            $prefix = 'Dompdf\\';
            $base_dir = $dompdfSrcDir;
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) require $file;
        });

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        // 4. Generar dinámicamente las filas de la tabla de partidas
        $rowsHtml = '';
        foreach ($items as $item) {
            $cant = $item['quantity'] ?? 1;
            $numParte = $item['product_part_number'] ?? 'S/N';
            $desc = $item['product_description'] ?? 'Sin descripción';
            $uPrice = number_format($item['unit_price'] ?? 0, 2);
            $tPrice = number_format($item['total_price'] ?? 0, 2);
            $entrega = $item['delivery_time_days'] ?? '4 a 7 días';

            $rowsHtml .= "
            <tr>
                <td class='text-center'>{$cant}</td>
                <td><strong>{$numParte}</strong></td>
                <td>{$desc}</td>
                <td class='text-end'>$ {$uPrice}</td>
                <td class='text-end'>$ {$tPrice}</td>
                <td class='text-center'>{$entrega}</td>
            </tr>
        ";
        }

        if (empty($items)) {
            $rowsHtml = '<tr><td colspan="6" class="text-center">No hay partidas registradas en esta cotización.</td></tr>';
        }

        $subtotalFormatted = number_format($quotation['subtotal'] ?? 0, 2);
        $ivaFormatted = number_format($quotation['tax_iva'] ?? 0, 2);
        $totalFormatted = number_format($quotation['total'] ?? 0, 2);
        $fechaEmision = date('d \d\e F \d\e Y', strtotime($quotation['quotation_date'] ?? date('Y-m-d')));

        // 5. Estructura HTML limpia con inyección de datos reales
        $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
            @page { margin: 20mm 15mm 35mm 15mm; }
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .header-table td { vertical-align: top; }
            .brand-era { font-size: 65px; font-weight: bold; letter-spacing: 3px; color: #111; margin: 0 0 3px 0; }
            .brand-sub { font-size: 10px; font-weight: bold; letter-spacing: 1px; color: #111; text-transform: uppercase; margin: 0; }
            .title { font-size: 22px; font-weight: bold; color: #a91d22; text-align: right; }
            .subtitle { font-size: 16px; font-weight: bold; text-align: right; color: #333; }
            .table-items { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .table-items th { background-color: #a91d22; color: #fff; padding: 8px 6px; text-align: center; font-size: 10px; border: none; font-weight: bold; }
            .table-items td { border: none; border-bottom: 1px solid #e0e0e0; padding: 8px 6px; font-size: 10px; }
            .table-items tr:nth-child(odd) { background-color: #e9e9e9; }
            .text-end { text-align: right; }
            .text-center { text-align: center; }
            .footer-totals-container { position: fixed; bottom: -32mm; left: 0; right: 0; width: 100%; border-top: 1px solid #000; padding-top: 8px; }
            .firma-nombre { font-family: Times New Roman, serif; font-style: italic; color: #a91d22; font-size: 15px; font-weight: bold; margin: 0; }
        </style>
    </head>
    <body>

        <table class="header-table">
            <tr>
                <td style="width: 35%;">
                    <div class="brand-container">
                        <div class="brand-era">ERA</div>
                        <div class="brand-sub">COMPUTACIONAL, S.A. DE C.V.</div>
                    </div>
                </td>
                <td style="width: 35%; text-align: center; font-size: 14px; color: #2b2a2a; vertical-align: middle;">
                    <p style="margin: 2px 0;"><strong>RFC:</strong> ECO-100211-SZ1</p>
                    <p style="margin: 2px 0;">Vista Hermosa 403 Col. Linda Vista,<br>Guadalupe, N.L. C.P. 67123</p>
                    <p style="margin: 2px 0;"><strong>Tel:</strong> 81-44-44-65-13</p>
                </td>
                <td style="width: 30%; text-align: right;">
                    <div class="title">COTIZACIÓN</div>
                    <div class="subtitle">Folio: ' . htmlspecialchars($quotation['folio']) . '</div>
                    <p style="margin: 5px 0; font-size: 10px;">Fecha: ' . $fechaEmision . '</p>
                </td>
            </tr>
        </table>

        <div style="margin-bottom: 20px;">
            <strong style="color: #a91d22; font-size: 14px;">CLIENTE: ' . htmlspecialchars(strtoupper($destName)) . '</strong><br>
            <span style="font-size: 14px;">At\'n: ' . htmlspecialchars($attentionTo) . '</span>
        </div>

        <table class="table-items">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Número de Parte</th>
                    <th>Descripción</th>
                    <th>Precio/Unidad</th>
                    <th>Total</th>
                    <th>Tiempo de Entrega</th>
                </tr>
            </thead>
            <tbody>
                ' . $rowsHtml . '
            </tbody>
        </table>

        <div class="footer-totals-container">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 35%; vertical-align: top; font-size: 8.5px; padding-right: 5px;">
                        <p style="color: #a91d22; font-weight: bold; text-decoration: underline; margin: 0 0 3px 0; font-size: 8px;">PRECIOS VALIDOS DURANTE EL MES EN CURSO O HASTA AGOTAR EXISTENCIAS</p>
                        <p style="margin: 2px 0;"><strong>Condiciones de Pago:</strong> ' . htmlspecialchars($quotation['payment_conditions']) . '</p>
                        <p style="margin: 2px 0;"><strong>Tiempo de Entrega:</strong> Especificado en cada producto</p>
                        <p style="margin: 2px 0;">Precios en ' . htmlspecialchars($quotation['currency']) . '</p>
                        <p style="margin: 2px 0;">Precios sujetos a cambio sin previo aviso. Los Precios No Incluyen IVA.</p>
                        
                        <div style="margin-top: 8px; font-weight: bold;">
                            <p style="margin: 0;">Cuenta Bancaria para Depósitos ó</p>
                            <p style="margin: 0;">Transferencias Electrónicas</p>
                        </div>
                        <p style="margin: 3px 0;"><strong>Banco: BANREGIO</strong><br>
                        Sucursal: 042 Pablo Livas<br>
                        Cuenta: 042918800012<br>
                        Clabe Interbancaria: 058580429188000127<br>
                        Moneda: Pesos Mexicanos</p>
                    </td>

                    <td style="width: 35%; vertical-align: top; font-size: 8.5px; padding-right: 5px; padding-top: 55px;">
                        <p style="margin: 3px 0;"><strong>Banco: BBVA BANCOMER</strong><br>
                        Sucursal: 4635 – Multiplaza Linda Vista<br>
                        Cuenta: 0117698313<br>
                        Clabe Interbancaria: 012580001176983131<br>
                        Moneda: Pesos Mexicanos</p>
                    </td>

                    <td style="width: 30%; vertical-align: top; text-align: right;">
                        <div style="margin-bottom: 15px;">
                            <p style="margin: 3px 0; font-size: 10px;">Sub-Total: <strong>$ ' . $subtotalFormatted . '</strong></p>
                            <p style="margin: 3px 0; font-size: 10px;">I.V.A. 16%: <strong>$ ' . $ivaFormatted . '</strong></p>
                            <p style="margin: 3px 0; font-size: 12px; color: #198754;">Total: <strong>$ ' . $totalFormatted . '</strong></p>
                        </div>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <p class="firma-nombre">' . htmlspecialchars($vendedorNombre) . '</p>
                            <p style="margin: 2px 0; font-style: italic; color: #a91d22; font-size: 10px; font-weight: bold;">Consultor Comercial</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

    </body>
    </html>
    ';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('Letter', 'portrait');
        $dompdf->render();

        $dompdf->stream("Cotizacion-{$quotation['folio']}.pdf", ["Attachment" => false]);
        exit;
    }
}
