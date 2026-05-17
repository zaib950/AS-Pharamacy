<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                $error = "Admins must use the secure admin portal.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Remember Me cookie
                if (isset($_POST['remember'])) {
                    setcookie("user_login", $user['id'], time() + (86400 * 30), "/"); // 30 days
                }

                // Redirect to verification (Mock)
                header("Location: dashboard.php?verify=1");
                exit();
            }
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AS Pharmacy</title>
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
                <h2 class="auth-title">Welcome Back</h2>
                <p class="auth-subtitle">Sign in to manage AS Pharmacy</p>
            </div>

            <form id="loginForm" action="login.php" method="POST">
                <?php if($error): ?>
                <div id="loginError"
                    style="color: var(--danger); font-size: 0.85rem; margin-bottom: 1rem; text-align: center;">
                    <i class="fa-solid fa-circle-exclamation"></i> <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="staff@aspharmacy.com" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label class="form-label">Password</label>
                        <a href="javascript:void(0)" onclick="openResetModal()" class="auth-link" style="font-size: 0.8rem;">Forgot Password?</a>
                    </div>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Exactly 8 characters" required minlength="8" maxlength="8">
                        <button type="button" class="input-toggle" onclick="togglePassword()">
                            <i class="fa-solid fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 2rem;">
                    <input type="checkbox" name="remember" id="remember" style="width: 16px; height: 16px; cursor: pointer;">
                    <label for="remember" style="font-size: 0.85rem; color: var(--text-muted); cursor: pointer; user-select: none;">Remember this device</label>
                </div>

                <button type="submit" name="login" class="btn btn-primary">
                    <span>Sign In</span>
                    <i class="fa-solid fa-right-to-bracket"></i>
                </button>
            </form>

            <div class="auth-footer" style="display: flex; flex-direction: column; gap: 0.8rem;">
                <div style="font-size: 0.85rem; color: var(--text-muted);">Please contact management for staff access issues.</div>
            </div>


        </div>
    </div>

    <!-- Professional Forgot Password Modal -->
    <div id="resetModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Account Recovery</h3>
                <div class="modal-close" onclick="closeResetModal()">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="step-progress">
                    <div class="step active" id="step1-dot" title="Identify">1</div>
                    <div class="step" id="step2-dot" title="Verify">2</div>
                    <div class="step" id="step3-dot" title="Secure">3</div>
                </div>

                <!-- Step 1: Identify Account -->
                <div id="reset-step-1" class="reset-step active">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 50px; height: 50px; background: var(--accent-glow); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-muted);">Enter your email to locate your account.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" id="reset-email" class="form-control" placeholder="example@aspharmacy.com">
                        </div>
                    </div>
                    <button onclick="nextStep(2)" class="btn btn-primary" style="margin-top: 1rem;">
                        <span>Continue</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

                <!-- Step 2: Security Verification -->
                <div id="reset-step-2" class="reset-step">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 50px; height: 50px; background: var(--warning); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; opacity: 0.2;">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-muted);">Verification Required: Please enter your Date of Birth.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-calendar input-icon"></i>
                            <input type="date" id="reset-dob" class="form-control">
                        </div>
                    </div>
                    <div id="reset-error-2" style="color: var(--danger); font-size: 0.85rem; margin-top: 0.75rem; display: none; text-align: center; background: #fee2e2; padding: 0.5rem; border-radius: 8px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <span></span>
                    </div>
                    <button onclick="nextStep(3)" class="btn btn-primary" style="margin-top: 1rem;">
                        <span>Verify Identity</span>
                        <i class="fa-solid fa-shield-check"></i>
                    </button>
                </div>

                <!-- Step 3: Set New Password -->
                <div id="reset-step-3" class="reset-step">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 50px; height: 50px; background: var(--accent-glow); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
                            <i class="fa-solid fa-lock-open"></i>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-muted);">Create a secure new password.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="reset-pass" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input type="password" id="reset-confirm" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                    <div id="reset-error-3" style="color: var(--danger); font-size: 0.85rem; margin-top: 0.75rem; display: none; text-align: center; background: #fee2e2; padding: 0.5rem; border-radius: 8px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <span></span>
                    </div>
                    <button onclick="finishReset()" class="btn btn-primary" style="margin-top: 1rem;">
                        <span>Update Password</span>
                        <i class="fa-solid fa-circle-check"></i>
                    </button>
                </div>

                <!-- Success State -->
                <div id="reset-step-success" class="reset-step" style="text-align: center; padding: 1rem 0;">
                    <div class="success-animation" style="width: 80px; height: 80px; background: var(--accent); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 1.5rem; box-shadow: 0 10px 20px var(--accent-glow);">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h3 style="margin-bottom: 0.5rem;">Password Restored</h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 2rem;">Your account has been secured with a new password. You can now log in.</p>
                    <button onclick="closeResetModal()" class="btn btn-primary">Back to Sign In</button>
                </div>
            </div>
        </div>
    </div>



    <script src="assets/js/components.js"></script>
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



        // --- Professional Reset Password System ---
        let currentResetEmail = '';

        function openResetModal() {
            document.getElementById('resetModal').classList.add('active');
            resetResetFlow();
        }

        function closeResetModal() {
            document.getElementById('resetModal').classList.remove('active');
        }

        function resetResetFlow() {
            // Hide all steps
            const steps = document.querySelectorAll('.reset-step');
            steps.forEach(s => s.classList.remove('active'));
            
            const dots = document.querySelectorAll('.step');
            dots.forEach(d => d.classList.remove('active', 'completed'));

            // Show step 1
            document.getElementById('reset-step-1').classList.add('active');
            document.getElementById('step1-dot').classList.add('active');
            
            // Clear inputs
            document.getElementById('reset-email').value = '';
            document.getElementById('reset-dob').value = '';
            document.getElementById('reset-pass').value = '';
            document.getElementById('reset-confirm').value = '';
            
            // Hide errors
            document.getElementById('reset-error-2').style.display = 'none';
            document.getElementById('reset-error-3').style.display = 'none';
        }

        function nextStep(step) {
            if (step === 2) {
                const email = document.getElementById('reset-email').value;
                if (!email) {
                    alert('Please enter your email address to continue.');
                    return;
                }
                currentResetEmail = email;
                
                // Advance UI
                document.getElementById('reset-step-1').classList.remove('active');
                document.getElementById('step1-dot').classList.add('completed');
                document.getElementById('reset-step-2').classList.add('active');
                document.getElementById('step2-dot').classList.add('active');
            } 
            else if (step === 3) {
                const dob = document.getElementById('reset-dob').value;
                if (!dob) {
                    alert('Please provide your date of birth for verification.');
                    return;
                }
                
                const result = Storage.verifyResetIdentity(currentResetEmail, dob);
                if (result.success) {
                    document.getElementById('reset-step-2').classList.remove('active');
                    document.getElementById('step2-dot').classList.add('completed');
                    document.getElementById('reset-step-3').classList.add('active');
                    document.getElementById('step3-dot').classList.add('active');
                    document.getElementById('reset-error-2').style.display = 'none';
                } else {
                    const err = document.getElementById('reset-error-2');
                    err.querySelector('span').innerText = result.message;
                    err.style.display = 'block';
                }
            }
        }

        function finishReset() {
            const pass = document.getElementById('reset-pass').value;
            const confirm = document.getElementById('reset-confirm').value;
            const err = document.getElementById('reset-error-3');
            
            if (pass.length < 6) {
                err.querySelector('span').innerText = 'Password must be at least 6 characters.';
                err.style.display = 'block';
                return;
            }
            
            if (pass !== confirm) {
                err.querySelector('span').innerText = 'Passwords do not match.';
                err.style.display = 'block';
                return;
            }

            const result = Storage.resetPassword(currentResetEmail, pass);
            if (result.success) {
                document.getElementById('reset-step-3').classList.remove('active');
                document.getElementById('step3-dot').classList.add('completed');
                document.getElementById('reset-step-success').classList.add('active');
                err.style.display = 'none';
            } else {
                alert(result.message);
            }
        }



        }
    </script>
</body>

</html>
