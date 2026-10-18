
<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.php");
    exit;
}

$name = $_SESSION['name'] ?? 'Buyer';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Pembeli - MedanMart</title>

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --cream: #fff7ed;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --background: #f8fafc;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            position: relative;
            z-index: 1;
        }

        .navbar-inner {
            width: min(1120px, 92%);
            min-height: 76px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            color: var(--text);
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: var(--cream);
            font-size: 23px;
        }

        .brand span:last-child {
            color: var(--primary-dark);
        }

        .nav-label {
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        /* MAIN CONTAINER */

        .container {
            width: min(1120px, 92%);
            margin: 38px auto;
        }

        /* WELCOME BANNER */

        .welcome {
            position: relative;
            overflow: hidden;
            background: linear-gradient(120deg, #f97316, #fb923c);
            color: var(--white);
            border-radius: 22px;
            padding: 38px;
            margin-bottom: 34px;
            box-shadow: 0 12px 30px rgba(249, 115, 22, 0.14);
        }

        .welcome::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            right: -55px;
            top: -95px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            pointer-events: none;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            max-width: 680px;
        }

        .welcome-label {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 14px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.15);
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .welcome h1 {
            font-size: clamp(25px, 4vw, 34px);
            line-height: 1.3;
            margin-bottom: 12px;
            overflow-wrap: anywhere;
        }

        .welcome p {
            font-size: 15px;
            line-height: 1.8;
            color: #fffaf5;
            max-width: 580px;
        }

        /* SECTION TITLE */

        .section-heading {
            margin-bottom: 20px;
        }

        .section-heading h2 {
            font-size: 23px;
            line-height: 1.4;
            margin-bottom: 5px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        /* MENU CARDS */

        .menu {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .menu-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 235px;
            padding: 27px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            color: var(--text);
            box-shadow: 0 5px 18px rgba(15, 23, 42, 0.035);
            transition: transform 0.2s ease, box-shadow 0.2s ease,
                        border-color 0.2s ease;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            border-color: #fdba74;
            box-shadow: 0 12px 25px rgba(15, 23, 42, 0.08);
        }

        .menu-icon {
            display: grid;
            place-items: center;
            width: 58px;
            height: 58px;
            margin-bottom: 20px;
            background: var(--cream);
            border-radius: 16px;
            font-size: 29px;
        }

        .menu-card h3 {
            font-size: 19px;
            line-height: 1.4;
            margin-bottom: 9px;
        }

        .menu-card p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
        }

        .card-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: auto;
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 700;
        }

        .card-action span {
            transition: transform 0.2s ease;
        }

        .menu-card:hover .card-action span {
            transform: translateX(4px);
        }

        /* BOTTOM ACTIONS */

        .bottom-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 32px;
            padding-top: 23px;
            border-top: 1px solid var(--border);
        }

        .bottom-note {
            color: var(--muted);
            font-size: 13px;
        }

        .logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 11px 19px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fff;
            color: #dc2626;
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s ease;
        }

        .logout:hover {
            background: #fef2f2;
        }

        /* FOOTER */

        .footer {
            padding: 22px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        /* RESPONSIVE */

        @media (max-width: 700px) {
            .navbar-inner {
                min-height: 68px;
            }

            .brand {
                font-size: 21px;
            }

            .brand-icon {
                width: 39px;
                height: 39px;
            }

            .container {
                margin: 25px auto;
            }

            .welcome {
                padding: 28px 23px;
                border-radius: 18px;
                margin-bottom: 29px;
            }

            .welcome::after {
                width: 150px;
                height: 150px;
                right: -65px;
                top: -45px;
            }

            .menu {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .menu-card {
                min-height: auto;
                padding: 23px;
            }

            .section-heading h2 {
                font-size: 21px;
            }
        }

        @media (max-width: 420px) {
            .navbar-inner,
            .container {
                width: 92%;
            }

            .nav-label {
                font-size: 12px;
            }

            .welcome {
                padding: 24px 19px;
            }

            .welcome p {
                font-size: 14px;
            }

            .bottom-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .logout {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="navbar-inner">
            <a href="dashboard.php" class="brand" aria-label="MedanMart Dashboard">
                <span class="brand-icon">🛍️</span>
                <span>Medan<span>Mart</span></span>
            </a>

            <span class="nav-label">Dashboard Pembeli</span>
        </div>
    </header>

    <main class="container">

        <!-- WELCOME -->
        <section class="welcome">
            <div class="welcome-content">
                <span class="welcome-label">AREA PEMBELI</span>

                <h1>
                    Selamat datang,
                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?> 👋
                </h1>

                <p>
                    Senang melihat kamu kembali di MedanMart.
                    Kamu login sebagai pembeli. Yuk, lanjutkan belanja
                    atau periksa status pesananmu di sini.
                </p>
            </div>
        </section>

        <!-- MENU -->
        <section aria-labelledby="menu-title">
            <div class="section-heading">
                <h2 id="menu-title">Menu Pembeli</h2>
                <p>Pilih layanan yang ingin kamu gunakan.</p>
            </div>

            <div class="menu">

                <!-- PESANAN SAYA -->
                <a href="../customer/orders.php" class="menu-card">
                    <div class="menu-icon" aria-hidden="true">📦</div>

                    <h3>Pesanan Saya</h3>

                    <p>
                        Periksa pesanan yang sudah dibuat, lihat detail
                        produk, total pembayaran, dan status pesananmu.
                    </p>

                    <span class="card-action">
                        Lihat pesanan <span aria-hidden="true">→</span>
                    </span>
                </a>

                <!-- BELANJA -->
                <a href="../customer/store.php" class="menu-card">
                    <div class="menu-icon" aria-hidden="true">🛒</div>

                    <h3>Belanja</h3>

                    <p>
                        Temukan produk yang tersedia dan pilih barang
                        yang ingin kamu beli melalui MedanMart.
                    </p>

                    <span class="card-action">
                        Mulai belanja <span aria-hidden="true">→</span>
                    </span>
                </a>

            </div>
        </section>

        <!-- LOGOUT -->
        <div class="bottom-actions">
            <p class="bottom-note">
                Terima kasih sudah menggunakan MedanMart.
            </p>

            <a href="../logout.php" class="logout">
                <span aria-hidden="true">↪</span>
                Logout
            </a>
        </div>

    </main>

    <footer class="footer">
        &copy; <?= date('Y'); ?> MedanMart. Belanja mudah, belanja nyaman.
    </footer>

</body>
</html>
