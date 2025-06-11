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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/images/about-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .mission-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .values-section {
            padding: 80px 0;
        }
        .value-card {
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        .value-card:hover {
            transform: translateY(-10px);
        }
        .value-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .team-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .team-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .team-card:hover {
            transform: translateY(-10px);
        }
        .team-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            object-fit: cover;
        }
        .stats-section {
            padding: 60px 0;
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: white;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .contact-section {
            padding: 80px 0;
        }
        .contact-card {
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: 100%;
        }
        .contact-icon {
            font-size: 2rem;
            color: #0d6efd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Safari Minibus Rentals</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Our Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About Us</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">My Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 mb-4">About Safari Minibus Rentals</h1>
            <p class="lead">Your trusted partner for comfortable and reliable transportation in Tanzania</p>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="mb-4">Our Mission</h2>
                    <p class="lead">To provide safe, reliable, and comfortable transportation solutions that connect people and places across Tanzania.</p>
                    <p>At Safari Minibus Rentals, we understand that transportation is more than just getting from point A to point B. It's about creating memorable experiences, ensuring safety, and providing comfort throughout your journey.</p>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/download.jfif" alt="Our Mission" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <h2 class="text-center mb-5">Our Core Values</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="value-card">
                        <i class="bi bi-shield-check value-icon"></i>
                        <h3>Safety First</h3>
                        <p>We prioritize the safety of our passengers and drivers above all else.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <i class="bi bi-star value-icon"></i>
                        <h3>Quality Service</h3>
                        <p>We are committed to providing exceptional service and comfort.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <i class="bi bi-heart value-icon"></i>
                        <h3>Customer Focus</h3>
                        <p>We put our customers at the heart of everything we do.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Modern Minibuses</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">Successful Trips</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Customer Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <h2 class="text-center mb-5">Get in Touch</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="contact-card">
                        <i class="bi bi-geo-alt contact-icon"></i>
                        <h4>Visit Us</h4>
                        <p>123 Safari Street<br>Dar es Salaam, Tanzania</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-card">
                        <i class="bi bi-telephone contact-icon"></i>
                        <h4>Call Us</h4>
                        <p>+255 683749514<br>+255 673601014</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-card">
                        <i class="bi bi-envelope contact-icon"></i>
                        <h4>Email Us</h4>
                        <p>info@safariminibus.co.tz<br>support@safariminibus.co.tz</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p>Phone: +255 683749514<br>
                    Email: info@safariminibus.co.tz</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-light">About Us</a></li>
                        <li><a href="services.php" class="text-light">Our Services</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 