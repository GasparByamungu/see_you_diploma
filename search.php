<?php
session_start();
require_once 'config/database.php';

// Get search parameters
$date = $_GET['date'] ?? '';
$passengers = $_GET['passengers'] ?? '';

// Validate date
if (empty($date)) {
    header("Location: index.php");
    exit();
}

// Parse passenger range
$passenger_range = explode('-', $passengers);
$min_passengers = (int)$passenger_range[0];
$max_passengers = (int)$passenger_range[1];

// Get available minibuses for the selected date and capacity
$stmt = $pdo->prepare("
    SELECT m.id, m.name, m.capacity, m.price_per_km, m.features, m.status, m.driver_id, m.created_at
    FROM minibuses m
    WHERE m.capacity BETWEEN ? AND ?
    AND m.id NOT IN (
        SELECT minibus_id
        FROM bookings
        WHERE start_date = ?
        AND status != 'cancelled'
    )
    ORDER BY m.created_at DESC
");
$stmt->execute([$min_passengers, $max_passengers, $date]);
$minibuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get images for all minibuses in one query to avoid N+1 problem
if (!empty($minibuses)) {
    $minibus_ids = array_column($minibuses, 'id');
    $placeholders = str_repeat('?,', count($minibus_ids) - 1) . '?';
    $images_stmt = $pdo->prepare("
        SELECT minibus_id, image_path
        FROM minibus_images
        WHERE minibus_id IN ($placeholders)
        ORDER BY minibus_id, display_order
    ");
    $images_stmt->execute($minibus_ids);
    $all_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group images by minibus_id
    $images_by_minibus = [];
    foreach ($all_images as $img) {
        $images_by_minibus[$img['minibus_id']][] = $img['image_path'];
    }

    // Add images to each minibus
    foreach ($minibuses as &$minibus) {
        $images = $images_by_minibus[$minibus['id']] ?? [];
        $minibus['images'] = implode(',', $images);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Safari Minibus Rentals</title>
    <meta name="description" content="Browse available minibuses for <?php echo date('F j, Y', strtotime($date)); ?> with capacity for <?php echo htmlspecialchars($passengers); ?> passengers.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <style>
        .search-summary {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .search-summary h2 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .search-criteria {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-top: 1rem;
        }

        .criteria-item {
            display: flex;
            align-items-center;
            margin-bottom: 0.5rem;
        }

        .criteria-item:last-child {
            margin-bottom: 0;
        }

        .criteria-icon {
            font-size: 1.2rem;
            margin-right: 0.75rem;
            min-width: 20px;
        }

        .minibus-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: none;
            overflow: hidden;
            transition: all var(--transition-normal);
            height: 100%;
        }

        .minibus-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .price-badge {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .capacity-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .hero-section {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            margin-top: 80px;
        }

        .filter-section {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
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
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="index.php" class="text-white text-decoration-none">
                                    <i class="bi bi-house me-1"></i>Home
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                Search Results
                            </li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-search me-3"></i>Available Minibuses
                    </h1>
                    <p class="lead mb-0">
                        Find the perfect minibus for your journey across Tanzania
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="search-criteria">
                        <div class="criteria-item">
                            <i class="bi bi-calendar-date criteria-icon"></i>
                            <span><?php echo date('F j, Y', strtotime($date)); ?></span>
                        </div>
                        <div class="criteria-item">
                            <i class="bi bi-people criteria-icon"></i>
                            <span><?php echo htmlspecialchars($passengers); ?> passengers</span>
                        </div>
                        <div class="criteria-item">
                            <i class="bi bi-check-circle criteria-icon"></i>
                            <span><?php echo count($minibuses); ?> available</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Search Results -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-custom">
                    <div class="card-body p-4">
                        <h4 class="mb-4">
                            <i class="bi bi-search me-2 text-primary"></i>Search Results
                            <?php if (!empty($date)): ?>
                                for <?php echo date('M d, Y', strtotime($date)); ?>
                            <?php endif; ?>
                        </h4>

                        <?php if (empty($minibuses)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-search display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">No Minibuses Found</h5>
                                <p class="text-muted">Try adjusting your search criteria or browse our available minibuses.</p>
                                <a href="index.php" class="btn btn-primary mt-3">
                                    <i class="bi bi-house me-2"></i>Back to Home
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($minibuses as $minibus): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="card minibus-card h-100">
                                            <div class="row g-0">
                                                <div class="col-md-4">
                                                    <img src="<?php echo htmlspecialchars($minibus['images']); ?>" 
                                                         class="img-fluid rounded-start h-100 object-fit-cover" 
                                                         alt="<?php echo htmlspecialchars($minibus['name']); ?>">
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="card-body">
                                                        <h5 class="card-title">
                                                            <?php echo htmlspecialchars($minibus['name']); ?>
                                                            <span class="badge bg-success float-end">
                                                                <?php echo $minibus['capacity']; ?> seats
                                                            </span>
                                                        </h5>
                                                        <div class="features mb-3">
                                                            <?php
                                                            $features = json_decode($minibus['features'] ?? '[]', true);
                                                            foreach ($features as $feature): ?>
                                                                <span class="badge bg-light text-dark me-2 mb-2">
                                                                    <i class="bi bi-check-circle me-1 text-success"></i>
                                                                    <?php echo htmlspecialchars($feature); ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <a href="book.php?id=<?php echo $minibus['id']; ?>&date=<?php echo urlencode($date); ?>" 
                                                               class="btn btn-primary">
                                                                <i class="bi bi-calendar-check me-2"></i>Book Now
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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

        // View toggle functionality
        function toggleView(viewType) {
            const gridBtn = document.getElementById('gridViewBtn');
            const listBtn = document.getElementById('listViewBtn');
            const minibusGrid = document.getElementById('minibusGrid');

            if (viewType === 'grid') {
                gridBtn.classList.add('btn-primary');
                gridBtn.classList.remove('btn-outline-secondary');
                listBtn.classList.add('btn-outline-secondary');
                listBtn.classList.remove('btn-primary');

                minibusGrid.className = 'row';
                document.querySelectorAll('.minibus-item').forEach(item => {
                    item.className = 'col-lg-4 col-md-6 mb-4 minibus-item';
                });
            } else {
                listBtn.classList.add('btn-primary');
                listBtn.classList.remove('btn-outline-secondary');
                gridBtn.classList.add('btn-outline-secondary');
                gridBtn.classList.remove('btn-primary');

                minibusGrid.className = 'row';
                document.querySelectorAll('.minibus-item').forEach(item => {
                    item.className = 'col-12 mb-4 minibus-item';
                });
            }
        }

        // Initialize grid view
        document.addEventListener('DOMContentLoaded', function() {
            toggleView('grid');

            // Add animation to minibus cards
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

            // Observe minibus cards for animation
            document.querySelectorAll('.minibus-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });

            // Add loading states to booking buttons
            document.querySelectorAll('a[href*="book.php"]').forEach(button => {
                button.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Loading...';
                    this.style.pointerEvents = 'none';

                    // Re-enable after 3 seconds in case of issues
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 3000);
                });
            });
        });

        // Enhanced search form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const dateInput = document.getElementById('date');
            const selectedDate = new Date(dateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                e.preventDefault();
                alert('Please select a date from today onwards.');
                dateInput.focus();
                return false;
            }
        });
    </script>
</body>
</html>