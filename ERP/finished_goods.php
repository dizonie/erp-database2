<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

// Fetch user details from session for display.
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

// Fetch All Finished Products (Updated query to include unit_cost)
$products_sql = "
    SELECT pm.product_id, pm.name, pm.stock_quantity, pm.unit_cost, pm.status, 
           GROUP_CONCAT(rm.name SEPARATOR ', ') as materials
    FROM product_materials pm
    LEFT JOIN raw_materials rm ON pm.raw_material_id = rm.id
    GROUP BY pm.product_id
    ORDER BY pm.product_id";
$products_result = $conn->query($products_sql);

// Fetch all raw materials for the selection dropdown in the modal
$all_raw_materials_sql = "SELECT id, name, code_color FROM raw_materials ORDER BY name";
$all_raw_materials_result = $conn->query($all_raw_materials_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="logo.png">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="company-logo">
                <img src="images/logo.png" alt="Company Logo" style="width: 60px; height: 60px; border-radius: 12px; object-fit: contain; display: block;">
            </div>
            <div class="company-name">James Polymer</div>
            <div class="company-subtitle">Manufacturing Corporation</div>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Inventory Management</div>
                <a href="index.php" class="menu-item" data-module="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <div class="menu-item menu-dropdown open" id="inventoryDropdown">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                    <i class="fas fa-chevron-down fa-rotate-180"></i>
                </div>
                <div class="dropdown-menu open" id="inventoryDropdownMenu">
                    <a href="raw_materials.php" class="menu-item" data-module="raw-materials">
                        <i class="fas fa-cubes"></i>
                        <span>Raw Materials</span>
                    </a>
                    <a href="finished_goods.php" class="menu-item active" data-module="finished-goods">
                        <i class="fas fa-box"></i>
                        <span>Product List</span>
                    </a>
                    <a href="transactions.php" class="menu-item" data-module="transactions">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </div>
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-section-title">System</div>
                <a href="logout.php" class="menu-item" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1 class="header-title">Product List</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"> <?php echo ucfirst($role); ?> </span>
                </div>
            </div>
        </div>
        <div class="content">
            <!-- Alert placeholder for delete messages -->
            <div id="delete-alert-placeholder"></div>
            <div class="module-content active" id="finished-goods">
                <div class="table-section">
                    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="search-container" style="position: relative; display: inline-block;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="productSearchInput" class="search-input" placeholder="Search products...">
                        </div>
                        <div class="table-title" style="text-align: center; flex-grow: 1;">Finished Products</div>
                        <button class="btn btn-primary" id="openAddProductModal">
                            <i class="fas fa-plus"></i> Add New Product
                        </button>
                    </div>
                     <div class="table-responsive">
                        <table id="productTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Raw Materials</th>
                                    <th>Unit Cost</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($products_result && $products_result->num_rows > 0) {
                                    $count = 1;
                                    while ($row = $products_result->fetch_assoc()) {
                                        $status_class = strtolower($row['status']) === 'active' ? 'completed' : 'cancelled';
                                        echo "<tr data-row-id='" . htmlspecialchars($row['product_id']) . "'>";
                                        echo "<td>" . $count++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['materials'] ?? 'N/A') . "</td>";
                                        echo "<td>₱" . htmlspecialchars(number_format($row['unit_cost'], 2)) . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['stock_quantity'])) . "</td>";
                                        echo "<td><span class='status-badge " . $status_class . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                        echo "<td class='actions-cell'>
                                                <button class='btn-icon btn-icon-blue view-product-btn' title='View Product' data-product-id='" . htmlspecialchars($row['product_id']) . "'><i class='fas fa-eye'></i></button>
                                                <button class='btn-icon btn-icon-green edit-product-btn' title='Edit Product' data-product-id='" . htmlspecialchars($row['product_id']) . "'><i class='fas fa-pencil-alt'></i></button>
                                                <button class='btn-icon btn-icon-red delete-product-btn' title='Delete Product' data-product-id='" . htmlspecialchars($row['product_id']) . "'><i class='fas fa-trash-alt'></i></button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                     echo "<tr><td colspan='7' style='text-align:center;'>No products found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add New Product Modal -->
    <div id="addProductModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="closeAddProductModal">&times;</span>
            <form class="form-section" id="addProductForm" method="POST" action="add_product.php" enctype="multipart/form-data">
                <div class="form-header"><h2 class="form-title">Add New Product</h2></div>
                
                <div id="addProductAlert" style="display:none; margin-bottom: 1rem;"></div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="product_name">Product Name</label>
                        <input type="text" class="form-input" id="product_name" name="name" placeholder="Enter product name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="stock_quantity">Initial Stock Quantity</label>
                        <input type="number" class="form-input" id="stock_quantity" name="stock_quantity" placeholder="Enter quantity" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="unit_cost">Unit Cost (₱)</label>
                        <input type="number" step="0.01" class="form-input" id="unit_cost" name="unit_cost" placeholder="Enter unit cost" min="0" value="0.00" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="raw_materials">Associated Raw Materials</label>
                    <select class="form-input" id="raw_materials" name="raw_materials[]" multiple required>
                        <?php
                        if ($all_raw_materials_result->num_rows > 0) {
                            $all_raw_materials_result->data_seek(0);
                            while ($mat = $all_raw_materials_result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($mat['id']) . "'>" . htmlspecialchars($mat['name'] . ' (' . $mat['code_color'] . ')') . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <small>Hold Ctrl/Cmd to select multiple materials.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="image_url">Product Image</label>
                    <input type="file" class="form-input" id="image_url" name="image_url" accept="image/*">
                </div>
                <div class="form-actions">
                    <button class="btn btn-outline" type="button" id="cancelAddProductModal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Generic Confirmation Modal -->
    <div id="confirmModal" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="max-width: 450px;">
            <div class="form-header"><h2 class="form-title" id="confirmModalTitle">Confirm Action</h2></div>
            <p id="confirmModalText">Are you sure you want to proceed with this action?</p>
            <div class="form-actions">
                <button class="btn btn-outline" id="confirmCancelBtn">Cancel</button>
                <button class="btn btn-secondary" id="confirmOkBtn">Confirm</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Logout Button Logic ---
            const logoutBtn = document.getElementById('logoutBtn');
            if(logoutBtn) {
                logoutBtn.addEventListener('click', (e) => e.stopPropagation());
            }

            // --- Add Product Modal Logic ---
            const openAddProductBtn = document.getElementById('openAddProductModal');
            const addProductModal = document.getElementById('addProductModal');
            const closeAddProductModalBtn = document.getElementById('closeAddProductModal');
            const cancelAddProductModalBtn = document.getElementById('cancelAddProductModal');
            const addProductForm = document.getElementById('addProductForm');
            const addProductAlert = document.getElementById('addProductAlert');

            function showModalAlert(message, type) {
                addProductAlert.className = 'alert alert-' + type;
                addProductAlert.textContent = message;
                addProductAlert.style.display = 'block';
            }

            function closeAndResetModal() {
                addProductModal.style.display = 'none';
                addProductAlert.style.display = 'none';
                addProductForm.reset();
                const submitButton = addProductForm.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.textContent = 'Save Product';
            }

            if (openAddProductBtn) openAddProductBtn.addEventListener('click', () => addProductModal.style.display = 'flex');
            if (closeAddProductModalBtn) closeAddProductModalBtn.addEventListener('click', closeAndResetModal);
            if (cancelAddProductModalBtn) cancelAddProductModalBtn.addEventListener('click', closeAndResetModal);
            if (addProductModal) addProductModal.addEventListener('click', (e) => { if (e.target === addProductModal) closeAndResetModal(); });

            if (addProductForm) {
                addProductForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const submitButton = this.querySelector('button[type="submit"]');
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving...';

                    fetch('add_product.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showModalAlert(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showModalAlert(data.message, 'danger');
                            submitButton.disabled = false;
                            submitButton.textContent = 'Save Product';
                        }
                    })
                    .catch(error => {
                        showModalAlert('An unexpected error occurred.', 'danger');
                        console.error('Error:', error);
                        submitButton.disabled = false;
                        submitButton.textContent = 'Save Product';
                    });
                });
            }

            // --- Delete Product Logic ---
            const confirmModal = document.getElementById('confirmModal');
            const confirmOkBtn = document.getElementById('confirmOkBtn');
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');
            const confirmModalTitle = document.getElementById('confirmModalTitle');
            const confirmModalText = document.getElementById('confirmModalText');
            let deleteHandler = null;

            function showDeleteAlert(message, type) {
                const placeholder = document.getElementById('delete-alert-placeholder');
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-' + type;
                alertDiv.textContent = message;
                placeholder.innerHTML = ''; // Clear previous alerts
                placeholder.appendChild(alertDiv);
                setTimeout(() => alertDiv.remove(), 3000);
            }

            document.querySelectorAll('.delete-product-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const productName = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                    
                    confirmModalTitle.textContent = 'Delete Product';
                    confirmModalText.textContent = `Are you sure you want to delete "${productName}"? This action cannot be undone.`;
                    confirmModal.style.display = 'flex';

                    deleteHandler = function() {
                        const formData = new FormData();
                        formData.append('id', productId);

                        fetch('delete_product.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showDeleteAlert(data.message, 'success');
                                const row = document.querySelector(`tr[data-row-id='${productId}']`);
                                if (row) row.remove();
                            } else {
                                showDeleteAlert(data.message, 'danger');
                            }
                        })
                        .catch(err => {
                            showDeleteAlert('An error occurred during deletion.', 'danger');
                            console.error(err);
                        })
                        .finally(() => {
                            confirmModal.style.display = 'none';
                        });
                    };
                });
            });

            confirmOkBtn.addEventListener('click', () => {
                if (typeof deleteHandler === 'function') {
                    deleteHandler();
                    deleteHandler = null; // Reset handler
                }
            });

            confirmCancelBtn.addEventListener('click', () => {
                confirmModal.style.display = 'none';
                deleteHandler = null; // Reset handler
            });
        });
    </script>
</body>
</html>
