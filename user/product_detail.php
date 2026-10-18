<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Pastikan buyer sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

// Cek ID produk
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['id']);

// Ambil data produk
$query = "
    SELECT 
        products.*,
        stores.store_name,
        stores.description AS store_description,
        stores.address AS store_address,
        categories.name AS category_name
    FROM products
    JOIN stores ON products.store_id = stores.id
    LEFT JOIN categories ON products.category_id = categories.id
    WHERE products.id = '$product_id'
";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "Produk tidak ditemukan.";
    exit;
}

$product = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($product['name']); ?> - MedanMart</title>

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

        /* NAVBAR */
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

        /* CONTAINER */
        .container {
            width: 86%;
            margin: 40px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #198754;
            text-decoration: none;
            font-weight: bold;
        }

        /* DETAIL */
        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            display: grid;
            grid-template-columns: 45% 55%;
            gap: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            background: #eee;
        }

        .no-image {
            width: 100%;
            height: 400px;
            background: #e9ecef;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #777;
        }

        .category {
            color: #198754;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .product-name {
            font-size: 34px;
            margin-bottom: 15px;
        }

        .price {
            color: #198754;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .description {
            line-height: 1.7;
            color: #555;
            margin-bottom: 20px;
        }

        .store {
            background: #f5f7fb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .store strong {
            display: block;
            margin-bottom: 5px;
        }

        .stock {
            margin-bottom: 15px;
            color: #555;
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .quantity input {
            width: 80px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 7px;
        }

        .btn-cart {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #198754;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-cart:hover {
            background: #157347;
        }

        @media (max-width: 768px) {

            .detail-card {
                grid-template-columns: 1fr;
            }

            .product-image,
            .no-image {
                height: 300px;
            }

        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
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

        <a href="products.php" class="back">
            ← Kembali ke Katalog
        </a>


        <div class="detail-card">

            <!-- GAMBAR -->
            <div>

                <?php if (!empty($product['image'])): ?>

                    <img
                        src="../uploads/products/<?= htmlspecialchars($product['image']); ?>"
                        class="product-image"
                        alt="<?= htmlspecialchars($product['name']); ?>"
                    >

                <?php else: ?>

                    <div class="no-image">
                        Tidak ada gambar
                    </div>

                <?php endif; ?>

            </div>


            <!-- INFORMASI -->
            <div>

                <div class="category">
                    <?= htmlspecialchars($product['category_name'] ?? 'Tanpa kategori'); ?>
                </div>

                <h1 class="product-name">
                    <?= htmlspecialchars($product['name']); ?>
                </h1>

                <div class="price">
                    Rp <?= number_format($product['price'], 0, ',', '.'); ?>
                </div>

                <p class="description">
                    <?= nl2br(htmlspecialchars($product['description'])); ?>
                </p>


                <div class="store">

                    <strong>
                        🏪 <?= htmlspecialchars($product['store_name']); ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars($product['store_address']); ?>
                    </span>

                </div>


                <div class="stock">
                    Stok tersedia:
                    <strong><?= $product['stock']; ?></strong>
                </div>


                <div class="quantity">

                    <label for="quantity">
                        Jumlah:
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        min="1"
                        max="<?= $product['stock']; ?>"
                        value="1"
                    >

                </div>


                <form action="add_to_cart.php" method="POST">
                <input
                 type="hidden"
                 name="product_id"
                value="<?= $product['id']; ?>"
                >

                <div class="quantity">

                <label for="quantity">
                    Jumlah:
                 </label>

        <input
            type="number"
            id="quantity"
            name="quantity"
            min="1"
            max="<?= $product['stock']; ?>"
            value="1"
            required
        >

    </div>

    <button type="submit" class="btn-cart">
        🛒 Tambah ke Keranjang
    </button>

</form>

            </div>

        </div>

    </div>

</body>

</html>