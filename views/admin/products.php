<section class="admin-products">
    <div class="section-header">
        <h2>Manage Products</h2>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add Product Form -->
    <div class="card form-card">
        <h3>Add New Product</h3>
        <form method="POST" action="<?= BASE_URL ?>/admin/products/create"
              class="form-inline-grid" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">

            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" class="form-input" required>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['Category_ID'] ?>"><?= htmlspecialchars($cat['Name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="unit">Unit</label>
                <input type="text" id="unit" name="unit" class="form-input" value="pcs" required>
            </div>

            <div class="form-group">
                <label for="price">Price (P)</label>
                <input type="number" id="price" name="price" class="form-input" min="0" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="photo">Photo (optional)</label>
                <input type="file" id="photo" name="photo" class="form-input"
                       accept="image/jpeg,image/png,image/webp">
                <small class="form-hint">JPG, PNG, or WEBP. Max 2MB.</small>
            </div>

            <div class="form-group form-actions">
                <button type="submit" class="btn btn-primary">Add Product</button>
            </div>
        </form>
    </div>

    <!-- Declare per-product forms here; inputs inside the table associate via the HTML5 `form` attribute -->
    <?php foreach ($products as $product): ?>
        <form id="edit-<?= $product['Product_ID'] ?>"
              method="POST" action="<?= BASE_URL ?>/admin/products/update"
              enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
            <input type="hidden" name="product_id" value="<?= $product['Product_ID'] ?>">
            <input type="hidden" name="active" value="<?= (int) $product['Active'] ?>">
        </form>
        <form id="toggle-<?= $product['Product_ID'] ?>"
              method="POST" action="<?= BASE_URL ?>/admin/products/update" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
            <input type="hidden" name="product_id" value="<?= $product['Product_ID'] ?>">
            <input type="hidden" name="name" value="<?= htmlspecialchars($product['Name']) ?>">
            <input type="hidden" name="category_id" value="<?= $product['Category_ID'] ?>">
            <input type="hidden" name="unit" value="<?= htmlspecialchars($product['Unit']) ?>">
            <input type="hidden" name="price" value="<?= $product['Price'] ?>">
            <input type="hidden" name="active" value="<?= $product['Active'] ? '0' : '1' ?>">
        </form>
    <?php endforeach; ?>

    <!-- Products Table (inline editable via `form` attribute) -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Price (P)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" class="text-center">No products found. Add your first product above.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                            $fid = 'edit-' . $product['Product_ID'];
                            $imgUrl = ProductModel::getProductImagePath($product['Name']);
                            $hasPhoto = strpos($imgUrl, 'placehold.co') === false;
                        ?>
                        <tr>
                            <td><?= $product['Product_ID'] ?></td>
                            <td class="products-photo-cell">
                                <img class="products-thumb"
                                     src="<?= htmlspecialchars($imgUrl) ?>"
                                     alt="<?= htmlspecialchars($product['Name']) ?>"
                                     loading="lazy">
                                <div class="products-photo-controls">
                                    <input type="file" name="photo" form="<?= $fid ?>"
                                           class="form-input input-sm"
                                           accept="image/jpeg,image/png,image/webp">
                                    <?php if ($hasPhoto): ?>
                                        <label class="products-remove-label">
                                            <input type="checkbox" name="remove_photo" value="1" form="<?= $fid ?>">
                                            Remove photo
                                        </label>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="name" form="<?= $fid ?>" class="form-input input-sm"
                                       value="<?= htmlspecialchars($product['Name']) ?>" required>
                            </td>
                            <td>
                                <select name="category_id" form="<?= $fid ?>" class="form-select input-sm" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['Category_ID'] ?>"
                                            <?= $cat['Category_ID'] == $product['Category_ID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="unit" form="<?= $fid ?>" class="form-input input-sm"
                                       value="<?= htmlspecialchars($product['Unit']) ?>" required>
                            </td>
                            <td>
                                <input type="number" name="price" form="<?= $fid ?>" class="form-input input-sm"
                                       min="0" step="0.01"
                                       value="<?= number_format((float) $product['Price'], 2, '.', '') ?>" required>
                            </td>
                            <td>
                                <span class="badge <?= $product['Active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $product['Active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button type="submit" form="<?= $fid ?>" class="btn btn-sm btn-primary">Save</button>
                                <button type="submit" form="toggle-<?= $product['Product_ID'] ?>"
                                        class="btn btn-sm <?= $product['Active'] ? 'btn-danger' : 'btn-success' ?>"
                                        onclick="return confirm('Are you sure?')">
                                    <?= $product['Active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination controls (client-side, all rows already in DOM) -->
    <div class="pagination" id="productsPagination" style="display:none;">
        <button type="button" class="btn btn-sm btn-outline" id="pagPrev">&laquo; Prev</button>
        <span class="pagination-info" id="pagInfo"></span>
        <button type="button" class="btn btn-sm btn-outline" id="pagNext">Next &raquo;</button>
    </div>
</section>

<script>
(function() {
    const PAGE_SIZE = 10;
    const tbody = document.querySelector('.admin-products tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.querySelector('input[name="name"]'));
    if (rows.length <= PAGE_SIZE) return; // no need to paginate

    const pag  = document.getElementById('productsPagination');
    const prev = document.getElementById('pagPrev');
    const next = document.getElementById('pagNext');
    const info = document.getElementById('pagInfo');
    pag.style.display = '';

    let page = 1;
    const totalPages = Math.ceil(rows.length / PAGE_SIZE);

    function render() {
        const start = (page - 1) * PAGE_SIZE;
        const end   = start + PAGE_SIZE;
        rows.forEach((r, i) => {
            r.style.display = (i >= start && i < end) ? '' : 'none';
        });
        info.textContent = 'Page ' + page + ' of ' + totalPages + ' (' + rows.length + ' items)';
        prev.disabled = (page === 1);
        next.disabled = (page === totalPages);
    }

    prev.addEventListener('click', () => { if (page > 1) { page--; render(); } });
    next.addEventListener('click', () => { if (page < totalPages) { page++; render(); } });

    render();
})();
</script>
