<?php
require_once __DIR__ . '/../models/Quotation.php';
require_once __DIR__ . '/../models/AuditModel.php';
require_once __DIR__ . '/../models/User.php';

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
}
