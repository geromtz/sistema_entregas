<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Order.php';

class OrdersController
{
    public function myOrders()
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) session_start();

        $orders = [];
        $tempPendingOrders = [];

        try {
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                throw new Exception("Usuario no logueado.");
            }

            $userModel = new User();
            $orderModel = new Order();

            // Obtener IDs de usuarios del equipo
            $teamIds = $userModel->getTeamUserIds($userId);

            // Pedidos normales del equipo
            $orders = $orderModel->getOrdersByUsers($teamIds);

            // Pedidos temporales o pendientes del usuario actual
            $tempPendingOrders = $orderModel->getTemporaryOrPendingOrders($userId);
        } catch (Exception $e) {
            $orders[] = ['error' => "Error al obtener pedidos: " . $e->getMessage()];
        }

        // Vista interna relativa a app/views
        $view = 'system/my_system_orders';

        // Pasamos ambos arrays a la vista
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function createOrder()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        $userFullname = $_SESSION['user']['fullname'] ?? '';

        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $orderModel = new Order();
        $userModel = new User();

        // 1️⃣ Revisar si viene el parámetro en la URL
        $systemOrderId = $_GET['system_order'] ?? null;

        if ($systemOrderId) {
            // Cargar orden existente
            $systemOrder = $orderModel->getSystemOrderById((int)$systemOrderId);

            // 🧠 Si no existe (por ejemplo, fue eliminada al recargar o cerrar)
            if (!$systemOrder) {
                // 🔁 Redirigir de vuelta a la lista de pedidos del usuario
                header('Location: index.php?page=my_system_orders&msg=order_deleted');
                exit;
            }

            // 🔒 Validar que el usuario pueda verla (CEO, líder o del mismo equipo)
            $teamIds = $userModel->getTeamUserIds($userId);
            if (!in_array($systemOrder['created_by'], $teamIds)) {
                header('Location: index.php?page=my_orders');
                exit;
            }
        } else {
            // 2️⃣ Si no hay orden, crear una nueva temporal
            $systemOrder = $orderModel->createSystemOrder($userId);

            // 🟢 Redirigir con el ID de la nueva orden en la URL
            header("Location: index.php?page=create_order&system_order=" . $systemOrder['id']);
            exit;
        }

        // 3️⃣ Cargar los datos del pedido del cliente (si existen)
        $customerOrder = $orderModel->getCustomerOrderBySystemId($systemOrder['id']);

        // 4️⃣ Preparar datos para la vista
        $formData = [
            'user_id'             => $userId,
            'user_fullname'       => $userFullname,
            'system_order_id'     => $systemOrder['id'],
            'system_order_number' => $systemOrder['system_order_series'] . '-' . str_pad($systemOrder['system_number'], 3, '0', STR_PAD_LEFT),
            'system_order_date'   => $systemOrder['system_number_date'],

            // Datos del pedido del cliente
            'customer_order_id'   => $customerOrder['id'] ?? null,
            'order_seller_series' => $customerOrder['order_seller_series'] ?? '',
            'order_seller_number' => $customerOrder['order_seller_number'] ?? '',
            'id_customer'         => $customerOrder['id_customer'] ?? null,
            'business_name'       => $customerOrder['business_name'] ?? null,
            'purchase_order'      => $customerOrder['purchase_order'] ?? '',
            'name_order'          => $customerOrder['name_order'] ?? '',
            'attn'                => $customerOrder['attn'] ?? '',
            'comments'            => $customerOrder['comments'] ?? '',
            'id_uso_cfdi'         => $customerOrder['id_uso_cfdi'] ?? null,
            'order_pdf_path'      => $customerOrder['order_pdf_path'] ?? '',
            'order_pdf_original'  => $customerOrder['order_pdf_original'] ?? '',
            'order_pdf_uploaded_at' => $customerOrder['order_pdf_uploaded_at'] ?? '',
        ];

        // 5️⃣ Cargar vista
        $view = 'forms/create_order';
        require __DIR__ . '/../views/layouts/app.php';
    }





    public function autoSaveCustomer()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $orderId = $_POST['system_id'] ?? null;
        $userId = $_SESSION['user']['id'] ?? null;

        header('Content-Type: application/json');

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID del pedido']);
            return;
        }

        require_once __DIR__ . '/../models/Order.php';
        require_once __DIR__ . '/../models/StatusManager.php'; // ✅ para actualización en cascada
        $orderModel = new Order();

        $data = [
            'id_customer' => $_POST['id_customer'] ?? null,
            'name_order' => $_POST['name_order'] ?? '',
            'purchase_order' => $_POST['purchase_order'] ?? '',
            'attn' => $_POST['attn'] ?? '',
            'comments' => $_POST['comments'] ?? '',
            'id_uso_cfdi'   => $_POST['id_uso_cfdi'] ?? null
        ];

        try {
            $saved = $orderModel->saveCustomerOrderAndMarkPending($orderId, $data, $userId);

            if ($saved) {
                // 🔹 Recuperar los datos completos del pedido recién guardado
                $customerOrder = $orderModel->getCustomerOrderBySystemId($orderId);

                // ==========================================================
                // 🔄 ACTUALIZAR ESTATUS JERÁRQUICO (customer → system)
                // ==========================================================
                if (!empty($customerOrder['id'])) {
                    $db = $orderModel->getConnection();
                    $statusManager = new StatusManager($db);
                    $statusManager->updateCustomerOrderStatus($customerOrder['id']);
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Datos del pedido guardados correctamente',
                    'data' => [
                        'customer_order_id' => $customerOrder['id'] ?? null,
                        'order_seller_series' => $customerOrder['order_seller_series'] ?? '',
                        'order_seller_number' => $customerOrder['order_seller_number'] ?? ''
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error desconocido en modelo']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }







    public function searchCustomer()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $term = $_GET['q'] ?? '';
        $userId = $_SESSION['user']['id'] ?? 0;

        header('Content-Type: application/json');

        if (!$term || !$userId) {
            echo json_encode([]);
            return;
        }

        require_once __DIR__ . '/../models/Customer.php';
        $customerModel = new Customer();
        $results = $customerModel->searchCustomers($term, $userId);

        echo json_encode($results);
    }


    public function createCustomerController()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Usuario no logueado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $businessName = $_POST['business_name'] ?? '';
        $companyName = $_POST['company_name'] ?? '';

        if (!$businessName) {
            echo json_encode(['success' => false, 'message' => 'Falta el business_name']);
            exit;
        }

        require_once __DIR__ . '/../models/Customer.php';
        $orderModel = new Customer();
        $customerId = $orderModel->createCustomer($businessName, $companyName, $userId);

        if ($customerId) {
            echo json_encode(['success' => true, 'id' => $customerId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el cliente']);
        }
    }

    public function deleteTemporaryOrder()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user']['id'] ?? null;
        $systemId = $_POST['system_id'] ?? null;

        if (!$userId || !$systemId) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos']);
            return;
        }

        $orderModel = new Order();

        // Reutilizamos el método que ya tienes
        $deleted = $orderModel->cleanupEmptyTemporaryOrders($userId);

        if ($deleted > 0) {
            echo json_encode(['success' => true, 'message' => 'Orden temporal eliminada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se eliminó ninguna orden (ya tenía datos o no era temporal)']);
        }
    }


    public function getSupplierOrderTypes()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            // Validar sesión
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
                return;
            }

            // Cargar modelo de órdenes
            require_once __DIR__ . '/../models/Order.php';
            $orderModel = new Order();

            // Obtener tipos desde el modelo
            $types = $orderModel->getSupplierOrderTypes();

            echo json_encode([
                'success' => true,
                'data' => $types
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener tipos de pedido: ' . $e->getMessage()
            ]);
        }
    }

    public function getUsosCfdi()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            require_once __DIR__ . '/../models/UsoCfdi.php';
            $usoCfdiModel = new UsoCfdi();

            $usos = $usoCfdiModel->getAll();

            echo json_encode([
                'success' => true,
                'data' => $usos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }




    public function uploadPurchaseOrder()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user']['id'] ?? null;
        $systemOrderId = $_POST['system_order_id'] ?? null;

        if (!$userId || !$systemOrderId) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            return;
        }

        if (!isset($_FILES['order_pdf']) || $_FILES['order_pdf']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Archivo PDF no recibido correctamente.']);
            return;
        }

        require_once __DIR__ . '/../models/Order.php';
        $orderModel = new Order();

        try {
            // 📅 Rutas por usuario y fecha
            $year  = date('Y');
            $month = date('m');
            $day   = date('d');

            // 📦 Carpeta exclusiva por orden (evita acumulaciones)
            $uploadDir = __DIR__ . "/../../storage/uploads/orders/user_{$userId}/{$year}/{$month}/{$day}/order_{$systemOrderId}";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // 🧹 Limpiar archivos viejos dentro de esa carpeta
            foreach (glob("{$uploadDir}/*") as $oldFile) {
                if (is_file($oldFile)) unlink($oldFile);
            }

            // 📄 Preparar nombres
            $originalName = $_FILES['order_pdf']['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                throw new Exception('Solo se permiten archivos PDF.');
            }

            $newName = "PO_{$systemOrderId}.pdf";
            $targetPath = "{$uploadDir}/{$newName}";

            // 📤 Mover archivo subido
            if (!move_uploaded_file($_FILES['order_pdf']['tmp_name'], $targetPath)) {
                throw new Exception('Error al guardar el archivo en el servidor.');
            }

            // 🧩 Ruta relativa para guardar en BD
            $relativePath = "storage/uploads/orders/user_{$userId}/{$year}/{$month}/{$day}/order_{$systemOrderId}/{$newName}";

            // 🗃️ Actualizar base de datos
            $orderModel->setCustomerOrderPdf($systemOrderId, $relativePath, $originalName);

            echo json_encode([
                'success' => true,
                'message' => 'Archivo subido correctamente.',
                'path'    => $relativePath,
                'name'    => $originalName
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    public function downloadPurchaseOrder()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        $orderId = $_GET['system_order_id'] ?? $_GET['id'] ?? null;

        if (!$userId || !$orderId) {
            http_response_code(403);
            echo "Acceso no autorizado.";
            return;
        }

        require_once __DIR__ . '/../models/Order.php';
        require_once __DIR__ . '/../models/User.php';

        $orderModel = new Order();
        $userModel  = new User();

        // Consultar directamente el customer_order por su ID principal
        $db = $orderModel->getConnection();
        $stmt = $db->prepare("SELECT * FROM customer_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $customerOrder = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customerOrder || empty($customerOrder['order_pdf_path'])) {
            http_response_code(404);
            echo "Archivo no encontrado.";
            return;
        }

        // 🔐 Validar jerarquía O roles/puestos globales permitidos (Logística, Dirección, Tesorería, Admins)
        $userPositionId = $_SESSION['user']['id_position'] ?? null;
        $userDepartmentId = $_SESSION['user']['department_id'] ?? null;

        // IDs de posición y departamento autorizados para ver todos los archivos
        $allowedPositions = [1, 4, 7, 11, 12]; // Ajusta el 12 al id_position exacto de tu operador de logística
        $allowedDepartments = [1, 4, 9];       // Dirección (1), Administración (4), Logística (9)

        $isGlobalAuditor = in_array($userPositionId, $allowedPositions) || in_array($userDepartmentId, $allowedDepartments);

        if (!$isGlobalAuditor) {
            $allowedIds = $userModel->getTeamUserIds($userId);
            if (!in_array($customerOrder['created_by'], $allowedIds)) {
                http_response_code(403);
                echo "No tienes permisos para acceder a este archivo.";
                return;
            }
        }

        // 📂 Ruta absoluta segura
        $absolutePath = __DIR__ . '/../../' . $customerOrder['order_pdf_path'];
        if (!file_exists($absolutePath)) {
            http_response_code(404);
            echo "El archivo no existe en el servidor.";
            return;
        }

        // 📄 Forzar vista inline del PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($customerOrder['order_pdf_original'] ?? 'orden_compra.pdf') . '"');
        header('Content-Length: ' . filesize($absolutePath));
        readfile($absolutePath);
        exit;
    }


}
