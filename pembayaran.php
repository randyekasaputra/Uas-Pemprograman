<?php
session_start();
require_once '../config/database.php';

// Ambil parameter dari URL
$trip_id = isset($_GET['trip_id']) ? $_GET['trip_id'] : null;
$price = isset($_GET['price']) ? $_GET['price'] : null;

// Cek apakah trip_id dan price ada
if (!$trip_id || !$price) {
    header('Location: ../index.php');
    exit();
}

// Ambil detail trip untuk konfirmasi
$stmt = $db->prepare("SELECT destination FROM trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: ../index.php');
    exit();
}

// Proses pembayaran (misalnya, simpan ke database atau lakukan integrasi dengan payment gateway)
// Di sini hanya contoh sederhana
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $bank_name = $_POST['bank_name'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $account_name = $_POST['account_name'] ?? '';

    // Simulasi proses pembayaran
    $payment_status = 'success'; // Ganti dengan logika pembayaran yang sebenarnya

    if ($payment_status == 'success') {
        // Pembayaran berhasil, lakukan tindakan yang diperlukan
        // Misalnya, simpan ke database atau kirim notifikasi
        $success_message = "Pembayaran untuk trip " . htmlspecialchars($trip['destination']) . " sebesar Rp " . number_format($price, 0, ',', '.') . " berhasil.";
        
        // Simpan detail pembayaran ke database
        $stmt = $db->prepare("INSERT INTO collection_payments (trip_id, amount, bank_name, account_number, account_name, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$trip_id, $price, $bank_name, $account_number, $account_name, $_SESSION['user_id']]);
    } else {
        $error_message = "Pembayaran gagal. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2 class="text-center mb-4">Pembayaran untuk Trip</h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($trip['destination']); ?></h5>
                <p class="card-text">Harga: Rp <?php echo number_format($price, 0, ',', '.'); ?></p>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="bank_name" class="form-label">Nama Bank:</label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="account_number" class="form-label">Nomor Akun:</label>
                        <input type="text" class="form-control" id="account_number" name="account_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="account_name" class="form-label">Nama Akun:</label>
                        <input type="text" class="form-control" id="account_name" name="account_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Konfirmasi Pembayaran</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 