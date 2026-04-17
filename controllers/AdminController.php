<?php
/**
 * AdminController — manage users, products, kiosks, audit log
 * Owner-only access
 */
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/RoleModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/CategoryModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class AdminController extends Controller
{
    private UserModel $userModel;
    private RoleModel $roleModel;
    private KioskModel $kioskModel;
    private ProductModel $productModel;
    private CategoryModel $categoryModel;
    private AuditLogModel $auditLog;

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->roleModel    = new RoleModel();
        $this->kioskModel   = new KioskModel();
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->auditLog     = new AuditLogModel();
    }

    // ============================
    // USERS
    // ============================

    /** Show user management page */
    public function users(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        $data = [
            'page_title' => 'Manage Users',
            'users'      => $this->userModel->getAllActive(),
            'roles'      => $this->roleModel->getAll(),
            'kiosks'     => $this->kioskModel->getActive(),
            'success'    => $_GET['success'] ?? null,
            'error'      => $_GET['error'] ?? null,
        ];

        $this->render('admin/users', $data);
    }

    /** Handle create user form */
    public function createUser(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/users?error=Invalid+request');
            return;
        }

        if (!$this->requirePost(['username', 'password', 'full_name', 'role_id'])) {
            $this->redirect('/admin/users?error=All+fields+are+required');
            return;
        }

        $username  = trim($this->post('username'));
        $password  = $this->post('password');
        $full_name = trim($this->post('full_name'));
        $role_id   = (int) $this->post('role_id');
        $outlet_id = $this->post('outlet_id') ? (int) $this->post('outlet_id') : null;

        // Staff must have an assigned kiosk
        if ($role_id === ROLE_STAFF && $outlet_id === null) {
            $this->redirect('/admin/users?error=Staff+must+be+assigned+to+a+kiosk');
            return;
        }

        // Check if username already exists
        $existing = $this->userModel->findByUsername($username);
        if ($existing) {
            $this->redirect('/admin/users?error=Username+already+exists');
            return;
        }

        $user_id = $this->userModel->create($role_id, $outlet_id, $username, $password, $full_name);
        $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Created user: {$username} (ID:{$user_id})");

        $this->redirect('/admin/users?success=User+created+successfully');
    }

    /** Handle deactivate user */
    public function deactivateUser(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/users?error=Invalid+request');
            return;
        }

        $user_id = (int) $this->post('user_id');

        // Prevent owner from deactivating themselves
        if ($user_id === Auth::userId()) {
            $this->redirect('/admin/users?error=You+cannot+deactivate+your+own+account');
            return;
        }

        $this->userModel->setActive($user_id, false);
        $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Deactivated user ID:{$user_id}");

        $this->redirect('/admin/users?success=User+deactivated');
    }

    /** Handle reactivate user */
    public function activateUser(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/users?error=Invalid+request');
            return;
        }

        $user_id = (int) $this->post('user_id');
        $this->userModel->setActive($user_id, true);
        $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Reactivated user ID:{$user_id}");

        $this->redirect('/admin/users?success=User+reactivated');
    }

    // ============================
    // PRODUCTS
    // ============================

    /** Show product management page */
    public function products(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        $data = [
            'page_title'  => 'Manage Products',
            'products'    => $this->productModel->getAll(),
            'categories'  => $this->categoryModel->getActive(),
            'success'     => $_GET['success'] ?? null,
            'error'       => $_GET['error'] ?? null,
        ];

        $this->render('admin/products', $data);
    }

    /** Handle create product form */
    public function createProduct(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/products?error=Invalid+request');
            return;
        }

        if (!$this->requirePost(['name', 'category_id', 'unit', 'price'])) {
            $this->redirect('/admin/products?error=All+fields+are+required');
            return;
        }

        $name        = trim($this->post('name'));
        $category_id = (int) $this->post('category_id');
        $unit        = trim($this->post('unit'));
        $price       = (float) $this->post('price');

        $product_id = $this->productModel->create($category_id, $name, $unit, $price);
        $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Created product: {$name} (ID:{$product_id})");

        $this->redirect('/admin/products?success=Product+added+successfully');
    }

    /** Handle update product form */
    public function updateProduct(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/products?error=Invalid+request');
            return;
        }

        $product_id  = (int) $this->post('product_id');
        $name        = trim($this->post('name'));
        $category_id = (int) $this->post('category_id');
        $unit        = trim($this->post('unit'));
        $price       = (float) $this->post('price', 0);
        $active      = (bool) $this->post('active', 0);

        $this->productModel->update($product_id, $category_id, $name, $unit, $price, $active);
        $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Updated product ID:{$product_id}");

        $this->redirect('/admin/products?success=Product+updated');
    }

    // ============================
    // KIOSKS
    // ============================

    /** Show kiosk management page */
    public function kiosks(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        $data = [
            'page_title' => 'Manage Kiosks',
            'kiosks'     => $this->kioskModel->getAll(),
            'success'    => $_GET['success'] ?? null,
            'error'      => $_GET['error'] ?? null,
        ];

        $this->render('admin/kiosks', $data);
    }

    /** Handle create kiosk form */
    public function createKiosk(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/kiosks?error=Invalid+request');
            return;
        }

        if (!$this->requirePost(['name', 'location'])) {
            $this->redirect('/admin/kiosks?error=All+fields+are+required');
            return;
        }

        $name     = trim($this->post('name'));
        $location = trim($this->post('location'));

        $kiosk_id = $this->kioskModel->create($name, $location);
        $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Created kiosk: {$name} (ID:{$kiosk_id})");

        $this->redirect('/admin/kiosks?success=Kiosk+added+successfully');
    }

    /** Handle update kiosk form */
    public function updateKiosk(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/kiosks?error=Invalid+request');
            return;
        }

        $kiosk_id = (int) $this->post('kiosk_id');
        $name     = trim($this->post('name'));
        $location = trim($this->post('location'));
        $active   = (bool) $this->post('active', 0);

        $this->kioskModel->update($kiosk_id, $name, $location, $active);
        $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Updated kiosk ID:{$kiosk_id}");

        $this->redirect('/admin/kiosks?success=Kiosk+updated');
    }

    // ============================
    // AUDIT LOG
    // ============================

    /** Show audit log page */
    public function auditLog(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        $from_date = $this->get('from_date');
        $to_date   = $this->get('to_date');

        $data = [
            'page_title' => 'Audit Log',
            'logs'       => $this->auditLog->getAll(null, $from_date, $to_date),
            'from_date'  => $from_date,
            'to_date'    => $to_date,
        ];

        $this->render('admin/audit-log', $data);
    }
}
