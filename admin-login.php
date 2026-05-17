<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: admin.php");
            exit();
        } else {
            $error = "Invalid admin credentials";
        }
    } else {
        $error = "Invalid admin credentials or unauthorized";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="auth-logo-icon">
                    <i class="fa-solid fa-briefcase-medical"></i>
                </div>
                <div class="auth-logo-text">AS Pharmacy</div>
            </div>

            <div class="auth-header">
                <h2 class="auth-title">Admin Access</h2>
                <p class="auth-subtitle">Restricted to authorized personnel only</p>
            </div>

            <form id="loginForm" method="POST" action="admin-login.php">
                <?php if($error): ?>
                <div id="loginError"
                    style="color: var(--danger); font-size: 0.85rem; margin-bottom: 1rem; text-align: center;">
                    <i class="fa-solid fa-circle-exclamation"></i> <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Admin Email</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" class="form-control" placeholder="admin@aspharmacy.com" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                        <button type="button" class="input-toggle" onclick="togglePassword()">
                            <i class="fa-solid fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span>Admin Login</span>
                    <i class="fa-solid fa-right-to-bracket"></i>
                </button>
            </form>
            
            <div style="margin-top: 1.5rem; text-align: center;">
                <a href="login.php" class="auth-link" style="font-size: 0.85rem; color: var(--text-muted);"><i class="fa-solid fa-arrow-left"></i> Return to Staff Login</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                passInput.type = 'password';
                icon.className = 'fa-solid fa-eye';
            }
        }
    </script>
</body>
</html>
