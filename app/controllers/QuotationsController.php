<?php
require_once __DIR__ . '/../models/Quotation.php';
require_once __DIR__ . '/../models/AuditModel.php';
require_once __DIR__ . '/../models/User.php';


use Dompdf\Dompdf;
use Dompdf\Options;

class QuotationsController
{
    private $auditModel;
    private $quotationModel; //

    public function __construct()
    {
        $this->auditModel = new AuditModel();
        $this->quotationModel = new Quotation();
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
        $quotationModel = new Quotation();

        // Obtener IDs de usuarios del equipo (jerarquía comercial)
        $teamIds = $userModel->getTeamUserIds($userId);

        // Obtener cotizaciones del equipo
        $quotations = $quotationModel->getQuotationsByUsers($teamIds);

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

        $formData = [
            'user_id'       => $userId,
            'user_fullname' => $_SESSION['user']['fullname'] ?? '',
            'date'          => date('Y-m-d')
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

        $quotationModel = new Quotation();

        // 1. Crear cabecera
        $quotationId = $quotationModel->createQuotation($_POST, $userId);

        if (!$quotationId) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la cabecera de la cotización']);
            exit;
        }

        // 2. Crear partidas si existen
        $items = $_POST['items'] ?? [];
        foreach ($items as $index => $item) {
            $item['item_number'] = $index + 1;
            $quotationModel->addItem($quotationId, $item, $userId);
        }

        // 3. Registrar en Auditoría (Estándar Pro)
        $this->auditModel->log(
            (int)$userId,
            'QUOTATION_CREATE',
            'quotations',
            (int)$quotationId,
            null,
            $_POST
        );

        echo json_encode([
            'success' => true,
            'message' => 'Cotización creada exitosamente',
            'quotation_id' => $quotationId
        ]);
        exit;
    }

    public function getCustomerContacts()
    {
        header('Content-Type: application/json');
        $customerId = $_GET['customer_id'] ?? 0;

        // Llamas al modelo para traer los contactos de ese cliente
        $contacts = $this->quotationModel->getContactsByCustomer($customerId);

        echo json_encode($contacts);
        exit;
    }

