<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: " . __DIR__ . "/dashboard.php");
    exit;
}

$order_id = intval($_GET['id']);

$query = "
    SELECT *
    FROM orders
    WHERE id = '$order_id'
    AND user_id = '$user_id'
";

$result = mysqli_query($conn, $query);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo "Pesanan tidak ditemukan.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pesanan Berhasil - MedanMart</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
        }

        .container {
            width: 600px;
            max-width: 90%;
            margin: 80px auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .success {
            font-size: 60px;
        }

        h1 {
            color: #198754;
            margin-bottom: 10px;
        }

        .order-code {
            background: #f1f3f5;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 18px;
        }

        .total {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 5px;
        }

        .btn:hover {
            background: #0b5ed7;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5c636a;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="success">✅</div>

    <h1>Pesanan Berhasil!</h1>

    <p>
        Terima kasih sudah berbelanja di <b>MedanMart</b>.
    </p>

    <div class="order-code">
        <strong>Kode Pesanan</strong><br><br>
        <?= htmlspecialchars($order['order_code']); ?>
    </div>

    <p>
        Metode Pembayaran:
        <b><?= strtoupper($order['payment_method']); ?></b>
    </p>

    <div class="total">
        Total: Rp <?= number_format($order['total'], 0, ',', '.'); ?>
    </div>

    <p>
        Status Pesanan:
        <b><?= strtoupper($order['status']); ?></b>
    </p>

    <?php if ($order['payment_method'] != 'cod'): ?>

        <p>
            Silakan lanjutkan dengan mengunggah
            bukti pembayaran.
        </p>

        <a href="payment.php?id=<?= $order['id']; ?>" class="btn">
            💳 Upload Bukti Pembayaran
        </a>

    <?php endif; ?>

    <br>

    <a href="dashboard.php" class="btn btn-secondary">
        🏠 Kembali ke Dashboard
    </a>

</div>

</body>
</html>