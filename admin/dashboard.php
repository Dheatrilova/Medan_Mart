
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/* =========================
   CEK LOGIN ADMIN
========================= */
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin';

/* =========================
   FUNGSI BANTU
========================= */
function escape($value)
{
    return htmlspecialchars(
        (string) ($value ?? '-'),
        ENT_QUOTES,
        'UTF-8'
    );
}

function getTotal($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return $row['total'] ?? 0;
}

function rupiah($angka)
{
    return 'Rp ' . number_format(
        (float) $angka,
        0,
        ',',
        '.'
    );
}

function statusLabel($status)
{
    $status = strtolower(trim((string) $status));

    $labels = [
        'pending'    => 'Menunggu',
        'waiting'    => 'Menunggu',
        'paid'       => 'Dibayar',
        'processing' => 'Diproses',
        'shipped'    => 'Dikirim',
        'completed'  => 'Selesai',
        'delivered'  => 'Sampai',
        'cancelled'  => 'Dibatalkan',
        'canceled'   => 'Dibatalkan'
    ];

    return $labels[$status] ?? ucfirst($status ?: 'Belum ada status');
}

function statusClass($status)
{
    $status = strtolower(trim((string) $status));

    if (in_array($status, ['paid', 'completed', 'delivered'])) {
        return 'success';
    }

    if (in_array($status, ['shipped', 'processing'])) {
        return 'info';
    }

    if (in_array($status, ['cancelled', 'canceled'])) {
        return 'danger';
    }

    return 'warning';
}

/* =========================
   AMBIL STATISTIK
========================= */

// Total pengguna selain admin
$totalPengguna = getTotal(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role != 'admin'"
);

// Total produk
$totalProduk = getTotal(
    $conn,
    "SELECT COUNT(*) AS total
     FROM products"
);

// Total pesanan selain yang dibatalkan
$totalPesanan = getTotal(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE LOWER(status) NOT IN ('cancelled', 'canceled')"
);

// Total nilai pesanan selain yang dibatalkan
$totalPenjualan = getTotal(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM orders
     WHERE LOWER(status) NOT IN ('cancelled', 'canceled')"
);

/* =========================
   AMBIL 8 PESANAN TERBARU
========================= */
$pesananTerbaru = [];

$queryPesanan = mysqli_query(
    $conn,
    "SELECT
        id,
        recipient_name AS buyer_name,
        total,
        status,
        created_at
     FROM orders
     ORDER BY created_at DESC
     LIMIT 8"
);

