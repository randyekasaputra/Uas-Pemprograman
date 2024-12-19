<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil data user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Ambil trip yang dibuat user
$stmt = $db->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as participant_count
    FROM trips t 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$created_trips = $stmt->fetchAll();

// Ambil trip yang diikuti user
$stmt = $db->prepare("
    SELECT t.*, tp.status, u.username as organizer_name,
           (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as participant_count
    FROM trips t 
    JOIN trip_participants tp ON t.id = tp.trip_id 
    JOIN users u ON t.user_id = u.id
    WHERE tp.user_id = ? 
    ORDER BY tp.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$joined_trips = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #6366f1 0%, #0ea5e9 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../assets/images/pattern-bg.png') repeat;
            opacity: 0.05;
            z-index: -1;
        }

        .profile-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(45deg, rgba(99, 102, 241, 0.1), rgba(14, 165, 233, 0.1));
            z-index: 0;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
            background: white;
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 1rem 0 0.5rem;
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 1;
        }

        .profile-username {
            color: #64748b;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 15px;
            margin-top: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        .nav-tabs {
            border: none;
            background: rgba(255, 255, 255, 0.5);
            padding: 0.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            color: #64748b;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .nav-tabs .nav-link:hover {
            color: #6366f1;
            background: rgba(99, 102, 241, 0.1);
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            color: white;
        }

        .trip-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .trip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .trip-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .trip-content {
            padding: 1.5rem;
        }

        .btn-edit-profile {
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-edit-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .profile-avatar {
                width: 120px;
                height: 120px;
            }

            .profile-name {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="container my-5">
        <!-- Profile Header -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <img src="../assets/images/profiles/<?php echo $user['profile_image'] ?: 'default-avatar.jpg'; ?>" 
                             class="rounded-circle mb-3" 
                             width="150" 
                             height="150"
                             alt="Profile Image"
                             style="object-fit: cover;">
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <p class="mb-3"><?php echo htmlspecialchars($user['bio'] ?: 'Belum ada bio'); ?></p>
                        <a href="edit-profil.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i> Edit Profil
                        </a>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="row text-center">
                            <div class="col-6">
                                <h5><?php echo count($created_trips); ?></h5>
                                <small class="text-muted">Trip Dibuat</small>
                            </div>
                            <div class="col-6">
                                <h5><?php echo count($joined_trips); ?></h5>
                                <small class="text-muted">Trip Diikuti</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="trips-tab" data-bs-toggle="tab" href="#trips" role="tab">
                            <i class="bi bi-compass"></i> Trip Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="joined-tab" data-bs-toggle="tab" href="#joined" role="tab">
                            <i class="bi bi-people"></i> Trip yang Diikuti
                        </a>
                    </li>
                </ul>

                <!-- Tab Contents -->
                <div class="tab-content" id="profileTabsContent">
                    <!-- Trip Saya -->
                    <div class="tab-pane fade show active" id="trips" role="tabpanel">
                        <?php if (empty($created_trips)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-compass display-1 text-muted"></i>
                                <p class="mt-3">Anda belum membuat trip apapun.</p>
                                <a href="tambah-destinasi.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Buat Trip Baru
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($created_trips as $trip): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <img src="../assets/images/trips/<?php echo $trip['image'] ?: 'default-trip.jpg'; ?>" 
                                                 class="card-img-top trip-image" 
                                                 alt="<?php echo htmlspecialchars($trip['destination']); ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($trip['destination']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars(substr($trip['description'], 0, 100)) . '...'; ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar"></i> 
                                                        <?php echo date('d M Y', strtotime($trip['start_date'])); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-people"></i> 
                                                        <?php echo $trip['participant_count']; ?>/<?php echo $trip['max_participants']; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <a href="collection-payment.php?trip_id=<?php echo $trip['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="bi bi-cash-stack"></i> Kelola Pembayaran
                                                        </a>
                                                        <a href="kelola-permintaan.php?trip_id=<?php echo $trip['id']; ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="bi bi-people"></i> Kelola Peserta
                                                        </a>
                                                    </div>
                                                    <a href="destinasi.php?id=<?php echo $trip['id']; ?>" 
                                                       class="btn btn-sm btn-info text-white">
                                                        <i class="bi bi-eye"></i> Lihat
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Trip yang Diikuti -->
                    <div class="tab-pane fade" id="joined" role="tabpanel">
                        <?php if (empty($joined_trips)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people display-1 text-muted"></i>
                                <p class="mt-3">Anda belum mengikuti trip apapun.</p>
                                <a href="../index.php" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Cari Trip
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($joined_trips as $trip): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <?php
                                                $badge_class = 
                                                    $trip['status'] == 'approved' ? 'bg-success' : 
                                                    ($trip['status'] == 'rejected' ? 'bg-danger' : 'bg-warning');
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($trip['status']); ?>
                                                </span>
                                            </div>
                                            <img src="../assets/images/trips/<?php echo $trip['image'] ?: 'default-trip.jpg'; ?>" 
                                                 class="card-img-top trip-image" 
                                                 alt="<?php echo htmlspecialchars($trip['destination']); ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($trip['destination']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars(substr($trip['description'], 0, 100)) . '...'; ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar"></i> 
                                                        <?php echo date('d M Y', strtotime($trip['start_date'])); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-people"></i> 
                                                        <?php echo $trip['participant_count']; ?>/<?php echo $trip['max_participants']; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        By @<?php echo htmlspecialchars($trip['organizer_name']); ?>
                                                    </small>
                                                    <?php if($trip['status'] == 'approved'): ?>
                                                        <a href="gabung-trip.php?id=<?php echo $trip['id']; ?>" 
                                                           class="btn btn-sm btn-success">
                                                            <i class="bi bi-check-circle"></i> Gabung
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="destinasi.php?id=<?php echo $trip['id']; ?>" 
                                                       class="btn btn-sm btn-info text-white">
                                                        <i class="bi bi-eye"></i> Lihat Detail
                                                    </a>
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

    <!-- <?php include '../components/footer.php'; ?> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>