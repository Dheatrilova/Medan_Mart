
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/* =========================
   CEK LOGIN SELLER
========================= */
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'seller'
) {
    header("Location: ../login.php");
    exit;
}

$seller_id = (int) $_SESSION['user_id'];

/* =========================
   AMBIL TOKO SELLER
========================= */
$store_stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM stores WHERE seller_id = ? LIMIT 1"
);

mysqli_stmt_bind_param($store_stmt, "i", $seller_id);
mysqli_stmt_execute($store_stmt);
$store_result = mysqli_stmt_get_result($store_stmt);
$store = mysqli_fetch_assoc($store_result);
mysqli_stmt_close($store_stmt);

if (!$store) {
    die("Toko seller tidak ditemukan.");
}

$store_id = (int) $store['id'];

/* =========================
   AMBIL PESANAN TOKO
========================= */
$order_stmt = mysqli_prepare(
    $conn,
    "SELECT DISTINCT
        o.id,
        o.order_code,
        o.recipient_name,
        o.phone,
        o.address,
        o.shipping_method,
        o.shipping_cost,
        o.total,
        o.payment_method,
        o.status,
        o.created_at
     FROM orders o
     INNER JOIN order_details od ON od.order_id = o.id
     INNER JOIN products p ON p.id = od.product_id
     WHERE p.store_id = ?
     ORDER BY o.id DESC"
);

