<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Safari Minibus Rentals</title>
    <meta name="description" content="Login to your Safari Minibus Rentals account to manage bookings and access exclusive features.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-truck me-2 fs-4"></i>
                <span>Safari Minibus Rentals</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-house me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Login Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card shadow-custom-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="bg-primary-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-person-circle fs-1 text-primary"></i>
                            </div>
                            <h2 class="fw-bold text-primary mb-2">Welcome Back</h2>
                            <p class="text-muted">Sign in to your account to continue</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="bi bi-envelope me-2 text-primary"></i>Email Address
                                </label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email"
                                       placeholder="Enter your email address" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="bi bi-lock me-2 text-primary"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-lg" id="password" name="password"
                                           placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Please provide your password.
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                                    <label class="form-check-label text-muted" for="rememberMe">
                                        Remember me for 30 days
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <p class="text-muted mb-3">Don't have an account?</p>
                            <a href="register.php" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus me-2"></i>Create New Account
                            </a>
                        </div>

                        <hr class="my-4">

                        <div class="text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Your information is secure and protected
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });

        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                const forms = document.getElementsByClassName('needs-validation');
                const validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Add loading state to submit button
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Signing In...';
            submitBtn.disabled = true;

            // Re-enable button after 5 seconds in case of error
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>