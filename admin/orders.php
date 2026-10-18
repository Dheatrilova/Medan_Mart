
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| CEK LOGIN ADMIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA PESANAN
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        orders.id,
        orders.order_code,
        orders.recipient_name,
        orders.phone,
        orders.address,
        orders.shipping_method,
        orders.shipping_cost,
        orders.subtotal,
        orders.total,
        orders.payment_method,
        orders.payment_proof,
        orders.status,
        orders.created_at,
        users.name AS buyer_name,
        users.email AS buyer_email
    FROM orders
    JOIN users ON orders.user_id = users.id
    ORDER BY orders.created_at DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    error_log("Query admin orders gagal: " . mysqli_error($conn));
    die("Data pesanan gagal dimuat. Silakan coba kembali.");
}

/*
|--------------------------------------------------------------------------
| FUNGSI FORMAT
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function rupiah($value)
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function statusLabel($status)
{
    $labels = [
        'pending'   => 'Menunggu Pembayaran',
        'paid'      => 'Sudah Dibayar',
        'shipped'   => 'Sedang Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan'
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function statusClass($status)
{
    $classes = [
        'pending'   => 'pending',
        'paid'      => 'paid',
        'shipped'   => 'shipped',
        'completed' => 'completed',
        'cancelled' => 'cancelled'
    ];

    return $classes[$status] ?? 'unknown';
}

/*
|--------------------------------------------------------------------------
| STATISTIK PESANAN
|--------------------------------------------------------------------------
*/

$totalOrders = mysqli_num_rows($result);
$orders = [];

