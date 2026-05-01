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
require_once __DIR__ . '/../models/TimeInModel.php';
require_once __DIR__ . '/../models/PartModel.php';

class AdminController extends Controller
{
    private UserModel $userModel;
    private RoleModel $roleModel;
    private KioskModel $kioskModel;
    private ProductModel $productModel;
    private CategoryModel $categoryModel;
    private AuditLogModel $auditLog;
    private TimeInModel $timeInModel;

    public function __construct()
    {
        $this->userModel     = new UserModel();
        $this->roleModel     = new RoleModel();
        $this->kioskModel    = new KioskModel();
        $this->productModel  = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->auditLog      = new AuditLogModel();
        $this->timeInModel   = new TimeInModel();
    }

    // ============================
    // USERS
    // ============================

    /** Show user management page */
    public function users(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        // ?show_all=1 includes deactivated users so the owner can reactivate them
        $show_all = (bool) $this->get('show_all', 0);

        $data = [
            'page_title'        => 'Manage Users',
            'users'             => $show_all ? $this->userModel->getAll() : $this->userModel->getAllActive(),
            'roles'             => $this->roleModel->getAll(),
            'kiosks'            => $this->kioskModel->getActive(),
            'show_all'          => $show_all,
            'active_sessions'   => $this->timeInModel->getActiveToday(),
            'completed_sessions'=> $this->timeInModel->getCompletedToday(),
            'success'           => $_GET['success'] ?? null,
            'error'             => $_GET['error'] ?? null,
        ];

        $this->render('admin/users', $data);
    }

    /** Show edit-user page (profile + password reset) */
    public function editUser(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        $user_id = (int) $this->get('id', 0);
        $user    = $user_id ? $this->userModel->findById($user_id) : null;

        if (!$user) {
            $this->redirect('/admin/users?error=User+not+found');
            return;
        }

        $data = [
            'page_title' => 'Edit User',
            'user'       => $user,
            'roles'      => $this->roleModel->getAll(),
            'kiosks'     => $this->kioskModel->getActive(),
            'success'    => $_GET['success'] ?? null,
            'error'      => $_GET['error'] ?? null,
        ];

        $this->render('admin/edit-user', $data);
    }

    /** Handle update user (profile fields only) */
    public function updateUser(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/users?error=Invalid+request');
            return;
        }

        $user_id = (int) $this->post('user_id');
        if ($user_id <= 0) {
            $this->redirect('/admin/users?error=Invalid+user');
            return;
        }

        if (!$this->requirePost(['username', 'full_name', 'role_id'])) {
            $this->redirect('/admin/users/edit?id=' . $user_id . '&error=All+fields+are+required');
            return;
        }

        $username  = trim($this->post('username'));
        $full_name = trim($this->post('full_name'));
        $role_id   = (int) $this->post('role_id');
        $kiosk_id  = $this->post('kiosk_id') ? (int) $this->post('kiosk_id') : null;

        // Staff must have an assigned kiosk
        if ($role_id === ROLE_STAFF && $kiosk_id === null) {
            $this->redirect('/admin/users/edit?id=' . $user_id . '&error=Staff+must+be+assigned+to+a+kiosk');
            return;
        }

        // Username uniqueness — only if changed to a name owned by another user
        $existing = $this->userModel->findByUsername($username);
        if ($existing && (int) $existing['User_ID'] !== $user_id) {
            $this->redirect('/admin/users/edit?id=' . $user_id . '&error=Username+already+exists');
            return;
        }

        $this->userModel->update($user_id, $role_id, $kiosk_id, $full_name, $username);
        $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Updated user ID:{$user_id} ({$username})");

