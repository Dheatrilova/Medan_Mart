<?php

session_start();

require_once __DIR__ . "/../config/database.php";

// Pastikan customer sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit;
}


/* =========================================
   AMBIL SEMUA PRODUK YANG MASIH TERSEDIA
========================================= */

$query = "
    SELECT
        products.*,
        categories.name AS category_name
    FROM products

    LEFT JOIN categories
        ON products.category_id = categories.id

    WHERE products.stock > 0

    ORDER BY products.id DESC
";

$products = mysqli_query($conn, $query);

if (!$products) {
    die("Gagal mengambil data produk: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Toko - MedanMart</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f7fa;
            color: #222;
        }

        .navbar {
            background: white;
            height: 70px;
            padding: 0 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;

            border-bottom: 1px solid #eee;
        }

        .logo {
            color: #e85d04;
            font-size: 24px;
            font-weight: bold;
        }

        .nav-menu {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav-menu a {
            text-decoration: none;
            color: #444;

            padding: 10px 15px;
            border-radius: 8px;
        }

        .nav-menu a:hover {
            background: #f5f5f5;
        }

        .cart-button {
            background: #e85d04;
            color: white !important;
        }

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 35px auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #777;
        }

        .back {
            display: inline-block;
            text-decoration: none;
            color: #e85d04;
            margin-bottom: 15px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.06);

            transition: 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 10px 25px
                rgba(0, 0, 0, 0.10);
        }

        .product-image {
            width: 100%;
            height: 210px;
            object-fit: cover;
            background: #f1f1f1;
        }

        .no-image {
            width: 100%;
            height: 210px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #f1f1f1;
            color: #999;
            font-size: 40px;
        }

        .product-info {
            padding: 18px;
        }

        .category {
            display: inline-block;

            font-size: 12px;
            color: #e85d04;
            background: #fff1e8;

            padding: 5px 9px;
            border-radius: 20px;

            margin-bottom: 10px;
        }

        .product-name {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .description {
            color: #777;
            font-size: 13px;
            line-height: 1.5;

            height: 40px;
            overflow: hidden;

            margin-bottom: 12px;
        }

        .price {
            color: #e85d04;
            font-size: 20px;
            font-weight: bold;

            margin-bottom: 6px;
        }

        .stock {
            color: #777;
            font-size: 13px;

            margin-bottom: 15px;
        }

        .detail-button {
            display: block;
            width: 100%;

            text-align: center;
            text-decoration: none;

            background: #e85d04;
            color: white;

            padding: 11px;
            border-radius: 8px;

            font-size: 14px;
            font-weight: bold;
        }

        .detail-button:hover {
            background: #d94f00;
        }

        .empty {
            background: white;
            border-radius: 15px;

            padding: 60px;
            text-align: center;

            color: #777;
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        @media (max-width: 1000px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 750px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            .navbar {
                padding: 0 5%;
            }

            .nav-menu a {
                padding: 8px;
                font-size: 13px;
            }

            .container {
                width: 90%;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>


<nav class="navbar">

    <div class="logo">
        🛍️ MedanMart
    </div>

    <div class="nav-menu">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="cart.php" class="cart-button">
            🛒 Keranjang
        </a>

    </div>

</nav>


<div class="container">


    <div class="page-header">

        <a href="dashboard.php" class="back">
            ← Kembali ke Dashboard
        </a>

        <h1>
            🛍️ Toko MedanMart
        </h1>

        <p>
            Temukan berbagai produk dari seller MedanMart.
        </p>

    </div>


    <?php if (mysqli_num_rows($products) > 0): ?>

        <div class="product-grid">


            <?php while ($product = mysqli_fetch_assoc($products)): ?>

                <div class="product-card">


                    <?php if (!empty($product['image'])): ?>

                        <img
                            src="../uploads/products/<?= htmlspecialchars($product['image']) ?>"
                            alt="<?= htmlspecialchars($product['name']) ?>"
                            class="product-image"
                        >

                    <?php else: ?>

                        <div class="no-image">
                            🛍️
                        </div>

                    <?php endif; ?>


                    <div class="product-info">


                        <span class="category">

                            <?= htmlspecialchars(
                                $product['category_name'] ?? 'Umum'
                            ) ?>

                        </span>


                        <h2 class="product-name">

                            <?= htmlspecialchars(
                                $product['name']
                            ) ?>

                        </h2>


                        <p class="description">

                            <?= htmlspecialchars(
                                $product['description']
                            ) ?>

                        </p>


                        <div class="price">

                            Rp
                            <?= number_format(
                                $product['price'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </div>


                        <div class="stock">

                            Stok:
                            <?= $product['stock'] ?>

                        </div>


                        <a
                            href="product_detail.php?id=<?= $product['id'] ?>"
                            class="detail-button"
                        >
                            Lihat Produk
                        </a>


                    </div>

                </div>

            <?php endwhile; ?>


        </div>


    <?php else: ?>

        <div class="empty">

            <div class="empty-icon">
                🛍️
            </div>

            <h2>
                Belum Ada Produk
            </h2>

            <p>
                Saat ini belum ada produk yang tersedia
                untuk dibeli.
            </p>

        </div>

    <?php endif; ?>


</div>


</body>

</html>
