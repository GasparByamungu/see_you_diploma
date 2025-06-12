<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate current password if changing password
    if (!empty($new_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $error_message = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long";
        } else {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $hashed_password, $_SESSION['user_id']]);
            $success_message = "Profile updated successfully";
        }
    } else {
        // Update without changing password
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
        $success_message = "Profile updated successfully";
    }

    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Safari Minibus Rentals</title>
    <meta name="description" content="Manage your Safari Minibus Rentals profile, update personal information, and change your password.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-truck me-2 fs-4"></i>
                <span>Safari Minibus Rentals</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">
                            <i class="bi bi-gear me-1"></i>Our Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="bi bi-info-circle me-1"></i>About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="bi bi-calendar-check me-1"></i>My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="bi bi-person me-1"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Content -->
    <div class="container py-5" style="margin-top: 80px;">
        <div class="row">
            <!-- Profile Header -->
            <div class="col-12 mb-4">
                <div class="card border-0 shadow-custom">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 80px; height: 80px;">
                                        <i class="bi bi-person fs-1 text-white"></i>
                                    </div>
                                    <div>
                                        <h2 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($user['name']); ?></h2>
                                        <p class="text-muted mb-1">
                                            <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-telephone me-2"></i><?php echo htmlspecialchars($user['phone']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="text-muted">
                                    <small>Member since</small><br>
                                    <strong><?php echo date('M d, Y', strtotime($user['created_at'])); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-custom">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0 text-primary">
                            <i class="bi bi-person-gear me-2"></i>Profile Information
                        </h4>
                        <p class="text-muted mb-0">Update your personal information and account settings</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <div><?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <div><?php echo htmlspecialchars($error_message); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="profile.php" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="name" class="form-label fw-semibold">
                                        <i class="bi bi-person me-2 text-primary"></i>Full Name
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name"
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide your full name.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="email" class="form-label fw-semibold">
                                        <i class="bi bi-envelope me-2 text-primary"></i>Email Address
                                    </label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email"
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid email address.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="phone" class="form-label fw-semibold">
                                    <i class="bi bi-telephone me-2 text-primary"></i>Phone Number
                                </label>
                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide your phone number.
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="text-primary mb-3">
                                <i class="bi bi-shield-lock me-2"></i>Change Password
                            </h5>
                            <p class="text-muted mb-4">Leave these fields empty if you don't want to change your password</p>

                            <div class="mb-4">
                                <label for="current_password" class="form-label fw-semibold">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-lg" id="current_password" name="current_password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="new_password" class="form-label fw-semibold">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg" id="new_password" name="new_password">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-custom mb-4">
                    <div class="card-header bg-white border-0 py-4">
                        <h5 class="mb-0 text-primary">
                            <i class="bi bi-gear me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-grid gap-2">
                            <a href="bookings.php" class="btn btn-outline-primary">
                                <i class="bi bi-calendar-check me-2"></i>View My Bookings
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-search me-2"></i>Book New Trip
                            </a>
                            <a href="mailto:info@safariminibus.co.tz" class="btn btn-outline-info">
                                <i class="bi bi-envelope me-2"></i>Contact Support
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-custom">
                    <div class="card-header bg-white border-0 py-4">
                        <h5 class="mb-0 text-primary">
                            <i class="bi bi-info-circle me-2"></i>Account Security
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-shield-check fs-4 text-success me-3"></i>
                            <div>
                                <div class="fw-semibold">Account Verified</div>
                                <small class="text-muted">Your account is secure</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-clock fs-4 text-info me-3"></i>
                            <div>
                                <div class="fw-semibold">Last Login</div>
                                <small class="text-muted">Today</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-key fs-4 text-warning me-3"></i>
                            <div>
                                <div class="fw-semibold">Password</div>
                                <small class="text-muted">Change regularly for security</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>
                        <i class="bi bi-truck me-2"></i>Safari Minibus Rentals
                    </h5>
                    <p class="mb-3">
                        Your trusted partner for comfortable and reliable minibus transportation
                        across Tanzania. Experience the beauty of our country with our premium fleet.
                    </p>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php"><i class="bi bi-house me-2"></i>Home</a></li>
                        <li class="mb-2"><a href="about.php"><i class="bi bi-info-circle me-2"></i>About Us</a></li>
                        <li class="mb-2"><a href="services.php"><i class="bi bi-gear me-2"></i>Our Services</a></li>
                        <li class="mb-2"><a href="bookings.php"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
                        <li class="mb-2"><a href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Contact Information</h5>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-telephone me-3 text-accent"></i>
                            <span>+255 683749514</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope me-3 text-accent"></i>
                            <span>info@safariminibus.co.tz</span>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="bi bi-geo-alt me-3 text-accent mt-1"></i>
                            <span>Dar es Salaam, Tanzania</span>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-color: rgba(255, 255, 255, 0.2);">

            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> Safari Minibus Rentals. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button type="button" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle" id="backToTop" style="width: 50px; height: 50px; display: none; z-index: 1000;">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNavbar');
            const backToTop = document.getElementById('backToTop');

            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
                backToTop.style.display = 'block';
            } else {
                navbar.classList.remove('scrolled');
                backToTop.style.display = 'none';
            }
        });

        // Back to top functionality
        document.getElementById('backToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Function to toggle password visibility
        function togglePasswordVisibility(toggleButton, passwordInput) {
            toggleButton.addEventListener('click', function (e) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        }

        // Initialize password toggles
        togglePasswordVisibility(
            document.querySelector('#toggleCurrentPassword'),
            document.querySelector('#current_password')
        );
        togglePasswordVisibility(
            document.querySelector('#toggleNewPassword'),
            document.querySelector('#new_password')
        );
        togglePasswordVisibility(
            document.querySelector('#toggleConfirmPassword'),
            document.querySelector('#confirm_password')
        );

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

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Add loading state to submit button
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Updating...';
            submitBtn.disabled = true;

            // Re-enable button after 5 seconds in case of error
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/[^\d+]/g, '');

            if (value && !value.startsWith('+')) {
                value = '+255' + value;
            }

            this.value = value;
        });
    </script>
</body>
</html>