<?php
session_start();
require_once 'config.php';

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $age   = intval($_POST['age'] ?? 0);

    // Validation
    if (!preg_match("/^[a-zA-Z ]+$/", $name) || strlen($name) < 2) {
        $msg = "Name should only contain alphabets (min 2 characters).";
    } elseif ($age < 1 || $age > 120) {
        $msg = "Please enter a valid age between 1 and 120.";
    } elseif (strlen($pass) < 8) {
        $msg = "Password must be at least 8 characters.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $msg = "This email is already registered. Please login.";
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $role   = 'user';
            $stmt   = $conn->prepare("INSERT INTO users (full_name, email, password, age, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $name, $email, $hashed, $age, $role);
            if ($stmt->execute()) {
                header("Location: login.php?msg=Registration+successful!+Please+login.");
                exit();
            } else {
                $msg = "Something went wrong. Please try again. (" . $conn->error . ")";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface-alt);
            padding: 20px;
        }
        /* Uses global .auth-card from style.css */
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="index.php" style="text-decoration: none; color: var(--primary); font-size: 2rem; font-weight: 800;">
                    <i class="fa-solid fa-briefcase-medical"></i> AS Pharmacy
                </a>
                <h2 style="margin-top: 1rem;">Create Account</h2>
                <p style="color: var(--text-muted);">Join our healthcare community today</p>
            </div>

            <?php if ($msg): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <form action="signup.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           placeholder="John Doe" required style="padding-left: 1rem;"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="name@example.com" required style="padding-left: 1rem;"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" class="form-control"
                           placeholder="Enter your age" required min="1" max="120"
                           style="padding-left: 1rem;"
                           value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Password <span style="color:var(--text-muted); font-weight:400;">(Min 8 characters)</span></label>
                    <input type="password" name="password" class="form-control"
                           placeholder="••••••••" required style="padding-left: 1rem;" minlength="8">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; height: 50px; margin-top: 1rem;">
                    Join Now <i class="fa-solid fa-user-plus" style="margin-left: 0.5rem;"></i>
                </button>
            </form>

            <div style="text-align: center; margin-top: 2rem; color: var(--text-muted);">
                Already have an account? <a href="login.php" class="auth-link">Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
