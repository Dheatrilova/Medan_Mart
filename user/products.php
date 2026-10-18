<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Pastikan yang mengakses adalah buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

// Ambil semua produk
$query = "
    SELECT 
        products.*,
        stores.store_name,
        categories.name AS category_name
    FROM products
    JOIN stores ON products.store_id = stores.id
    LEFT JOIN categories ON products.category_id = categories.id
    ORDER BY products.created_at DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Katalog Produk - MedanMart</title>

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

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-right a {
            color: white;
            text-decoration: none;
        }

        .logout {
            background: white;
            color: #198754 !important;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: bold;
        }

        /* HEADER */
        .container {
            width: 86%;
            margin: 35px auto;
        }

        .header {
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .header p {
            color: #777;
        }

        /* PRODUK */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #eee;
        }

        .no-image {
            width: 100%;
            height: 200px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }

        .product-info {
            padding: 18px;
        }

        .category {
            font-size: 13px;
            color: #198754;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .store {
            font-size: 13px;
            color: #777;
            margin-bottom: 12px;
        }

        .price {
            color: #198754;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stock {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }

        .btn-detail {
            display: block;
            text-align: center;
            background: #198754;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 7px;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            color: #777;
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">

    <div class="logo">
        🛍️ MedanMart
    </div>

    <div class="nav-right">
        <span>
            Halo, <?= htmlspecialchars($_SESSION['name']); ?> 👋
        </span>

        <a href="dashboard.php">Dashboard</a>

        <a href="../logout.php" class="logout">
            Logout
        </a>
    </div>

</div>


<!-- CONTENT -->
<div class="container">

    <div class="header">
        <h1>🛍️ Katalog Produk</h1>

        <p>
            Temukan berbagai produk UMKM lokal Kota Medan.
        </p>
    </div>


    <?php if (mysqli_num_rows($result) > 0): ?>

        <div class="product-grid">

            <?php while ($product = mysqli_fetch_assoc($result)): ?>

                <div class="product-card">

                    <!-- GAMBAR -->
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


                    <!-- INFORMASI -->
                    <div class="product-info">

                        <div class="category">
                            <?= htmlspecialchars($product['category_name'] ?? 'Tanpa kategori'); ?>
                        </div>

                        <div class="product-name">
                            <?= htmlspecialchars($product['name']); ?>
                        </div>

                        <div class="store">
                            🏪 <?= htmlspecialchars($product['store_name']); ?>
                        </div>

                        <div class="price">
                            Rp <?= number_format($product['price'], 0, ',', '.'); ?>
                        </div>

                        <div class="stock">
                            Stok: <?= $product['stock']; ?>
                        </div>

                        <a 
                            href="product_detail.php?id=<?= $product['id']; ?>"
                            class="btn-detail"
                        >
                            Lihat Detail
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty">
            <h3>Belum ada produk 😔</h3>
            <p>
                Saat ini belum ada produk yang tersedia.
            </p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>