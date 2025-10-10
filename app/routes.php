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

    'app_orders' => [
    'controller' => 'OrdersController',
    'method' => 'myOrders',
    'public' => false
],

    // Aquí puedes agregar 50, 100 o 200 rutas sin tocar el router
];
