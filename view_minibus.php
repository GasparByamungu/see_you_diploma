<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

// Get minibus details
$stmt = $pdo->prepare("
    SELECT m.id, m.name, m.capacity, m.price_per_km, m.features, m.status, m.driver_id, m.created_at
    FROM minibuses m
    WHERE m.id = ?
");
$stmt->execute([$id]);
$minibus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$minibus) {
    header("Location: index.php");
    exit();
}

// Get driver information separately
$minibus['driver_name'] = null;
$minibus['driver_phone'] = null;
if ($minibus['driver_id']) {
    $driver_stmt = $pdo->prepare("
        SELECT name as driver_name, phone as driver_phone
        FROM drivers WHERE id = ?
    ");
    $driver_stmt->execute([$minibus['driver_id']]);
    $driver = $driver_stmt->fetch(PDO::FETCH_ASSOC);

    if ($driver) {
        $minibus['driver_name'] = $driver['driver_name'];
        $minibus['driver_phone'] = $driver['driver_phone'];
    }
}

// Get images separately
$images_stmt = $pdo->prepare("
    SELECT image_path FROM minibus_images
    WHERE minibus_id = ?
    ORDER BY display_order
");
$images_stmt->execute([$id]);
$image_paths = $images_stmt->fetchAll(PDO::FETCH_COLUMN);
$images = !empty($image_paths) ? $image_paths : ['assets/images/default-minibus.jpg'];
$features = json_decode($minibus['features'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($minibus['name']); ?> - Safari Minibus Rentals</title>
    <meta name="description" content="View detailed information about <?php echo htmlspecialchars($minibus['name']); ?> including features, pricing, and booking options.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <style>
        .image-gallery {
            position: relative;
            margin-bottom: 2rem;
        }

        .main-image {
            height: 500px;
            object-fit: cover;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
        }

        .main-image:hover {
            transform: scale(1.02);
        }

        .thumbnail-container {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .thumbnail {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-normal);
            border: 3px solid transparent;
        }

        .thumbnail:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .thumbnail.active {
            border-color: var(--primary-color);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(45, 80, 22, 0.05);
            border-radius: var(--radius-sm);
            transition: all var(--transition-normal);
        }

        .feature-item:hover {
            background: rgba(45, 80, 22, 0.1);
            transform: translateX(5px);
        }

        .feature-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            min-width: 30px;
        }

        .price-display {
            background: var(--gradient-secondary);
            color: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            text-align: center;
            margin-bottom: 2rem;
        }

        .price-amount {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .price-unit {
            font-size: 1rem;
            opacity: 0.9;
        }

        .status-badge {
            font-size: 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
        }

        .info-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: none;
            overflow: hidden;
        }

        .driver-info {
            background: var(--gradient-primary);
            color: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
        }

        .breadcrumb-nav {
            background: var(--light-gray);
            padding: 1rem 0;
            margin-top: 80px;
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
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">
                                <i class="bi bi-calendar-check me-1"></i>My Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person me-1"></i>My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white ms-2 px-3" href="register.php">
                                <i class="bi bi-person-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <section class="breadcrumb-nav">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="index.php" class="text-decoration-none">
                            <i class="bi bi-house me-1"></i>Home
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="index.php#fleet" class="text-decoration-none">Fleet</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($minibus['name']); ?>
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Minibus Details -->
    <div class="container py-5">
        <div class="row">
            <!-- Image Gallery -->
            <div class="col-lg-8 mb-4">
                <div class="image-gallery">
                    <img src="<?php echo htmlspecialchars($images[0]); ?>"
                         class="main-image w-100"
                         id="mainImage"
                         alt="<?php echo htmlspecialchars($minibus['name']); ?>">

                    <?php if(count($images) > 1): ?>
                    <div class="thumbnail-container">
                        <?php foreach($images as $index => $image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>"
                             class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                             onclick="changeMainImage(this.src, this)"
                             alt="Thumbnail <?php echo $index + 1; ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Additional Information -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-primary">
                                    <i class="bi bi-info-circle me-2"></i>Vehicle Information
                                </h5>
                                <div class="feature-item">
                                    <i class="bi bi-calendar feature-icon"></i>
                                    <span>Added: <?php echo date('M d, Y', strtotime($minibus['created_at'])); ?></span>
                                </div>
                                <div class="feature-item">
                                    <i class="bi bi-gear feature-icon"></i>
                                    <span>Status: <?php echo ucfirst($minibus['status']); ?></span>
                                </div>
                                <div class="feature-item">
                                    <i class="bi bi-shield-check feature-icon"></i>
                                    <span>Fully Insured & Licensed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-primary">
                                    <i class="bi bi-star me-2"></i>Why Choose This Vehicle
                                </h5>
                                <div class="feature-item">
                                    <i class="bi bi-check-circle feature-icon"></i>
                                    <span>Regular Maintenance</span>
                                </div>
                                <div class="feature-item">
                                    <i class="bi bi-check-circle feature-icon"></i>
                                    <span>Professional Driver</span>
                                </div>
                                <div class="feature-item">
                                    <i class="bi bi-check-circle feature-icon"></i>
                                    <span>24/7 Support</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Info -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <!-- Price Card -->
                    <div class="price-display">
                        <div class="price-amount">TZS <?php echo number_format($minibus['price_per_km']); ?></div>
                        <div class="price-unit">per kilometer</div>
                        <div class="mt-2">
                            <span class="badge bg-<?php echo $minibus['status'] === 'available' ? 'success' : 'warning'; ?> status-badge">
                                <i class="bi bi-<?php echo $minibus['status'] === 'available' ? 'check-circle' : 'clock'; ?> me-1"></i>
                                <?php echo ucfirst($minibus['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Vehicle Details Card -->
                    <div class="card info-card mb-4">
                        <div class="card-body">
                            <h4 class="card-title text-primary mb-4">
                                <i class="bi bi-truck me-2"></i><?php echo htmlspecialchars($minibus['name']); ?>
                            </h4>

                            <div class="mb-4">
                                <h6 class="text-muted mb-3">SPECIFICATIONS</h6>
                                <div class="feature-item">
                                    <i class="bi bi-people feature-icon"></i>
                                    <span><strong>Capacity:</strong> <?php echo htmlspecialchars($minibus['capacity']); ?> passengers</span>
                                </div>
                            </div>

                            <?php if($minibus['driver_name']): ?>
                            <div class="driver-info">
                                <h6 class="mb-3">
                                    <i class="bi bi-person-badge me-2"></i>Your Driver
                                </h6>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                        <i class="bi bi-person fs-4 text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($minibus['driver_name']); ?></div>
                                        <?php if($minibus['driver_phone']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($minibus['driver_phone']); ?>"
                                           class="text-white text-decoration-none">
                                            <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($minibus['driver_phone']); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if(!empty($features)): ?>
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">FEATURES & AMENITIES</h6>
                                <?php foreach($features as $feature): ?>
                                <div class="feature-item">
                                    <i class="bi bi-check-circle-fill feature-icon"></i>
                                    <span><?php echo htmlspecialchars($feature); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2">
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="book.php?id=<?php echo $minibus['id']; ?>" class="btn btn-primary btn-lg">
                                        <i class="bi bi-calendar-plus me-2"></i>Book This Vehicle
                                    </a>
                                    <a href="tel:+255683749514" class="btn btn-outline-primary">
                                        <i class="bi bi-telephone me-2"></i>Call for Inquiry
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary btn-lg">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Book
                                    </a>
                                    <a href="register.php" class="btn btn-outline-primary">
                                        <i class="bi bi-person-plus me-2"></i>Create Account
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Card -->
                    <div class="card info-card">
                        <div class="card-body text-center">
                            <h6 class="text-primary mb-3">Need Help?</h6>
                            <p class="text-muted mb-3">Our team is here to assist you with your booking</p>
                            <div class="d-grid gap-2">
                                <a href="tel:+255683749514" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-telephone me-2"></i>+255 683749514
                                </a>
                                <a href="mailto:info@safariminibus.co.tz" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-envelope me-2"></i>Email Us
                                </a>
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
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li class="mb-2"><a href="bookings.php"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
                            <li class="mb-2"><a href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <?php else: ?>
                            <li class="mb-2"><a href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a></li>
                            <li class="mb-2"><a href="register.php"><i class="bi bi-person-plus me-2"></i>Register</a></li>
                        <?php endif; ?>
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

        // Image gallery functionality
        function changeMainImage(src, thumbnail) {
            document.getElementById('mainImage').src = src;

            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }

        // Add loading state to booking buttons
        document.querySelectorAll('a[href*="book.php"]').forEach(button => {
            button.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Loading...';
                this.style.pointerEvents = 'none';

                // Re-enable after 3 seconds in case of issues
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                }, 3000);
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe cards for animation
        document.querySelectorAll('.info-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html>