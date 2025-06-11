<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

// Get minibus details with all images
$stmt = $pdo->prepare("
    SELECT m.*, d.name as driver_name, d.phone as driver_phone,
           GROUP_CONCAT(mi.image_path ORDER BY mi.display_order) as images
    FROM minibuses m 
    LEFT JOIN drivers d ON m.driver_id = d.id
    LEFT JOIN minibus_images mi ON m.id = mi.minibus_id 
    WHERE m.id = ?
    GROUP BY m.id
");
$stmt->execute([$id]);
$minibus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$minibus) {
    header("Location: index.php");
    exit();
}

$images = $minibus['images'] ? explode(',', $minibus['images']) : ['assets/images/default-minibus.jpg'];
$features = json_decode($minibus['features'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($minibus['name']); ?> - Safari Minibus Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .image-gallery {
            position: relative;
            margin-bottom: 2rem;
        }
        .main-image {
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
        }
        .thumbnail-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .thumbnail {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .thumbnail:hover {
            opacity: 0.8;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .feature-icon {
            font-size: 1.5rem;
            color: #0d6efd;
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #198754;
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
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

    <!-- Minibus Details -->
    <div class="container my-5">
        <div class="row">
            <!-- Image Gallery -->
            <div class="col-lg-8">
                <div class="image-gallery">
                    <img src="<?php echo htmlspecialchars($images[0]); ?>" class="main-image w-100" id="mainImage" alt="<?php echo htmlspecialchars($minibus['name']); ?>">
                    <div class="thumbnail-container">
                        <?php foreach($images as $index => $image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" 
                             class="thumbnail" 
                             onclick="changeMainImage(this.src)"
                             alt="Thumbnail <?php echo $index + 1; ?>">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Minibus Info -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-3"><?php echo htmlspecialchars($minibus['name']); ?></h2>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="price-tag">TZS <?php echo number_format($minibus['price_per_km']); ?>/km</span>
                            <span class="badge bg-<?php echo $minibus['status'] === 'available' ? 'success' : 'warning'; ?> status-badge">
                                <?php echo ucfirst($minibus['status']); ?>
                            </span>
                        </div>

                        <div class="mb-4">
                            <h4>Specifications</h4>
                            <div class="feature-item">
                                <i class="bi bi-people feature-icon"></i>
                                <span>Capacity: <?php echo htmlspecialchars($minibus['capacity']); ?> passengers</span>
                            </div>
                            <?php if($minibus['driver_name']): ?>
                            <div class="feature-item driver-info p-3 bg-light rounded mb-3">
                                <i class="bi bi-person-badge feature-icon"></i>
                                <div>
                                    <h5 class="mb-2">Driver Information</h5>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($minibus['driver_name']); ?></p>
                                    <?php if($minibus['driver_phone']): ?>
                                    <p class="mb-0">
                                        <strong>Phone:</strong> 
                                        <a href="tel:<?php echo htmlspecialchars($minibus['driver_phone']); ?>" class="text-decoration-none">
                                            <i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($minibus['driver_phone']); ?>
                                        </a>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($features)): ?>
                        <div class="mb-4">
                            <h4>Features</h4>
                            <?php foreach($features as $feature): ?>
                            <div class="feature-item">
                                <i class="bi bi-check-circle-fill feature-icon"></i>
                                <span><?php echo htmlspecialchars($feature); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="book.php?id=<?php echo $minibus['id']; ?>" class="btn btn-primary btn-lg w-100">Book Now</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-lg w-100">Login to Book</a>
                        <?php endif; ?>
                    </div>
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
    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }
    </script>
</body>
</html> 