<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: inventory.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_med'])) {
    $id = $_POST['medId'];
    $name = $_POST['medName'];
    $category = $_POST['medCategory'];
    $company = $_POST['medCompany'];
    $price = $_POST['medPrice'];
    $qty = $_POST['medQty'];
    $expiry = $_POST['medExpiry'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE inventory SET product_name=?, category=?, company=?, quantity=?, price=?, expiry=? WHERE id=?");
        $stmt->bind_param("sssidsi", $name, $category, $company, $qty, $price, $expiry, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory (product_name, category, company, quantity, price, expiry) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssids", $name, $category, $company, $qty, $price, $expiry);
        $stmt->execute();
    }
    header("Location: inventory.php");
    exit();
}

$result = $conn->query("SELECT id, product_name as name, category, company, quantity as qty, price, expiry FROM inventory ORDER BY id DESC");
$allMeds = [];
while ($row = $result->fetch_assoc()) {
    $allMeds[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <div class="main-wrapper">
            <main class="content-area">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2 style="font-size: 1.5rem;">Medicine Inventory</h2>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">Manage and track your medicine stock</p>
                    </div>
                    <button class="btn btn-primary" onclick="openMedModal()">
                        <i class="fa-solid fa-plus"></i> Add Medicine
                    </button>
                </div>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; gap: 1rem;">
                        <div class="input-wrapper" style="flex: 1;">
                            <i class="fa-solid fa-search input-icon"></i>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by name, company, or category..." oninput="updateFilteredItems()">
                        </div>
                        <select id="categoryFilter" class="form-control" style="width: 200px; padding-left: 1rem;" onchange="updateFilteredItems()">
                            <option value="">All Categories</option>
                            <option>Analgesic</option>
                            <option>Antibiotic</option>
                            <option>Antacid</option>
                            <option>Vitamin</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Category</th>
                                    <th>Company</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Expiry</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="medsTableBody">
                                <!-- Data injected here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination">
                        <div class="pagination-info">
                            Showing <span id="pageStart">0</span>-<span id="pageEnd">0</span> of <span id="totalItems">0</span> items
                        </div>
                        <div class="pagination-btns">
                            <button class="page-btn" onclick="changePage(-1)" id="prevBtn"><i class="fa-solid fa-chevron-left"></i> Previous</button>
                            <button class="page-btn" onclick="changePage(1)" id="nextBtn">Next <i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Medicine Modal -->
    <div id="medModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 2rem;">
        <div class="card" style="width: 100%; max-width: 600px; box-shadow: var(--shadow-lg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 id="modalTitle">Add Medicine</h3>
                <button onclick="closeMedModal()" style="background: none; border: none; cursor: pointer; color: var(--text-muted);"><i class="fa-solid fa-xmark fa-xl"></i></button>
            </div>
            <form id="medForm" method="POST" action="inventory.php">
                <input type="hidden" id="medId" name="medId">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Medicine Name</label>
                        <input type="text" id="medName" name="medName" class="form-control" style="padding-left: 1rem;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select id="medCategory" name="medCategory" class="form-control" style="padding-left: 1rem;" required>
                            <option>Analgesic</option>
                            <option>Antibiotic</option>
                            <option>Antacid</option>
                            <option>Vitamin</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Manufacturer</label>
                        <input type="text" id="medCompany" name="medCompany" class="form-control" style="padding-left: 1rem;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (Rs.)</label>
                        <input type="number" id="medPrice" name="medPrice" step="0.01" class="form-control" style="padding-left: 1rem;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" id="medQty" name="medQty" class="form-control" style="padding-left: 1rem;" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" id="medExpiry" name="medExpiry" class="form-control" style="padding-left: 1rem;" required>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeMedModal()">Cancel</button>
                    <button type="submit" name="save_med" class="btn btn-primary" style="flex: 1;">Save Medicine</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/components.js"></script>
    <script>
        let allMeds = <?php echo json_encode($allMeds); ?>;
        let currentPage = 1;
        const itemsPerPage = 10;
        let filteredItems = [];
        let highlightId = null;

        document.addEventListener('DOMContentLoaded', () => {
            Components.renderSidebar('inventory');
            Components.renderTopbar('Inventory Management');
            
            // Check for highlight parameter
            const urlParams = new URLSearchParams(window.location.search);
            highlightId = parseInt(urlParams.get('highlight'));
            
            updateFilteredItems();

            if (highlightId) {
                const index = filteredItems.findIndex(m => m.id === highlightId);
                if (index !== -1) {
                    currentPage = Math.floor(index / itemsPerPage) + 1;
                    renderMeds();
                    
                    // Scroll to the highlighted row after rendering
                    setTimeout(() => {
                        const row = document.querySelector(`.highlight-row`);
                        if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 500);
                }
            }
        });

        function updateFilteredItems() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            
            filteredItems = allMeds.filter(m => {
                const matchesSearch = m.name.toLowerCase().includes(search) || 
                                    m.company.toLowerCase().includes(search) || 
                                    m.category.toLowerCase().includes(search);
                const matchesCategory = category === '' || m.category === category;
                return matchesSearch && matchesCategory;
            });

            currentPage = 1;
            renderMeds();
        }

        function renderMeds() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const pageItems = filteredItems.slice(startIndex, endIndex);

            const tbody = document.getElementById('medsTableBody');
            tbody.innerHTML = pageItems.map(m => {
                const isLow = m.qty < 10;
                const statusBadge = isLow ? '<span class="badge badge-warning">Low Stock</span>' : '<span class="badge badge-success">In Stock</span>';
                
                return `
                    <tr class="${m.id === highlightId ? 'highlight-row' : ''}">
                        <td><strong>${m.name}</strong></td>
                        <td><span class="badge" style="background: var(--surface-alt); color: var(--text-muted);">${m.category}</span></td>
                        <td>${m.company}</td>
                        <td>Rs. ${m.price}</td>
                        <td style="font-weight: 600; color: ${isLow ? 'var(--danger)' : 'inherit'}">${m.qty}</td>
                        <td>${m.expiry}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="action-btn" onclick="editMed(${m.id})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="action-btn" onclick="if(confirm('Are you sure you want to delete this medicine?')) window.location.href='inventory.php?delete=${m.id}'" title="Delete" style="color: var(--danger);"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            // Update Pagination Info
            document.getElementById('pageStart').innerText = filteredItems.length > 0 ? startIndex + 1 : 0;
            document.getElementById('pageEnd').innerText = Math.min(endIndex, filteredItems.length);
            document.getElementById('totalItems').innerText = filteredItems.length;

            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = endIndex >= filteredItems.length;
        }

        function changePage(dir) {
            currentPage += dir;
            renderMeds();
        }

        function openMedModal(id = null) {
            const modal = document.getElementById('medModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('medForm');
            
            if (id) {
                const med = allMeds.find(m => m.id == id);
                document.getElementById('medId').value = med.id;
                document.getElementById('medName').value = med.name;
                document.getElementById('medCategory').value = med.category;
                document.getElementById('medCompany').value = med.company;
                document.getElementById('medPrice').value = med.price;
                document.getElementById('medQty').value = med.qty;
                document.getElementById('medExpiry').value = med.expiry;
                title.innerText = 'Edit Medicine';
            } else {
                form.reset();
                document.getElementById('medId').value = '';
                title.innerText = 'Add Medicine';
            }
            
            modal.style.display = 'flex';
        }

        function closeMedModal() {
            document.getElementById('medModal').style.display = 'none';
        }

        function editMed(id) {
            openMedModal(id);
        }
    </script>
</body>
</html>
