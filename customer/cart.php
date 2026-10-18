
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Pastikan pengguna sudah login sebagai buyer
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'buyer'
) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$notice = $_GET['notice'] ?? '';

/* =========================================
   FUNGSI BANTU
========================================= */

function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($value)
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

/* =========================================
   TAMBAH PRODUK KE KERANJANG
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int) $_POST['product_id'];
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

    // Pastikan produk tersedia
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, stock FROM products WHERE id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $product_result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($product_result);
    mysqli_stmt_close($stmt);

    if (!$product) {
        header("Location: store.php");
        exit;
    }

    $stock = (int) $product['stock'];

    if ($stock < 1) {
        header("Location: cart.php?notice=out_of_stock");
        exit;
    }

    if ($quantity > $stock) {
        $quantity = $stock;
    }

    // Periksa apakah produk sudah ada di keranjang pengguna
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, quantity
         FROM cart
         WHERE user_id = ? AND product_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($stmt);
    $cart_result = mysqli_stmt_get_result($stmt);
    $cart_item = mysqli_fetch_assoc($cart_result);
    mysqli_stmt_close($stmt);

    if ($cart_item) {
        $cart_id = (int) $cart_item['id'];
        $new_quantity = min(
            (int) $cart_item['quantity'] + $quantity,
            $stock
        );

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE cart
             SET quantity = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $new_quantity,
            $cart_id,
            $user_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO cart (user_id, product_id, quantity)
             VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $user_id,
            $product_id,
            $quantity
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: cart.php?notice=added");
    exit;
}

/* =========================================
   UPDATE JUMLAH PRODUK
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $cart_id = (int) ($_POST['cart_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);
    $quantity = max(1, $quantity);

    // Ambil stok produk milik item keranjang pengguna
    $stmt = mysqli_prepare(
        $conn,
        "SELECT products.stock
         FROM cart
         JOIN products ON products.id = cart.product_id
         WHERE cart.id = ? AND cart.user_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);
    $stock_result = mysqli_stmt_get_result($stmt);
    $stock_data = mysqli_fetch_assoc($stock_result);
    mysqli_stmt_close($stmt);

    if ($stock_data) {
        $stock = (int) $stock_data['stock'];

        if ($stock < 1) {
            header("Location: cart.php?notice=out_of_stock");
            exit;
        }

        $quantity = min($quantity, $stock);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE cart
             SET quantity = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $quantity,
            $cart_id,
            $user_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: cart.php?notice=updated");
    exit;
}

/* =========================================
   HAPUS PRODUK DARI KERANJANG
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cart'])) {
    $cart_id = (int) ($_POST['cart_id'] ?? 0);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM cart WHERE id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: cart.php?notice=deleted");
    exit;
}

/* =========================================
   AMBIL ISI KERANJANG
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        cart.id AS cart_id,
        cart.quantity,
        products.id AS product_id,
        products.name,
        products.price,
        products.image,
        products.stock,
        categories.name AS category_name
     FROM cart
     JOIN products ON products.id = cart.product_id
     LEFT JOIN categories ON categories.id = products.category_id
     WHERE cart.user_id = ?
     ORDER BY cart.id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
$total = 0;
$total_items = 0;
$has_out_of_stock = false;

while ($item = mysqli_fetch_assoc($result)) {
    $item['quantity'] = (int) $item['quantity'];
    $item['stock'] = (int) $item['stock'];
    $item['price'] = (float) $item['price'];
    $item['subtotal'] = $item['price'] * $item['quantity'];

    $total += $item['subtotal'];
    $total_items += $item['quantity'];

    if ($item['stock'] < 1 || $item['quantity'] > $item['stock']) {
        $has_out_of_stock = true;
    }

    $items[] = $item;
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja | MedanMart</title>

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
            --danger: #DC2626;
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

        a {
            text-decoration: none;
        }

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 16px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            color: var(--orange-dark);
            font-size: 23px;
            font-weight: 800;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a {
            padding: 9px 13px;
            border-radius: 9px;
            color: var(--text);
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--cream);
            color: var(--orange-dark);
        }

        .container {
            max-width: 1180px;
            margin: 38px auto;
            padding: 0 22px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .eyebrow {
            color: var(--orange-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .page-heading h1 {
            font-size: clamp(26px, 4vw, 34px);
            line-height: 1.25;
            margin-bottom: 8px;
        }

        .page-heading p {
            color: var(--muted);
            font-size: 15px;
        }

        .notice {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            background: var(--cream);
            color: #9A3412;
            border: 1px solid #FED7AA;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 24px;
            align-items: start;
        }

        .cart-panel,
        .summary,
        .empty {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 6px 22px rgba(31, 41, 55, .04);
        }

        .cart-panel {
            padding: 8px 22px;
        }

        .panel-heading {
            padding: 17px 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .panel-heading h2 {
            font-size: 17px;
        }

        .item-count {
            background: var(--cream);
            color: var(--orange-dark);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 112px minmax(0, 1fr) auto;
            align-items: center;
            gap: 18px;
            padding: 22px 0;
            border-bottom: 1px solid var(--border);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-image,
        .no-image {
            width: 112px;
            height: 112px;
            border-radius: 12px;
            background: #F9FAFB;
            border: 1px solid #F1F5F9;
        }

        .product-image {
            display: block;
            object-fit: contain;
            padding: 7px;
        }

        .no-image {
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
            padding: 8px;
        }

        .category {
            display: inline-block;
            color: var(--orange-dark);
            background: var(--cream);
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 750;
            line-height: 1.4;
            margin-bottom: 5px;
            overflow-wrap: anywhere;
        }

        .price {
            color: var(--orange-dark);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .stock-note {
            font-size: 12px;
            color: var(--muted);
            margin: -5px 0 10px;
        }

        .stock-warning {
            color: var(--danger);
            font-weight: 700;
        }

        .quantity-form {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .quantity-form input {
            width: 68px;
            min-height: 37px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            outline: none;
        }

        .quantity-form input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, .12);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
        }

        .btn-update {
            color: var(--white);
            background: var(--text);
        }

        .btn-update:hover {
            background: #374151;
        }

        .btn-delete {
            background: transparent;
            color: var(--danger);
            padding: 8px 0;
            margin-top: 4px;
        }

        .btn-delete:hover {
            text-decoration: underline;
        }

        .item-subtotal {
            text-align: right;
            font-size: 15px;
            font-weight: 800;
            white-space: nowrap;
            align-self: start;
            padding-top: 5px;
        }

        .summary {
            padding: 23px;
            position: sticky;
            top: 20px;
        }

        .summary h2 {
            font-size: 19px;
            margin-bottom: 6px;
        }

        .summary-description {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 22px;
        }

        .summary-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
            font-size: 14px;
        }

        .summary-row span:first-child {
            color: var(--muted);
        }

        .summary-row span:last-child {
            text-align: right;
            font-weight: 700;
        }

        .summary-total {
            border-top: 1px dashed #D1D5DB;
            margin-top: 19px;
            padding-top: 18px;
            font-size: 16px;
        }

        .summary-total span:first-child {
            color: var(--text);
            font-weight: 800;
        }

        .summary-total .total-price {
            color: var(--orange-dark);
            font-size: 19px;
        }

        .btn-checkout {
            width: 100%;
            min-height: 48px;
            margin-top: 12px;
            background: var(--orange);
            color: var(--white);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
        }

        .btn-checkout:hover {
            background: var(--orange-dark);
        }

        .btn-checkout.disabled {
            background: #9CA3AF;
            cursor: not-allowed;
        }

        .btn-shopping {
            display: block;
            text-align: center;
            color: var(--orange-dark);
            font-size: 13px;
            font-weight: 700;
            margin-top: 16px;
        }

        .shipping-note {
            border-radius: 9px;
            background: #F9FAFB;
            padding: 12px;
            margin-top: 18px;
            color: var(--muted);
            font-size: 12px;
        }

        .empty {
            padding: 65px 22px;
            text-align: center;
        }

        .empty-icon {
            width: 78px;
            height: 78px;
            margin: 0 auto 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cream);
            font-size: 34px;
        }

        .empty h2 {
            font-size: 22px;
            margin-bottom: 7px;
        }

        .empty p {
            color: var(--muted);
            margin-bottom: 22px;
            font-size: 14px;
        }

        .empty .btn {
            background: var(--orange);
            color: var(--white);
            padding: 12px 19px;
        }

        .footer {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            padding: 28px 16px;
        }

        @media (max-width: 900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .summary {
                position: static;
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 14px 18px;
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .nav-links {
                width: 100%;
                gap: 5px;
                flex-wrap: wrap;
            }

            .nav-links a {
                padding: 8px 10px;
                font-size: 12px;
            }

            .container {
                margin: 27px auto;
                padding: 0 14px;
            }

            .cart-panel {
                padding: 6px 14px;
            }

            .cart-item {
                grid-template-columns: 78px minmax(0, 1fr);
                gap: 12px;
            }

            .product-image,
            .no-image {
                width: 78px;
                height: 78px;
            }

            .item-subtotal {
                grid-column: 2;
                text-align: left;
                padding-top: 0;
                font-size: 14px;
            }

            .product-name {
                font-size: 14px;
            }

            .quantity-form input {
                width: 57px;
            }

            .summary {
                padding: 19px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="store.php" class="logo">🛒 MedanMart</a>

    <nav class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="store.php">Belanja</a>
        <a href="cart.php" class="active">Keranjang</a>
        <a href="orders.php">Pesanan Saya</a>
    </nav>
</header>

<main class="container">

    <div class="page-heading">
        <div class="eyebrow">Belanja lebih mudah</div>
        <h1>Keranjang Belanja 🛒</h1>
        <p>Periksa produk dan jumlahnya sebelum melanjutkan ke checkout.</p>
    </div>

    <?php if ($notice === 'added'): ?>
        <div class="notice">Produk berhasil ditambahkan ke keranjang.</div>
    <?php elseif ($notice === 'updated'): ?>
        <div class="notice">Jumlah produk berhasil diperbarui.</div>
    <?php elseif ($notice === 'deleted'): ?>
        <div class="notice">Produk berhasil dihapus dari keranjang.</div>
    <?php elseif ($notice === 'out_of_stock'): ?>
        <div class="notice">Stok produk habis atau tidak mencukupi. Periksa kembali keranjangmu.</div>
    <?php endif; ?>

    <?php if (count($items) === 0): ?>

        <section class="empty">
            <div class="empty-icon">🛍️</div>
            <h2>Keranjangmu masih kosong</h2>
            <p>Belum ada produk yang kamu pilih. Yuk, cari barang yang kamu butuhkan!</p>
            <a href="store.php" class="btn">Mulai Belanja</a>
        </section>

    <?php else: ?>

        <div class="cart-layout">

            <section class="cart-panel">
                <div class="panel-heading">
                    <h2>Produk Pilihanmu</h2>
                    <span class="item-count">
                        <?= count($items); ?> jenis produk
                    </span>
                </div>

                <?php foreach ($items as $item): ?>
                    <article class="cart-item">

                        <div>
                            <?php if (!empty($item['image'])): ?>
                                <img
                                    src="../uploads/products/<?= e(basename($item['image'])); ?>"
                                    class="product-image"
                                    alt="<?= e($item['name']); ?>"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                >
                                <div class="no-image" style="display:none;">
                                    Gambar tidak tersedia
                                </div>
                            <?php else: ?>
                                <div class="no-image">Gambar tidak tersedia</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <div class="category">
                                <?= e($item['category_name'] ?: 'Umum'); ?>
                            </div>

                            <div class="product-name">
                                <?= e($item['name']); ?>
                            </div>

                            <div class="price">
                                <?= rupiah($item['price']); ?>
                            </div>

                            <?php if ($item['stock'] < 1): ?>
                                <div class="stock-note stock-warning">
                                    Stok habis
                                </div>
                            <?php else: ?>
                                <div class="stock-note">
                                    Stok tersedia: <?= $item['stock']; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="cart.php" class="quantity-form">
                                <input
                                    type="hidden"
                                    name="cart_id"
                                    value="<?= (int) $item['cart_id']; ?>"
                                >

                                <input
                                    type="number"
                                    name="quantity"
                                    value="<?= $item['quantity']; ?>"
                                    min="1"
                                    max="<?= max(1, $item['stock']); ?>"
                                    aria-label="Jumlah <?= e($item['name']); ?>"
                                    required
                                >

                                <button
                                    type="submit"
                                    name="update_cart"
                                    value="1"
                                    class="btn btn-update"
                                >
                                    Perbarui
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="cart.php"
                                onsubmit="return confirm('Yakin ingin menghapus produk ini dari keranjang?');"
                            >
                                <input
                                    type="hidden"
                                    name="cart_id"
                                    value="<?= (int) $item['cart_id']; ?>"
                                >

                                <button
                                    type="submit"
                                    name="delete_cart"
                                    value="1"
                                    class="btn btn-delete"
                                >
                                    🗑 Hapus produk
                                </button>
                            </form>
                        </div>

                        <div class="item-subtotal">
                            <?= rupiah($item['subtotal']); ?>
                        </div>

                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="summary">
                <h2>Ringkasan Belanja</h2>
                <p class="summary-description">
                    Periksa kembali total belanja sebelum checkout.
                </p>

                <div class="summary-row">
                    <span>Jenis produk</span>
                    <span><?= count($items); ?> produk</span>
                </div>

                <div class="summary-row">
                    <span>Total barang</span>
                    <span><?= $total_items; ?> item</span>
                </div>

                <div class="summary-row">
                    <span>Subtotal produk</span>
                    <span><?= rupiah($total); ?></span>
                </div>

                <div class="summary-row summary-total">
                    <span>Total sementara</span>
                    <span class="total-price"><?= rupiah($total); ?></span>
                </div>

                <?php if ($has_out_of_stock): ?>
                    <div class="notice" style="margin-top:16px; margin-bottom:0;">
                        Ada produk dengan stok tidak mencukupi. Sesuaikan atau hapus produk tersebut sebelum checkout.
                    </div>
                    <span class="btn btn-checkout disabled" style="display:flex; margin-top:12px;">
                        Periksa stok terlebih dahulu
                    </span>
                <?php else: ?>
                    <a href="checkout.php" class="btn btn-checkout">
                        Lanjut ke Checkout →
                    </a>
                <?php endif; ?>

                <a href="store.php" class="btn-shopping">
                    ← Lanjut Belanja
                </a>

                <div class="shipping-note">
                    Biaya pengiriman dan total akhir akan dihitung pada halaman checkout.
                </div>
            </aside>

        </div>

    <?php endif; ?>

</main>

<footer class="footer">
    © <?= date('Y'); ?> MedanMart — Belanja kebutuhanmu dengan mudah.
</footer>

</body>
</html>