        $this->redirect('/admin/users/edit?id=' . $user_id . '&success=Profile+updated');
    }

    /** Handle password reset for a user */
    public function resetUserPassword(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/users?error=Invalid+request');
            return;
        }

        $user_id      = (int) $this->post('user_id');
        $new_password = $this->post('new_password', '');

        if ($user_id <= 0) {
            $this->redirect('/admin/users?error=Invalid+user');
            return;
        }
        if (strlen($new_password) < 4) {
            $this->redirect('/admin/users/edit?id=' . $user_id . '&error=Password+must+be+at+least+4+characters');
            return;
        }

        $this->userModel->resetPassword($user_id, $new_password);
        $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Reset password for user ID:{$user_id}");

        $this->redirect('/admin/users/edit?id=' . $user_id . '&success=Password+reset+successfully');
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
        $kiosk_id = $this->post('kiosk_id') ? (int) $this->post('kiosk_id') : null;

        // Staff must have an assigned kiosk
        if ($role_id === ROLE_STAFF && $kiosk_id === null) {
            $this->redirect('/admin/users?error=Staff+must+be+assigned+to+a+kiosk');
            return;
        }

        // Check if username already exists
        $existing = $this->userModel->findByUsername($username);
        if ($existing) {
            $this->redirect('/admin/users?error=Username+already+exists');
            return;
        }

        $user_id = $this->userModel->create($role_id, $kiosk_id, $username, $password, $full_name);
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

    /** Record time-out for an active session */
    public function timeout(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/users?error=Invalid+request');
            return;
        }

        $timein_id = (int) $this->post('timein_id');
        if ($timein_id <= 0) {
            $this->redirect('/admin/users?error=Invalid+session');
            return;
        }

        $updated = $this->timeInModel->recordTimeOut($timein_id);
        if ($updated) {
            $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Recorded time-out for session ID:{$timein_id}");
            $this->redirect('/admin/users?success=Time-out+recorded');
        } else {
            $this->redirect('/admin/users?error=Could+not+record+time-out+(already+timed+out?)');
        }
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

    /** Show product management page (also hosts the Parts CRUD tab and recipe editor) */
    public function products(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        $partModel = new PartModel();
        $products  = $this->productModel->getAll();

        $data = [
            'page_title'  => 'Manage Products & Parts',
            'products'    => $products,
            'categories'  => $this->categoryModel->getActive(),
            'all_parts'   => $partModel->getActive(),     // for the recipe editor checkboxes
            'parts_all'   => $partModel->getAll(),         // for the Parts tab table (incl. inactive)
            'recipe_map'  => $this->buildRecipeMap($products),
            'success'     => $_GET['success'] ?? null,
            'error'       => $_GET['error'] ?? null,
        ];

        $this->render('admin/products', $data);
    }

    /**
     * Build a [Product_ID => [Part_ID => Quantity_needed]] lookup map for
     * the recipe editor so the view can pre-fill checkboxes + qty inputs
     * without firing an extra query per row.
     */
    private function buildRecipeMap(array $products): array
    {
        $map = [];
        foreach ($products as $p) {
            $pid    = (int) $p['Product_ID'];
            $recipe = $this->productModel->getRecipe($pid);
            foreach ($recipe as $r) {
                $map[$pid][(int) $r['Part_ID']] = (int) $r['Quantity_needed'];
            }
        }
        return $map;
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

        // Optional photo upload
        $photoError = $this->handlePhotoUpload($name, 'photo');
        if ($photoError !== null) {
            $this->redirect('/admin/products?error=' . urlencode('Product added, but photo upload failed: ' . $photoError));
            return;
        }

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

        // Remove-photo checkbox takes precedence over upload
        $remove = (bool) $this->post('remove_photo', 0);
        if ($remove) {
            $this->deleteProductPhotos($name);
        } else {
            $photoError = $this->handlePhotoUpload($name, 'photo');
            if ($photoError !== null) {
                $this->redirect('/admin/products?error=' . urlencode('Product updated, but photo upload failed: ' . $photoError));
                return;
            }
        }

        $this->redirect('/admin/products?success=Product+updated');
    }

    // ============================
    // PARTS (raw ingredients)
    // ============================

    /** Create a new Part — POST: name, unit */
    public function createPart(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/products?error=Invalid+request');
            return;
        }

        $name = trim($this->post('name', ''));
        $unit = trim($this->post('unit', 'pcs'));
        if ($name === '') {
            $this->redirect('/admin/products?tab=parts&error=Name+is+required');
            return;
        }

        try {
            $id = (new PartModel())->create($name, $unit);
            $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Created Part: {$name} (ID:{$id})");
            $this->redirect('/admin/products?tab=parts&success=Part+added');
        } catch (\Exception $e) {
            // Likely a duplicate Name (UNIQUE uq_part_name) — surface a friendly error
            $this->redirect('/admin/products?tab=parts&error=' . urlencode('Could not add part: ' . $e->getMessage()));
        }
    }

    /** Update a Part — POST: part_id, name, unit, active */
    public function updatePart(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/products?error=Invalid+request');
            return;
        }

        $part_id = (int) $this->post('part_id');
        $name    = trim($this->post('name', ''));
        $unit    = trim($this->post('unit', 'pcs'));
        $active  = (bool) $this->post('active', 0);

        if ($part_id <= 0 || $name === '') {
            $this->redirect('/admin/products?tab=parts&error=Invalid+input');
            return;
        }

        (new PartModel())->update($part_id, $name, $unit, $active);
        $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Updated Part ID:{$part_id}");
        $this->redirect('/admin/products?tab=parts&success=Part+updated');
    }

    /**
     * Save a product's recipe — replaces the entire Product_Part set for the product.
     * POST: product_id, part_ids[], quantities[]  (parallel arrays from the form)
     */
    public function saveRecipe(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/admin/products?error=Invalid+request');
            return;
        }

        $product_id = (int) $this->post('product_id');
        $part_ids   = $this->post('part_ids', []);
        $quantities = $this->post('quantities', []);

        if ($product_id <= 0) {
            $this->redirect('/admin/products?error=Invalid+product');
            return;
        }
        if (!is_array($part_ids)) $part_ids = [];

        $items = [];
        foreach ($part_ids as $idx => $pid) {
            $qty = (int) ($quantities[$idx] ?? 0);
            if ($qty <= 0) continue;
            $items[] = [
                'part_id'         => (int) $pid,
                'quantity_needed' => $qty,
            ];
        }

        $this->productModel->setRecipe($product_id, $items);
        $this->auditLog->log(Auth::userId(), ACTION_UPDATE,
            "Saved recipe for Product ID:{$product_id} (" . count($items) . " parts)");

        $this->redirect("/admin/products?success=Recipe+saved#product-{$product_id}");
    }

    /**
     * Handle a product photo upload from $_FILES[$field].
     * Returns null on success (or when no file was uploaded), or a short error string.
     * Saves to assets/img/products/{slug}.{ext} using the same slug rule as
     * ProductModel::getProductImagePath() so the resolver picks it up automatically.
     */
    private function handlePhotoUpload(string $productName, string $field): ?string
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null; // nothing uploaded
        }

        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return 'upload error code ' . $f['error'];
        }
        if ($f['size'] > 2 * 1024 * 1024) {
            return 'file too large (max 2MB)';
        }

        // Validate actual image content (not just extension)
        $info = @getimagesize($f['tmp_name']);
        if ($info === false) {
            return 'not a valid image';
        }
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($mimeToExt[$info['mime']])) {
            return 'unsupported image type (use JPG, PNG, or WEBP)';
        }
        $ext = $mimeToExt[$info['mime']];

        $slug = $this->productSlug($productName);
        if ($slug === '') {
            return 'cannot derive filename from product name';
        }

        $dir = __DIR__ . '/../assets/img/products/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // Remove any other-extension variants so the resolver only finds the new one
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $oldExt) {
            $old = $dir . $slug . '.' . $oldExt;
            if (is_file($old)) {
                @unlink($old);
            }
        }

        $dest = $dir . $slug . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            return 'failed to save file';
        }

        $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Uploaded photo for product: {$productName}");
        return null;
    }

    /** Delete any slug-matching photos for a product (used by the Remove photo checkbox). */
    private function deleteProductPhotos(string $productName): void
    {
        $slug = $this->productSlug($productName);
        if ($slug === '') return;
        $dir = __DIR__ . '/../assets/img/products/';
        $removed = false;
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $path = $dir . $slug . '.' . $ext;
            if (is_file($path)) {
                @unlink($path);
                $removed = true;
            }
        }
        if ($removed) {
            $this->auditLog->log(Auth::userId(), ACTION_UPDATE, "Removed photo for product: {$productName}");
        }
    }

    /** Product-name -> slug (mirrors ProductModel::getProductImagePath slug rule). */
    private function productSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    // ============================
    // KIOSKS
    // ============================

    /** Show kiosk management page */
    public function kiosks(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        // ?show_all=1 includes inactive kiosks
        $show_all = (bool) $this->get('show_all', 0);
        $kiosks   = $show_all ? $this->kioskModel->getAll() : $this->kioskModel->getActive();

        $data = [
            'page_title' => 'Manage Kiosks',
            'kiosks'     => $kiosks,
            'show_all'   => $show_all,
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

    /** Show audit log page — filtered, paginated */
    public function auditLog(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        $from_date = $this->get('from_date');
        $to_date   = $this->get('to_date');
        $action    = $this->get('action', '');
        $page      = max(1, (int) ($this->get('page', 1)));
        $per_page  = 20;

        $total_rows  = $this->auditLog->countAll(null, $from_date, $to_date, $action ?: null);
        $total_pages = max(1, (int) ceil($total_rows / $per_page));
        $page        = min($page, $total_pages);
        $offset      = ($page - 1) * $per_page;

        $data = [
            'page_title'   => 'Audit Log',
            'logs'         => $this->auditLog->getAll(null, $from_date, $to_date, $action ?: null, $per_page, $offset),
            'from_date'    => $from_date,
            'to_date'      => $to_date,
            'action'       => $action,
            'total_rows'   => $total_rows,
            'total_pages'  => $total_pages,
            'current_page' => $page,
            'per_page'     => $per_page,
        ];

        $this->render('admin/audit-log', $data);
    }
}
