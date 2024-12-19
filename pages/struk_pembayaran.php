<?php
session_start();
require_once '../config/database.php';

if (!isset($_GET['payment_id'])) {
    header('Location: ../index.php');
    exit();
}

$payment_id = $_GET['payment_id'];

// Ambil data pembayaran
$stmt = $db->prepare("
    SELECT cp.*, t.destination, u.username as user_name 
    FROM collection_payments cp
    JOIN trips t ON cp.trip_id = t.id
    JOIN users u ON cp.user_id = u.id
    WHERE cp.id = ?
");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                background-color: white !important;
            }
            .receipt-container {
                box-shadow: none !important;
                background: white !important;
            }
        }
        
        body {
            background: linear-gradient(135deg, #6366f1 0%, #0ea5e9 100%);
            min-height: 100vh;
        }
        
        .receipt-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            max-width: 800px;
            margin: 20px auto;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px dashed rgba(99, 102, 241, 0.3);
            position: relative;
        }

        .receipt-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background: #6366f1;
            border-radius: 50%;
        }

        .receipt-logo {
            width: 120px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .receipt-title {
            background: linear-gradient(to right, #6366f1, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .receipt-subtitle {
            color: #64748b;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .receipt-info {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.9), rgba(249, 250, 251, 0.9));
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(99, 102, 241, 0.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
        }

        .receipt-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .receipt-label {
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .receipt-value {
            color: #1e293b;
            font-weight: 600;
            text-align: right;
            font-size: 0.95rem;
        }

        .receipt-total {
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 25px 0;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .receipt-total .receipt-row {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .receipt-total .receipt-label,
        .receipt-total .receipt-value {
            color: white;
            font-size: 1.1rem;
        }

        .receipt-status {
            text-align: center;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .status-pending {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #000;
        }

        .status-approved {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .status-rejected {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed rgba(99, 102, 241, 0.3);
            color: #64748b;
            position: relative;
        }

        .receipt-footer::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background: #6366f1;
            border-radius: 50%;
        }

        .qr-code {
            text-align: center;
            margin: 25px 0;
            padding: 15px;
            background: white;
            border-radius: 15px;
            display: inline-block;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .qr-code img {
            width: 120px;
            height: 120px;
            padding: 10px;
            background: white;
            border-radius: 10px;
        }

        .btn {
            padding: 12px 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin: 0 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #0ea5e9);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-outline-secondary {
            border: 2px solid #64748b;
            color: #64748b;
        }

        .btn-outline-secondary:hover {
            background: #64748b;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../components/navbar.php'; ?>

    <div class="container my-4">
        <div class="receipt-container">
            <div class="receipt-header">
                <img src="../assets/images/logo.png" alt="TripMates Logo" class="receipt-logo">
                <h1 class="receipt-title">STRUK PEMBAYARAN</h1>
                <p class="receipt-subtitle">TripMates - Your Travel Companion</p>
            </div>

            <div class="receipt-body">
                <div class="receipt-info">
                    <div class="receipt-row">
                        <span class="receipt-label">No. Transaksi</span>
                        <span class="receipt-value">#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Tanggal</span>
                        <span class="receipt-value"><?php echo date('d/m/Y H:i', strtotime($payment['payment_date'])); ?></span>
                    </div>
                </div>

                <div class="receipt-info">
                    <div class="receipt-row">
                        <span class="receipt-label">Nama Pelanggan</span>
                        <span class="receipt-value"><?php echo htmlspecialchars($payment['user_name']); ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Destinasi</span>
                        <span class="receipt-value"><?php echo htmlspecialchars($payment['destination']); ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Metode Pembayaran</span>
                        <span class="receipt-value">
                            <?php echo $payment['payment_method'] == 'bank' ? 'Transfer Bank' : 'DANA'; ?>
                            <?php if($payment['payment_method'] == 'bank'): ?>
                                (<?php echo htmlspecialchars($payment['bank_name']); ?>)
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="receipt-total">
                    <div class="receipt-row">
                        <span class="receipt-label" style="color: white;">Total Pembayaran</span>
                        <span class="receipt-value" style="color: white;">
                            Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>

                <div class="receipt-status status-<?php echo strtolower($payment['status']); ?>">
                    Status: <?php echo ucfirst($payment['status']); ?>
                </div>

                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=TRIPMATES-<?php echo $payment['id']; ?>" 
                         alt="QR Code">
                </div>
            </div>

            <div class="receipt-footer">
                <p>Terima kasih telah menggunakan layanan TripMates</p>
                <small>Struk ini adalah bukti pembayaran yang sah</small>
            </div>

            <div class="text-center mt-4 no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Cetak Struk
                </button>
                <a href="destinasi.php?id=<?php echo $payment['trip_id']; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="no-print">
        <?php include '../components/footer.php'; ?>
    </div>
</body>
</html> 