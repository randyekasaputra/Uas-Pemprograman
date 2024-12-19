<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fungsi untuk memastikan koneksi database aktif
function checkConnection($db) {
    try {
        $db->query('SELECT 1');
    } catch (PDOException $e) {
        // Coba reconnect
        require_once '../config/database.php';
    }
    return $db;
}

try {
    // Pastikan koneksi aktif sebelum query
    $db = checkConnection($db);

    // Handle verifikasi pembayaran
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['payment_id'])) {
        $payment_id = $_POST['payment_id'];
        $action = $_POST['action'];
        $notes = $_POST['notes'] ?? '';
        $status = ($action === 'verify') ? 'verified' : 'rejected';
        
        // Mulai transaction
        $db->beginTransaction();
        
        try {
            // Update status pembayaran
            $stmt = $db->prepare("
                UPDATE collection_payments 
                SET status = ?, notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([$status, $notes, $payment_id]);

            // Catat history pembayaran
            $stmt = $db->prepare("
                INSERT INTO payment_history (collection_payment_id, status, notes, created_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$payment_id, $status, $notes, $_SESSION['user_id']]);

            // Commit transaction
            $db->commit();

            $_SESSION['success'] = "Status pembayaran berhasil diperbarui.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } catch(PDOException $e) {
            // Rollback jika terjadi error
            $db->rollBack();
            throw $e;
        }
    }

    // Ambil daftar pembayaran dengan prepared statement
    $stmt = $db->prepare("
        SELECT 
            cp.*,
            t.destination,
            u.username,
            u.full_name,
            t.price as trip_price,
            cp.bank_name,
            cp.account_number,
            cp.account_name
        FROM collection_payments cp
        JOIN trips t ON cp.trip_id = t.id
        JOIN users u ON cp.user_id = u.id
        ORDER BY cp.created_at DESC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Log error (gunakan proper logging di production)
    error_log("Database Error: " . $e->getMessage());
    $error = "Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.";
    
    // Tampilkan pesan error yang lebih user-friendly
    if ($e->getCode() == 2006) {
        $error = "Koneksi database terputus. Silakan refresh halaman.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #6366f1 0%, #0ea5e9 100%);
            min-height: 100vh;
        }

        .page-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-title {
            color: #6366f1;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(99, 102, 241, 0.1);
        }

        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            color: white;
        }

        .table th {
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
        }

        .payment-proof-img {
            max-width: 100px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .payment-proof-img:hover {
            transform: scale(1.05);
        }

        .badge {
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, #fbbf24, #f59e0b) !important;
            color: #000;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
        }

        .badge.bg-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, #6366f1, #0ea5e9) !important;
        }

        .badge.bg-info {
            background: linear-gradient(135deg, #0ea5e9, #38bdf8) !important;
            color: white;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .modal-footer {
            border-top: 1px solid rgba(99, 102, 241, 0.1);
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            padding: 10px 15px;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        /* Alert Styling */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            color: #059669;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #dc2626;
        }

        /* Responsive Table */
        @media (max-width: 768px) {
            .table-responsive {
                border-radius: 15px;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    

    <div class="container">
        <div class="page-container">
            <h2 class="page-title">
                <i class="bi bi-credit-card me-2"></i>
                Kelola Pembayaran
            </h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Peserta</th>
                            <th>Destinasi</th>
                            <th>Metode</th>
                            <th>Jumlah</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($payment['full_name']); ?></div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($payment['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($payment['destination']); ?></td>
                                <td>
                                    <?php if($payment['payment_method'] == 'bank_transfer'): ?>
                                        <span class="badge bg-primary">Bank Transfer</span>
                                        <div class="small text-muted mt-1">
                                            <?php echo htmlspecialchars($payment['bank_name']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-info">DANA</span>
                                    <?php endif; ?>
                                </td>
                                <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <img src="../assets/images/payments/<?php echo $payment['payment_proof']; ?>" 
                                         class="payment-proof-img"
                                         data-bs-toggle="modal"
                                         data-bs-target="#imageModal"
                                         data-img-src="../assets/images/payments/<?php echo $payment['payment_proof']; ?>"
                                         alt="Bukti Pembayaran">
                                </td>
        
                                <td>
                                    <?php
                                    $badge_class = 
                                        $payment['status'] == 'verified' ? 'bg-success' : 
                                        ($payment['status'] == 'rejected' ? 'bg-danger' : 'bg-warning');
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($payment['status'] == 'pending'): ?>
                                        <button type="button" 
                                                class="btn btn-success btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#verifyModal"
                                                data-payment-id="<?php echo $payment['id']; ?>"
                                                data-action="verify">
                                            <i class="bi bi-check-lg"></i> Verifikasi
                                        </button>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#verifyModal"
                                                data-payment-id="<?php echo $payment['id']; ?>"
                                                data-action="reject">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            Sudah diproses
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Preview Gambar -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="modal-img" alt="Bukti Pembayaran">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Verifikasi -->
    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" id="payment_id">
                        <input type="hidden" name="action" id="action">
                        
                        <p id="modal-message"></p>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan:</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn" id="confirm-button">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar
        document.querySelectorAll('.payment-proof-img').forEach(img => {
            img.addEventListener('click', function() {
                document.querySelector('#imageModal .modal-img').src = this.dataset.imgSrc;
            });
        });

        // Setup modal verifikasi
        document.getElementById('verifyModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const action = button.dataset.action;
            const paymentId = button.dataset.paymentId;
            
            this.querySelector('#payment_id').value = paymentId;
            this.querySelector('#action').value = action;
            
            const message = action === 'verify' ? 
                'Apakah Anda yakin ingin memverifikasi pembayaran ini?' :
                'Apakah Anda yakin ingin menolak pembayaran ini?';
            
            this.querySelector('#modal-message').textContent = message;
            
            const confirmButton = this.querySelector('#confirm-button');
            confirmButton.className = `btn btn-${action === 'verify' ? 'success' : 'danger'}`;
            confirmButton.textContent = action === 'verify' ? 'Verifikasi' : 'Tolak';
        });
    </script>
</body>
</html> 