<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit();
}

// Handle Category Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_category'])) {
    $name = $_POST['catName'];
    $desc = $_POST['catDesc'];
    $id = $_POST['catId'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $desc, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $desc);
    }
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

$result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$allCats = [];
while ($row = $result->fetch_assoc()) {
    $allCats[] = $row;
}

$currentUser = [
    'name' => $_SESSION['user_name'],
    'role' => $_SESSION['user_role']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <div class="main-wrapper">
            <main class="content-area">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2 style="font-size: 1.5rem;">Medicine Categories</h2>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">Organize your inventory with categories</p>
                    </div>
                    <button class="btn btn-primary" onclick="openCatModal()">
                        <i class="fa-solid fa-plus"></i> Add Category
                    </button>
                </div>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allCats as $cat): ?>
                                <tr>
                                    <td><strong><?php echo $cat['name']; ?></strong></td>
                                    <td style="color: var(--text-muted); font-size: 0.9rem;"><?php echo $cat['description'] ?: 'No description'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="action-btn" onclick="editCat(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', '<?php echo addslashes($cat['description']); ?>')"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <button class="action-btn" onclick="if(confirm('Delete this category?')) window.location.href='categories.php?delete=<?php echo $cat['id']; ?>'" style="color: var(--danger);"><i class="fa-solid fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="catModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 2rem;">
        <div class="card" style="width: 100%; max-width: 450px;">
            <h3 id="modalTitle" style="margin-bottom: 1.5rem;">Add Category</h3>
            <form id="catForm" method="POST" action="categories.php">
                <input type="hidden" id="catId" name="catId">
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" id="catName" name="catName" class="form-control" required style="padding-left: 1rem;">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="catDesc" name="catDesc" class="form-control" style="height: 100px; padding: 1rem;"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeCatModal()">Cancel</button>
                    <button type="submit" name="save_category" class="btn btn-primary" style="flex: 1;">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/components.js"></script>
    <script>
        window.currentUser = <?php echo json_encode($currentUser); ?>;
        document.addEventListener('DOMContentLoaded', () => {
            Components.renderSidebar('categories');
            Components.renderTopbar('Categories Management');
        });

        function openCatModal() {
            document.getElementById('catModal').style.display = 'flex';
            document.getElementById('modalTitle').innerText = 'Add Category';
            document.getElementById('catForm').reset();
            document.getElementById('catId').value = '';
        }

        function closeCatModal() {
            document.getElementById('catModal').style.display = 'none';
        }

        function editCat(id, name, desc) {
            openCatModal();
            document.getElementById('modalTitle').innerText = 'Edit Category';
            document.getElementById('catId').value = id;
            document.getElementById('catName').value = name;
            document.getElementById('catDesc').value = desc;
        }
    </script>
</body>
</html>