mysqli_stmt_bind_param($order_stmt, "i", $store_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

/* =========================
   HELPER TAMPILAN
========================= */
function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($value)
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

$status_labels = [
    'pending'   => 'Pending',
    'paid'      => 'Paid',
    'shipped'   => 'Shipped',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk | MedanMart</title>

    <style>
        :root {
            --orange: #F97316;
            --orange-dark: #EA580C;
            --cream: #FFF7ED;
            --text: #1F2937;
            --muted: #6B7280;
            --border: #E5E7EB;
            --background: #F8FAFC;
            --white: #FFFFFF;
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
            line-height: 1.6;
        }

        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 15px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            color: var(--orange-dark);
            font-size: 22px;
            font-weight: 800;
            text-decoration: none;
        }

        .topbar-label {
            color: var(--muted);
            font-size: 14px;
        }

        .hero {
            background: linear-gradient(120deg, #FFF7ED, #FFEDD5);
            padding: 32px 5%;
            border-bottom: 1px solid #FED7AA;
        }

        .hero-inner,
        .container {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: clamp(25px, 4vw, 32px);
            line-height: 1.3;
            margin-bottom: 7px;
        }

        .hero p {
            color: var(--muted);
            font-size: 15px;
        }

        .container {
            padding: 26px 20px 48px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--orange-dark);
            text-decoration: none;
            font-weight: 700;
            margin-bottom: 22px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .notice {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .notice.success {
            color: #166534;
            background: #DCFCE7;
            border: 1px solid #BBF7D0;
        }

        .notice.error {
            color: #991B1B;
            background: #FEE2E2;
            border: 1px solid #FECACA;
        }

        .section-heading {
            margin-bottom: 18px;
        }

        .section-heading h2 {
            font-size: 21px;
            margin-bottom: 3px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .order-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, 0.04);
            min-width: 0;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 14px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .order-code {
            font-size: 19px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .order-date {
            color: var(--muted);
            font-size: 13px;
            margin-top: 4px;
        }

        .status {
            display: inline-block;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-paid {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .status-shipped {
            background: #E0E7FF;
            color: #3730A3;
        }

        .status-completed {
            background: #DCFCE7;
            color: #166534;
        }

        .status-cancelled {
            background: #FEE2E2;
            color: #991B1B;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin: 20px 0;
        }

        .info-item {
            background: #F9FAFB;
            border: 1px solid #F3F4F6;
            padding: 13px 14px;
            border-radius: 10px;
            min-width: 0;
        }

        .info-label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .products-title {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid #F3F4F6;
        }

        .product-name {
            font-size: 14px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .product-detail {
            font-size: 13px;
            color: var(--muted);
            margin-top: 3px;
        }

        .product-price {
            color: var(--orange-dark);
            font-weight: 800;
            white-space: nowrap;
            font-size: 14px;
        }

        .subtotal-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-top: 17px;
            padding: 14px 16px;
            background: var(--cream);
            border-radius: 10px;
        }

        .subtotal-label {
            font-size: 14px;
            font-weight: 700;
        }

        .subtotal-value {
            font-size: 18px;
            font-weight: 800;
            color: var(--orange-dark);
            text-align: right;
        }

        .shipping-note {
            color: var(--muted);
            font-size: 12px;
            margin-top: 8px;
        }

        .update-box {
            border-top: 1px solid var(--border);
            margin-top: 22px;
            padding-top: 20px;
        }

        .update-box label {
            display: block;
            font-weight: 800;
            font-size: 14px;
            margin-bottom: 9px;
        }

        .update-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .update-form select {
            width: 100%;
            max-width: 300px;
            min-width: 0;
            padding: 12px 13px;
            border: 1px solid #D1D5DB;
            border-radius: 9px;
            background: white;
            color: var(--text);
            font-size: 14px;
            outline: none;
        }

        .update-form select:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        .btn-update {
            border: none;
            background: var(--orange);
            color: white;
            padding: 12px 18px;
            border-radius: 9px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-update:hover {
            background: var(--orange-dark);
        }

        .empty {
            background: white;
            text-align: center;
            padding: 48px 20px;
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .empty-icon {
            font-size: 38px;
            margin-bottom: 8px;
        }

        .empty h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .empty p {
            color: var(--muted);
            font-size: 14px;
        }

        @media (max-width: 640px) {
            .topbar {
                padding: 14px 5%;
            }

            .brand {
                font-size: 20px;
            }

            .topbar-label {
                font-size: 12px;
                text-align: right;
            }

            .hero {
                padding: 26px 5%;
            }

            .container {
                padding: 22px 15px 36px;
            }

            .order-card {
                padding: 17px;
                border-radius: 13px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 9px;
            }

            .product-item {
                align-items: flex-start;
            }

            .subtotal-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }

            .subtotal-value {
                text-align: left;
            }

            .update-form {
                flex-direction: column;
                align-items: stretch;
            }

            .update-form select {
                max-width: none;
            }

            .btn-update {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <a href="dashboard.php" class="brand">MedanMart</a>
    <span class="topbar-label">Panel Penjual</span>
</header>

<section class="hero">
    <div class="hero-inner">
        <h1>📦 Pesanan Masuk</h1>
        <p>Pantau detail pembelian dan kelola status pesanan toko Anda.</p>
    </div>
</section>

<main class="container">

    <a href="dashboard.php" class="back-link">
        ← Kembali ke Dashboard
    </a>

    <?php if (isset($_GET['success'])): ?>
        <div class="notice success">
            Status pesanan berhasil diperbarui.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice error">
            Pesanan tidak berhasil diproses. Silakan periksa kembali.
        </div>
    <?php endif; ?>

    <div class="section-heading">
        <h2>Daftar Pesanan</h2>
        <p>Pesanan yang berisi produk dari toko Anda akan ditampilkan di sini.</p>
    </div>

    <?php if (mysqli_num_rows($order_result) === 0): ?>

        <div class="empty">
            <div class="empty-icon">🛍️</div>
            <h2>Belum Ada Pesanan</h2>
            <p>Pesanan pelanggan akan muncul di halaman ini setelah ada pembelian.</p>
        </div>

    <?php else: ?>

        <?php while ($order = mysqli_fetch_assoc($order_result)): ?>

            <?php
            $order_id = (int) $order['id'];
            $current_status = (string) $order['status'];

            $status_class = isset($status_labels[$current_status])
                ? $current_status
                : 'pending';

            $detail_stmt = mysqli_prepare(
                $conn,
                "SELECT
                    od.product_name,
                    od.price,
                    od.quantity,
                    od.subtotal
                 FROM order_details od
                 INNER JOIN products p ON p.id = od.product_id
                 WHERE od.order_id = ?
                   AND p.store_id = ?"
            );

            mysqli_stmt_bind_param(
                $detail_stmt,
                "ii",
                $order_id,
                $store_id
            );

            mysqli_stmt_execute($detail_stmt);
            $detail_result = mysqli_stmt_get_result($detail_stmt);

            $details = [];
            $store_subtotal = 0;

            while ($detail = mysqli_fetch_assoc($detail_result)) {
                $details[] = $detail;
                $store_subtotal += (float) $detail['subtotal'];
            }

            mysqli_stmt_close($detail_stmt);
            ?>

            <article class="order-card">

                <div class="order-header">
                    <div>
                        <div class="order-code">
                            Pesanan #<?= e($order['order_code']); ?>
                        </div>

                        <div class="order-date">
                            <?= e(date(
                                'd M Y, H:i',
                                strtotime($order['created_at'])
                            )); ?>
                        </div>
                    </div>

                    <span class="status status-<?= e($status_class); ?>">
                        <?= e($status_labels[$current_status] ?? $current_status); ?>
                    </span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Nama Penerima</span>
                        <div class="info-value">
                            <?= e($order['recipient_name']); ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Nomor HP</span>
                        <div class="info-value">
                            <?= e($order['phone']); ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Alamat Pengiriman</span>
                        <div class="info-value">
                            <?= nl2br(e($order['address'])); ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Metode Pengiriman</span>
                        <div class="info-value">
                            <?= e($order['shipping_method'] ?: '-'); ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Metode Pembayaran</span>
                        <div class="info-value">
                            <?= e(strtoupper($order['payment_method'] ?? '-')); ?>
                        </div>
                    </div>
                </div>

                <div class="products-title">Produk dari Toko Anda</div>

                <?php if (count($details) > 0): ?>

                    <?php foreach ($details as $detail): ?>
                        <div class="product-item">
                            <div>
                                <div class="product-name">
                                    <?= e($detail['product_name']); ?>
                                </div>

                                <div class="product-detail">
                                    <?= (int) $detail['quantity']; ?> ×
                                    <?= rupiah($detail['price']); ?>
                                </div>
                            </div>

                            <div class="product-price">
                                <?= rupiah($detail['subtotal']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="product-detail">
                        Detail produk tidak ditemukan.
                    </p>

                <?php endif; ?>

                <div class="subtotal-row">
                    <span class="subtotal-label">Subtotal Produk Toko</span>
                    <span class="subtotal-value">
                        <?= rupiah($store_subtotal); ?>
                    </span>
                </div>

                <p class="shipping-note">
                    Subtotal di atas hanya menghitung produk milik toko Anda.
                    Ongkos kirim dan total seluruh checkout tidak dijumlahkan
                    ke subtotal toko.
                </p>

                <div class="update-box">
                    <label for="status-<?= $order_id; ?>">
                        Status Pesanan
                    </label>

                    <form
                        method="POST"
                        action="update_order.php"
                        class="update-form"
                        onsubmit="return confirm('Yakin ingin memperbarui status pesanan ini?');"
                    >
                        <input
                            type="hidden"
                            name="order_id"
                            value="<?= $order_id; ?>"
                        >

                        <select
                            name="status"
                            id="status-<?= $order_id; ?>"
                            required
                        >
                            <?php foreach ($status_labels as $value => $label): ?>
                                <option
                                    value="<?= e($value); ?>"
                                    <?= $current_status === $value ? 'selected' : ''; ?>
                                >
                                    <?= e($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn-update">
                            Simpan Status
                        </button>
                    </form>
                </div>

            </article>

        <?php endwhile; ?>

    <?php endif; ?>

</main>

<?php
mysqli_stmt_close($order_stmt);
?>

</body>
</html>
