<?php
    // Active tab (?tab=parts) — defaults to Products tab
    $active_tab = ($_GET['tab'] ?? 'products') === 'parts' ? 'parts' : 'products';

    // Count active products with ₱0.00 price (warning banner)
    $zero_price_count = 0;
    foreach ($products as $p) {
        if ((int) $p['Active'] === 1 && (float) $p['Price'] <= 0) {
            $zero_price_count++;
        }
    }
?>
<section class="admin-products">
    <div class="section-header">
        <h2>Manage Products &amp; Parts</h2>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ===== TOP-LEVEL TAB NAV ===== -->
    <div class="admin-products-tab-nav">
        <button class="admin-products-tab-btn <?= $active_tab === 'products' ? 'active' : '' ?>"
                data-tab="products">🍔 Products</button>
        <button class="admin-products-tab-btn <?= $active_tab === 'parts' ? 'active' : '' ?>"
                data-tab="parts">🧩 Parts (Ingredients)</button>
    </div>

    <!-- ============================================================
         TAB: PRODUCTS  (existing CRUD + inline recipe editor)
         ============================================================ -->
    <div class="admin-products-tab-panel <?= $active_tab === 'products' ? 'active' : '' ?>"
         id="adminProductsTab">

        <?php if ($zero_price_count > 0): ?>
            <div class="alert alert-warning">
                ⚠ <strong><?= $zero_price_count ?></strong> active product<?= $zero_price_count === 1 ? '' : 's' ?>
                still ha<?= $zero_price_count === 1 ? 's' : 've' ?> a price of ₱0.00.
                Sales of these products will record as ₱0 line totals — set a price before they are sold.
            </div>
        <?php endif; ?>

        <!-- Add Product Form (collapsed by default) -->
        <details class="add-form-details">
            <summary class="add-form-summary">
                <span class="add-form-summary-icon">+</span>
                <span>Add New Product</span>
            </summary>
            <div class="card form-card add-form-card">
                <form method="POST" action="<?= BASE_URL ?>/admin/products/create"
                      enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">

                    <!-- Basic fields -->
                    <div class="form-inline-grid">
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
                    </div>

                    <!-- Recipe section (optional — sets parts the product is built from) -->
                    <details class="add-recipe-details">
                        <summary class="add-recipe-summary">
                            🧩 Recipe — pick the parts that make 1 unit of this product
                            <span class="text-muted">(optional · you can also edit it later)</span>
                        </summary>
                        <div class="add-recipe-body">
                            <p class="text-muted" style="margin:0 0 8px 0;font-size:13px;">
                                Tick a part to include it · the quantity is per <strong>1 unit</strong> of the product
                                (e.g. Lumpia Bowl = 4 Lumpia + 1 Rice + 1 Rice Cup).
                            </p>
                            <div class="recipe-parts-list">
                                <?php foreach ($all_parts as $part): ?>
                                    <div class="recipe-part-row">
                                        <label class="recipe-part-label">
                                            <input type="checkbox" name="part_ids[]" value="<?= $part['Part_ID'] ?>">
                                            <span><?= htmlspecialchars($part['Name']) ?>
                                                <small class="text-muted">(<?= htmlspecialchars($part['Unit']) ?>)</small>
                                            </span>
                                        </label>
                                        <input type="number" name="quantities[]" class="form-input recipe-qty"
                                               value="1" min="1" max="99" disabled>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>

                    <div class="form-actions" style="margin-top:14px;">
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </details>

        <!-- Per-product hidden forms — kept in the DOM (referenced by form= attribute on table buttons) but take 0 layout space -->
        <div class="hidden-forms-bag" hidden>
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
        </div>

        <!-- Products Table (inline editable + per-row recipe panel) -->
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
                        <th>Recipe</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="9" class="text-center">No products found. Add your first product above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                                $fid       = 'edit-' . $product['Product_ID'];
                                $imgUrl    = ProductModel::getProductImagePath($product['Name']);
                                $hasPhoto  = strpos($imgUrl, 'placehold.co') === false;
                                $pid       = (int) $product['Product_ID'];
                                $partsHere = $recipe_map[$pid] ?? [];
                                $partCt    = count($partsHere);
                            ?>
                            <tr id="product-<?= $pid ?>">
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
                                <?php $priceWarn = ((int) $product['Active'] === 1 && (float) $product['Price'] <= 0); ?>
                                <td class="<?= $priceWarn ? 'cell-price-warn' : '' ?>">
                                    <input type="number" name="price" form="<?= $fid ?>" class="form-input input-sm"
                                           min="0" step="0.01"
                                           value="<?= number_format((float) $product['Price'], 2, '.', '') ?>" required>
                                    <?php if ($priceWarn): ?>
                                        <span class="price-warn-icon" title="Active product priced at ₱0.00">⚠</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline"
                                            onclick="toggleRecipe(<?= $pid ?>)">
                                        🧩 Edit Recipe
                                        <?php if ($partCt > 0): ?>
                                            <span class="badge badge-active" style="margin-left:4px;font-size:10px;">
                                                <?= $partCt ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive" style="margin-left:4px;font-size:10px;">none</span>
                                        <?php endif; ?>
                                    </button>
                                </td>
                                <td>
                                    <span class="badge <?= $product['Active'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $product['Active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary"
                                            onclick="showConfirmModal({
                                                title: 'Save Product Changes',
                                                message: 'You\'re about to update this product. Price changes will apply to all new sales going forward — historical sales are not affected. Continue?',
                                                confirmText: 'Yes, Save',
                                                type: 'submit',
                                                onConfirm: () => document.getElementById('<?= $fid ?>').submit()
                                            })">Save</button>
                                    <button type="button"
                                            class="btn btn-sm <?= $product['Active'] ? 'btn-danger' : 'btn-success' ?>"
                                            onclick="showConfirmModal({
                                                title: <?= $product['Active'] ? "'Deactivate Product'" : "'Activate Product'" ?>,
                                                message: <?= $product['Active']
                                                    ? "'Are you sure you want to deactivate this product? It will be hidden from the POS and sales screens.'"
                                                    : "'Are you sure you want to reactivate this product?'" ?>,
                                                confirmText: <?= $product['Active'] ? "'Yes, Deactivate'" : "'Yes, Activate'" ?>,
                                                type: <?= $product['Active'] ? "'delete'" : "'submit'" ?>,
                                                onConfirm: () => document.getElementById('toggle-<?= $product['Product_ID'] ?>').submit()
                                            })">
                                        <?= $product['Active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </td>
                            </tr>

                            <!-- Hidden recipe editor row — empty placeholder; panel HTML is injected on click -->
                            <tr id="recipe-<?= $pid ?>" class="recipe-row" data-product-id="<?= $pid ?>"
                                data-product-name="<?= htmlspecialchars($product['Name']) ?>"
                                style="display:none;">
                                <td colspan="9"><!-- panel injected by JS --></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Shared parts list + per-product recipe data — used to inject panels on demand -->
        <script id="recipeAllParts" type="application/json"><?= json_encode(array_map(function($p) {
            return ['id' => (int) $p['Part_ID'], 'name' => $p['Name'], 'unit' => $p['Unit']];
        }, $all_parts)) ?></script>
        <script id="recipeMap" type="application/json"><?= json_encode($recipe_map) ?></script>
        <input type="hidden" id="recipeCsrf" value="<?= Auth::generateCsrf() ?>">
        <input type="hidden" id="recipeBaseUrl" value="<?= BASE_URL ?>">

        <!-- Pagination -->
        <div class="pagination" id="productsPagination" style="display:none;">
            <button type="button" class="btn btn-sm btn-outline" id="pagPrev">&laquo; Prev</button>
            <span class="pagination-info" id="pagInfo"></span>
            <button type="button" class="btn btn-sm btn-outline" id="pagNext">Next &raquo;</button>
        </div>

    </div><!-- /adminProductsTab -->

    <!-- ============================================================
         TAB: PARTS  (CRUD for raw ingredients)
         ============================================================ -->
    <div class="admin-products-tab-panel <?= $active_tab === 'parts' ? 'active' : '' ?>"
         id="adminPartsTab">

        <!-- Add Part Form (collapsed by default) -->
        <details class="add-form-details">
            <summary class="add-form-summary">
                <span class="add-form-summary-icon">+</span>
                <span>Add New Part</span>
            </summary>
            <div class="card form-card add-form-card">
                <form method="POST" action="<?= BASE_URL ?>/admin/parts/create" class="form-inline-grid">
                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">

                    <div class="form-group">
                        <label for="part_name">Part Name</label>
                        <input type="text" id="part_name" name="name" class="form-input"
                               placeholder="e.g. Mayonnaise, Sausage, Pork Skin" required>
                    </div>

                    <div class="form-group">
                        <label for="part_unit">Unit</label>
                        <select id="part_unit" name="unit" class="form-select">
                            <option value="pcs">pcs</option>
                            <option value="cup">cup</option>
                            <option value="pack">pack</option>
                            <option value="bottle">bottle</option>
                            <option value="can">can</option>
                            <option value="tsp">tsp</option>
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="ml">ml</option>
                        </select>
                    </div>

                    <div class="form-group form-actions">
                        <button type="submit" class="btn btn-primary">Add Part</button>
                    </div>
                </form>
            </div>
        </details>

        <!-- Per-part hidden forms — bagged so they take 0 layout space -->
        <div class="hidden-forms-bag" hidden>
            <?php foreach ($parts_all as $part): ?>
                <form id="edit-part-<?= $part['Part_ID'] ?>"
                      method="POST" action="<?= BASE_URL ?>/admin/parts/update">
                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                    <input type="hidden" name="part_id" value="<?= $part['Part_ID'] ?>">
                    <input type="hidden" name="active" value="<?= (int) $part['Active'] ?>">
                </form>
                <form id="toggle-part-<?= $part['Part_ID'] ?>"
                      method="POST" action="<?= BASE_URL ?>/admin/parts/update" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                    <input type="hidden" name="part_id" value="<?= $part['Part_ID'] ?>">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($part['Name']) ?>">
                    <input type="hidden" name="unit" value="<?= htmlspecialchars($part['Unit']) ?>">
                    <input type="hidden" name="active" value="<?= $part['Active'] ? '0' : '1' ?>">
                </form>
            <?php endforeach; ?>
        </div>

        <!-- Parts Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Part Name</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parts_all)): ?>
                        <tr><td colspan="6" class="text-center">No parts yet. Add your first part above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($parts_all as $part): ?>
                            <?php $efid = 'edit-part-' . $part['Part_ID']; ?>
                            <tr class="<?= $part['Active'] ? '' : 'row-inactive' ?>">
                                <td><?= $part['Part_ID'] ?></td>
                                <td>
                                    <input type="text" name="name" form="<?= $efid ?>"
                                           class="form-input input-sm"
                                           value="<?= htmlspecialchars($part['Name']) ?>" required>
                                </td>
                                <td>
                                    <select name="unit" form="<?= $efid ?>" class="form-select input-sm">
                                        <?php foreach (['pcs','cup','pack','bottle','can','tsp','kg','g','ml'] as $u): ?>
                                            <option value="<?= $u ?>" <?= $part['Unit'] === $u ? 'selected' : '' ?>><?= $u ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <span class="badge <?= $part['Active'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $part['Active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($part['Created_at'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary"
                                            onclick="showConfirmModal({
                                                title: 'Save Part Changes',
                                                message: 'Update this part? Active recipes that use it will continue to work.',
                                                confirmText: 'Yes, Save',
                                                type: 'submit',
                                                onConfirm: () => document.getElementById('<?= $efid ?>').submit()
                                            })">Save</button>
                                    <button type="button"
                                            class="btn btn-sm <?= $part['Active'] ? 'btn-danger' : 'btn-success' ?>"
                                            onclick="showConfirmModal({
                                                title: <?= $part['Active'] ? "'Deactivate Part'" : "'Activate Part'" ?>,
                                                message: <?= $part['Active']
                                                    ? "'Deactivate this part? It will stay linked to existing recipes but cannot be added to new ones.'"
                                                    : "'Reactivate this part so it can be added to recipes again?'" ?>,
                                                confirmText: <?= $part['Active'] ? "'Yes, Deactivate'" : "'Yes, Activate'" ?>,
                                                type: <?= $part['Active'] ? "'delete'" : "'submit'" ?>,
                                                onConfirm: () => document.getElementById('toggle-part-<?= $part['Part_ID'] ?>').submit()
                                            })">
                                        <?= $part['Active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div><!-- /adminPartsTab -->

</section>

<script>
(function () {
    // ===== TOP-LEVEL TAB SWITCHING =====
    document.querySelectorAll('.admin-products-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tab = this.dataset.tab;
            document.querySelectorAll('.admin-products-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.admin-products-tab-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(tab === 'parts' ? 'adminPartsTab' : 'adminProductsTab').classList.add('active');
            // Update URL ?tab= without reload (so refresh stays on same tab)
            const url = new URL(window.location.href);
            if (tab === 'parts') url.searchParams.set('tab', 'parts');
            else                  url.searchParams.delete('tab');
            window.history.replaceState({}, '', url.toString());
        });
    });

    // ===== RECIPE LAZY-INJECT + TOGGLE =====
    // The recipe panel HTML used to be pre-rendered for every product (heavy).
    // Now we render it only when the user clicks Edit Recipe.
    const allPartsData = JSON.parse(document.getElementById('recipeAllParts').textContent || '[]');
    const recipeMap    = JSON.parse(document.getElementById('recipeMap').textContent || '{}');
    const csrfToken    = document.getElementById('recipeCsrf').value;
    const baseUrl      = document.getElementById('recipeBaseUrl').value;

    function buildRecipePanel(pid, productName) {
        const recipe = recipeMap[pid] || {};
        const partsHtml = allPartsData.map(function (p) {
            const cur = parseInt(recipe[p.id] || 0, 10);
            const checked  = cur > 0 ? 'checked' : '';
            const disabled = cur > 0 ? '' : 'disabled';
            return '<div class="recipe-part-row">'
                +    '<label class="recipe-part-label">'
                +      '<input type="checkbox" name="part_ids[]" value="' + p.id + '" ' + checked + '>'
                +      '<span>' + escapeHtml(p.name) + ' <small class="text-muted">(' + escapeHtml(p.unit) + ')</small></span>'
                +    '</label>'
                +    '<input type="number" name="quantities[]" class="form-input recipe-qty" '
                +         'value="' + Math.max(1, cur) + '" min="1" max="99" ' + disabled + '>'
                +  '</div>';
        }).join('');

        return ''
            + '<div class="recipe-panel">'
            +   '<h4>Recipe for ' + escapeHtml(productName) + '</h4>'
            +   '<p class="text-muted" style="margin:0 0 8px 0;">'
            +     'Tick the parts needed to make <strong>1 unit</strong> of this product. '
            +     'The quantity is per single unit (e.g. Lumpia Bowl = 4 Lumpia + 1 Rice + 1 Rice Cup).'
            +   '</p>'
            +   '<form method="POST" action="' + baseUrl + '/admin/parts/save-recipe">'
            +     '<input type="hidden" name="csrf_token" value="' + csrfToken + '">'
            +     '<input type="hidden" name="product_id" value="' + pid + '">'
            +     '<div class="recipe-parts-list">' + partsHtml + '</div>'
            +     '<div class="recipe-actions">'
            +       '<button type="submit" class="btn btn-primary">Save Recipe</button>'
            +       '<button type="button" class="btn btn-outline" onclick="toggleRecipe(' + pid + ')">Cancel</button>'
            +     '</div>'
            +   '</form>'
            + '</div>';
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    window.toggleRecipe = function (pid) {
        const row = document.getElementById('recipe-' + pid);
        if (!row) return;
        const willOpen = (row.style.display === 'none' || row.style.display === '');
        if (willOpen) {
            const td = row.querySelector('td');
            // Inject only the first time it's opened
            if (td && !td.dataset.loaded) {
                td.innerHTML  = buildRecipePanel(pid, row.dataset.productName);
                td.dataset.loaded = '1';
            }
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    };

    // Sync recipe checkbox -> qty input enable/disable
    document.addEventListener('change', function (e) {
        if (e.target.type !== 'checkbox' || e.target.name !== 'part_ids[]') return;
        const row = e.target.closest('.recipe-part-row');
        if (!row) return;
        const qty = row.querySelector('input[name="quantities[]"]');
        if (qty) qty.disabled = !e.target.checked;
    });

    // ===== PRODUCTS PAGINATION (paginates only product rows; closes any open recipe row on page change) =====
    const PAGE_SIZE = 10;
    const tbody = document.querySelector('#adminProductsTab tbody');
    if (!tbody) return;
    const productRows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.querySelector('input[name="name"]'));
    if (productRows.length <= PAGE_SIZE) return;

    const pag  = document.getElementById('productsPagination');
    const prev = document.getElementById('pagPrev');
    const next = document.getElementById('pagNext');
    const info = document.getElementById('pagInfo');
    pag.style.display = '';

    let page = 1;
    const totalPages = Math.ceil(productRows.length / PAGE_SIZE);

    function render() {
        // Close every open recipe row first — avoids orphaned panels on the wrong page
        document.querySelectorAll('.recipe-row').forEach(r => r.style.display = 'none');

        const start = (page - 1) * PAGE_SIZE;
        const end   = start + PAGE_SIZE;
        productRows.forEach(function (r, i) {
            const visible = (i >= start && i < end);
            r.style.display = visible ? '' : 'none';
            // Recipe row is the immediate next sibling — match its visibility envelope (still hidden until toggled)
            const next = r.nextElementSibling;
            if (next && next.classList.contains('recipe-row')) {
                // We never auto-show recipe rows; just ensure they're hidden when their product is hidden
                next.style.display = 'none';
            }
        });
        info.textContent = 'Page ' + page + ' of ' + totalPages + ' (' + productRows.length + ' items)';
        prev.disabled = (page === 1);
        next.disabled = (page === totalPages);
    }

    prev.addEventListener('click', () => { if (page > 1) { page--; render(); } });
    next.addEventListener('click', () => { if (page < totalPages) { page++; render(); } });

    render();
})();
</script>