$stats = [
    'pending'   => 0,
    'paid'      => 0,
    'shipped'   => 0,
    'completed' => 0,
    'cancelled' => 0
];

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;

    if (isset($stats[$row['status']])) {
        $stats[$row['status']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Pesanan | Admin MedanMart</title>

    <style>
        :root {
            --primary: #F97316;
            --primary-dark: #EA580C;
            --cream: #FFF7ED;
            --text: #1F2937;
            --muted: #6B7280;
            --border: #E5E7EB;
            --background: #F8FAFC;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
        }

        a {
            color: inherit;
        }

        /* NAVBAR */

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .nav-inner {
            width: min(1120px, 92%);
            min-height: 72px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
            font-size: 21px;
            font-weight: 800;
            text-decoration: none;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            background: var(--cream);
            border-radius: 13px;
            font-size: 23px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-label {
            color: var(--muted);
            font-size: 13px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn-primary {
            color: var(--white);
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: var(--white);
            border-color: var(--border);
            color: var(--text);
        }

        .btn-outline:hover {
            background: var(--cream);
            border-color: var(--primary);
        }

        /* MAIN */

        .container {
            width: min(1120px, 92%);
            margin: 32px auto 50px;
        }

        .breadcrumb {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 15px;
        }

        .breadcrumb a {
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0 0 7px;
            font-size: clamp(25px, 4vw, 32px);
            letter-spacing: -.7px;
        }

        .page-heading p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        /* STATISTICS */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            padding: 20px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .025);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 14px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--muted);
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--cream);
            font-size: 19px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.2;
        }

        .stat-note {
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
        }

        /* SECTION */

        .section-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .035);
        }

        .section-heading {
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 18px;
        }

        .section-heading p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .order-list {
            padding: 22px;
        }

        /* ORDER CARD */

        .order-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: border-color .2s, box-shadow .2s;
        }

        .order-card:last-child {
            margin-bottom: 0;
        }

        .order-card:hover {
            border-color: #FDBA74;
            box-shadow: 0 5px 18px rgba(249, 115, 22, .06);
        }

        .order-header {
            padding: 19px 21px;
            background: #FFFCF9;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .order-code {
            font-size: 16px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .order-date {
            color: var(--muted);
            font-size: 12px;
            margin-top: 5px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            white-space: normal;
            text-align: center;
        }

        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-paid {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-shipped {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .status-completed {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-cancelled {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status-unknown {
            background: #F3F4F6;
            color: #374151;
        }

        .order-body {
            padding: 22px;
        }

        .subheading {
            margin: 0 0 13px;
            font-size: 14px;
            font-weight: 800;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .info-box {
            min-width: 0;
            padding: 14px 15px;
            border: 1px solid #F0F1F3;
            background: #FAFAFA;
            border-radius: 12px;
        }

        .info-label {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .info-value {
            display: block;
            font-size: 13px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        /* TOTAL */

        .payment-summary {
            margin-top: 20px;
            padding: 18px 20px;
            border: 1px solid #FED7AA;
            background: var(--cream);
            border-radius: 13px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            color: #6B7280;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-total {
            padding-top: 12px;
            margin-top: 12px;
            border-top: 1px dashed #FDBA74;
            color: var(--primary-dark);
            font-size: 17px;
            font-weight: 800;
        }

        .summary-total span:last-child {
            text-align: right;
        }

        /* PAYMENT PROOF */

        .proof-section {
            margin-top: 20px;
            padding-top: 19px;
            border-top: 1px solid var(--border);
        }

        .proof-image {
            display: block;
            width: auto;
            max-width: 100%;
            max-height: 280px;
            margin-top: 12px;
            object-fit: contain;
            object-position: left;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #FAFAFA;
        }

        .proof-link {
            display: inline-block;
            margin-top: 10px;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .proof-link:hover {
            text-decoration: underline;
        }

        .empty-state {
            padding: 55px 20px;
            text-align: center;
        }

        .empty-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 15px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: var(--cream);
            font-size: 30px;
        }

        .empty-state h3 {
            margin: 0 0 7px;
            font-size: 19px;
        }

        .empty-state p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .footer {
            padding: 22px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        /* RESPONSIVE */

        @media (max-width: 850px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .nav-inner {
                min-height: 65px;
            }

            .brand {
                font-size: 17px;
            }

            .brand-icon {
                width: 36px;
                height: 36px;
                font-size: 20px;
            }

            .admin-label {
                display: none;
            }

            .btn {
                padding: 9px 11px;
                font-size: 12px;
            }

            .container {
                margin-top: 23px;
            }

            .page-heading {
                align-items: flex-start;
            }

            .stats-grid {
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 24px;
            }

            .stat-icon {
                width: 32px;
                height: 32px;
                font-size: 16px;
            }

            .section-heading {
                padding: 18px;
            }

            .order-list {
                padding: 12px;
            }

            .order-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 16px;
            }

            .order-body {
                padding: 16px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .payment-summary {
                padding: 15px;
            }

            .summary-total {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="nav-inner">
        <a class="brand" href="dashboard.php">
            <span class="brand-icon">🛍️</span>
            <span>MedanMart</span>
        </a>

        <div class="nav-right">
            <span class="admin-label">Administrator</span>
            <a class="btn btn-outline" href="dashboard.php">
                ← Dashboard
            </a>
        </div>
    </div>
</header>

<main class="container">

    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        &nbsp; / &nbsp; Kelola Pesanan
    </div>

    <section class="page-heading">
        <div>
            <h1>Kelola Pesanan</h1>
            <p>Pantau transaksi dan informasi pesanan pembeli MedanMart.</p>
        </div>
    </section>

    <!-- STATISTIK PESANAN -->

    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Pesanan</span>
                <span class="stat-icon">📦</span>
            </div>
            <div class="stat-number">
                <?= $totalOrders; ?>
            </div>
            <div class="stat-note">Seluruh transaksi</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Menunggu Pembayaran</span>
                <span class="stat-icon">⏳</span>
            </div>
            <div class="stat-number">
                <?= $stats['pending']; ?>
            </div>
            <div class="stat-note">Pesanan berstatus pending</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Sedang Dikirim</span>
                <span class="stat-icon">🚚</span>
            </div>
            <div class="stat-number">
                <?= $stats['shipped']; ?>
            </div>
            <div class="stat-note">Pesanan dalam pengiriman</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Pesanan Selesai</span>
                <span class="stat-icon">✅</span>
            </div>
            <div class="stat-number">
                <?= $stats['completed']; ?>
            </div>
            <div class="stat-note">Transaksi telah selesai</div>
        </div>

    </section>

    <!-- DAFTAR PESANAN -->

    <section class="section-card">

        <div class="section-heading">
            <div>
                <h2>Daftar Transaksi</h2>
                <p>
                    Terdapat <?= $totalOrders; ?> pesanan
                    dalam sistem.
                </p>
            </div>
        </div>

        <?php if ($totalOrders === 0): ?>

            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Belum Ada Pesanan</h3>
                <p>Pesanan pembeli akan ditampilkan di halaman ini.</p>
            </div>

        <?php else: ?>

            <div class="order-list">

                <?php foreach ($orders as $order): ?>

                    <article class="order-card">

                        <div class="order-header">
                            <div>
                                <div class="order-code">
                                    <?= e($order['order_code']); ?>
                                </div>

                                <div class="order-date">
                                    Dipesan pada:
                                    <?php
                                    $timestamp = strtotime($order['created_at']);
                                    echo $timestamp
                                        ? e(date('d-m-Y H:i', $timestamp))
                                        : e($order['created_at']);
                                    ?>
                                </div>
                            </div>

                            <span class="status status-<?= e(statusClass($order['status'])); ?>">
                                <?= e(statusLabel($order['status'])); ?>
                            </span>
                        </div>

                        <div class="order-body">

                            <h3 class="subheading">
                                Informasi Pembeli
                            </h3>

                            <div class="info-grid">

                                <div class="info-box">
                                    <span class="info-label">Nama Pembeli</span>
                                    <span class="info-value">
                                        <?= e($order['buyer_name']); ?>
                                    </span>
                                </div>

                                <div class="info-box">
                                    <span class="info-label">Email</span>
                                    <span class="info-value">
                                        <?= e($order['buyer_email']); ?>
                                    </span>
                                </div>

                                <div class="info-box">
                                    <span class="info-label">Nama Penerima</span>
                                    <span class="info-value">
                                        <?= e($order['recipient_name']); ?>
                                    </span>
                                </div>

                                <div class="info-box">
                                    <span class="info-label">Nomor Telepon</span>
                                    <span class="info-value">
                                        <?= e($order['phone']); ?>
                                    </span>
                                </div>

                                <div class="info-box">
                                    <span class="info-label">Alamat Pengiriman</span>
                                    <span class="info-value">
                                        <?= nl2br(e($order['address'])); ?>
                                    </span>
                                </div>

                                <div class="info-box">
                                    <span class="info-label">Metode Pengiriman</span>
                                    <span class="info-value">
                                        <?= e($order['shipping_method']); ?>
                                    </span>
                                </div>

                                <div class="info-box">
                                    <span class="info-label">Metode Pembayaran</span>
                                    <span class="info-value">
                                        <?= e(strtoupper($order['payment_method'])); ?>
                                    </span>
                                </div>

                                <div class="info-box">
                                    <span class="info-label">Status Transaksi</span>
                                    <span class="info-value">
                                        <?= e(statusLabel($order['status'])); ?>
                                    </span>
                                </div>

                            </div>

                            <!-- RINGKASAN PEMBAYARAN -->

                            <div class="payment-summary">

                                <div class="summary-row">
                                    <span>Subtotal Produk</span>
                                    <span><?= rupiah($order['subtotal']); ?></span>
                                </div>

                                <div class="summary-row">
                                    <span>Ongkos Kirim</span>
                                    <span><?= rupiah($order['shipping_cost']); ?></span>
                                </div>

                                <div class="summary-row summary-total">
                                    <span>Total Pembayaran</span>
                                    <span><?= rupiah($order['total']); ?></span>
                                </div>

                            </div>

                            <!-- BUKTI PEMBAYARAN -->

                            <div class="proof-section">

                                <h3 class="subheading">
                                    Bukti Pembayaran
                                </h3>

                                <?php if (!empty($order['payment_proof'])): ?>

                                    <?php
                                    $proofName = basename(
                                        str_replace('\\', '/', $order['payment_proof'])
                                    );

                                    $proofPath = __DIR__
                                        . "/../uploads/payments/"
                                        . $proofName;
                                    ?>

                                    <?php if (is_file($proofPath)): ?>

                                        <a
                                            href="../uploads/payments/<?= rawurlencode($proofName); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <img
                                                class="proof-image"
                                                src="../uploads/payments/<?= rawurlencode($proofName); ?>"
                                                alt="Bukti pembayaran pesanan <?= e($order['order_code']); ?>"
                                                loading="lazy"
                                            >
                                        </a>

                                        <a
                                            class="proof-link"
                                            href="../uploads/payments/<?= rawurlencode($proofName); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Lihat bukti pembayaran ↗
                                        </a>

                                    <?php else: ?>

                                        <p class="stat-note">
                                            File bukti pembayaran tidak ditemukan.
                                        </p>

                                    <?php endif; ?>

                                <?php elseif (strtolower((string) $order['payment_method']) === 'cod'): ?>

                                    <p class="stat-note">
                                        Pesanan menggunakan COD.
                                        Tidak memerlukan bukti pembayaran saat checkout.
                                    </p>

                                <?php else: ?>

                                    <p class="stat-note">
                                        Pembeli belum mengunggah bukti pembayaran.
                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<footer class="footer">
    © <?= date('Y'); ?> MedanMart · Admin Panel
</footer>

</body>
</html>
