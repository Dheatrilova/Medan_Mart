
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit;
}

$name = $_SESSION['name'] ?? 'Seller';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Seller - MedanMart</title>

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
        }

        .navbar-inner {
            width: min(1120px, 92%);
            min-height: 76px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
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
            background: var(--cream);
            border-radius: 13px;
            font-size: 23px;
        }

        .brand-name span {
            color: var(--primary-dark);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .nav-label {
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 42px;
            padding: 10px 17px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #dc2626;
            background: white;
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s ease;
        }

        .logout:hover {
            background: #fef2f2;
        }

        /* MAIN CONTAINER */

        .container {
            width: min(1120px, 92%);
            margin: 38px auto;
        }

        /* WELCOME */

        .welcome {
            position: relative;
            overflow: hidden;
            padding: 38px;
            margin-bottom: 34px;
            border-radius: 22px;
            color: var(--white);
            background: linear-gradient(120deg, #f97316, #fb923c);
            box-shadow: 0 12px 30px rgba(249, 115, 22, 0.14);
        }

        .welcome::after {
            content: "";
            position: absolute;
            top: -95px;
            right: -55px;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            pointer-events: none;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            max-width: 700px;
        }

        .welcome-label {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 14px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.15);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .welcome h1 {
            margin-bottom: 12px;
            font-size: clamp(25px, 4vw, 34px);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .welcome p {
            max-width: 580px;
            color: #fffaf5;
            font-size: 15px;
            line-height: 1.8;
        }

        /* SECTION HEADING */

        .section-heading {
            margin-bottom: 20px;
        }

        .section-heading h2 {
            margin-bottom: 5px;
            font-size: 23px;
            line-height: 1.4;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        /* MENU CARDS */

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 265px;
            padding: 27px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--white);
            color: var(--text);
            box-shadow: 0 5px 18px rgba(15, 23, 42, 0.035);
            transition: transform 0.2s ease,
                        box-shadow 0.2s ease,
                        border-color 0.2s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            border-color: #fdba74;
            box-shadow: 0 12px 25px rgba(15, 23, 42, 0.08);
        }

        .icon {
            display: grid;
            place-items: center;
            width: 58px;
            height: 58px;
            margin-bottom: 20px;
            border-radius: 16px;
            background: var(--cream);
            font-size: 29px;
        }

        .card h3 {
            margin-bottom: 9px;
            font-size: 19px;
            line-height: 1.45;
        }

        .card p {
            margin-bottom: 20px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.8;
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

        .card:hover .card-action span {
            transform: translateX(4px);
        }

        /* FOOTER */

        .footer {
            padding: 22px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        /* RESPONSIVE */

        @media (max-width: 900px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .card:last-child {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 600px) {
            .navbar-inner {
                min-height: 68px;
                gap: 10px;
            }

            .brand {
                gap: 8px;
                font-size: 20px;
            }

            .brand-icon {
                width: 39px;
                height: 39px;
                font-size: 21px;
            }

            .nav-right {
                gap: 8px;
            }

            .nav-label {
                display: none;
            }

            .logout {
                min-height: 39px;
                padding: 8px 11px;
                font-size: 13px;
            }

            .container {
                margin: 25px auto;
            }

            .welcome {
                padding: 28px 23px;
                margin-bottom: 29px;
                border-radius: 18px;
            }

            .welcome::after {
                top: -45px;
                right: -65px;
                width: 150px;
                height: 150px;
            }

            .cards {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card:last-child {
                grid-column: auto;
            }

            .card {
                min-height: auto;
                padding: 23px;
            }

            .section-heading h2 {
                font-size: 21px;
            }
        }

        @media (max-width: 360px) {
            .brand {
                font-size: 18px;
            }

            .welcome {
                padding: 23px 18px;
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
                <span class="brand-name">Medan<span>Mart</span></span>
            </a>

            <div class="nav-right">
                <span class="nav-label">Dashboard Penjual</span>

                <a href="../logout.php" class="logout">
                    <span aria-hidden="true">↪</span>
                    Logout
                </a>
            </div>

        </div>
    </header>

    <main class="container">

        <!-- WELCOME -->
        <section class="welcome">
            <div class="welcome-content">

                <span class="welcome-label">AREA PENJUAL</span>

                <h1>
                    Selamat datang,
                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?> 👋
                </h1>

                <p>
                    Kelola toko dan produkmu dengan mudah melalui MedanMart.
                    Kamu juga bisa memeriksa pesanan pembeli dan melihat
                    bukti pembayaran yang masuk.
                </p>

            </div>
        </section>

        <!-- MENU -->
        <section aria-labelledby="menu-title">

            <div class="section-heading">
                <h2 id="menu-title">Kelola Toko Kamu</h2>
                <p>Pilih menu untuk mulai mengelola aktivitas tokomu.</p>
            </div>

            <div class="cards">

                <!-- KELOLA TOKO -->
                <a href="store.php" class="card">

                    <div class="icon" aria-hidden="true">🏪</div>

                    <h3>Kelola Toko</h3>

                    <p>
                        Atur informasi toko UMKM, termasuk nama,
                        deskripsi, dan alamat toko.
                    </p>

                    <span class="card-action">
                        Buka pengaturan toko
                        <span aria-hidden="true">→</span>
                    </span>

                </a>

                <!-- KELOLA PRODUK -->
                <a href="products.php" class="card">

                    <div class="icon" aria-hidden="true">📦</div>

                    <h3>Kelola Produk</h3>

                    <p>
                        Tambahkan produk baru, edit informasi produk,
                        atau hapus produk yang tidak lagi dijual.
                    </p>

                    <span class="card-action">
                        Kelola produk
                        <span aria-hidden="true">→</span>
                    </span>

                </a>

                <!-- PESANAN MASUK -->
                <a href="orders.php" class="card">

                    <div class="icon" aria-hidden="true">🛒</div>

                    <h3>Pesanan Masuk</h3>

                    <p>
                        Periksa pesanan pembeli, detail transaksi,
                        dan bukti pembayaran yang masuk.
                    </p>

                    <span class="card-action">
                        Lihat pesanan
                        <span aria-hidden="true">→</span>
                    </span>

                </a>

            </div>
        </section>

    </main>

    <footer class="footer">
        &copy; <?= date('Y'); ?> MedanMart. Belanja mudah, belanja nyaman.
    </footer>

</body>
</html>