    public function searchProspect()
    {
        header('Content-Type: application/json');
        $term = $_GET['q'] ?? '';
        $userId = $_SESSION['user_id'] ?? 1; // O como obtengas el ID del vendedor actual

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
            // Llamas al modelo para insertar el contacto en la base de datos
            $success = $this->quotationModel->saveQuickContact([
                'customer_id' => $customerId,
                'contact_name' => $contactName,
                'position' => $position,
                'phone' => $phone,
                'email' => $email
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
        $baseDompdfDir = dirname(dirname(__DIR__)) . '/public/assets/libs/dompdf/';

        // 1. Incluir dependencias manuales requeridas
        require_once $baseDompdfDir . 'lib/Cpdf.php';

        // Incluir nuestro puente nativo de HTML5 para evitar requerir Composer
        require_once $baseDompdfDir . 'lib/HTML5.php';

        // 2. Autoloader nativo para las clases de Dompdf en src/
        $dompdfSrcDir = $baseDompdfDir . 'src/';

        spl_autoload_register(function ($class) use ($dompdfSrcDir) {
            $prefix = 'Dompdf\\';
            $base_dir = $dompdfSrcDir;

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });

        // 3. Configurar opciones de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        // 4. Estructura HTML y CSS formal estilo cotización tradicional
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
                
                /* Definir los márgenes de la página para dejar espacio al pie de página fijo */
                @page {
                    margin: 20mm 15mm 35mm 15mm; /* Margen inferior de 35mm reservado para los totales */
                }

                .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .header-table td { vertical-align: top; }
                .brand-era { font-size: 65px; font-weight: bold; letter-spacing: 3px; color: #111; margin: 0 0 3px 0; }
                .brand-sub { font-size: 10px; font-weight: bold; letter-spacing: 1px; color: #111; text-transform: uppercase; margin: 0; }
                .title { font-size: 22px; font-weight: bold; color: #a91d22; text-align: right; }
                .subtitle { font-size: 18px; font-weight: bold; text-align: right; color: #333; }
                
                /* Estilo corporativo minimalista para la tabla de partidas */
                .table-items { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .table-items th { 
                    background-color: #a91d22; 
                    color: #fff; 
                    padding: 8px 6px; 
                    text-align: center; 
                    font-size: 10px; 
                    border: none; 
                    font-weight: bold;
                }
                .table-items td { 
                    border: none;
                    border-bottom: 1px solid #e0e0e0; /* Línea horizontal sutil entre filas */
                    padding: 8px 6px; 
                    font-size: 10px; 
                }
                /* Fondo gris alternado muy elegante para diferenciar filas */
                .table-items tr:nth-child(odd) {
                    background-color: #e9e9e9;
                }

                .text-end { text-align: right; }
                .text-center { text-align: center; }

                /* Contenedor inferior fijo al fondo de cada página con línea divisoria superior */
                .footer-totals-container {
                position: fixed;
                bottom: -32mm; /* Ajustado para dar espacio a todo el contenido bancario */
                left: 0;
                right: 0;
                width: 100%;
                border-top: 1px solid #000; /* Línea de cierre superior exacta */
                padding-top: 8px;
                }

                .firma-nombre {
                font-family: Times New Roman, serif;
                font-style: italic;
                color: #a91d22;
                font-size: 15px;
                font-weight: bold;
                margin: 0;
                }
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
                        <div class="subtitle">Folio: 20102-26</div>
                        <p style="margin: 5px 0; font-size: 10px;">Fecha: 16 de septiembre de 2026</p>
                    </td>
                </tr>
            </table>

            <div style="margin-bottom: 20px;">
                <strong style="color: #a91d22; font-size: 14px;">CLIENTE: DELIPRIME</strong><br>
                <span style="font-size: 14px;">At\'n: PEDRO RODRIGUEZ</span>
            </div>

            <!-- Tabla de partidas con diseño limpio y minimalista -->
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
                    <tr>
                        <td class="text-center">1</td>
                        <td>2HWN3</td>
                        <td>Laptop Dell Pro PC14250 Intel Ultra 7-255U 14 pulg 16GB 512GB SSD Windows 11 Pro</td>
                        <td class="text-end">$ 1,378.00</td>
                        <td class="text-end">$ 1,378.00</td>
                        <td class="text-center">1-2 DIAS</td>
                    </tr>
                </tbody>
            </table>

            <!-- Bloque inferior con línea de cierre superior -->
            <div class="footer-totals-container">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <!-- Columna Izquierda: Condiciones y vigencia -->
                        <td style="width: 35%; vertical-align: top; font-size: 8.5px; padding-right: 5px;">
                            <p style="color: #a91d22; font-weight: bold; text-decoration: underline; margin: 0 0 3px 0; font-size: 8px;">PRECIOS VALIDOS DURANTE EL MES EN CURSO O HASTA AGOTAR EXISTENCIAS</p>
                            <p style="margin: 2px 0;"><strong>Condiciones de Pago:</strong> Credito</p>
                            <p style="margin: 2px 0;"><strong>Tiempo de Entrega:</strong> Especificado en cada producto</p>
                            <p style="margin: 2px 0;">Precios en Dolares</p>
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

                        <!-- Columna Central: Segundo Banco (BBVA) -->
                        <td style="width: 35%; vertical-align: top; font-size: 8.5px; padding-right: 5px; padding-top: 55px;">
                            <p style="margin: 3px 0;"><strong>Banco: BBVA BANCOMER</strong><br>
                            Sucursal: 4635 – Multiplaza Linda Vista<br>
                            Cuenta: 0117698313<br>
                            Clabe Interbancaria: 012580001176983131<br>
                            Moneda: Pesos Mexicanos</p>
                        </td>

                        <!-- Columna Derecha: Totales y Firma del Asesor -->
                        <td style="width: 30%; vertical-align: top; text-align: right;">
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 3px 0; font-size: 10px;">Sub-Total: <strong>$ 1,378.00</strong></p>
                                <p style="margin: 3px 0; font-size: 10px;">I.V.A. 16%: <strong>$ 220.48</strong></p>
                                <p style="margin: 3px 0; font-size: 12px; color: #198754;">Total: <strong>$ 1,598.48</strong></p>
                            </div>
                            
                            <!-- Firma / Contacto Comercial -->
                            <div   div style="text-align: center; margin-top: 20px;">
                            <p class="firma-nombre">Alicia Alonso Zenteno</p>
                            <p style="margin: 2px 0; font-style: italic; color: #a91d22; font-size: 10px; font-weight: bold;">Consultor Comercial</p>
                            <a href="mailto:alicia.alonso@eracomputacional.com.mx" style="font-size: 8.5px; color: #0000EE; text-decoration: underline;">alicia.alonso@eracomputacional.com.mx</a>
                        </div>
                        </td>
                    </tr>
                </table>
            </div>

        </body>
        </html>
        ';

        // 5. Cargar el HTML, configurar formato Carta y renderizar
        $dompdf->loadHtml($html);
        $dompdf->setPaper('Letter', 'portrait');
        $dompdf->render();

        // 6. Enviar el PDF limpio al navegador para previsualización
        $dompdf->stream("Cotizacion-20102-26.pdf", ["Attachment" => false]);
        exit;
    }

    
}
