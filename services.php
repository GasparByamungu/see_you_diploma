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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
                        <a class="nav-link active" href="services.php">Our Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
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

    <!-- Services Content -->
    <div class="container my-5">
        <h1 class="text-center mb-5">Our Services</h1>

        <div class="row">
            <!-- Airport Transfer -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-airplane fs-1 text-primary"></i>
                        </div>
                        <h3 class="card-title text-center">Airport Transfer</h3>
                        <p class="card-text">
                            Reliable airport transfer services to and from all major airports in Tanzania. Our professional drivers ensure timely pickups and drop-offs.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success"></i> 24/7 Service</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Flight Tracking</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Meet & Greet</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- City Tours -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-building fs-1 text-primary"></i>
                        </div>
                        <h3 class="card-title text-center">City Tours</h3>
                        <p class="card-text">
                            Explore Tanzania's vibrant cities with our comfortable minibuses. Perfect for group tours and city sightseeing.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Custom Routes</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Professional Guides</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Flexible Duration</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Safari Tours -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-tree fs-1 text-primary"></i>
                        </div>
                        <h3 class="card-title text-center">Safari Tours</h3>
                        <p class="card-text">
                            Experience the wild beauty of Tanzania with our safari-ready minibuses. Perfect for wildlife viewing and nature exploration.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success"></i> All Major Parks</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Experienced Drivers</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Custom Packages</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Corporate Transport -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-briefcase fs-1 text-primary"></i>
                        </div>
                        <h3 class="card-title text-center">Corporate Transport</h3>
                        <p class="card-text">
                            Professional transportation services for corporate events, meetings, and employee transport.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Business Events</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Employee Shuttle</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Long-term Contracts</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Wedding Transport -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-heart fs-1 text-primary"></i>
                        </div>
                        <h3 class="card-title text-center">Wedding Transport</h3>
                        <p class="card-text">
                            Make your special day even more memorable with our wedding transportation services.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Guest Transport</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Decorated Vehicles</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Professional Service</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Custom Tours -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-geo-alt fs-1 text-primary"></i>
                        </div>
                        <h3 class="card-title text-center">Custom Tours</h3>
                        <p class="card-text">
                            Create your own adventure with our customizable tour packages. Perfect for special occasions and unique experiences.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Tailored Routes</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Flexible Duration</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Special Events</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

       

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