<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Safari Minibus Rentals</title>
    <meta name="description" content="Discover our comprehensive transportation services including airport transfers, safari tours, city tours, and corporate transport across Tanzania.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
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
                        <a class="nav-link active" href="services.php">
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
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-3 fw-bold mb-4">
                        Our <span class="text-accent">Premium Services</span>
                    </h1>
                    <p class="lead mb-4">
                        Comprehensive transportation solutions tailored to meet all your travel needs across Tanzania.
                        From airport transfers to safari adventures, we've got you covered.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Content -->
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold text-primary mb-3">
                    <i class="bi bi-gear me-3"></i>What We Offer
                </h2>
                <p class="lead text-muted">
                    Professional transportation services designed to provide comfort, safety, and reliability for every journey
                </p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Airport Transfer -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-custom service-card">
                    <div class="card-body p-4 text-center">
                        <div class="bg-primary-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-airplane fs-1 text-primary"></i>
                        </div>
                        <h4 class="fw-bold text-primary mb-3">Airport Transfer</h4>
                        <p class="text-muted mb-4">
                            Reliable airport transfer services to and from all major airports in Tanzania. Our professional drivers ensure timely pickups and drop-offs.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> 24/7 Service Available</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Real-time Flight Tracking</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Meet & Greet Service</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Luggage Assistance</li>
                        </ul>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- City Tours -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-custom service-card">
                    <div class="card-body p-4 text-center">
                        <div class="bg-secondary-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-building fs-1 text-secondary"></i>
                        </div>
                        <h4 class="fw-bold text-primary mb-3">City Tours</h4>
                        <p class="text-muted mb-4">
                            Explore Tanzania's vibrant cities with our comfortable minibuses. Perfect for group tours and city sightseeing adventures.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Custom Route Planning</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Professional Tour Guides</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Flexible Duration</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Group Discounts</li>
                        </ul>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Safari Tours -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-custom service-card">
                    <div class="card-body p-4 text-center">
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-tree fs-1 text-white"></i>
                        </div>
                        <h4 class="fw-bold text-primary mb-3">Safari Tours</h4>
                        <p class="text-muted mb-4">
                            Experience the wild beauty of Tanzania with our safari-ready minibuses. Perfect for wildlife viewing and nature exploration.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> All Major National Parks</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Experienced Safari Drivers</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Custom Safari Packages</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Wildlife Photography Support</li>
                        </ul>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Corporate Transport -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-custom service-card">
                    <div class="card-body p-4 text-center">
                        <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-briefcase fs-1 text-white"></i>
                        </div>
                        <h4 class="fw-bold text-primary mb-3">Corporate Transport</h4>
                        <p class="text-muted mb-4">
                            Professional transportation services for corporate events, meetings, and employee transport solutions.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Business Event Transport</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Employee Shuttle Service</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Long-term Contracts</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Executive Transportation</li>
                        </ul>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wedding Transport -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-custom service-card">
                    <div class="card-body p-4 text-center">
                        <div class="bg-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-heart fs-1 text-white"></i>
                        </div>
                        <h4 class="fw-bold text-primary mb-3">Wedding Transport</h4>
                        <p class="text-muted mb-4">
                            Make your special day even more memorable with our elegant wedding transportation services.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Guest Transportation</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Decorated Vehicles</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Professional Service</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Wedding Day Coordination</li>
                        </ul>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Tours -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-custom service-card">
                    <div class="card-body p-4 text-center">
                        <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-geo-alt fs-1 text-white"></i>
                        </div>
                        <h4 class="fw-bold text-primary mb-3">Custom Tours</h4>
                        <p class="text-muted mb-4">
                            Create your own adventure with our customizable tour packages. Perfect for special occasions and unique experiences.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Tailored Route Planning</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Flexible Duration Options</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Special Event Support</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Personal Tour Guide</li>
                        </ul>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-primary">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="row mt-5">
            <div class="col-lg-10 mx-auto">
                <div class="card border-0 shadow-custom-lg" style="background: var(--gradient-primary);">
                    <div class="card-body p-5 text-white text-center">
                        <h3 class="fw-bold mb-3">Need a Custom Solution?</h3>
                        <p class="mb-4 fs-5">
                            Can't find exactly what you're looking for? We specialize in creating custom transportation solutions
                            tailored to your specific needs and requirements.
                        </p>
                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                            <a href="tel:+255683749514" class="btn btn-light btn-lg">
                                <i class="bi bi-telephone me-2"></i>Call Us Now
                            </a>
                            <a href="mailto:info@safariminibus.co.tz" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-envelope me-2"></i>Email Us
                            </a>
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

        // Service card hover effects
        document.querySelectorAll('.service-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
                this.style.transition = 'all 0.3s ease';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
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

        // Observe all service cards
        document.querySelectorAll('.service-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html>