<?php
require_once __DIR__ . '/../../config/db.php';

class Requisition
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Obtener los datos de la cotización y sus partidas para la requisición
     */
    public function getQuotationDetailsForRequisition(int $quotationId): ?array
    {
        try {
            // 1. Datos generales de la cotización
            $stmt = $this->db->prepare("
                SELECT q.*, c.business_name AS customer_name 
                FROM quotations q
                LEFT JOIN customers c ON c.id = q.customer_id
                WHERE q.id = :quotationId 
                LIMIT 1
            ");
            $stmt->execute([':quotationId' => $quotationId]);
            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$quotation) return null;

            // 2. Partidas de la cotización con su proveedor y sucursal sugerida
            $stmtItems = $this->db->prepare("
                SELECT 
                    qi.*, 
                    p.part_number AS product_part_number, 
                    p.description AS product_description,
                    s.business_name AS supplier_name
                FROM quotation_items qi
                LEFT JOIN products p ON p.id = qi.product_id
                LEFT JOIN suppliers s ON s.id = qi.id_supplier
                WHERE qi.quotation_id = :quotationId
            ");
            $stmtItems->execute([':quotationId' => $quotationId]);
            $quotation['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            return $quotation;
        } catch (PDOException $e) {
            error_log("Error fetching quotation for requisition: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Guardar la Requisition y sus partidas fraccionadas/tácticas
     */
    public function storeRequisition(int $userId, array $postData, array $itemsData): int
    {
        try {
            $this->db->beginTransaction();

            $quotationId = $postData['quotation_id'] ?? null;
            $customerId = $postData['customer_id'] ?? null;
            $title = $postData['requisition_title'] ?? 'Requisición interna';
            $notes = $postData['internal_notes'] ?? '';
            $status = $postData['status'] ?? 'Borrador';

            // 🛑 NUEVO: Capturar si se aceptó la pérdida y quién lo hizo
            $lossAccepted = isset($postData['loss_accepted']) ? (int)$postData['loss_accepted'] : 0;
            $lossAcceptedBy = ($lossAccepted === 1) ? $userId : null;

            // Generar folio interno de requisición (Ej. REQ-0001)
            $stmtFolio = $this->db->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM requisitions");
            $nextId = $stmtFolio->fetchColumn();
            $folio = 'REQ-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            // 1. Insertar cabecera de requisición con los campos de auditoría de pérdida
            $stmtReq = $this->db->prepare("
                INSERT INTO requisitions (
                    quotation_id, customer_id, requisition_folio, requisition_title, 
                    internal_notes, status, loss_accepted, loss_accepted_by, loss_accepted_at, created_by
                )
                VALUES (
                    :quotationId, :customerId, :folio, :title, 
                    :notes, :status, :lossAccepted, :lossAcceptedBy, 
                    " . ($lossAccepted === 1 ? "NOW()" : "NULL") . ", :userId
                )
            ");
            
            $stmtReq->execute([
                ':quotationId'    => $quotationId,
                ':customerId'     => $customerId,
                ':folio'          => $folio,
                ':title'          => $title,
                ':notes'          => $notes,
                ':status'         => $status,
                ':lossAccepted'   => $lossAccepted,
                ':lossAcceptedBy' => $lossAcceptedBy,
                ':userId'         => $userId
            ]);

            $requisitionId = $this->db->lastInsertId();

            // 2. Insertar las partidas (código que ya tienes para items...)
            $stmtItem = $this->db->prepare("
                INSERT INTO requisition_items 
                (requisition_id, quotation_item_id, product_id, part_number, description, quantity, id_supplier, id_branch, purchase_unit_cost, unit_price, delivery_time_days, freight_cost)
                VALUES 
                (:reqId, :quotationItemId, :productId, :partNumber, :description, :quantity, :supplierId, :branchId, :purchaseCost, :unitPrice, :deliveryTime, :freightCost)
            ");

            foreach ($itemsData as $item) {
                $isSelected = isset($item['selected']) ? 1 : 0;
                $quantity = $isSelected ? ($item['quantity'] ?? 0) : 0;

                $stmtItem->execute([
                    ':reqId'             => $requisitionId,
                    ':quotationItemId'   => $item['quotation_item_id'] ?? null,
                    ':productId'         => $item['product_id'],
                    ':partNumber'        => $item['part_number'] ?? '',
                    ':description'       => $item['description'] ?? '',
                    ':quantity'          => $quantity,
                    ':supplierId'        => $item['id_supplier'] ?? null,
                    ':branchId'          => $item['id_branch'] ?? 1,
                    ':purchaseCost'      => $item['purchase_unit_cost'] ?? 0.00,
                    ':unitPrice'         => $item['unit_price'] ?? 0.00,
                    ':deliveryTime'      => $item['delivery_time_days'] ?? 0,
                    ':freightCost'       => $item['freight_cost'] ?? 0.00
                ]);
            }

            $this->db->commit();
            return (int)$requisitionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error storing requisition: " . $e->getMessage());
            return 0;
        }
    }

    public function quotationHasRequisition(int $quotationId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM requisitions WHERE quotation_id = :quotationId LIMIT 1");
            $stmt->execute([':quotationId' => $quotationId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getRequisitionsByUsers(array $userIds, bool $isPurchasingOrAdmin): array
    {
        try {
            $sql = "
            SELECT 
                r.id,
                r.requisition_folio AS folio,
                r.quotation_id,
                q.folio AS quotation_folio,
                c.business_name AS customer_name,
                r.status,
                r.created_at AS quotation_date, -- Usado para el filtro de fechas en la vista
                u.firstname,
                u.apellidos
            FROM requisitions r
            LEFT JOIN quotations q ON q.id = r.quotation_id
            LEFT JOIN customers c ON c.id = r.customer_id
            LEFT JOIN users u ON u.id = r.created_by
        ";

            if ($isPurchasingOrAdmin) {
                // Compras o admin ven todo
                $sql .= " ORDER BY r.created_at DESC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            } else {
                // Vendedor solo ve los de su equipo/jerarquía
                if (empty($userIds)) return [];
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $sql .= " WHERE r.created_by IN ($placeholders) ORDER BY r.created_at DESC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($userIds);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching requisitions list: " . $e->getMessage());
            return [];
        }
    }
    public function getRequisitionById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, c.business_name AS customer_name 
                FROM requisitions r
                LEFT JOIN customers c ON c.id = r.customer_id
                WHERE r.id = :id 
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getItemsByRequisitionId(int $requisitionId): array
    {
        try {
            $stmt = $this->db->prepare("
            SELECT ri.*, s.business_name AS supplier_name 
            FROM requisition_items ri
            LEFT JOIN suppliers s ON s.id = ri.id_supplier
            WHERE ri.requisition_id = :reqId
        ");
            $stmt->execute([':reqId' => $requisitionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Actualizar una requisición en borrador y sus partidas
     */
   // 👈 Añadimos int $userId en los parámetros
    public function updateRequisition(int $requisitionId, array $postData, array $itemsData, int $userId): bool
    {
        try {
            $this->db->beginTransaction();

            $title = $postData['requisition_title'] ?? 'Requisición interna';
            $notes = $postData['internal_notes'] ?? '';
            $status = $postData['status'] ?? 'Borrador';

            // Re-evaluación automática de pérdida:
            $stillHasLoss = false;
            foreach ($itemsData as $item) {
                $isSelected = isset($item['selected']) ? 1 : 0;
                if ($isSelected) {
                    $purchaseCost = (float)($item['purchase_unit_cost'] ?? 0);
                    $unitPrice = (float)($item['unit_price'] ?? 0);
                    
                    if ($purchaseCost > $unitPrice) {
                        $stillHasLoss = true;
                        break;
                    }
                }
            }

            if ($stillHasLoss) {
                $lossAccepted = isset($postData['loss_accepted']) ? (int)$postData['loss_accepted'] : 0;
                $lossAcceptedBy = ($lossAccepted === 1) ? $userId : null; // 👈 Aquí ya reconoce $userId sin problemas
                $lossAcceptedExpr = ($lossAccepted === 1) ? "NOW()" : "NULL";
            } else {
                $lossAccepted = 0;
                $lossAcceptedBy = null;
                $lossAcceptedExpr = "NULL";
            }

            // 1. Actualizar cabecera de la requisición
            $stmtReq = $this->db->prepare("
                UPDATE requisitions 
                SET requisition_title = :title, 
                    internal_notes = :notes, 
                    status = :status,
                    loss_accepted = :lossAccepted,
                    loss_accepted_by = :lossAcceptedBy,
                    loss_accepted_at = {$lossAcceptedExpr}
                WHERE id = :id
            ");
            
            $stmtReq->execute([
                ':title'          => $title,
                ':notes'          => $notes,
                ':status'         => $status,
                ':lossAccepted'   => $lossAccepted,
                ':lossAcceptedBy' => $lossAcceptedBy,
                ':id'             => $requisitionId
            ]);

            // 2. Eliminar partidas anteriores y reinsertar... (resto del código igual)
            $stmtDel = $this->db->prepare("DELETE FROM requisition_items WHERE requisition_id = :reqId");
            $stmtDel->execute([':reqId' => $requisitionId]);

            $stmtItem = $this->db->prepare("
                INSERT INTO requisition_items 
                (requisition_id, quotation_item_id, product_id, part_number, description, quantity, id_supplier, id_branch, purchase_unit_cost, unit_price, delivery_time_days, freight_cost)
                VALUES 
                (:reqId, :quotationItemId, :productId, :partNumber, :description, :quantity, :supplierId, :branchId, :purchaseCost, :unitPrice, :deliveryTime, :freightCost)
            ");

            foreach ($itemsData as $item) {
                $isSelected = isset($item['selected']) ? 1 : 0;
                $quantity = $isSelected ? ($item['quantity'] ?? 0) : 0;

                $stmtItem->execute([
                    ':reqId'             => $requisitionId,
                    ':quotationItemId'   => $item['quotation_item_id'] ?? null,
                    ':productId'         => $item['product_id'],
                    ':partNumber'        => $item['part_number'] ?? '',
                    ':description'       => $item['description'] ?? '',
                    ':quantity'          => $quantity,
                    ':supplierId'        => $item['id_supplier'] ?? null,
                    ':branchId'          => $item['id_branch'] ?? 1,
                    ':purchaseCost'      => $item['purchase_unit_cost'] ?? 0.00,
                    ':unitPrice'         => $item['unit_price'] ?? 0.00,
                    ':deliveryTime'      => $item['delivery_time_days'] ?? 0,
                    ':freightCost'       => $item['freight_cost'] ?? 0.00
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating requisition: " . $e->getMessage());
            return false;
        }
    }
}
