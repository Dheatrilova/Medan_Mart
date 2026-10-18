<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Pastikan buyer login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "
    SELECT
        cart.id AS cart_id,
        cart.quantity,
        products.name,
        products.price,
        products.image,
        products.stock,
        stores.store_name
    FROM cart
    JOIN products ON cart.product_id = products.id
    JOIN stores ON products.store_id = stores.id
    WHERE cart.user_id = '$user_id'
    ORDER BY cart.created_at DESC
";

$result = mysqli_query($conn, $query);

$total = 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Keranjang - MedanMart</title>

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

        h1 {
            margin-bottom: 25px;
        }

        .cart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .store {
            color: #777;
            font-size: 14px;
            margin-bottom: 7px;
        }

        .price {
            color: #198754;
            font-weight: bold;
        }

        .quantity {
            color: #555;
        }

        .subtotal {
            font-weight: bold;
            min-width: 130px;
            text-align: right;
        }

        .delete {
            color: #dc3545;
            text-decoration: none;
        }

        .summary {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .total {
            display: flex;
            justify-content: space-between;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .checkout {
            display: block;
            text-align: center;
            background: #198754;
            color: white;
            padding: 13px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 50px;
            text-align: center;
            border-radius: 12px;
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

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </div>

    </div>


    <div class="container">

        <h1>
            🛒 Keranjang Belanja
        </h1>


        <?php if (mysqli_num_rows($result) > 0): ?>

            <?php while ($item = mysqli_fetch_assoc($result)): ?>

                <?php
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
                ?>

                <div class="cart-card">

                    <?php if (!empty($item['image'])): ?>

                        <img
                            src="../uploads/products/<?= htmlspecialchars($item['image']); ?>"
                            class="product-image"
                            alt="<?= htmlspecialchars($item['name']); ?>"
                        >

                    <?php else: ?>

                        <div class="product-image"></div>

                    <?php endif; ?>


                    <div class="product-info">

                        <div class="product-name">
                            <?= htmlspecialchars($item['name']); ?>
                        </div>

                        <div class="store">
                            🏪 <?= htmlspecialchars($item['store_name']); ?>
                        </div>

                        <div class="price">
                            Rp <?= number_format($item['price'], 0, ',', '.'); ?>
                        </div>

                    </div>


                    <div class="quantity">

                        Jumlah:
                        <?= $item['quantity']; ?>

                    </div>


                    <div class="subtotal">

                        Rp <?= number_format($subtotal, 0, ',', '.'); ?>

                    </div>


                    <a
                        href="remove_cart.php?id=<?= $item['cart_id']; ?>"
                        class="delete"
                        onclick="return confirm('Hapus produk dari keranjang?')"
                    >
                        Hapus
                    </a>

                </div>

            <?php endwhile; ?>


            <div class="summary">

                <div class="total">

                    <span>
                        Total Belanja
                    </span>

                    <span>
                        Rp <?= number_format($total, 0, ',', '.'); ?>
                    </span>

                </div>

                <a href="checkout.php" class="checkout">
                    Lanjut ke Checkout →
                </a>

            </div>


        <?php else: ?>

            <div class="empty">

                <h2>
                    Keranjang masih kosong 🛒
                </h2>

                <p>
                    Yuk cari produk UMKM favoritmu.
                </p>

                <br>

                <a href="products.php" class="checkout">
                    Lihat Produk
                </a>

            </div>

        <?php endif; ?>

    </div>

</body>

</html>