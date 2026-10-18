<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Cek login sebagai buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.php");
    exit;
}

// Ambil ID produk dari URL
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: store.php");
    exit;
}

// Ambil data produk
$query = "
    SELECT
        products.*,
        categories.name AS category_name
    FROM products
    LEFT JOIN categories
        ON products.category_id = categories.id
    WHERE products.id = '$product_id'
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Produk tidak ditemukan.");
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
            background: #f7f7f7;
            color: #333;
        }

        .navbar {
            background: #ff6b00;
            color: white;
            padding: 18px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 15px;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            padding: 9px 15px;
            border-radius: 8px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #ff6b00;
            font-weight: bold;
        }

        .product-detail {
            background: white;
            border-radius: 18px;
            padding: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .product-image {
            width: 100%;
            height: 420px;
            object-fit: contain;
            background: #f5f5f5;
            border-radius: 15px;
        }

        .no-image {
            width: 100%;
            height: 420px;
            background: #f1f1f1;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .category {
            display: inline-block;
            background: #fff0e6;
            color: #ff6b00;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 15px;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .price {
            font-size: 28px;
            font-weight: bold;
            color: #ff6b00;
            margin-bottom: 20px;
        }

        .description {
            color: #666;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .stock {
            margin-bottom: 20px;
            font-weight: bold;
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .quantity label {
            font-weight: bold;
        }

        .quantity input {
            width: 80px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
        }

        .btn-cart {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            background: #ff6b00;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-cart:hover {
            background: #e85d00;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }

            .product-detail {
                grid-template-columns: 1fr;
            }

            .product-image,
            .no-image {
                height: 300px;
            }

            h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

    <div class="navbar">

        <div class="logo">
            🛒 MedanMart
        </div>

        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="store.php">Store</a>
            <a href="cart.php">Keranjang 🛒</a>
        </div>

    </div>

    <div class="container">

        <a href="store.php" class="back">
            ← Kembali ke Store
        </a>

        <div class="product-detail">

            <div>

                <?php if (!empty($product['image'])): ?>

                    <img
                        src="../uploads/products/<?= htmlspecialchars($product['image']); ?>"
                        alt="<?= htmlspecialchars($product['name']); ?>"
                        class="product-image"
                    >

                <?php else: ?>

                    <div class="no-image">
                        Tidak ada gambar produk
                    </div>

                <?php endif; ?>

            </div>

            <div>

                <span class="category">
                    <?= htmlspecialchars($product['category_name'] ?? 'Umum'); ?>
                </span>

                <h1>
                    <?= htmlspecialchars($product['name']); ?>
                </h1>

                <div class="price">
                    Rp <?= number_format($product['price'], 0, ',', '.'); ?>
                </div>

                <p class="description">
                    <?= nl2br(htmlspecialchars($product['description'] ?? 'Tidak ada deskripsi produk.')); ?>
                </p>

                <div class="stock">
                    Stok tersedia: <?= (int) $product['stock']; ?> produk
                </div>

                <form action="cart.php" method="POST">

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
                            value="1"
                            min="1"
                            max="<?= (int) $product['stock']; ?>"
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