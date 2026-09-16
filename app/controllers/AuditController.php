<?php
require_once __DIR__ . '/../models/AuditModel.php';

class AuditController
{
    private $auditModel;

    public function __construct()
    {
        $this->auditModel = new AuditModel();
    }

    public function index()
    {
        $logs = $this->auditModel->getLogs(150);
        $view = 'system/audit_logs';
        require __DIR__ . '/../views/layouts/app.php';
    }
}