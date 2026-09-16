<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Prospect.php';
require_once __DIR__ . '/../models/AuditModel.php';

class ProspectsController
{
    private $auditModel;

    public function __construct()
    {
        $this->auditModel = new AuditModel();
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $prospects = [];

        try {
            $userModel = new User();
            $prospectModel = new Prospect();

            $teamIds = $userModel->getTeamUserIds($userId);
            $prospects = $prospectModel->getProspectsByUsers($teamIds);

        } catch (Exception $e) {
            $prospects = [];
        }

        $view = 'commercial/prospects_list';
        require __DIR__ . '/../views/layouts/app.php';
    }

    /**
     * Endpoint AJAX para guardar interacciones en la bitácora con Auditoría
     */
    public function saveInteraction()
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

        $prospectId = $_POST['prospect_id'] ?? null;
        if (!$prospectId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID del prospecto']);
            exit;
        }

        $prospectModel = new Prospect();
        $result = $prospectModel->addInteraction($_POST, $userId);

        if ($result['success']) {
            $lastId = $prospectModel->getConnection()->lastInsertId();
            $this->auditModel->log(
                (int)$userId,
                'INTERACTION_CREATE',
                'prospect_interactions',
                (int)$lastId,
                null,
                $_POST
            );
        }

        echo json_encode($result);
    }

    /**
     * Endpoint AJAX para actualizar interacciones existentes (Correcciones) con Auditoría
     */
    public function updateInteraction()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit;
        }

        $interactionId = $_POST['interaction_id'] ?? null;
        if (!$interactionId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID de la interacción']);
            exit;
        }

        $prospectModel = new Prospect();
        $oldData = $prospectModel->getInteractionById((int)$interactionId);

        $result = $prospectModel->updateInteraction((int)$interactionId, $_POST);

        if ($result['success']) {
            $this->auditModel->log(
                (int)$userId,
                'INTERACTION_UPDATE',
                'prospect_interactions',
                (int)$interactionId,
                $oldData,
                $_POST
            );
        }

        echo json_encode($result);
    }

    /**
     * Endpoint AJAX para obtener el historial de un prospecto y pintarlo en el modal
     */
    public function getInteractions()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $prospectId = $_GET['prospect_id'] ?? null;
        if (!$prospectId) {
            echo json_encode(['success' => false, 'data' => []]);
            exit;
        }

        $prospectModel = new Prospect();
        $interactions = $prospectModel->getInteractionsByProspect((int)$prospectId);

        echo json_encode(['success' => true, 'data' => $interactions]);
        exit;
    }

    /**
     * Guardar un nuevo prospecto con validación anti-duplicados y asignación de dueño
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

        $companyName = trim($_POST['company_name'] ?? '');
        if (!$companyName) {
            echo json_encode(['success' => false, 'message' => 'El nombre de la empresa es obligatorio']);
            exit;
        }

        $userModel = new User();
        $prospectModel = new Prospect();

        // 🔍 VALIDACIÓN ANTI-DUPLICADOS POR NOMBRE DE EMPRESA
        $existing = $prospectModel->findDuplicateCompany($companyName);
        if ($existing) {
            $ownerName = trim($existing['firstname'] . ' ' . $existing['apellidos']);
            echo json_encode([
                'success' => false, 
                'message' => "⚠️ La empresa '{$existing['company_name']}' ya está registrada en el sistema por el vendedor {$ownerName} (Registrado el: {$existing['created_at']}). Evita duplicar cuentas."
            ]);
            exit;
        }

        // 🏢 Jerarquía: Obtener el dueño oficial de la cuenta (Jefe/Vendedor) o usar el propio si no tiene superior
        $managerId = $userModel->getManagerId($userId) ?? $userId;

        // Guardar asignando el prospect_owner (dueño) y el registered_by (quien lo tecleó)
        $created = $prospectModel->createProspect($_POST, (int)$managerId, (int)$userId);

        if ($created) {
            $lastId = $prospectModel->getConnection()->lastInsertId();
            $this->auditModel->log(
                (int)$userId,
                'PROSPECT_CREATE',
                'prospects',
                (int)$lastId,
                null,
                $_POST
            );

            echo json_encode(['success' => true, 'message' => 'Prospecto registrado exitosamente y asignado a la cartera del equipo.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el prospecto en la base de datos']);
        }
    }

    /**
     * Actualizar un prospecto existente (Correcciones) con Auditoría
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

        $prospectId = $_POST['prospect_id'] ?? null;
        if (!$prospectId) {
            echo json_encode(['success' => false, 'message' => 'Falta el ID del prospecto']);
            exit;
        }

        $prospectModel = new Prospect();
        $oldData = $prospectModel->getProspectById((int)$prospectId);

        $updated = $prospectModel->updateProspect((int)$prospectId, $_POST);

        if ($updated) {
            $this->auditModel->log(
                (int)$userId,
                'PROSPECT_UPDATE',
                'prospects',
                (int)$prospectId,
                $oldData,
                $_POST
            );

            echo json_encode(['success' => true, 'message' => 'Prospecto actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el prospecto en la base de datos']);
        }
    }

    /**
     * Endpoint AJAX para verificar si una empresa ya existe (Validación en tiempo real)
     */
    public function checkDuplicate()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        $companyName = trim($_GET['company_name'] ?? '');
        if (!$companyName) {
            echo json_encode(['exists' => false]);
            exit;
        }

        $prospectModel = new Prospect();
        $existing = $prospectModel->findDuplicateCompany($companyName);

        if ($existing) {
            $ownerName = trim($existing['firstname'] . ' ' . $existing['apellidos']);
            echo json_encode([
                'exists' => true,
                'message' => "⚠️ La empresa '{$existing['company_name']}' ya está registrada por el vendedor **{$ownerName}** (Registrado el: {$existing['created_at']})."
            ]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit;
    }
}