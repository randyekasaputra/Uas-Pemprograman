<?php
session_start();
require_once 'config/database.php';

// Ambil daftar trip terbaru
$stmt = $db->query("
    SELECT t.*, u.username, 
           (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as participant_count 
    FROM trips t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 6
");
$latest_trips = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMates - Temukan Teman Traveling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #6366f1 0%, #0ea5e9 100%);
            min-height: 100vh;
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.8) 0%, rgba(14, 165, 233, 0.8) 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/pattern.png') repeat;
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .content-wrapper {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            color: #6366f1;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            border-radius: 2px;
        }

        .trip-card {
            background: white;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .trip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .trip-image {
            height: 200px;
            object-fit: cover;
        }

        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .destination-card {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .destination-img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .destination-card:hover .destination-img {
            transform: scale(1.1);
        }

        .destination-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .btn-outline-gradient {
            background: transparent;
            border: 2px solid white;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: white;
            color: #6366f1;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <!-- Hero Section dengan gradient -->
    <div class="hero-section py-5">
        <div class="container">
            <div class="row align-items-center hero-content">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold text-white mb-4">Temukan Teman Traveling</h1>
                    <p class="lead text-white mb-4">Jelajahi destinasi impian bersama teman baru. Bergabung dengan TripMates sekarang!</p>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="pages/register.php" class="btn btn-gradient me-3">Daftar Sekarang</a>
                        <a href="pages/login.php" class="btn btn-outline-gradient">Login</a>
                    <?php else: ?>
                        <a href="pages/tambah-destinasi.php" class="btn btn-gradient">Buat Trip Baru</a>
                    <?php endif; ?>
                </div>
                <div class="col-md-5">
                    <img src="assets/images/travel-hero.png" class="img-fluid rounded-3 shadow" alt="Travel Hero">
                </div>
            </div>
        </div>
    </div>

    <!-- Trip Terbaru Section -->
    <div class="container">
        <div class="content-wrapper">
            <h2 class="section-title">Trip Terbaru</h2>
            <div class="row">
                <?php if(empty($latest_trips)): ?>
                    <div class="col-12 text-center">
                        <p>Belum ada trip yang tersedia.</p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="pages/tambah-destinasi.php" class="btn btn-primary">Buat Trip Pertama</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach($latest_trips as $trip): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card trip-card h-100 shadow-sm">
                                <img src="assets/images/trips/<?php echo htmlspecialchars($trip['image'] ?: 'default-trip.jpg'); ?>" 
                                     class="card-img-top trip-image" 
                                     alt="<?php echo htmlspecialchars($trip['destination']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($trip['destination']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($trip['description'], 0, 100)) . '...'; ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> 
                                                <?php echo date('d M Y', strtotime($trip['start_date'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <small class="text-muted me-3">
                                                <i class="bi bi-people"></i> 
                                                <?php echo $trip['participant_count']; ?>/<?php echo $trip['max_participants']; ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="bi bi-cash"></i> 
                                                Rp <?php echo number_format($trip['price'], 0, ',', '.'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            By @<?php echo htmlspecialchars($trip['username']); ?>
                                        </small>
                                        <a href="pages/destinasi.php?id=<?php echo $trip['id']; ?>" 
                                           class="btn btn-primary btn-sm">Lihat Detail</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fitur Section -->
    <div class="container">
        <div class="content-wrapper">
            <h2 class="section-title">Mengapa TripMates?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-people-fill feature-icon"></i>
                        <h4>Teman Baru</h4>
                        <p>Temukan teman traveling yang sesuai dengan minat dan tujuan perjalananmu.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-shield-check feature-icon"></i>
                        <h4>Aman & Terpercaya</h4>
                        <p>Semua pengguna telah terverifikasi dan trip dikelola dengan profesional.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-cash-coin feature-icon"></i>
                        <h4>Harga Terjangkau</h4>
                        <p>Berbagi biaya perjalanan dengan peserta lain untuk menghemat pengeluaran.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Destinasi Populer Section -->
    <div class="container mb-5">
        <div class="content-wrapper">
            <h2 class="section-title">Destinasi Populer</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card destination-card">
                        <div class="destination-img-wrapper">
                            <img src="./assets/images/destinations/bali.jpg" class="card-img destination-img" alt="Bali">
                            <div class="destination-overlay">
                                <div class="destination-content">
                                    <h5 class="card-title">Bali</h5>
                                    <p class="card-text">Pulau Dewata dengan keindahan alam dan budaya yang memukau</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card destination-card">
                        <div class="destination-img-wrapper">
                            <img src="assets/images/destinations/lombok.jpg" class="card-img destination-img" alt="Lombok">
                            <div class="destination-overlay">
                                <div class="destination-content">
                                    <h5 class="card-title">Lombok</h5>
                                    <p class="card-text">Surga tersembunyi dengan pantai-pantai yang menakjubkan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card destination-card">
                        <div class="destination-img-wrapper">
                            <img src="assets/images/destinations/raja-ampat.jpeg" class="card-img destination-img" alt="Raja Ampat">
                            <div class="destination-overlay">
                                <div class="destination-content">
                                    <h5 class="card-title">Raja Ampat</h5>
                                    <p class="card-text">Surga bawah laut dengan keindahan terumbu karang</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>