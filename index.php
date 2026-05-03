<?php
/**
 * Chicken Deluxe — Entry Point / Front Controller
 * All requests are routed through this file
 */

// Load constants and core classes
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Router.php';

// Initialize session
Auth::init();

// Set up routes
$router = new Router();

// -- Auth routes --
$router->get('/login',    'AuthController', 'showLogin');
$router->post('/login',   'AuthController', 'handleLogin');
$router->get('/logout',   'AuthController', 'logout');

// -- Dashboard --
$router->get('/',          'DashboardController', 'index');
$router->get('/dashboard', 'DashboardController', 'index');

// -- Admin routes (Owner only) --
$router->get('/admin',                'AdminController', 'users');
$router->get('/admin/users',          'AdminController', 'users');
$router->post('/admin/users/create',  'AdminController', 'createUser');
$router->get('/admin/users/edit',     'AdminController', 'editUser');
$router->post('/admin/users/update',  'AdminController', 'updateUser');
$router->post('/admin/users/reset-password', 'AdminController', 'resetUserPassword');
$router->post('/admin/users/deactivate', 'AdminController', 'deactivateUser');
$router->post('/admin/users/activate',   'AdminController', 'activateUser');
$router->get('/admin/products',          'AdminController', 'products');
$router->post('/admin/products/create',  'AdminController', 'createProduct');
$router->post('/admin/products/update',  'AdminController', 'updateProduct');
$router->post('/admin/parts/create',      'AdminController', 'createPart');
$router->post('/admin/parts/update',      'AdminController', 'updatePart');
$router->post('/admin/parts/save-recipe', 'AdminController', 'saveRecipe');
$router->get('/admin/kiosks',            'AdminController', 'kiosks');
$router->post('/admin/kiosks/create',    'AdminController', 'createKiosk');
$router->post('/admin/kiosks/update',    'AdminController', 'updateKiosk');
$router->get('/admin/audit-log',         'AdminController', 'auditLog');

// -- Inventory routes --
$router->get('/inventory',        'InventoryController', 'index');
$router->post('/inventory/store', 'InventoryController', 'store');
$router->post('/inventory/auto-generate', 'InventoryController', 'autoGenerateBeginning');
$router->post('/inventory/unlock', 'InventoryController', 'unlock');
$router->post('/inventory/lock',    'InventoryController', 'lock');

// -- Delivery routes --
$router->get('/delivery',          'DeliveryController', 'index');
$router->post('/delivery/store',   'DeliveryController', 'store');
$router->post('/delivery/lock',    'DeliveryController', 'lock');
$router->post('/delivery/unlock',  'DeliveryController', 'unlock');
$router->post('/delivery/delete',  'DeliveryController', 'delete');
$router->post('/delivery/update',   'DeliveryController', 'update');
$router->post('/delivery/pullout',  'DeliveryController', 'pullout');

// -- Sales routes --
$router->get('/sales',          'SalesController', 'index');
$router->post('/sales/store',   'SalesController', 'store');
$router->post('/sales/store-batch', 'SalesController', 'storeBatch');
$router->post('/sales/lock',    'SalesController', 'lock');
$router->post('/sales/unlock',  'SalesController', 'unlock');
$router->post('/sales/delete',  'SalesController', 'delete');

// -- Expenses routes --
$router->get('/expenses',          'ExpenseController', 'index');
$router->post('/expenses/store',   'ExpenseController', 'store');
$router->post('/expenses/lock',    'ExpenseController', 'lock');
$router->post('/expenses/unlock',  'ExpenseController', 'unlock');
$router->post('/expenses/delete',  'ExpenseController', 'delete');
$router->post('/expenses/update',  'ExpenseController', 'update');

// -- Reports routes --
$router->get('/reports',              'ReportController', 'index');
$router->get('/reports/daily',        'ReportController', 'daily');
$router->get('/reports/consolidated', 'ReportController', 'consolidated');
$router->get('/reports/timein',       'ReportController', 'timeIn');

// Dispatch the request
$router->dispatch();