if ($queryPesanan) {
    while ($row = mysqli_fetch_assoc($queryPesanan)) {
        $pesananTerbaru[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - MedanMart</title>

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

            --blue: #3b82f6;
            --green: #16a34a;
            --purple: #8b5cf6;

            --shadow: 0 5px 18px rgba(15, 23, 42, 0.04);
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
            color: inherit;
        }

        /* NAVBAR */

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .navbar-inner {
            width: min(1200px, 92%);
            min-height: 76px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
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
            justify-content: flex-end;
            gap: 18px;
        }

        .admin-label {
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 16px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #dc2626;
            font-size: 13px;
            font-weight: 700;
            transition: background 0.2s ease;
        }

        .logout:hover {
            background: #fef2f2;
        }

        /* MAIN */

        .container {
            width: min(1200px, 92%);
            margin: 34px auto;
        }

        .page-heading {
            margin-bottom: 26px;
        }

        .page-heading h1 {
            font-size: clamp(25px, 3vw, 32px);
            line-height: 1.35;
            margin-bottom: 7px;
            overflow-wrap: anywhere;
        }

        .page-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        /* WELCOME BANNER */

        .welcome {
            position: relative;
            overflow: hidden;
            background: linear-gradient(
                120deg,
                #f97316,
                #fb923c
            );
            color: var(--white);
            border-radius: 22px;
            padding: 34px;
            margin-bottom: 32px;
            box-shadow: 0 12px 30px rgba(249, 115, 22, 0.14);
        }

        .welcome::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: -45px;
            top: -100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.13);
            pointer-events: none;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            max-width: 700px;
        }

        .welcome-label {
            display: inline-block;
            padding: 5px 12px;
            margin-bottom: 13px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.15);
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .welcome h2 {
            font-size: clamp(23px, 3vw, 31px);
            line-height: 1.4;
            margin-bottom: 9px;
            overflow-wrap: anywhere;
        }

        .welcome p {
            color: #fffaf5;
            font-size: 14px;
            line-height: 1.8;
            max-width: 590px;
        }

        /* NAVIGATION CARDS */

        .quick-links {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 15px;
            background: var(--white);
            box-shadow: var(--shadow);
            transition: transform 0.2s ease,
                        border-color 0.2s ease,
                        box-shadow 0.2s ease;
        }

        .quick-link:hover {
            transform: translateY(-3px);
            border-color: #fdba74;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        }

        .quick-icon {
            width: 45px;
            height: 45px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            background: var(--cream);
            color: var(--primary-dark);
            border-radius: 12px;
            font-size: 22px;
        }

        .quick-link h3 {
            font-size: 14px;
            margin-bottom: 2px;
        }

        .quick-link p {
            color: var(--muted);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        /* STATISTICS */

        .section-heading {
            margin-bottom: 19px;
        }

        .section-heading h2 {
            font-size: 22px;
            line-height: 1.4;
            margin-bottom: 4px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 19px;
            margin-bottom: 35px;
        }

        .stat-card {
            min-width: 0;
            padding: 23px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 17px;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease,
                        box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.07);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            font-size: 24px;
        }

        .stat-orange .stat-icon {
            background: #fff7ed;
        }

        .stat-blue .stat-icon {
            background: #eff6ff;
        }

        .stat-green .stat-icon {
            background: #f0fdf4;
        }

        .stat-purple .stat-icon {
            background: #f5f3ff;
        }

        .stat-tag {
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            text-align: right;
        }

        .stat-card h3 {
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: clamp(23px, 2.4vw, 30px);
            font-weight: 800;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .stat-orange .stat-value {
            color: var(--primary-dark);
        }

        .stat-blue .stat-value {
            color: var(--blue);
        }

        .stat-green .stat-value {
            color: var(--green);
        }

        .stat-purple .stat-value {
            color: var(--purple);
        }

        .stat-description {
            margin-top: 8px;
            color: var(--muted);
            font-size: 12px;
        }

        /* TABLE */

        .table-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
        }

        .table-header h2 {
            font-size: 19px;
            line-height: 1.4;
        }

        .table-header p {
            color: var(--muted);
            font-size: 13px;
            margin-top: 3px;
        }

        .view-all {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 700;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }

        thead {
            background: var(--cream);
        }

        th {
            padding: 15px 20px;
            color: #9a3412;
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            vertical-align: middle;
        }

        tbody tr {
            transition: background 0.15s ease;
        }

        tbody tr:hover {
            background: #fffaf5;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .order-id {
            color: var(--primary-dark);
            font-weight: 700;
            white-space: nowrap;
        }

        .buyer-name {
            color: var(--text);
            font-weight: 600;
        }

        .amount {
            font-weight: 700;
            white-space: nowrap;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
        }

        .status-info {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .empty-state {
            padding: 42px 20px;
            text-align: center;
            color: var(--muted);
        }

        .empty-icon {
            font-size: 36px;
            margin-bottom: 8px;
        }

        .empty-state h3 {
            color: var(--text);
            font-size: 16px;
            margin-bottom: 5px;
        }

        .empty-state p {
            font-size: 13px;
        }

        /* FOOTER */

        .footer {
            padding: 25px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        /* RESPONSIVE */

        @media (max-width: 950px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .quick-links {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .navbar-inner {
                min-height: 68px;
            }

            .brand {
                font-size: 20px;
            }

            .brand-icon {
                width: 39px;
                height: 39px;
            }

            .nav-right {
                gap: 9px;
            }

            .admin-label {
                display: none;
            }

            .logout {
                padding: 9px 11px;
                font-size: 12px;
            }

            .container {
                margin: 25px auto;
            }

            .welcome {
                padding: 26px 22px;
                border-radius: 18px;
                margin-bottom: 25px;
            }

            .quick-links {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 27px;
            }

            .stats {
                gap: 12px;
                margin-bottom: 27px;
            }

            .stat-card {
                padding: 17px;
            }

            .stat-top {
                align-items: flex-start;
                margin-bottom: 14px;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 21px;
            }

            .stat-tag {
                font-size: 10px;
            }

            .section-heading h2 {
                font-size: 20px;
            }

            .table-header {
                padding: 18px;
            }
        }

        @media (max-width: 380px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 27px;
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

            <a href="dashboard.php" class="brand"
               aria-label="MedanMart Dashboard">

                <span class="brand-icon">🛍️</span>

                <span class="brand-name">
                    Medan<span>Mart</span>
                </span>
            </a>

            <div class="nav-right">
                <span class="admin-label">
                    Panel Administrator
                </span>

                <a href="../logout.php" class="logout">
                    <span aria-hidden="true">↪</span>
                    Logout
                </a>
            </div>

        </div>
    </header>

    <main class="container">

        <!-- PAGE HEADING -->
        <section class="page-heading">
            <h1>Dashboard Admin</h1>
            <p>
                Kelola dan pantau aktivitas MedanMart dari satu halaman.
            </p>
        </section>

        <!-- WELCOME -->
        <section class="welcome">
            <div class="welcome-content">

                <span class="welcome-label">
                    PANEL ADMINISTRATOR
                </span>

                <h2>
                    Selamat datang,
                    <?= escape($adminName); ?> 👋
                </h2>

                <p>
                    Senang melihat kamu kembali di MedanMart.
                    Pantau data pengguna, produk, dan pesanan
                    untuk memastikan aktivitas marketplace berjalan
                    dengan baik.
                </p>

            </div>
        </section>

        <!-- QUICK LINKS -->
        <section class="quick-links" aria-label="Menu administrasi">

            <a href="orders.php" class="quick-link">
                <div class="quick-icon" aria-hidden="true">🛒</div>
                <div>
                    <h3>Kelola Pesanan</h3>
                    <p>Lihat dan periksa pesanan</p>
                </div>
            </a>

            <a href="users.php" class="quick-link">
                <div class="quick-icon" aria-hidden="true">👥</div>
                <div>
                    <h3>Kelola Pengguna</h3>
                    <p>Periksa data pengguna</p>
                </div>
            </a>

            <a href="reports.php" class="quick-link">
                <div class="quick-icon" aria-hidden="true">📊</div>
                <div>
                    <h3>Laporan</h3>
                    <p>Lihat ringkasan marketplace</p>
                </div>
            </a>

        </section>

        <!-- STATISTICS -->
        <section aria-labelledby="statistics-title">

            <div class="section-heading">
                <h2 id="statistics-title">Ringkasan Marketplace</h2>
                <p>
                    Informasi terbaru mengenai aktivitas MedanMart.
                </p>
            </div>

            <div class="stats">

                <article class="stat-card stat-orange">
                    <div class="stat-top">
                        <div class="stat-icon" aria-hidden="true">👥</div>
                        <span class="stat-tag">PENGGUNA</span>
                    </div>

                    <h3>Total Pengguna</h3>

                    <div class="stat-value">
                        <?= number_format((int) $totalPengguna, 0, ',', '.'); ?>
                    </div>

                    <p class="stat-description">
                        Seluruh pengguna selain admin
                    </p>
                </article>

                <article class="stat-card stat-blue">
                    <div class="stat-top">
                        <div class="stat-icon" aria-hidden="true">📦</div>
                        <span class="stat-tag">PRODUK</span>
                    </div>

                    <h3>Total Produk</h3>

                    <div class="stat-value">
                        <?= number_format((int) $totalProduk, 0, ',', '.'); ?>
                    </div>

                    <p class="stat-description">
                        Produk yang tercatat
                    </p>
                </article>

                <article class="stat-card stat-green">
                    <div class="stat-top">
                        <div class="stat-icon" aria-hidden="true">🛒</div>
                        <span class="stat-tag">PESANAN</span>
                    </div>

                    <h3>Total Pesanan</h3>

                    <div class="stat-value">
                        <?= number_format((int) $totalPesanan, 0, ',', '.'); ?>
                    </div>

                    <p class="stat-description">
                        Tidak termasuk pesanan batal
                    </p>
                </article>

                <article class="stat-card stat-purple">
                    <div class="stat-top">
                        <div class="stat-icon" aria-hidden="true">💰</div>
                        <span class="stat-tag">NILAI PESANAN</span>
                    </div>

                    <h3>Total Nilai Pesanan</h3>

                    <div class="stat-value">
                        <?= rupiah($totalPenjualan); ?>
                    </div>

                    <p class="stat-description">
                        Nilai pesanan selain yang dibatalkan
                    </p>
                </article>

            </div>

        </section>

        <!-- RECENT ORDERS -->
        <section aria-labelledby="orders-title">

            <div class="table-card">

                <div class="table-header">
                    <div>
                        <h2 id="orders-title">Pesanan Terbaru</h2>
                        <p>
                            Delapan pesanan terakhir yang tercatat.
                        </p>
                    </div>

                    <a href="orders.php" class="view-all">
                        Lihat semua pesanan <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="table-wrapper">

                    <table>
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Nama Pembeli</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($pesananTerbaru)): ?>

                                <?php foreach ($pesananTerbaru as $pesanan): ?>

                                    <tr>
                                        <td class="order-id">
                                            #<?= escape($pesanan['id']); ?>
                                        </td>

                                        <td class="buyer-name">
                                            <?= escape($pesanan['buyer_name'] ?? '-'); ?>
                                        </td>

                                        <td class="amount">
                                            <?= rupiah($pesanan['total'] ?? 0); ?>
                                        </td>

                                        <td>
                                            <span class="status status-<?= escape(statusClass($pesanan['status'] ?? '')); ?>">
                                                <?= escape(statusLabel($pesanan['status'] ?? '')); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php
                                            $tanggal = $pesanan['created_at'] ?? '';

                                            if (
                                                $tanggal !== '' &&
                                                strtotime($tanggal) !== false
                                            ) {
                                                echo escape(
                                                    date('d/m/Y H:i', strtotime($tanggal))
                                                );
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="empty-icon">📭</div>
                                            <h3>Belum Ada Pesanan</h3>
                                            <p>
                                                Pesanan akan muncul di sini
                                                setelah tersedia.
                                            </p>
                                        </div>
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>

                </div>

            </div>

        </section>

    </main>

    <footer class="footer">
        &copy; <?= date('Y'); ?> MedanMart.
        Belanja mudah, belanja nyaman.
    </footer>

</body>
</html>
