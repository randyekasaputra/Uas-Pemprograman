<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<style>
    .modern-navbar {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        padding: 15px 0;
    }

    .modern-navbar .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        background: linear-gradient(135deg, #6366f1, #0ea5e9);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modern-navbar .navbar-brand i {
        font-size: 1.8rem;
        color: #6366f1;
    }

    .modern-navbar .nav-link {
        color: #64748b !important;
        font-weight: 500;
        padding: 8px 16px !important;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
        margin: 0 4px;
    }

    .modern-navbar .nav-link:hover {
        color: #6366f1 !important;
        background: rgba(99, 102, 241, 0.08);
    }

    .modern-navbar .nav-link i {
        margin-right: 6px;
        font-size: 1.1rem;
    }

    .modern-navbar .nav-item.active .nav-link {
        color: #6366f1 !important;
        background: rgba(99, 102, 241, 0.1);
    }

    /* Tombol Login dan Register */
    .modern-navbar .auth-buttons .nav-link {
        padding: 8px 20px !important;
    }

    .modern-navbar .auth-buttons .nav-link.register-btn {
        background: linear-gradient(135deg, #6366f1, #0ea5e9);
        color: white !important;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
    }

    .modern-navbar .auth-buttons .nav-link.register-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
    }

    .modern-navbar .auth-buttons .nav-link.login-btn:hover {
        background: rgba(99, 102, 241, 0.1);
    }

    /* Admin Button */
    .modern-navbar .admin-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white !important;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
    }

    .modern-navbar .admin-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
    }

    /* Dropdown untuk mobile */
    @media (max-width: 991.98px) {
        .modern-navbar .navbar-collapse {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-top: 15px;
        }

        .modern-navbar .nav-link {
            padding: 12px 16px !important;
            margin: 4px 0;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light modern-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-airplane-engines"></i>
            TripMates
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house-door"></i> Beranda
                    </a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/tambah-destinasi.php">
                            <i class="bi bi-plus-circle"></i> Tambah Trip
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/profil.php">
                            <i class="bi bi-person-circle"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link login-btn" href="pages/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link register-btn" href="pages/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
                <?php if(isset($_SESSION['user_id']) && $_SESSION['username'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link admin-btn" href="<?php echo $baseUrl; ?>pages/collection-payment.php">
                            <i class="bi bi-cash-stack"></i> Kelola Pembayaran
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Spacer untuk fixed navbar -->
<div style="margin-top: 80px;"></div> 