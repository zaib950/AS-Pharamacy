<?php
session_start();
require_once 'config.php';

// Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle User Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $name = $_POST['userName'];
    $email = $_POST['userEmail'];
    $role = $_POST['userRole'];
    $password = password_hash($_POST['userPass'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);
    $stmt->execute();
    header("Location: admin.php");
    exit();
}

// Handle User Deletion
if (isset($_GET['delete_user'])) {
    $uid = $_GET['delete_user'];
    
    // Limitation: Staff cannot remove anyone (if they somehow get here)
    if ($_SESSION['user_role'] === 'admin') {
        if ($uid != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
        }
    }
    header("Location: admin.php");
    exit();
}

// Fetch Users
$users_result = $conn->query("SELECT id, full_name as name, email, role, created_at as joined FROM users ORDER BY created_at DESC");
$allUsers = [];
while ($row = $users_result->fetch_assoc()) {
    $allUsers[] = $row;
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
    <title>Admin Panel - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="app-container">
        <div class="main-wrapper">
            <main class="content-area">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2 style="font-size: 1.5rem;">Admin Panel</h2>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">Manage system users and global settings
                        </p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                    <!-- User Management -->
                    <div class="card" style="padding: 0; overflow: hidden;">
                        <div
                            style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 1.1rem;">System Users</h3>
                            <button class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                                onclick="openUserModal()">
                                <i class="fa-solid fa-user-plus"></i> Add User
                            </button>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <!-- Users injected here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="card">
                            <h3 style="margin-bottom: 1.5rem;">System Information</h3>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                                    <span style="color: var(--text-muted);">Version</span>
                                    <span style="font-weight: 600;">v2.1.0-Pro</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                                    <span style="color: var(--text-muted);">Database</span>
                                    <span style="font-weight: 600;">MySQL (Medical_Store)</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                                    <span style="color: var(--text-muted);">Status</span>
                                    <span style="font-weight: 600; color: var(--primary);">Online</span>
                                </div>
                            </div>

                            <button class="btn btn-outline" style="width: 100%; margin-top: 1.5rem;">
                                <i class="fa-solid fa-cloud-arrow-down"></i> Backup Data
                            </button>
                        </div>

                        <div class="card">
                            <h3 style="margin-bottom: 1.5rem;">Global Settings</h3>
                            <div class="form-group">
                                <label class="form-label">Pharmacy Name</label>
                                <input type="text" class="form-control" value="AS Pharmacy" style="padding-left: 1rem;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" value="Rs." style="padding-left: 1rem;">
                            </div>
                            <button class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">Save
                                Changes</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 2rem;">
        <div class="card" style="width: 100%; max-width: 450px; box-shadow: var(--shadow-lg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3>Add New User</h3>
                <button onclick="closeUserModal()"
                    style="background: none; border: none; cursor: pointer; color: var(--text-muted);"><i
                        class="fa-solid fa-xmark fa-xl"></i></button>
            </div>
            <form id="userForm" method="POST" action="admin.php">
                <input type="hidden" name="add_user" value="1">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="userName" name="userName" class="form-control" style="padding-left: 1rem;" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="userEmail" name="userEmail" class="form-control" style="padding-left: 1rem;" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select id="userRole" name="userRole" class="form-control" style="padding-left: 1rem;">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" id="userPass" name="userPass" class="form-control" style="padding-left: 1rem;" required>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;"
                        onclick="closeUserModal()">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary" style="flex: 1;">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/components.js"></script>
    <script>
        window.currentUser = <?php echo json_encode($currentUser); ?>;
        let allUsers = <?php echo json_encode($allUsers); ?>;
        
        document.addEventListener('DOMContentLoaded', () => {
            Components.renderSidebar('admin');
            Components.renderTopbar('Admin Panel');
            renderUsers();
        });

        function renderUsers() {
            const users = allUsers;
            const tbody = document.getElementById('usersTableBody');

            tbody.innerHTML = users.map(u => `
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">${u.name.charAt(0)}</div>
                            <strong>${u.name}</strong>
                        </div>
                    </td>
                    <td>${u.email}</td>
                    <td><span class="badge ${u.role === 'admin' ? 'badge-success' : 'badge-info'}">${u.role.toUpperCase()}</span></td>
                    <td>${u.joined}</td>
                    <td>
                        <button class="action-btn" onclick="deleteUser(${u.id})" title="Remove User" style="color: var(--danger);">
                            <i class="fa-solid fa-user-minus"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function openUserModal() {
            document.getElementById('userModal').style.display = 'flex';
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        // PHP handles submission now

        function deleteUser(id) {
            if (id == <?php echo $_SESSION['user_id']; ?>) {
                alert('You cannot delete your own admin account.');
                return;
            }

            if (confirm('Are you sure you want to remove this user? This action cannot be undone.')) {
                window.location.href = 'admin.php?delete_user=' + id;
            }
        }
    </script>
</body>

</html>
