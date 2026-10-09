<?php

/**
 * Controlador de Requisiciones Internas (Promesas de Compra)
 * Ruta: app/controllers/RequisitionsController.php
 */
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Quotation.php';
require_once __DIR__ . '/../models/Requisition.php';

class RequisitionsController
{
    private $requisitionModel;

    public function __construct()
    {
        $this->requisitionModel = new Requisition();
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $userModel = new User();

        // Validar si el usuario actual es de compras o admin global
        $userDept = $_SESSION['user']['department_id'] ?? null;
        $userPosition = $_SESSION['user']['id_position'] ?? null;
        $isPurchasingOrAdmin = ($userDept == 2 || in_array($userPosition, [1, 7])); // Ajusta según tus IDs de puestos/depto

        // Obtener IDs de usuarios del equipo (jerarquía comercial)
        $teamIds = $userModel->getTeamUserIds($userId);

        // Obtener requisiciones filtradas
        $requisitions = $this->requisitionModel->getRequisitionsByUsers($teamIds, $isPurchasingOrAdmin);

        $viewData = [
            'is_purchasing' => $isPurchasingOrAdmin
        ];

        // Definir la vista interna
        $view = 'commercial/requisition_list';
        require __DIR__ . '/../views/layouts/app.php';
    }
    /**
     * Muestra la vista limpia del Formulario para convertir cotización a Requisición.
     * Ruta: index.php?page=convert_quotation_view&id=X
     */
    public function convertQuotationToRequisitionForm()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        $quotationId = $_GET['id'] ?? null;

        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        if (!$quotationId) {
            header('Location: index.php?page=my_quotations&msg=missing_quotation_id');
            exit;
        }

        $requisitionModel = new Requisition();

        // 🛑 VALIDACIÓN: Verificar si esta cotización ya cuenta con una requisición previa
        if ($requisitionModel->quotationHasRequisition((int)$quotationId)) {
            header('Location: index.php?page=my_quotations&msg=quotation_already_requisitioned');
            exit;
        }

        $quotationModelParent = new Quotation();
        $quotation = $quotationModelParent->getQuotationById((int)$quotationId);

        if (!$quotation) {
            header('Location: index.php?page=my_quotations&msg=quotation_not_found');
            exit;
        }

        if ($quotation['status'] !== 'Enviada') {
            header('Location: index.php?page=my_quotations&msg=invalid_status_for_conversion');
            exit;
        }

        $items = $quotationModelParent->getItemsByQuotationId((int)$quotationId);

        $formData = [
            'user_id'       => $userId,
            'quotation_id'  => $quotationId
        ];

        $view = 'forms/convert_quotation_to_requisition';
        require __DIR__ . '/../views/layouts/app.php';
    }

    // (Opcional por compatibilidad si tu enrutador usa ambos nombres)
    public function convertQuotationForm()
    {
        return $this->convertQuotationToRequisitionForm();
    }

   /**
     * Procesar y almacenar la Requisición en la base de datos
     * Ruta POST: index.php?page=store_requisition
     */
    public function storeRequisition()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = $_POST;
            $itemsData = $_POST['items'] ?? [];

            // 🧹 SANITIZACIÓN DE PARTIDAS: Asegurar tipos de datos correctos
            foreach ($itemsData as &$item) {
                $item['delivery_time_days'] = isset($item['delivery_time_days']) ? max(0, (int)$item['delivery_time_days']) : 0;
                $item['freight_cost'] = isset($item['freight_cost']) ? max(0.00, (float)$item['freight_cost']) : 0.00;
                $item['purchase_unit_cost'] = isset($item['purchase_unit_cost']) ? max(0.00, (float)$item['purchase_unit_cost']) : 0.00;
            }
            unset($item);

            // Definir estatus según el botón presionado
            $action = $_POST['action'] ?? 'draft';
            $postData['status'] = ($action === 'emit') ? 'Enviada' : 'Borrador';

            $requisitionModel = new Requisition();
            $requisitionId = $requisitionModel->storeRequisition($userId, $postData, $itemsData);

            if ($requisitionId > 0) {
                header("Location: index.php?page=my_requisitions&msg=requisition_success");
                exit;
            } else {
                header("Location: index.php?page=my_quotations&msg=requisition_error");
                exit;
            }
        }
    }

    /**
     * Muestra el detalle o lectura de una requisición específica
     * Ruta: index.php?page=view_requisition_detail&id=X
     */
    public function viewDetail()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $requisitionId = $_GET['id'] ?? 0;
        if (!$requisitionId) {
            header('Location: index.php?page=my_requisitions');
            exit;
        }

        $requisitionModel = new Requisition();
        
        // Obtener cabecera de la requisición con datos del cliente y vendedor
        $requisition = $requisitionModel->getRequisitionById((int)$requisitionId);
        
        if (!$requisition) {
            header('Location: index.php?page=my_requisitions&msg=not_found');
            exit;
        }

        // Obtener las partidas asociadas a la requisición
        $items = $requisitionModel->getItemsByRequisitionId((int)$requisitionId);

        $view = 'forms/view_requisition_detail';
        require __DIR__ . '/../views/layouts/app.php';
    }
    
    public function updateRequisition()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $requisitionId = $_GET['id'] ?? null;
        if (!$requisitionId) {
            header('Location: index.php?page=my_requisitions&msg=missing_id');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = $_POST;
            $itemsData = $_POST['items'] ?? [];

            foreach ($itemsData as &$item) {
                $item['delivery_time_days'] = isset($item['delivery_time_days']) ? max(0, (int)$item['delivery_time_days']) : 0;
                $item['freight_cost'] = isset($item['freight_cost']) ? max(0.00, (float)$item['freight_cost']) : 0.00;
                $item['purchase_unit_cost'] = isset($item['purchase_unit_cost']) ? max(0.00, (float)$item['purchase_unit_cost']) : 0.00;
            }
            unset($item);

            $action = $_POST['action'] ?? 'draft';
            $postData['status'] = ($action === 'emit') ? 'Enviada' : 'Borrador';

            $requisitionModel = new Requisition();
            
            // 👈 AQUÍ PASAMOS EL $userId COMO ÚLTIMO PARÁMETRO
            $success = $requisitionModel->updateRequisition((int)$requisitionId, $postData, $itemsData, (int)$userId);

            if ($success) {
                header("Location: index.php?page=my_requisitions&msg=requisition_update_success");
                exit;
            } else {
                header("Location: index.php?page=my_requisitions&msg=requisition_update_error");
                exit;
            }
        }
    }
}
