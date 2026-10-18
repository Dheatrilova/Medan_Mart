
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f97316">
    <title>MedanMart - Marketplace Lokal</title>

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --primary-light: #fff7ed;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --surface: #f8fafc;
            --container: 1180px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background: white;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        .container {
            width: min(var(--container), calc(100% - 48px));
            margin-inline: auto;
        }

        /* NAVBAR */

        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.97);
            border-bottom: 1px solid #f0f0f0;
            backdrop-filter: blur(12px);
        }

        .nav-inner {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -1px;
            white-space: nowrap;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            background: var(--primary-light);
            border-radius: 13px;
            font-size: 23px;
        }

        .logo-name span {
            color: var(--primary);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link {
            min-height: 43px;
            padding: 0 21px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .nav-login {
            color: var(--text);
            background: white;
            border: 1px solid var(--border);
        }

        .nav-login:hover {
            border-color: var(--primary);
            color: var(--primary-dark);
        }

        .nav-register {
            color: white;
            background: var(--primary);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
        }

        .nav-register:hover,
        .hero-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* HERO */

        .hero {
            overflow: hidden;
            background:
                radial-gradient(
                    ellipse at 85% 20%,
                    rgba(255, 210, 165, 0.4),
                    transparent 35%
                ),
                linear-gradient(135deg, #fff7ed, #ffffff 70%);
        }

        .hero-inner {
            min-height: 520px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            align-items: center;
            gap: 55px;
            padding-block: 65px;
        }

        .hero-text {
            max-width: 590px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 8px 13px;
            margin-bottom: 23px;
            border: 1px solid #fed7aa;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.8);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.1px;
        }

        .eyebrow-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
        }

        .hero h1 {
            margin-bottom: 22px;
            font-size: clamp(36px, 4.2vw, 54px);
            font-weight: 800;
            line-height: 1.14;
            letter-spacing: -2px;
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero-description {
            max-width: 510px;
            margin-bottom: 30px;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.9;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 13px;
        }

        .hero-button,
        .hero-secondary {
            min-height: 50px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 0 23px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .hero-button {
            color: white;
            background: var(--primary);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.2);
        }

        .hero-secondary {
            color: var(--text);
            background: white;
            border: 1px solid var(--border);
        }

        .hero-secondary:hover {
            border-color: var(--primary);
            color: var(--primary-dark);
        }

        .hero-note {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 27px;
            color: var(--muted);
            font-size: 12px;
        }

        .hero-note span {
            color: #16a34a;
            font-weight: 800;
        }

        /* ILUSTRASI BELANJA */

        .hero-visual {
            position: relative;
            min-width: 0;
            min-height: 410px;
            display: grid;
            place-items: center;
            padding: 35px 15px;
        }

        .visual-glow {
            position: absolute;
            width: min(320px, 78%);
            aspect-ratio: 1;
            border-radius: 50%;
            background: linear-gradient(145deg, #fed7aa, #ffedd5);
        }

        .shopping-card {
            position: relative;
            z-index: 1;
            width: min(100%, 365px);
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.97);
            box-shadow: 0 24px 65px rgba(124, 68, 22, 0.12);
        }

        .shopping-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 24px;
        }

        .shopping-card-title {
            font-size: 15px;
            font-weight: 800;
        }

        .shopping-card-label {
            flex-shrink: 0;
            padding: 6px 9px;
            border-radius: 8px;
            background: #dcfce7;
            color: #15803d;
            font-size: 9px;
            font-weight: 800;
        }

        .shopping-cart-icon {
            min-height: 125px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
            font-size: 76px;
        }

        .shopping-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 20px;
        }

        .shopping-card-footer > div:first-child {
            min-width: 0;
            flex: 1;
        }

        .shopping-card-footer strong {
            display: block;
            font-size: 14px;
            line-height: 1.5;
        }

        .shopping-card-footer p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .mini-icon {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            font-size: 19px;
        }

        /* KARTU INTERAKTIF */

        .floating-badge {
            position: absolute;
            z-index: 2;
            width: 205px;
            min-height: 76px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border: 1px solid #f3f4f6;
            border-radius: 16px;
            background: white;
            color: var(--text);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .floating-badge:hover {
            border-color: #fed7aa;
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.12);
        }

        .floating-badge:focus-visible {
            outline: 3px solid #fdba74;
            outline-offset: 4px;
        }

        .badge-icon {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 12px;
            background: var(--primary-light);
            font-size: 20px;
        }

        .floating-badge strong {
            display: block;
            font-size: 12px;
            line-height: 1.5;
        }

        .floating-badge small {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.5;
            white-space: nowrap;
        }

        /* Kedua kartu ditempatkan di luar area tulisan kartu utama */

        .badge-top {
            top: 0;
            right: 0;
        }

        .badge-bottom {
            bottom: 0;
            left: 0;
        }

        /* FITUR */

        .features {
            padding-block: 82px 88px;
            background: var(--surface);
        }

        .section-heading {
            max-width: 590px;
            margin: 0 auto 42px;
            text-align: center;
        }

        .section-label {
            margin-bottom: 11px;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.4px;
        }

        .section-heading h2 {
            margin-bottom: 13px;
            font-size: clamp(27px, 3vw, 35px);
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -1px;
        }

        .section-heading > p:last-child {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
        }

        .feature-container {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .feature {
            min-width: 0;
            padding: 29px 27px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: white;
            transition: 0.2s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
            border-color: #fed7aa;
            box-shadow: 0 15px 35px rgba(31, 41, 55, 0.06);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            display: grid;
            place-items: center;
            margin-bottom: 23px;
            border-radius: 16px;
            background: var(--primary-light);
            font-size: 27px;
        }

        .feature h3 {
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: 800;
        }

        .feature p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.85;
        }

        /* FOOTER */

        footer {
            padding: 28px 24px;
            border-top: 1px solid var(--border);
            background: white;
            text-align: center;
        }

        footer p {
            color: var(--muted);
            font-size: 13px;
        }

        footer strong {
            color: var(--primary-dark);
        }

        /* TABLET */

        @media (max-width: 900px) {
            .hero-inner {
                grid-template-columns: 1fr 0.9fr;
                gap: 25px;
                padding-block: 50px;
            }

            .hero h1 {
                font-size: clamp(34px, 4.5vw, 45px);
                letter-spacing: -1.5px;
            }

            .hero-description {
                font-size: 15px;
            }

            .hero-visual {
                min-height: 390px;
                padding-inline: 8px;
            }

            .shopping-card {
                padding: 20px;
            }

            .shopping-card-title {
                font-size: 13px;
            }

            .shopping-card-label {
                font-size: 8px;
            }

            .floating-badge {
                width: 185px;
                min-height: 68px;
                gap: 8px;
                padding: 9px;
            }

            .badge-icon {
                width: 34px;
                height: 34px;
                font-size: 18px;
            }

            .floating-badge strong {
                font-size: 11px;
            }

            .floating-badge small {
                font-size: 9px;
            }

            .feature-container {
                gap: 15px;
            }

            .feature {
                padding: 24px 20px;
            }
        }

        /* HP */

        @media (max-width: 650px) {
            .container {
                width: calc(100% - 32px);
            }

            .nav-inner {
                min-height: 68px;
                gap: 10px;
            }

            .logo {
                gap: 7px;
                font-size: 19px;
                letter-spacing: -0.7px;
            }

            .logo-icon {
                width: 35px;
                height: 35px;
                border-radius: 10px;
                font-size: 19px;
            }

            .nav-menu {
                gap: 7px;
            }

            .nav-link {
                min-height: 38px;
                padding-inline: 12px;
                border-radius: 9px;
                font-size: 12px;
            }

            .hero-inner {
                grid-template-columns: 1fr;
                gap: 15px;
                padding-block: 45px 30px;
            }

            .hero-text {
                text-align: center;
            }

            .eyebrow {
                margin-bottom: 19px;
                font-size: 10px;
            }

            .hero h1 {
                font-size: clamp(33px, 8vw, 43px);
                line-height: 1.17;
                letter-spacing: -1.5px;
            }

            .hero-description {
                margin-inline: auto;
                margin-bottom: 24px;
                font-size: 14px;
                line-height: 1.85;
            }

            .hero-actions {
                justify-content: center;
            }

            .hero-button,
            .hero-secondary {
                min-height: 46px;
                padding-inline: 15px;
                font-size: 13px;
            }

            .hero-note {
                justify-content: center;
                flex-wrap: wrap;
                font-size: 11px;
            }

            .hero-visual {
                width: 100%;
                min-height: 365px;
                margin-top: 5px;
                padding: 35px 0;
            }

            .visual-glow {
                width: 75%;
            }

            .shopping-card {
                width: min(88%, 340px);
                padding: 20px;
                border-radius: 22px;
            }

            .shopping-card-header {
                gap: 7px;
                margin-bottom: 18px;
            }

            .shopping-card-title {
                font-size: 13px;
            }

            .shopping-cart-icon {
                min-height: 105px;
                font-size: 65px;
            }

            .shopping-card-footer {
                gap: 8px;
                margin-top: 16px;
            }

            .shopping-card-footer strong {
                font-size: 12px;
            }

            .shopping-card-footer p {
                font-size: 11px;
            }

            .mini-icon {
                width: 34px;
                height: 34px;
            }

            .floating-badge {
                width: 174px;
                min-height: 64px;
                gap: 8px;
                padding: 8px;
                border-radius: 13px;
            }

            .badge-icon {
                width: 32px;
                height: 32px;
                border-radius: 10px;
                font-size: 16px;
            }

            .floating-badge strong {
                font-size: 10px;
            }

            .floating-badge small {
                font-size: 9px;
            }

            .badge-top {
                top: 0;
                right: 0;
            }

            .badge-bottom {
                bottom: 0;
                left: 0;
            }

            .features {
                padding-block: 55px;
            }

            .section-heading {
                margin-bottom: 28px;
            }

            .section-heading h2 {
                font-size: 28px;
            }

            .section-heading > p:last-child {
                font-size: 14px;
            }

            .feature-container {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .feature {
                display: grid;
                grid-template-columns: 48px minmax(0, 1fr);
                column-gap: 15px;
                padding: 21px;
            }

            .feature-icon {
                width: 48px;
                height: 48px;
                grid-row: span 2;
                margin-bottom: 0;
                border-radius: 13px;
                font-size: 23px;
            }

            .feature h3 {
                margin-bottom: 5px;
                font-size: 16px;
            }

            .feature p {
                font-size: 13px;
            }

            footer {
                padding: 23px 16px;
            }
        }

        /* LAYAR SANGAT KECIL */

        @media (max-width: 380px) {
            .container {
                width: calc(100% - 24px);
            }

            .logo {
                font-size: 17px;
            }

            .nav-link {
                padding-inline: 9px;
                font-size: 11px;
            }

            .hero-visual {
                min-height: 340px;
            }

            .shopping-card {
                width: 90%;
                padding: 15px;
            }

            .floating-badge {
                width: 158px;
                gap: 6px;
                padding: 7px;
            }

            .floating-badge small {
                font-size: 8px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="container nav-inner">

            <a href="index.php" class="logo" aria-label="MedanMart Beranda">
                <span class="logo-icon">🛍️</span>
                <span class="logo-name">Medan<span>Mart</span></span>
            </a>

            <div class="nav-menu">
                <a href="login.php" class="nav-link nav-login">
                    Login
                </a>

                <a href="register.php" class="nav-link nav-register">
                    Daftar
                </a>
            </div>

        </div>
    </nav>


    <!-- HERO -->
    <main>
        <section class="hero">
            <div class="container hero-inner">

                <div class="hero-text">

                    <div class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        MARKETPLACE LOKAL
                    </div>

                    <h1>
                        Belanja Mudah,<br>
                        <span>Jualan Lebih Mudah.</span>
                    </h1>

                    <p class="hero-description">
                        Selamat datang di MedanMart, tempat pembeli
                        menemukan produk dan penjual mengelola bisnis
                        dalam satu platform yang praktis dan mudah digunakan.
                    </p>

                    <div class="hero-actions">
                        <a href="login.php" class="hero-button">
                            Mulai Belanja <span aria-hidden="true">→</span>
                        </a>

                        <a href="register.php" class="hero-secondary">
                            Gabung Sekarang
                        </a>
                    </div>

                    <div class="hero-note">
                        <span aria-hidden="true">✓</span>
                        Satu platform untuk pembeli dan penjual
                    </div>

                </div>


                <!-- ILUSTRASI -->
                <div class="hero-visual">

                    <div class="visual-glow"></div>

                    <div class="shopping-card">

                        <div class="shopping-card-header">
                            <div class="shopping-card-title">
                                Belanja di MedanMart
                            </div>

                            <div class="shopping-card-label">
                                MARKETPLACE
                            </div>
                        </div>

                        <div class="shopping-cart-icon"
                             role="img"
                             aria-label="Ilustrasi keranjang belanja">
                            🛒
                        </div>

                        <div class="shopping-card-footer">
                            <div>
                                <strong>Belanja lebih praktis</strong>
                                <p>Temukan produk favoritmu</p>
                            </div>

                            <div class="mini-icon" aria-hidden="true">
                                →
                            </div>
                        </div>

                    </div>


                    <!-- PILIHAN PRODUK -->
                    <a href="login.php"
                       class="floating-badge badge-top"
                       aria-label="Masuk untuk mulai belanja">

                        <div class="badge-icon">🛍️</div>

                        <div>
                            <strong>Pilihan Produk</strong>
                            <small>Belanja dengan mudah ↗</small>
                        </div>

                    </a>


                    <!-- KELOLA TOKO -->
                    <a href="login.php"
                       class="floating-badge badge-bottom"
                       aria-label="Masuk untuk mengelola toko">

                        <div class="badge-icon">🏪</div>

                        <div>
                            <strong>Kelola Toko</strong>
                            <small>Praktis untuk seller ↗</small>
                        </div>

                    </a>

                </div>

            </div>
        </section>


        <!-- FITUR -->
        <section class="features">
            <div class="container">

                <div class="section-heading">
                    <p class="section-label">KENALI MEDANMART</p>

                    <h2>
                        Semua Lebih Mudah<br>
                        dalam Satu Platform
                    </h2>

                    <p>
                        MedanMart menghubungkan pembeli dan penjual
                        dengan fitur yang dirancang agar aktivitas
                        marketplace menjadi lebih praktis.
                    </p>
                </div>


                <div class="feature-container">

                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            🛍️
                        </div>

                        <h3>Untuk Pembeli</h3>

                        <p>
                            Jelajahi produk, pilih barang yang dibutuhkan,
                            lakukan checkout, dan pantau pesanan melalui
                            akun pembeli.
                        </p>
                    </article>


                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            🏪
                        </div>

                        <h3>Untuk Seller</h3>

                        <p>
                            Kelola informasi toko, tambahkan produk,
                            perbarui stok, dan pantau pesanan masuk
                            melalui dashboard seller.
                        </p>
                    </article>


                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            🔐
                        </div>

                        <h3>Sistem Terintegrasi</h3>

                        <p>
                            Login dan hak akses pengguna membantu
                            memisahkan fitur sesuai peran pembeli,
                            seller, dan administrator.
                        </p>
                    </article>

                </div>

            </div>
        </section>
    </main>


    <!-- FOOTER -->
    <footer>
        <p>
            © 2026 <strong>MedanMart</strong>. All Rights Reserved.
        </p>
    </footer>

</body>

</html>
