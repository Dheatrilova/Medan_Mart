<?php

session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];

/* =========================
   AMBIL TOKO SELLER
========================= */

$store_query = mysqli_query(
    $conn,
    "SELECT id FROM stores WHERE seller_id = '$seller_id' LIMIT 1"
);

if (!$store_query || mysqli_num_rows($store_query) === 0) {
    die("Toko seller tidak ditemukan.");
}

$store = mysqli_fetch_assoc($store_query);
$store_id = (int) $store['id'];


/* =========================
   AMBIL PESANAN SELLER
========================= */

$order_query = mysqli_query(
    $conn,
    "
    SELECT DISTINCT
        orders.id,
        orders.order_code,
        orders.recipient_name,
        orders.phone,
        orders.address,
        orders.shipping_method,
        orders.shipping_cost,
        orders.total,
        orders.payment_method,
        orders.status,
        orders.created_at
    FROM orders
    JOIN order_details
        ON orders.id = order_details.order_id
    JOIN products
        ON order_details.product_id = products.id
    WHERE products.store_id = '$store_id'
    ORDER BY orders.id DESC
    "
);

if (!$order_query) {
    die("Gagal mengambil data pesanan: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pesanan Masuk - MedanMart</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f7f7f7;
            color: #333;
        }

        .header {
            background: #ff7a00;
            color: white;
            padding: 22px 40px;
        }

        .header h1 {
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #ff7a00;
            font-weight: bold;
        }

        .success {
            background: #e8f8ed;
            color: #218838;
            border: 1px solid #b7e4c7;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .order-card {
            background: white;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .order-code {
            font-size: 20px;
            font-weight: bold;
            color: #222;
        }

        .date {
            color: #777;
            font-size: 14px;
            margin-top: 5px;
        }

        .status {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-shipped {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .info {
            background: #fafafa;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
        }

        .info p {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .info p:last-child {
            margin-bottom: 0;
        }

        .products-title {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            gap: 15px;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-name {
            font-weight: bold;
        }

        .product-detail {
            color: #777;
            font-size: 14px;
            margin-top: 4px;
        }

        .product-price {
            color: #ff7a00;
            font-weight: bold;
            white-space: nowrap;
        }

        .total {
            text-align: right;
            margin-top: 18px;
            font-size: 20px;
            font-weight: bold;
            color: #ff7a00;
        }

        .update-box {
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .update-box label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .update-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .update-form select {
            padding: 11px 13px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            flex: 1;
            max-width: 250px;
        }

        .btn-update {
            border: none;
            background: #ff7a00;
            color: white;
            padding: 11px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-update:hover {
            background: #e66d00;
        }

        .empty {
            background: white;
            padding: 50px;
            text-align: center;
            border-radius: 14px;
            color: #777;
        }

        @media (max-width: 600px) {

            .header {
                padding: 20px;
            }

            .container {
                width: 94%;
            }

            .product-item {
                flex-direction: column;
                align-items: flex-start;
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

    <div class="header">

        <h1>📦 Pesanan Masuk</h1>

        <p>
            Kelola pesanan yang masuk ke toko Anda
        </p>

    </div>


    <div class="container">

        <a href="dashboard.php" class="back">
            ← Kembali ke Dashboard
        </a>


        <?php if (isset($_GET['success'])): ?>

            <div class="success">
                ✅ Status pesanan berhasil diperbarui.
            </div>

        <?php endif; ?>


        <?php if (mysqli_num_rows($order_query) === 0): ?>

            <div class="empty">

                <h2>Belum Ada Pesanan</h2>

                <p style="margin-top:10px;">
                    Pesanan dari customer akan muncul di halaman ini.
                </p>

            </div>

        <?php else: ?>


            <?php while ($order = mysqli_fetch_assoc($order_query)): ?>

                <div class="order-card">

                    <div class="order-header">

                        <div>

                            <div class="order-code">
                                <?= htmlspecialchars($order['order_code']); ?>
                            </div>

                            <div class="date">
                                <?= date(
                                    'd M Y, H:i',
                                    strtotime($order['created_at'])
                                ); ?>
                            </div>

                        </div>


                        <span class="status status-<?= htmlspecialchars($order['status']); ?>">

                            <?= htmlspecialchars($order['status']); ?>

                        </span>

                    </div>


                    <div class="info">

                        <p>
                            <strong>👤 Penerima:</strong>
                            <?= htmlspecialchars($order['recipient_name']); ?>
                        </p>

                        <p>
                            <strong>📱 No. HP:</strong>
                            <?= htmlspecialchars($order['phone']); ?>
                        </p>

                        <p>
                            <strong>📍 Alamat:</strong>
                            <?= nl2br(htmlspecialchars($order['address'])); ?>
                        </p>

                        <p>
                            <strong>🚚 Pengiriman:</strong>
                            <?= htmlspecialchars($order['shipping_method'] ?? '-'); ?>
                        </p>

                        <p>
                            <strong>💳 Pembayaran:</strong>
                            <?= strtoupper(htmlspecialchars($order['payment_method'])); ?>
                        </p>

                    </div>


                    <div class="products-title">
                        Produk yang Dibeli
                    </div>


                    <?php

                    $order_id = (int) $order['id'];

                    $detail_query = mysqli_query(
                        $conn,
                        "
                        SELECT
                            order_details.product_name,
                            order_details.price,
                            order_details.quantity,
                            order_details.subtotal
                        FROM order_details
                        JOIN products
                            ON order_details.product_id = products.id
                        WHERE order_details.order_id = '$order_id'
                        AND products.store_id = '$store_id'
                        "
                    );

                    ?>


                    <?php while ($detail = mysqli_fetch_assoc($detail_query)): ?>

                        <div class="product-item">

                            <div>

                                <div class="product-name">
                                    <?= htmlspecialchars($detail['product_name']); ?>
                                </div>

                                <div class="product-detail">
                                    <?= $detail['quantity']; ?> ×
                                    Rp <?= number_format($detail['price'], 0, ',', '.'); ?>
                                </div>

                            </div>

                            <div class="product-price">
                                Rp <?= number_format(
                                    $detail['subtotal'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>
                            </div>

                        </div>

                    <?php endwhile; ?>


                    <div class="total">

                        Total Pesanan:
                        Rp <?= number_format(
                            $order['total'],
                            0,
                            ',',
                            '.'
                        ); ?>

                    </div>


                    <!-- UPDATE STATUS -->

                    <div class="update-box">

                        <label>
                            Ubah Status Pesanan
                        </label>


                        <form
                            method="POST"
                            action="update_order_status.php"
                            class="update-form"
                        >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= $order['id']; ?>"
                            >


                            <select name="status">

                                <option
                                    value="pending"
                                    <?= $order['status'] === 'pending' ? 'selected' : ''; ?>
                                >
                                    Pending
                                </option>

                                <option
                                    value="paid"
                                    <?= $order['status'] === 'paid' ? 'selected' : ''; ?>
                                >
                                    Paid
                                </option>

                                <option
                                    value="shipped"
                                    <?= $order['status'] === 'shipped' ? 'selected' : ''; ?>
                                >
                                    Shipped
                                </option>

                                <option
                                    value="completed"
                                    <?= $order['status'] === 'completed' ? 'selected' : ''; ?>
                                >
                                    Completed
                                </option>

                                <option
                                    value="cancelled"
                                    <?= $order['status'] === 'cancelled' ? 'selected' : ''; ?>
                                >
                                    Cancelled
                                </option>

                            </select>


                            <button
                                type="submit"
                                class="btn-update"
                            >
                                Update Status
                            </button>

                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php endif; ?>

    </div>

</body>

</html>