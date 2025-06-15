<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Debug information
        $debug_info = "Phone number validation:";
        $debug_info .= "\nInput value: '" . $phone . "'";
        $debug_info .= "\nLength: " . strlen($phone);
        $debug_info .= "\nPattern match result: " . (preg_match('/^\+255[0-9]{8,9}$/', $phone) ? 'true' : 'false');
        
        if (!preg_match('/^\+255[0-9]{8,9}$/', $phone)) {
            $error = "Phone number must start with +255 followed by 8-9 digits. Debug: " . $debug_info;
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already registered";
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
                
                try {
                    $stmt->execute([$name, $email, $phone, $hashed_password]);
                    $success = "Registration successful! You can now login.";
                } catch (PDOException $e) {
                    $error = "Registration failed. Please try again.";
                }
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
    <title>Create Account - Safari Minibus Rentals</title>
    <meta name="description" content="Join Safari Minibus Rentals today. Create your account to book premium minibus transportation across Tanzania.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <style>
        .auth-container {
            min-height: 100vh;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .auth-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            border: none;
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }

        .auth-header {
            background: var(--gradient-secondary);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .auth-body {
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating > .form-control {
            height: 3.5rem;
            border-radius: var(--radius-md);
            border: 2px solid rgba(45, 80, 22, 0.1);
            transition: all var(--transition-normal);
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(45, 80, 22, 0.25);
        }

        .form-floating > label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            transition: all var(--transition-normal);
        }

        .strength-weak { background: var(--danger-color); }
        .strength-medium { background: var(--warning-color); }
        .strength-strong { background: var(--success-color); }

        .auth-footer {
            background: var(--light-gray);
            padding: 1.5rem 2rem;
            text-align: center;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .feature-list {
            background: rgba(45, 80, 22, 0.05);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .feature-item:last-child {
            margin-bottom: 0;
        }

        .feature-icon {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-right: 0.75rem;
            min-width: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-truck me-2 fs-4"></i>
                <span>Safari Minibus Rentals</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Registration Form -->
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row g-0 auth-card mx-auto">
                        <!-- Left Side - Form -->
                        <div class="col-lg-7">
                            <div class="auth-header">
                                <h1 class="h3 fw-bold mb-2">
                                    <i class="bi bi-person-plus me-2"></i>Create Your Account
                                </h1>
                                <p class="mb-0 opacity-90">
                                    Join thousands of satisfied customers and start your journey with us
                                </p>
                            </div>

                            <div class="auth-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success d-flex align-items-center" role="alert">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <div>
                                            <?php echo htmlspecialchars($success); ?>
                                            <div class="mt-2">
                                                <a href="login.php" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login Now
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="register.php" class="needs-validation" novalidate>
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name"
                                               placeholder="Full Name"
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                               required>
                                        <label for="name">
                                            <i class="bi bi-person me-2"></i>Full Name
                                        </label>
                                        <div class="invalid-feedback">
                                            Please provide your full name.
                                        </div>
                                    </div>

                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email"
                                               placeholder="Email Address"
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                               required>
                                        <label for="email">
                                            <i class="bi bi-envelope me-2"></i>Email Address
                                        </label>
                                        <div class="invalid-feedback">
                                            Please provide a valid email address.
                                        </div>
                                    </div>

                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                               placeholder="Phone Number"
                                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                               pattern="^\+255[0-9]{8,9}$" required>
                                        <label for="phone">
                                            <i class="bi bi-telephone me-2"></i>Phone Number
                                        </label>
                                        <div class="invalid-feedback">
                                            Please provide a valid phone number starting with +255 followed by 8-9 digits.
                                        </div>
                                        <div class="form-text">
                                            <small>Include country code (e.g., +255 for Tanzania)</small>
                                        </div>
                                    </div>

                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="password" name="password"
                                               placeholder="Password" minlength="6" required>
                                        <label for="password">
                                            <i class="bi bi-lock me-2"></i>Password
                                        </label>
                                        <button class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2"
                                                type="button" id="togglePassword" style="z-index: 10; border: none; background: none;">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Password must be at least 6 characters long.
                                        </div>
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <div class="form-text">
                                            <small id="passwordHelp">Password strength: <span id="strengthText">Enter password</span></small>
                                        </div>
                                    </div>

                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                               placeholder="Confirm Password" required>
                                        <label for="confirm_password">
                                            <i class="bi bi-lock-fill me-2"></i>Confirm Password
                                        </label>
                                        <button class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2"
                                                type="button" id="toggleConfirmPassword" style="z-index: 10; border: none; background: none;">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Passwords do not match.
                                        </div>
                                    </div>

                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary btn-lg" id="registerBtn">
                                            <i class="bi bi-person-plus me-2"></i>Create Account
                                        </button>
                                    </div>

                                    
                                </form>
                            </div>

                            <div class="auth-footer">
                                <p class="mb-0">
                                    Already have an account?
                                    <a href="login.php" class="text-decoration-none fw-semibold">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>Login here
                                    </a>
                                </p>
                            </div>
                        </div>

                        <!-- Right Side - Benefits -->
                        <div class="col-lg-5 d-none d-lg-block" style="background: var(--gradient-primary);">
                            <div class="p-5 text-white h-100 d-flex flex-column justify-content-center">
                                <div class="text-center mb-4">
                                    <i class="bi bi-truck display-1 mb-3"></i>
                                    <h3 class="fw-bold mb-3">Welcome to Safari Minibus Rentals</h3>
                                    <p class="lead opacity-90">
                                        Join our community and experience premium transportation across Tanzania
                                    </p>
                                </div>

                                
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNavbar');

            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Function to toggle password visibility
        function togglePasswordVisibility(toggleButton, passwordInput) {
            toggleButton.addEventListener('click', function (e) {
                e.preventDefault();
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';

            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;

            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');

            if (password.length === 0) {
                strengthBar.className = 'password-strength';
                strengthText.textContent = 'Enter password';
                return;
            }

            if (strength <= 2) {
                strengthBar.className = 'password-strength strength-weak';
                strengthText.textContent = 'Weak';
            } else if (strength <= 4) {
                strengthBar.className = 'password-strength strength-medium';
                strengthText.textContent = 'Medium';
            } else {
                strengthBar.className = 'password-strength strength-strong';
                strengthText.textContent = 'Strong';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize password toggles
            togglePasswordVisibility(
                document.querySelector('#togglePassword'),
                document.querySelector('#password')
            );
            togglePasswordVisibility(
                document.querySelector('#toggleConfirmPassword'),
                document.querySelector('#confirm_password')
            );

            // Password strength checking
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                validatePasswordMatch();
            });

            confirmPasswordInput.addEventListener('input', validatePasswordMatch);

            // Password match validation
            function validatePasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword && password !== confirmPassword) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }

            // Phone number formatting and validation
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function() {
                let value = this.value.replace(/[^\d+]/g, '');
                
                // Always ensure it starts with +255
                if (!value.startsWith('+255')) {
                    value = '+255' + value.replace('+255', '');
                }
                
                // Limit to 12-13 characters (+255 + 8-9 digits)
                if (value.length > 13) {
                    value = value.substring(0, 13);
                }
                
                this.value = value;
                
                // Validate the format
                const isValid = /^\+255[0-9]{8,9}$/.test(value);
                this.setCustomValidity(isValid ? '' : 'Phone number must start with +255 followed by 8-9 digits');
                
                // Log validation status for debugging
                console.log('Phone validation:', {
                    value: value,
                    isValid: isValid,
                    length: value.length
                });
            });

            // Form validation
            const form = document.querySelector('.needs-validation');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    // Add loading state to submit button
                    const submitBtn = document.getElementById('registerBtn');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating Account...';
                    submitBtn.disabled = true;

                    // Re-enable button after 10 seconds in case of error
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 10000);
                }
                form.classList.add('was-validated');
            }, false);

            // Add animation to form elements
            const formElements = document.querySelectorAll('.form-floating');
            formElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;

                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100 + (index * 100));
            });
        });
    </script>
</body>
</html> 