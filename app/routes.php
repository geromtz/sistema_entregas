<?php
// app/routes.php

return [
    // Public pages
    'login' => [
        'controller' => 'LoginController',
        'method' => 'index',
        'public' => true
    ],
    'login_auth' => [
        'controller' => 'LoginController',
        'method' => 'login',
        'public' => true
    ],

    // Private pages
    'app' => [
        'controller' => 'AppController',
        'method' => 'index',
        'public' => false
    ],

    'profile' => [
        'controller' => 'ProfileController',
        'method' => 'index',
        'public' => false
    ],
    'profile_edit' => [
        'controller' => 'ProfileController',
        'method' => 'edit',
        'public' => false
    ],

    // Dashboard si lo quieres por separado
    'dashboard' => [
        'controller' => 'DashboardController',
        'method' => 'index',
        'public' => false
    ],


    'my_system_orders' => [
        'controller' => 'OrdersController',
        'method' => 'myOrders',
        'public' => false
    ],

    'create_order' => [
        'controller' => 'OrdersController',
        'method' => 'createOrder',
        'public' => false
    ],

    'search_customer' => [
        'controller' => 'OrdersController',
        'method' => 'searchCustomer',
        'public' => false
    ],

    'create_customer' => [
        'controller' => 'OrdersController',
        'method' => 'createCustomerController',
        'public' => false
    ],

    'auto_save_customer' => [
        'controller' => 'OrdersController',
        'method' => 'autoSaveCustomer',
        'public' => false
    ],

    'delete_temporary_order' => [
        'controller' => 'OrdersController',
        'method' => 'deleteTemporaryOrder',
        'public' => false
    ],

    'get_supplier_order_types' => [
        'controller' => 'OrdersController',
        'method' => 'getSupplierOrderTypes',
        'public' => false
    ],

    'save_single_supplier_order' => [
        'controller' => 'SupplierOrdersController',
        'method' => 'saveSingleSupplierOrder',
        'public' => false
    ],

    'get_supplier_orders' => [
        'controller' => 'SupplierOrdersController',
        'method' => 'getSupplierOrders',
        'public' => false
    ],

    'get_supplier_items' => [
        'controller' => 'SupplierOrdersController',
        'method' => 'getSupplierItems',
        'public' => false
    ],

    'cancel_supplier_order' => [
        'controller' => 'SupplierOrdersController',
        'method'     => 'cancelSupplierOrder',
        'public'     => false
    ],

    'suppliers_list' => [
        'controller' => 'SuppliersController',
        'method' => 'getSuppliers',
        'public' => false
    ],

    'create_supplier' => [
        'controller' => 'SuppliersController',
        'method' => 'createSupplier',
        'public' => false
    ],

    'update_supplier_order' => [
        'controller' => 'SupplierOrdersController',
        'method' => 'updateSupplierOrder',
        'public' => false
    ],

    'update_supplier_item' => [
        'controller' => 'SupplierOrdersController',
        'method' => 'updateSupplierItem',
        'public' => false
    ],

    'supplier_branches_list' => [
        'controller' => 'SuppliersController',
        'method'     => 'getBranchesBySupplier',
        'public'     => false
    ],

    'create_supplier_branch' => [
        'controller' => 'SuppliersController',
        'method'     => 'createBranch',
        'public'     => false
    ],

    'get_usos_cfdi' => [
        'controller' => 'OrdersController',
        'method' => 'getUsosCfdi',
        'public' => false
    ],

    'upload_purchase_order' => [
        'controller' => 'OrdersController',
        'method' => 'uploadPurchaseOrder',
        'public' => false
    ],

    'download_purchase_order' => [
        'controller' => 'OrdersController',
        'method' => 'downloadPurchaseOrder',
        'public' => false
    ],

    // ==========================================================
    // TRACKING DE PEDIDOS PROVEEDOR (seguimiento)
    // ==========================================================
    'get_tracking_by_item' => [
        'controller' => 'TrackingSupplierOrdersController',
        'method' => 'getTrackingByItem',
        'public' => false
    ],

    'update_tracking' => [
        'controller' => 'TrackingSupplierOrdersController',
        'method' => 'updateTracking',
        'public' => false
    ],

    'logout' => [
        'controller' => 'LoginController',
        'method' => 'logout',
        'public' => false
    ],


    'api_get_items_by_order' => [
        'controller' => 'SupplierItemsController',
        'method' => 'getItemsByOrder',
        'public' => false
    ],


    'update_item_status' => [
        'controller' => 'SupplierItemsController',
        'method' => 'updateItemStatus',
        'public' => false
    ],


    'other_trips' => [
        'controller' => 'OtherTripsController',
        'method' => 'index',
        'public' => false
    ],

    'api_other_trips_store' => [
        'controller' => 'OtherTripsController',
        'method' => 'store',
        'public' => false
    ],

    'api_other_trips_update' => [
        'controller' => 'OtherTripsController',
        'method' => 'update',
        'public' => false
    ],

    // ==========================================================
    // LOGÍSTICA
    // ==========================================================

    'my_pickups' => [
        'controller' => 'LogisticController',
        'method' => 'myPickups',
        'public' => false
    ],

    'my_deliverys' => [
        'controller' => 'LogisticController',
        'method' => 'myDeliverys',
        'public' => false
    ],

    'mark_pickup' => [
        'controller' => 'LogisticController',
        'method' => 'markPickup',
        'public' => false
    ],

    'mark_delivery' => [
        'controller' => 'LogisticController',
        'method' => 'markDelivery',
        'public' => false
    ],

    'main_logistic' => [
        'controller' => 'LogisticController',
        'method' => 'mainLogistic',
        'public' => false
    ],

    'api_get_stage_data' => [
        'controller' => 'MainLogisticController',
        'method' => 'apiGetStageData',
        'public' => false
    ],

    'save_incident_ajax' => [
        'controller' => 'LogisticController',
        'method' => 'saveIncidentAjax',
        'public' => false
    ],

    'assign_driver' => [
        'controller' => 'LogisticController',
        'method' => 'assignDriver', // Este es el método que creamos en el controlador
        'public' => false
    ],

    'unassign_driver' => [
        'controller' => 'LogisticController',
        'method' => 'unassignDriver',
        'public' => false
    ],

    'search_products' => [
        'controller' => 'SupplierItemsController',
        'method' => 'searchProducts',
        'public' => false
    ],
    'create_quick_product' => [
        'controller' => 'ProductsController',
        'method'     => 'createQuickProduct',
        'public'     => false
    ],
    'logistic_pickups' => [
        'controller' => 'LogisticController',
        'method' => 'logisticPickups', // O el método que uses para las recolecciones de logística
        'public' => false
    ],

    'warehouse_reception' => [
        'controller' => 'WarehouseController',
        'method' => 'index',
        'public' => false
    ],
    'process_warehouse_entry' => [
        'controller' => 'WarehouseController',
        'method' => 'storeEntry',
        'public' => false
    ],

    'delivery' => [
        'controller' => 'LogisticController',
        'method' => 'logisticDeliveries', // O el nombre del método que prefieras en tu controlador
        'public' => false
    ],
    'management_users' => [
        'controller' => 'AdminUsersController',
        'method' => 'index',
        'public' => false
    ],
    'menu_editor' => [
        'controller' => 'MenuEditorController',
        'method' => 'index',
        'public' => false
    ],
    'audit_logs' => [
        'controller' => 'AuditController',
        'method' => 'index',
        'public' => false
    ],

    'stock_audit' => [
        'controller' => 'WarehouseController',
        'method' => 'stockAudit',
        'public' => false
    ],

    // CLIENTES

    'my_customers' => [
        'controller' => 'CustomerController',
        'method' => 'listCustomers',
        'public' => false
    ],

    'update_customer_full' => [
        'controller' => 'CustomerController',
        'method' => 'updateCustomerFull',
        'public' => false
    ],
    'get_customer_addresses' => [
        'controller' => 'CustomerController',
        'method' => 'getCustomerAddresses',
        'public' => false
    ],

    'save_customer_address' => [
        'controller' => 'CustomerController',
        'method' => 'saveCustomerAddress',
        'public' => false
    ],
    'delete_customer_address' => [
        'controller' => 'CustomerController',
        'method' => 'deleteCustomerAddress',
        'public' => false
    ],

    // ==========================================
    // MÓDULO DE PROSPECCIÓN Y CRM
    // ==========================================

    'my_prospects' => [
        'controller' => 'ProspectsController',
        'method'     => 'index',
        'public'     => false
    ],
    'store_prospect' => [
        'controller' => 'ProspectsController',
        'method'     => 'store',
        'public'     => false
    ],
    'update_prospect' => [
        'controller' => 'ProspectsController',
        'method'     => 'update',
        'public'     => false
    ],
    'save_interaction' => [
        'controller' => 'ProspectsController',
        'method'     => 'saveInteraction',
        'public'     => false
    ],
    'update_interaction' => [
        'controller' => 'ProspectsController',
        'method'     => 'updateInteraction',
        'public'     => false
    ],
    'get_interactions' => [
        'controller' => 'ProspectsController',
        'method'     => 'getInteractions',
        'public'     => false
    ],
    'check_duplicate_prospect' => [
        'controller' => 'ProspectsController',
        'method'     => 'checkDuplicate',
        'public'     => false
    ],

    // ================= COTIZACIONES =================
    'my_quotations' => [
        'controller' => 'QuotationsController',
        'method'     => 'index',       
        'public'     => false
    ],
    'create_quotation' => [
        'controller' => 'QuotationsController',
        'method'     => 'create',      
        'public'     => false
    ],
    'store_quotation' => [
        'controller' => 'QuotationsController',
        'method'     => 'store',       
        'public'     => false
    ],
    'get_customer_contacts' => [
        'controller' => 'QuotationsController',
        'method'     => 'getCustomerContacts',       
        'public'     => false
    ],
    'search_prospect' => [
        'controller' => 'QuotationsController',
        'method'     => 'searchProspect',       
        'public'     => false
    ],
    'store_quick_contact' => [
        'controller' => 'QuotationsController',
        'method'     => 'storeQuickContact',       
        'public'     => false
    ],
    'print_quotation' => [
        'controller' => 'QuotationsController',
        'method'     => 'printPdf',
        'public'     => false
    ],    
    'preview_quotation_html' => [
        'controller' => 'QuotationsController',
        'method'     => 'previewHtml',      
        'public'     => false
    ],

    
];
