<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "
    SELECT *
    FROM orders
    WHERE user_id = '$user_id'
    ORDER BY created_at DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pesanan Saya - MedanMart</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
        }

        .navbar {
            background: #0d6efd;
            color: white;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h2 {
            margin: 0;
        }

        .navbar a {
            color: white;
            text-decoration: none;
        }

        .container {
            width: 1000px;
            max-width: 90%;
            margin: 40px auto;
        }

        h1 {
            margin-bottom: 25px;
        }

        .order-card {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .order-code {
            font-weight: bold;
            font-size: 18px;
        }

        .status {
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .paid {
            background: #d1e7dd;
            color: #0f5132;
        }

        .shipped {
            background: #cfe2ff;
            color: #084298;
        }

        .completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .info {
            line-height: 1.8;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
            margin-top: 15px;
        }

        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 16px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 7px;
        }

        .btn:hover {
            background: #0b5ed7;
        }

        .btn-payment {
            background: #198754;
        }

        .btn-payment:hover {
            background: #157347;
        }

        .empty {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
        }
    </style>
</head>

<body>

<div class="navbar">

    <h2>🛍️ MedanMart</h2>

    <a href="dashboard.php">
        ← Dashboard
    </a>

</div>

<div class="container">

    <h1>📦 Pesanan Saya</h1>

    <?php if (mysqli_num_rows($result) == 0): ?>

        <div class="empty">
            <h2>Belum Ada Pesanan</h2>

            <p>
                Kamu belum melakukan pembelian.
            </p>

            <a href="products.php" class="btn">
                🛍️ Mulai Belanja
            </a>
        </div>

    <?php else: ?>

        <?php while ($order = mysqli_fetch_assoc($result)): ?>

            <div class="order-card">

                <div class="order-header">

                    <div>
                        <div class="order-code">
                            <?= htmlspecialchars($order['order_code']); ?>
                        </div>

                        <small>
                            <?= date('d-m-Y H:i', strtotime($order['created_at'])); ?>
                        </small>
                    </div>

                    <span class="status <?= $order['status']; ?>">
                        <?= strtoupper($order['status']); ?>
                    </span>

                </div>

                <div class="info">

                    <div>
                        <strong>Penerima:</strong>
                        <?= htmlspecialchars($order['recipient_name']); ?>
                    </div>

                    <div>
                        <strong>Alamat:</strong>
                        <?= htmlspecialchars($order['address']); ?>
                    </div>

                    <div>
                        <strong>Pengiriman:</strong>
                        <?= htmlspecialchars($order['shipping_method']); ?>
                    </div>

                    <div>
                        <strong>Pembayaran:</strong>
                        <?= strtoupper($order['payment_method']); ?>
                    </div>

                    <div class="total">
                        Total:
                        Rp <?= number_format($order['total'], 0, ',', '.'); ?>
                    </div>

                </div>

                <?php if (
                    $order['payment_method'] != 'cod'
                    && empty($order['payment_proof'])
                ): ?>

                    <a
                        href="payment.php?id=<?= $order['id']; ?>"
                        class="btn btn-payment"
                    >
                        💳 Upload Bukti Pembayaran
                    </a>

                <?php elseif (
                    $order['payment_method'] != 'cod'
                    && !empty($order['payment_proof'])
                ): ?>

                    <p style="color: #198754;">
                        ✅ Bukti pembayaran sudah diupload.
                    </p>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

</div>

</body>
</html>