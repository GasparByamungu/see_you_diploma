<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safari Minibus Rentals - Tanzania's Premier Minibus Service</title>
    <meta name="description" content="Experience Tanzania with our premium minibus rental service. Comfortable, reliable, and affordable transportation for your safari adventures.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                        <a class="nav-link active" href="index.php">
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

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-3 fw-bold mb-4">
                        Explore Tanzania with
                        <span class="text-accent">Safari Minibus Rentals</span>
                    </h1>
                    <p class="lead mb-4">
                        Your trusted partner for comfortable and reliable minibus transportation.
                        Experience the beauty of Tanzania with our premium fleet and professional drivers.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                        <div class="d-flex align-items-center text-white">
                            <i class="bi bi-shield-check fs-4 me-2 text-accent"></i>
                            <span>Safe & Reliable</span>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <i class="bi bi-people fs-4 me-2 text-accent"></i>
                            <span>Professional Drivers</span>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <i class="bi bi-geo-alt fs-4 me-2 text-accent"></i>
                            <span>All Tanzania</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <form action="search.php" method="GET" class="search-form">
                        <div class="row align-items-center">
                            <div class="col-12 mb-3">
                                <h3 class="text-center mb-0 text-primary">
                                    <i class="bi bi-search me-2"></i>Find Your Perfect Minibus
                                </h3>
                                <p class="text-center text-muted mb-0">Choose your travel date and group size</p>
                            </div>
                        </div>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="search-date" class="form-label">
                                    <i class="bi bi-calendar3 me-1"></i>Travel Date
                                </label>
                                <input type="date" class="form-control form-control-lg" id="search-date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="search-passengers" class="form-label">
                                    <i class="bi bi-people me-1"></i>Number of Passengers
                                </label>
                                <select class="form-select form-select-lg" id="search-passengers" name="passengers" required>
                                    <option value="">Select passenger count</option>
                                    <option value="1-10">1-10 Passengers</option>
                                    <option value="11-20">11-20 Passengers</option>
                                    <option value="21-25">21-25 Passengers</option>
                                    <option value="26-30">26-30 Passengers</option>
                                    <option value="31-35">31-35 Passengers</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-search me-2"></i>Search Minibuses
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Minibuses Section -->
    <div class="container my-5">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold text-primary mb-3">
                    <i class="bi bi-truck me-3"></i>Our Premium Fleet
                </h2>
                <p class="lead text-muted">
                    Choose from our carefully maintained fleet of modern minibuses,
                    each equipped with comfort features for your journey across Tanzania.
                </p>
            </div>
        </div>

        <div class="row" id="minibus-list">
            <?php
            $stmt = $pdo->query("
                SELECT m.*, GROUP_CONCAT(mi.image_path ORDER BY mi.display_order) as images
                FROM minibuses m
                LEFT JOIN minibus_images mi ON m.id = mi.minibus_id
                WHERE m.status = 'available'
                GROUP BY m.id
                ORDER BY m.created_at DESC
            ");
            while($minibus = $stmt->fetch(PDO::FETCH_ASSOC)):
                $images = $minibus['images'] ? explode(',', $minibus['images']) : ['assets/images/default-minibus.jpg'];
                $features = json_decode($minibus['features'] ?? '[]', true);
            ?>
            <div class="col-lg-4 col-md-6 mb-4 fade-in-up">
                <div class="card minibus-card h-100">
                    <div class="position-relative">
                        <div id="minibusCarousel<?php echo $minibus['id']; ?>" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach($images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($minibus['name']); ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if(count($images) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#minibusCarousel<?php echo $minibus['id']; ?>" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#minibusCarousel<?php echo $minibus['id']; ?>" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="capacity-badge">
                                <i class="bi bi-people me-1"></i><?php echo $minibus['capacity']; ?> Seats
                            </span>
                        </div>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-truck me-2 text-primary"></i>
                            <?php echo htmlspecialchars($minibus['name']); ?>
                        </h5>

                        <div class="price-display mb-3">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="bi bi-currency-exchange me-2"></i>
                                <span>TZS <?php echo number_format($minibus['price_per_km']); ?></span>
                                <small class="ms-1">per km</small>
                            </div>
                        </div>

                        <?php if(!empty($features)): ?>
                        <div class="features-list mb-3">
                            <?php foreach(array_slice($features, 0, 4) as $feature): ?>
                                <span class="feature-tag">
                                    <i class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($feature); ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if(count($features) > 4): ?>
                                <span class="feature-tag">
                                    <i class="bi bi-plus-circle me-1"></i>+<?php echo count($features) - 4; ?> more
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-auto">
                            <div class="d-grid gap-2">
                                <a href="view_minibus.php?id=<?php echo $minibus['id']; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-eye me-2"></i>View Details
                                </a>
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="book.php?id=<?php echo $minibus['id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-calendar-plus me-2"></i>Book Now
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Book
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <?php if($pdo->query("SELECT COUNT(*) FROM minibuses WHERE status = 'available'")->fetchColumn() == 0): ?>
        <div class="row">
            <div class="col-12 text-center py-5">
                <div class="card bg-light">
                    <div class="card-body py-5">
                        <i class="bi bi-truck display-1 text-muted mb-3"></i>
                        <h3 class="text-muted">No Minibuses Available</h3>
                        <p class="text-muted">Please check back later or contact us for availability.</p>
                        <a href="about.php" class="btn btn-primary">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <di class="row">
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

        // Set minimum date for search
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('search-date');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('min', today);
                dateInput.value = today;
            }
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

        // Observe all fade-in-up elements
        document.querySelectorAll('.fade-in-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>