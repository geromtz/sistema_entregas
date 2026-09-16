<?php
require_once __DIR__ . '/../models/Customer.php';

class CustomerController
{
    private $customerModel;

    public function __construct()
    {
        // Siguiendo el estándar de seguridad de tus controladores
        if (class_exists('AuthMiddleware')) {
            AuthMiddleware::checkLogin();
        }

        $this->customerModel = new Customer();
    }

    public function listCustomers()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        $positionId = $_SESSION['user']['id_position'] ?? null;

        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        // 1. Obtenemos los datos del modelo respetando la seguridad de roles/puestos
        $customers = $this->customerModel->getCustomersByRole($userId, $positionId);

        // 2. Definimos la ruta de la vista que consumirá el layout global
        $view = 'commercial/customers_list';

        // 3. Requerimos la plantilla principal tal como lo hace WarehouseController
        require_once __DIR__ . '/../views/layouts/app.php';
    }

    public function updateCustomerFull()
    {
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = $this->customerModel->updateFullCustomer($_POST);

            if (session_status() === PHP_SESSION_NONE) session_start();
            if ($success) {
                $_SESSION['success'] = "Expediente del cliente actualizado correctamente.";
            } else {
                $_SESSION['error'] = "Hubo un error al actualizar los datos del cliente.";
            }
        }
        header('Location: index.php?page=my_customers');
        exit;
    }

    public function getCustomerAddresses()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    // Capturar el ID ya sea por GET o por POST para evitar fallos
    $customerId = $_GET['id'] ?? $_POST['id'] ?? null;

    if (!$customerId) {
        echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado', 'addresses' => []]);
        exit;
    }

    $addresses = $this->customerModel->getAddressesByCustomerId((int)$customerId);

    echo json_encode([
        'success' => true,
        'addresses' => $addresses
    ]);
    exit;
}

    public function saveCustomerAddress()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = $this->customerModel->saveAddress($_POST);
            echo json_encode(['success' => $success]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }

    public function deleteCustomerAddress()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $addressId = $_POST['address_id'] ?? null;
        if ($addressId) {
            $success = $this->customerModel->deleteAddress((int)$addressId);
            echo json_encode(['success' => $success]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }
}
