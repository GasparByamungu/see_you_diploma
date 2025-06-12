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
    SELECT m.*, GROUP_CONCAT(mi.image_path ORDER BY mi.display_order) as images
    FROM minibuses m 
    LEFT JOIN minibus_images mi ON m.id = mi.minibus_id 
    WHERE m.capacity BETWEEN ? AND ?
    AND m.id NOT IN (
        SELECT minibus_id 
        FROM bookings 
        WHERE start_date = ? 
        AND status != 'cancelled'
    )
    GROUP BY m.id
");
$stmt->execute([$min_passengers, $max_passengers, $date]);
$minibuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
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

    <!-- Search Results -->
    <div class="container py-5">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h4 class="mb-3 text-primary">
                        <i class="bi bi-funnel me-2"></i>Refine Your Search
                    </h4>
                    <form method="GET" action="search.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="date" class="form-label fw-semibold">
                                <i class="bi bi-calendar-date me-2 text-primary"></i>Travel Date
                            </label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?php echo htmlspecialchars($date); ?>"
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="passengers" class="form-label fw-semibold">
                                <i class="bi bi-people me-2 text-primary"></i>Passengers
                            </label>
                            <select class="form-select" id="passengers" name="passengers" required>
                                <option value="1-7" <?php echo $passengers === '1-7' ? 'selected' : ''; ?>>1-7 passengers</option>
                                <option value="8-14" <?php echo $passengers === '8-14' ? 'selected' : ''; ?>>8-14 passengers</option>
                                <option value="15-25" <?php echo $passengers === '15-25' ? 'selected' : ''; ?>>15-25 passengers</option>
                                <option value="26-50" <?php echo $passengers === '26-50' ? 'selected' : ''; ?>>26+ passengers</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Update Search
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="search-stats">
                        <div class="d-flex justify-content-lg-end justify-content-center gap-4 mt-3 mt-lg-0">
                            <div class="text-center">
                                <div class="fw-bold fs-4 text-primary"><?php echo count($minibuses); ?></div>
                                <small class="text-muted">Available</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold fs-4 text-success">
                                    <?php echo count($minibuses) > 0 ? min(array_column($minibuses, 'price_per_km')) : '0'; ?>
                                </div>
                                <small class="text-muted">From TZS/km</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($minibuses)): ?>
            <div class="no-results">
                <i class="bi bi-search display-1 text-muted mb-4"></i>
                <h3 class="text-primary mb-3">No Minibuses Available</h3>
                <p class="text-muted mb-4 lead">
                    We couldn't find any available minibuses for <strong><?php echo date('F j, Y', strtotime($date)); ?></strong>
                    with capacity for <strong><?php echo htmlspecialchars($passengers); ?> passengers</strong>.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>Try Different Search
                    </a>
                    <a href="tel:+255683749514" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-telephone me-2"></i>Call for Assistance
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Results Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="mb-3 mb-md-0">
                            <h3 class="text-primary fw-bold mb-2">
                                <i class="bi bi-truck me-2"></i>Available Minibuses
                            </h3>
                            <p class="text-muted mb-0">
                                Showing <?php echo count($minibuses); ?> available minibus<?php echo count($minibuses) !== 1 ? 'es' : ''; ?>
                                for your selected criteria
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="toggleView('grid')" id="gridViewBtn">
                                <i class="bi bi-grid-3x3-gap me-1"></i>Grid
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="toggleView('list')" id="listViewBtn">
                                <i class="bi bi-list me-1"></i>List
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Minibus Grid -->
            <div class="row" id="minibusGrid">
                <?php foreach ($minibuses as $minibus):
                    $images = $minibus['images'] ? explode(',', $minibus['images']) : ['assets/images/default-minibus.jpg'];
                ?>
                <div class="col-lg-4 col-md-6 mb-4 minibus-item">
                    <div class="card minibus-card h-100">
                        <!-- Image Section -->
                        <div class="position-relative">
                            <?php if(count($images) > 1): ?>
                            <div id="minibusCarousel<?php echo $minibus['id']; ?>" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <?php foreach($images as $index => $image): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($image); ?>"
                                             class="card-img-top"
                                             alt="<?php echo htmlspecialchars($minibus['name']); ?>"
                                             style="height: 250px; object-fit: cover;">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#minibusCarousel<?php echo $minibus['id']; ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#minibusCarousel<?php echo $minibus['id']; ?>" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                            <?php else: ?>
                            <img src="<?php echo htmlspecialchars($images[0]); ?>"
                                 class="card-img-top"
                                 alt="<?php echo htmlspecialchars($minibus['name']); ?>"
                                 style="height: 250px; object-fit: cover;">
                            <?php endif; ?>

                            <!-- Badges -->
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="capacity-badge">
                                    <i class="bi bi-people me-1"></i><?php echo htmlspecialchars($minibus['capacity']); ?> seats
                                </span>
                            </div>
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="price-badge">
                                    TZS <?php echo number_format($minibus['price_per_km']); ?>/km
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary fw-bold mb-3">
                                <i class="bi bi-truck me-2"></i><?php echo htmlspecialchars($minibus['name']); ?>
                            </h5>

                            <div class="mb-3 flex-grow-1">
                                <div class="row g-2 text-muted">
                                    <div class="col-6">
                                        <small class="d-flex align-items-center">
                                            <i class="bi bi-people me-2"></i>
                                            <?php echo htmlspecialchars($minibus['capacity']); ?> passengers
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="d-flex align-items-center">
                                            <i class="bi bi-shield-check me-2"></i>
                                            Available
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="d-flex align-items-center">
                                            <i class="bi bi-geo-alt me-2"></i>
                                            All Tanzania
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="d-flex align-items-center">
                                            <i class="bi bi-star me-2"></i>
                                            Premium
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <a href="view_minibus.php?id=<?php echo $minibus['id']; ?>"
                                           class="btn btn-outline-primary w-100">
                                            <i class="bi bi-eye me-1"></i>Details
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <?php if(isset($_SESSION['user_id'])): ?>
                                            <a href="book.php?id=<?php echo $minibus['id']; ?>&date=<?php echo urlencode($date); ?>"
                                               class="btn btn-primary w-100">
                                                <i class="bi bi-calendar-plus me-1"></i>Book
                                            </a>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-primary w-100">
                                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Call to Action -->
            <div class="row mt-5">
                <div class="col-lg-10 mx-auto">
                    <div class="card border-0 shadow-custom-lg" style="background: var(--gradient-secondary);">
                        <div class="card-body p-5 text-white text-center">
                            <h3 class="fw-bold mb-3">Need Help Choosing?</h3>
                            <p class="mb-4 fs-5">
                                Our travel experts are here to help you find the perfect minibus for your journey.
                                Get personalized recommendations and special offers.
                            </p>
                            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                                <a href="tel:+255683749514" class="btn btn-light btn-lg">
                                    <i class="bi bi-telephone me-2"></i>Call Expert: +255 683749514
                                </a>
                                <a href="mailto:info@safariminibus.co.tz" class="btn btn-outline-light btn-lg">
                                    <i class="bi bi-envelope me-2"></i>Email Us
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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