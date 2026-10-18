<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Pastikan buyer sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil isi keranjang
$query = "
    SELECT
        cart.id AS cart_id,
        cart.quantity,
        products.id AS product_id,
        products.name,
        products.price,
        products.stock,
        products.image,
        stores.store_name
    FROM cart
    JOIN products ON cart.product_id = products.id
    JOIN stores ON products.store_id = stores.id
    WHERE cart.user_id = '$user_id'
";

$result = mysqli_query($conn, $query);

// Jika keranjang kosong
if (mysqli_num_rows($result) == 0) {
    header("Location: cart.php");
    exit;
}

$items = [];
$subtotal = 0;

while ($item = mysqli_fetch_assoc($result)) {

    $item['subtotal'] = $item['price'] * $item['quantity'];

    $subtotal += $item['subtotal'];

    $items[] = $item;
}

// Ongkir dummy
$shipping_cost = 15000;

$total = $subtotal + $shipping_cost;
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - MedanMart</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f7fb;
            color: #333;
        }

        .navbar {
            background: #198754;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        .container {
            width: 86%;
            margin: 40px auto;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }

        .card h2 {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 15px;
        }

        textarea {
            height: 110px;
            resize: vertical;
        }

        .payment-option {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .payment-option input {
            width: auto;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .product-name {
            font-weight: bold;
        }

        .product-detail {
            color: #777;
            font-size: 14px;
            margin-top: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .total {
            border-top: 1px solid #ddd;
            padding-top: 15px;
            font-size: 21px;
            font-weight: bold;
            color: #198754;
        }

        .btn-order {
            width: 100%;
            padding: 14px;
            background: #198754;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }

        .btn-order:hover {
            background: #157347;
        }

        .shipping-info {
            background: #e8f5e9;
            padding: 12px;
            border-radius: 7px;
            color: #27632a;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {

            .checkout-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

    <div class="navbar">

        <div class="logo">
            🛍️ MedanMart
        </div>

        <div>

            <a href="products.php">
                Katalog
            </a>

            <a href="cart.php">
                🛒 Keranjang
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </div>

    </div>


    <div class="container">

        <h1 class="page-title">
            🧾 Checkout
        </h1>


        <form action="process_order.php" method="POST">

            <div class="checkout-grid">

                <!-- DATA PENGIRIMAN -->
                <div>

                    <div class="card">

                        <h2>
                            📍 Data Pengiriman
                        </h2>

                        <div class="form-group">

                            <label>
                                Nama Penerima
                            </label>

                            <input
                                type="text"
                                name="recipient_name"
                                value="<?= htmlspecialchars($_SESSION['name']); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Nomor HP
                            </label>

                            <input
                                type="text"
                                name="phone"
                                placeholder="Contoh: 081234567890"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Alamat Lengkap
                            </label>

                            <textarea
                                name="address"
                                placeholder="Masukkan alamat lengkap pengiriman..."
                                required
                            ></textarea>

                        </div>


                        <div class="form-group">

                            <label>
                                Metode Pengiriman
                            </label>

                            <select name="shipping_method" required>

                                <option value="">
                                    -- Pilih Pengiriman --
                                </option>

                                <option value="Reguler">
                                    Reguler
                                </option>

                                <option value="Express">
                                    Express
                                </option>

                            </select>

                        </div>


                        <div class="shipping-info">

                            🚚 Ongkir Dummy:
                            <strong>
                                Rp <?= number_format($shipping_cost, 0, ',', '.'); ?>
                            </strong>

                        </div>

                    </div>


                    <!-- PEMBAYARAN -->
                    <div class="card">

                        <h2>
                            💳 Metode Pembayaran
                        </h2>

                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="transfer"
                                required
                            >

                            🏦 Transfer Bank

                        </label>


                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="qris"
                            >

                            📱 QRIS

                        </label>


                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="cod"
                            >

                            💵 COD

                        </label>

                    </div>

                </div>


                <!-- RINGKASAN -->
                <div>

                    <div class="card">

                        <h2>
                            🛒 Ringkasan Pesanan
                        </h2>


                        <?php foreach ($items as $item): ?>

                            <div class="product-item">

                                <div>

                                    <div class="product-name">
                                        <?= htmlspecialchars($item['name']); ?>
                                    </div>

                                    <div class="product-detail">

                                        <?= $item['quantity']; ?>
                                        ×
                                        Rp <?= number_format($item['price'], 0, ',', '.'); ?>

                                    </div>

                                </div>


                                <strong>

                                    Rp <?= number_format(
                                        $item['subtotal'],
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                </strong>

                            </div>

                        <?php endforeach; ?>


                        <br>


                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                Rp <?= number_format(
                                    $subtotal,
                                    0,
                                    ',',
                                    '.'
                                ); ?>
                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Ongkir
                            </span>

                            <strong>
                                Rp <?= number_format(
                                    $shipping_cost,
                                    0,
                                    ',',
                                    '.'
                                ); ?>
                            </strong>

                        </div>


                        <div class="summary-row total">

                            <span>
                                Total
                            </span>

                            <span>
                                Rp <?= number_format(
                                    $total,
                                    0,
                                    ',',
                                    '.'
                                ); ?>
                            </span>

                        </div>


                        <input
                            type="hidden"
                            name="shipping_cost"
                            value="<?= $shipping_cost; ?>"
                        >


                        <input
                            type="hidden"
                            name="subtotal"
                            value="<?= $subtotal; ?>"
                        >


                        <input
                            type="hidden"
                            name="total"
                            value="<?= $total; ?>"
                        >


                        <button
                            type="submit"
                            class="btn-order"
                        >
                            🛍️ Buat Pesanan
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</body>

</html>