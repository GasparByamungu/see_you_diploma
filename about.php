<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Safari Minibus Rentals</title>
    <meta name="description" content="Learn about Safari Minibus Rentals - Tanzania's premier transportation service. Our mission, values, and commitment to excellence.">
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
                        <a class="nav-link" href="services.php">
                            <i class="bi bi-gear me-1"></i>Our Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">
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
                        About <span class="text-accent">Safari Minibus Rentals</span>
                    </h1>
                    <p class="lead mb-4">
                        Your trusted partner for comfortable and reliable transportation across Tanzania.
                        Connecting people, places and experiences since our founding.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-4 mb-4">
                        <div class="d-flex align-items-center text-white">
                            <i class="bi bi-award fs-4 me-2 text-accent"></i>
                            <span>Award Winning Service</span>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <i class="bi bi-people fs-4 me-2 text-accent"></i>
                            <span>500+ Happy Customers</span>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <i class="bi bi-calendar-check fs-4 me-2 text-accent"></i>
                            <span>1000+ Successful Trips</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="pe-lg-4">
                        <h2 class="display-5 fw-bold text-primary mb-4">
                            <i class="bi bi-bullseye me-3"></i>Our Mission
                        </h2>
                        <p class="lead text-secondary mb-4">
                            To provide safe, reliable and comfortable transportation solutions that connect people and places across Tanzania.
                        </p>
                        <p class="mb-4">
                            At Safari Minibus Rentals, we understand that transportation is more than just getting from point A to point B.
                            It's about creating memorable experiences, ensuring safety, and providing comfort throughout your journey.
                        </p>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                        <i class="bi bi-shield-check text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Safety First</h6>
                                        <small class="text-muted">Your security is our priority</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                        <i class="bi bi-clock text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">On Time</h6>
                                        <small class="text-muted">Punctual and reliable service</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative">
                        <img src="assets/images/minibuses/1.jpg" alt="Our Mission" class="img-fluid rounded-custom shadow-custom-lg">
                        <div class="position-absolute top-0 start-0 bg-primary text-white p-3 rounded-custom m-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-star-fill me-2"></i>
                                <div>
                                    <div class="fw-bold">4.9/5</div>
                                    <small>Customer Rating</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold text-primary mb-3">
                        <i class="bi bi-gem me-3"></i>Our Core Values
                    </h2>
                    <p class="lead text-muted">
                        The principles that guide our service and define our commitment to excellence
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-custom text-center">
                        <div class="card-body p-4">
                            <div class="bg-primary-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="bi bi-shield-check fs-1 text-primary"></i>
                            </div>
                            <h4 class="fw-bold text-primary mb-3">Safety First</h4>
                            <p class="text-muted">
                                We prioritize the safety of our passengers and drivers above all else.
                                Our vehicles undergo regular maintenance and safety inspections.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-custom text-center">
                        <div class="card-body p-4">
                            <div class="bg-secondary-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="bi bi-star fs-1 text-secondary"></i>
                            </div>
                            <h4 class="fw-bold text-primary mb-3">Quality Service</h4>
                            <p class="text-muted">
                                We are committed to providing exceptional service and comfort.
                                Every journey with us is designed to exceed your expectations.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mx-auto">
                    <div class="card h-100 border-0 shadow-custom text-center">
                        <div class="card-body p-4">
                            <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="bi bi-heart fs-1 text-white"></i>
                            </div>
                            <h4 class="fw-bold text-primary mb-3">Customer Focus</h4>
                            <p class="text-muted">
                                We put our customers at the heart of everything we do.
                                Your satisfaction and comfort are our ultimate goals.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5" style="background: var(--gradient-primary);">
        <div class="container">
            <div class="row text-center text-white">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <i class="bi bi-people display-4 mb-3"></i>
                        <div class="stat-number display-4 fw-bold">500+</div>
                        <div class="stat-label fs-5">Happy Customers</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <i class="bi bi-truck display-4 mb-3"></i>
                        <div class="stat-number display-4 fw-bold">50+</div>
                        <div class="stat-label fs-5">Modern Minibuses</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <i class="bi bi-geo-alt display-4 mb-3"></i>
                        <div class="stat-number display-4 fw-bold">1000+</div>
                        <div class="stat-label fs-5">Successful Trips</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <i class="bi bi-headset display-4 mb-3"></i>
                        <div class="stat-number display-4 fw-bold">24/7</div>
                        <div class="stat-label fs-5">Customer Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold text-primary mb-3">
                        <i class="bi bi-telephone me-3"></i>Get in Touch
                    </h2>
                    <p class="lead text-muted">
                        Ready to start your journey? Contact us today and let us help you plan your perfect trip
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-custom text-center">
                        <div class="card-body p-4">
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                                <i class="bi bi-geo-alt fs-3 text-white"></i>
                            </div>
                            <h4 class="fw-bold text-primary mb-3">Visit Us</h4>
                            <p class="text-muted mb-0">
                                123 Safari Street<br>
                                Dar es Salaam, Tanzania<br>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-custom text-center">
                        <div class="card-body p-4">
                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                                <i class="bi bi-telephone fs-3 text-white"></i>
                            </div>
                            <h4 class="fw-bold text-primary mb-3">Call Us</h4>
                            <p class="text-muted mb-0">
                                <a href="tel:+255683749514" class="text-decoration-none">+255 683749514</a><br>
                                <a href="tel:+255673601014" class="text-decoration-none">+255 673601014</a><br>
                                <small class="text-primary">Available 24/7</small>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mx-auto">
                    <div class="card h-100 border-0 shadow-custom text-center">
                        <div class="card-body p-4">
                            <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                                <i class="bi bi-envelope fs-3 text-white"></i>
                            </div>
                            <h4 class="fw-bold text-primary mb-3">Email Us</h4>
                            <p class="text-muted mb-0">
                                <a href="mailto:info@safariminibus.co.tz" class="text-decoration-none">info@safariminibus.co.tz</a><br>
                                <a href="mailto:support@safariminibus.co.tz" class="text-decoration-none">support@safariminibus.co.tz</a><br>
                                <small class="text-primary">Quick Response</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="row mt-5">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="card border-0 shadow-custom-lg" style="background: var(--gradient-secondary);">
                        <div class="card-body p-5 text-white">
                            <h3 class="fw-bold mb-3">Ready to Book Your Journey?</h3>
                            <p class="mb-4">Join hundreds of satisfied customers who trust us for their transportation needs</p>
                            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                                <a href="index.php" class="btn btn-light btn-lg">
                                    <i class="bi bi-search me-2"></i>Find Minibuses
                                </a>
                                <a href="tel:+255683749514" class="btn btn-outline-light btn-lg">
                                    <i class="bi bi-telephone me-2"></i>Call Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(number => {
                        if (!number.classList.contains('animated')) {
                            number.classList.add('animated');
                            // Simplified animation - just add a CSS class instead of complex JS
                            number.style.transform = 'scale(1.1)';
                            setTimeout(() => {
                                number.style.transform = 'scale(1)';
                            }, 300);
                        }
                    });
                    observer.unobserve(entry.target); // Stop observing once animated
                }
            });
        }, observerOptions);

        // Observe stats section
        const statsSection = document.querySelector('[style*="gradient-primary"]');
        if (statsSection) {
            observer.observe(statsSection);
        }

        // Add smooth scrolling to anchor links
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
    </script>
</body>
</html>