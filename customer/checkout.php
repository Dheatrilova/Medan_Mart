<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| 1. CEK LOGIN BUYER
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'buyer'
) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$error = "";

/*
|--------------------------------------------------------------------------
| 2. AMBIL DATA USER
|--------------------------------------------------------------------------
*/

$stmt_user = mysqli_prepare(
    $conn,
    "SELECT * FROM users WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);

$user_result = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($user_result);

mysqli_stmt_close($stmt_user);

if (!$user) {
    die("Data pengguna tidak ditemukan.");
}

/*
|--------------------------------------------------------------------------
| 3. FUNGSI FORMAT RUPIAH
|--------------------------------------------------------------------------
*/

function rupiah($angka)
{
    return "Rp " . number_format((float) $angka, 0, ',', '.');
}

/*
|--------------------------------------------------------------------------
| 4. AMBIL KERANJANG BUYER
|--------------------------------------------------------------------------
*/

$cart_sql = "
    SELECT
        c.id AS cart_id,
        c.product_id,
        c.quantity,
        p.name,
        p.price,
        p.stock,
        p.image
    FROM cart c
    INNER JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
    ORDER BY c.id DESC
";

$stmt_cart = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt_cart, "i", $user_id);
mysqli_stmt_execute($stmt_cart);

$cart_result = mysqli_stmt_get_result($stmt_cart);

$cart_items = [];
$subtotal = 0;
$stock_problem = false;

while ($item = mysqli_fetch_assoc($cart_result)) {
    $item['quantity'] = (int) $item['quantity'];
    $item['stock'] = (int) $item['stock'];
    $item['price'] = (float) $item['price'];

    $item['item_subtotal'] =
        $item['price'] * $item['quantity'];

    if (
        $item['quantity'] <= 0 ||
        $item['stock'] < $item['quantity']
    ) {
        $stock_problem = true;
    }

    $subtotal += $item['item_subtotal'];
    $cart_items[] = $item;
}

mysqli_stmt_close($stmt_cart);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| 5. PROSES CHECKOUT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $recipient_name = trim(
        $_POST['recipient_name'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );

    $address = trim(
        $_POST['address'] ?? ''
    );

    $shipping_method = trim(
        $_POST['shipping_method'] ?? ''
    );

    $payment_method = trim(
        $_POST['payment_method'] ?? ''
    );

    /*
    |--------------------------------------------------------------------------
    | VALIDASI INPUT
    |--------------------------------------------------------------------------
    */

    if (
        $recipient_name === '' ||
        $phone === '' ||
        $address === '' ||
        $shipping_method === '' ||
        $payment_method === ''
    ) {
        $error = "Semua data checkout wajib diisi.";

    } elseif (
        !in_array(
            $shipping_method,
            ['Reguler', 'Express', 'Pickup'],
            true
        )
    ) {
        $error = "Metode pengiriman tidak valid.";

    } elseif (
        !in_array(
            $payment_method,
            ['transfer', 'qris', 'cod'],
            true
        )
    ) {
        $error = "Metode pembayaran tidak valid.";

    } elseif ($stock_problem) {
        $error = "Ada produk yang stoknya tidak mencukupi. Silakan periksa keranjang.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | HITUNG ONGKIR
        |--------------------------------------------------------------------------
        */

        switch ($shipping_method) {
            case 'Reguler':
                $shipping_cost = 10000;
                break;

            case 'Express':
                $shipping_cost = 20000;
                break;

            case 'Pickup':
                $shipping_cost = 0;
                break;

            default:
                $shipping_cost = 0;
        }

        $total = $subtotal + $shipping_cost;

        /*
        |--------------------------------------------------------------------------
        | BUAT KODE PESANAN
        |--------------------------------------------------------------------------
        */

        $order_code = 'MM-' .
            date('YmdHis') . '-' .
            strtoupper(bin2hex(random_bytes(3)));

        /*
        |--------------------------------------------------------------------------
        | MULAI TRANSAKSI DATABASE
        |--------------------------------------------------------------------------
        */

        mysqli_begin_transaction($conn);

        try {

            /*
            |--------------------------------------------------------------------------
            | 6. PERIKSA ULANG STOK TERBARU
            |--------------------------------------------------------------------------
            */

            $stmt_stock = mysqli_prepare(
                $conn,
                "SELECT name, stock
                 FROM products
                 WHERE id = ?
                 FOR UPDATE"
            );

            foreach ($cart_items as $item) {

                $product_id = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];

                mysqli_stmt_bind_param(
                    $stmt_stock,
                    "i",
                    $product_id
                );

                mysqli_stmt_execute($stmt_stock);

                $stock_result = mysqli_stmt_get_result(
                    $stmt_stock
                );

                $latest_product = mysqli_fetch_assoc(
                    $stock_result
                );

                if (!$latest_product) {
                    throw new Exception(
                        "Produk tidak ditemukan."
                    );
                }

                if (
                    $quantity <= 0 ||
                    (int) $latest_product['stock'] < $quantity
                ) {
                    throw new Exception(
                        "Stok produk " .
                        $latest_product['name'] .
                        " tidak mencukupi. Silakan periksa keranjang."
                    );
                }
            }

            mysqli_stmt_close($stmt_stock);

            /*
            |--------------------------------------------------------------------------
            | 7. SIMPAN PESANAN
            |--------------------------------------------------------------------------
            |
            | Status awal: pending
            | Status ini dibaca oleh halaman buyer dan seller.
            |
            */

            $stmt_order = mysqli_prepare(
                $conn,
                "INSERT INTO orders (
                    user_id,
                    order_code,
                    recipient_name,
                    phone,
                    address,
                    shipping_method,
                    shipping_cost,
                    subtotal,
                    total,
                    payment_method,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );

            /*
             * i = integer
             * s = string
             * d = double
             *
             * Urutan:
             * user_id, order_code, recipient_name, phone,
             * address, shipping_method, shipping_cost,
             * subtotal, total, payment_method
             */

            mysqli_stmt_bind_param(
                $stmt_order,
                "isssssidds",
                $user_id,
                $order_code,
                $recipient_name,
                $phone,
                $address,
                $shipping_method,
                $shipping_cost,
                $subtotal,
                $total,
                $payment_method
            );

            if (!mysqli_stmt_execute($stmt_order)) {
                throw new Exception(
                    "Gagal menyimpan pesanan."
                );
            }

            $order_id = mysqli_insert_id($conn);

            mysqli_stmt_close($stmt_order);

            /*
            |--------------------------------------------------------------------------
            | 8. SIMPAN DETAIL DAN KURANGI STOK
            |--------------------------------------------------------------------------
            */

            $stmt_detail = mysqli_prepare(
                $conn,
                "INSERT INTO order_details (
                    order_id,
                    product_id,
                    product_name,
                    price,
                    quantity,
                    subtotal
                ) VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt_update_stock = mysqli_prepare(
                $conn,
                "UPDATE products
                 SET stock = stock - ?
                 WHERE id = ? AND stock >= ?"
            );

            foreach ($cart_items as $item) {

                $product_id = (int) $item['product_id'];
                $product_name = $item['name'];
                $price = (float) $item['price'];
                $quantity = (int) $item['quantity'];
                $item_subtotal = $price * $quantity;

                /*
                |--------------------------------------------------------------------------
                | SIMPAN DETAIL PESANAN
                |--------------------------------------------------------------------------
                */

                mysqli_stmt_bind_param(
                    $stmt_detail,
                    "iisdid",
                    $order_id,
                    $product_id,
                    $product_name,
                    $price,
                    $quantity,
                    $item_subtotal
                );

                if (!mysqli_stmt_execute($stmt_detail)) {
                    throw new Exception(
                        "Gagal menyimpan detail pesanan."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | KURANGI STOK PRODUK
                |--------------------------------------------------------------------------
                */

                mysqli_stmt_bind_param(
                    $stmt_update_stock,
                    "iii",
                    $quantity,
                    $product_id,
                    $quantity
                );

                if (!mysqli_stmt_execute($stmt_update_stock)) {
                    throw new Exception(
                        "Gagal memperbarui stok produk."
                    );
                }

                if (mysqli_stmt_affected_rows($stmt_update_stock) !== 1) {
                    throw new Exception(
                        "Stok produk berubah. Silakan ulangi checkout."
                    );
                }
            }

            mysqli_stmt_close($stmt_detail);
            mysqli_stmt_close($stmt_update_stock);

            /*
            |--------------------------------------------------------------------------
            | 9. KOSONGKAN KERANJANG BUYER
            |--------------------------------------------------------------------------
            */

            $stmt_delete_cart = mysqli_prepare(
                $conn,
                "DELETE FROM cart WHERE user_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt_delete_cart,
                "i",
                $user_id
            );

            if (!mysqli_stmt_execute($stmt_delete_cart)) {
                throw new Exception(
                    "Gagal mengosongkan keranjang."
                );
            }

            mysqli_stmt_close($stmt_delete_cart);

            /*
            |--------------------------------------------------------------------------
            | 10. SELESAIKAN TRANSAKSI
            |--------------------------------------------------------------------------
            */

            mysqli_commit($conn);

            /*
             * Buyer diarahkan ke halaman pesanan.
             * orders.php harus mengambil data berdasarkan
             * orders.user_id milik buyer yang login.
             */

            header(
                "Location: orders.php?success=1&order_code=" .
                urlencode($order_code)
            );

            exit;

        } catch (Throwable $e) {

            mysqli_rollback($conn);

            $error = $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| 11. DATA AWAL FORM
|--------------------------------------------------------------------------
*/

$default_name = $user['name'] ?? '';
$default_phone = $user['phone'] ?? '';

$old_name = $_POST['recipient_name'] ?? $default_name;
$old_phone = $_POST['phone'] ?? $default_phone;
$old_address = $_POST['address'] ?? '';
$old_shipping = $_POST['shipping_method'] ?? '';
$old_payment = $_POST['payment_method'] ?? '';

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
            gap: 10px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 9px 14px;
            border-radius: 8px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,.2);
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .title {
            margin-bottom: 25px;
        }

        .title h1 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .title p {
            color: #777;
        }

        .back {
            display: inline-block;
            color: #ff6b00;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .error {
            background: #ffe5e5;
            color: #b42318;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .notice {
            background: #fff4e8;
            color: #974500;
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .checkout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 350px;
            gap: 25px;
            align-items: start;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
            margin-bottom: 20px;
        }

        .card h2 {
            margin-bottom: 20px;
            font-size: 21px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 9px;
            font-size: 15px;
            outline: none;
            background: white;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #ff6b00;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .product {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .product:last-of-type {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .product-image {
            width: 65px;
            height: 65px;
            object-fit: contain;
            background: #f5f5f5;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 5px;
            overflow-wrap: anywhere;
        }

        .product-qty {
            font-size: 14px;
            color: #777;
        }

        .product-price {
            font-weight: bold;
            color: #ff6b00;
            white-space: nowrap;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 15px;
        }

        .summary-row span:last-child {
            text-align: right;
        }

        .total {
            border-top: 1px solid #eee;
            padding-top: 18px;
            font-size: 19px;
            font-weight: bold;
        }

        .total-price {
            color: #ff6b00;
        }

        .btn-order {
            width: 100%;
            border: none;
            background: #ff6b00;
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-order:hover {
            background: #e85d00;
        }

        .btn-order:disabled {
            background: #aaa;
            cursor: not-allowed;
        }

        .status-note {
            margin-top: 14px;
            color: #777;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 800px) {
            .checkout {
                grid-template-columns: 1fr;
            }

            .navbar {
                padding: 15px 20px;
            }

            .nav-links {
                display: none;
            }

            .container {
                margin: 25px auto;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="logo">🛒 MedanMart</div>

    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="store.php">Store</a>
        <a href="cart.php">Keranjang</a>
        <a href="orders.php">Pesanan</a>
    </div>
</nav>

<main class="container">

    <a href="cart.php" class="back">
        ← Kembali ke Keranjang
    </a>

    <div class="title">
        <h1>Checkout 📦</h1>
        <p>Lengkapi informasi pengiriman dan pembayaran.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($stock_problem): ?>
        <div class="notice">
            Ada produk yang jumlahnya melebihi stok tersedia.
            Perbarui jumlah produk di keranjang sebelum membuat pesanan.
        </div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm">

        <div class="checkout">

            <div>

                <section class="card">
                    <h2>📍 Informasi Pengiriman</h2>

                    <div class="form-group">
                        <label for="recipient_name">Nama Penerima</label>
                        <input
                            type="text"
                            id="recipient_name"
                            name="recipient_name"
                            maxlength="150"
                            value="<?= htmlspecialchars($old_name, ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">Nomor Telepon</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            maxlength="30"
                            value="<?= htmlspecialchars($old_phone, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Contoh: 08123456789"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat Lengkap</label>
                        <textarea
                            id="address"
                            name="address"
                            maxlength="1000"
                            placeholder="Masukkan alamat lengkap pengiriman..."
                            required
                        ><?= htmlspecialchars($old_address, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="shipping_method">Metode Pengiriman</label>
                        <select
                            id="shipping_method"
                            name="shipping_method"
                            required
                        >
                            <option value="">-- Pilih Pengiriman --</option>

                            <option value="Reguler"
                                <?= $old_shipping === 'Reguler' ? 'selected' : '' ?>>
                                Reguler - Rp 10.000
                            </option>

                            <option value="Express"
                                <?= $old_shipping === 'Express' ? 'selected' : '' ?>>
                                Express - Rp 20.000
                            </option>

                            <option value="Pickup"
                                <?= $old_shipping === 'Pickup' ? 'selected' : '' ?>>
                                Ambil di Toko - Gratis
                            </option>
                        </select>
                    </div>
                </section>

                <section class="card">
                    <h2>💳 Metode Pembayaran</h2>

                    <div class="form-group">
                        <label for="payment_method">Pilih Pembayaran</label>
                        <select
                            id="payment_method"
                            name="payment_method"
                            required
                        >
                            <option value="">-- Pilih Pembayaran --</option>

                            <option value="transfer"
                                <?= $old_payment === 'transfer' ? 'selected' : '' ?>>
                                Bank Transfer
                            </option>

                            <option value="qris"
                                <?= $old_payment === 'qris' ? 'selected' : '' ?>>
                                QRIS
                            </option>

                            <option value="cod"
                                <?= $old_payment === 'cod' ? 'selected' : '' ?>>
                                COD
                            </option>
                        </select>
                    </div>
                </section>

            </div>

            <aside class="card">
                <h2>🛍️ Ringkasan Pesanan</h2>

                <?php foreach ($cart_items as $item): ?>
                    <div class="product">

                        <div class="product-info">

                            <?php if (!empty($item['image'])): ?>
                                <img
                                    src="../uploads/products/<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="product-image"
                                    alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php endif; ?>

                            <div>
                                <div class="product-name">
                                    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <div class="product-qty">
                                    <?= (int) $item['quantity'] ?> ×
                                    <?= rupiah($item['price']) ?>
                                </div>

                                <?php if (
                                    $item['quantity'] <= 0 ||
                                    $item['stock'] < $item['quantity']
                                ): ?>
                                    <div style="color:#b42318;font-size:12px;margin-top:5px">
                                        Stok tidak mencukupi
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>

                        <div class="product-price">
                            <?= rupiah($item['item_subtotal']) ?>
                        </div>

                    </div>
                <?php endforeach; ?>

                <br>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal"
                          data-value="<?= htmlspecialchars((string) $subtotal, ENT_QUOTES, 'UTF-8') ?>">
                        <?= rupiah($subtotal) ?>
                    </span>
                </div>

                <div class="summary-row">
                    <span>Ongkir</span>
                    <span id="shippingCost">Pilih pengiriman</span>
                </div>

                <div class="summary-row total">
                    <span>Total</span>
                    <span class="total-price" id="grandTotal">
                        <?= rupiah($subtotal) ?>
                    </span>
                </div>

                <button
                    type="submit"
                    class="btn-order"
                    id="submitOrder"
                    <?= $stock_problem ? 'disabled' : '' ?>
                >
                    ✅ Buat Pesanan
                </button>

                <p class="status-note">
                    Setelah pesanan dibuat, status awalnya adalah
                    <strong>Menunggu Diproses</strong>.
                    Kamu dapat memantau perkembangannya melalui menu Pesanan.
                </p>
            </aside>

        </div>

    </form>

</main>

<script>
    const shippingSelect = document.getElementById('shipping_method');
    const subtotalElement = document.getElementById('subtotal');
    const shippingCostElement = document.getElementById('shippingCost');
    const grandTotalElement = document.getElementById('grandTotal');

    const subtotal = Number(subtotalElement.dataset.value || 0);

    function formatRupiah(value) {
        return 'Rp ' + Number(value).toLocaleString('id-ID', {
            maximumFractionDigits: 0
        });
    }

    function updateTotal() {
        const method = shippingSelect.value;

        let shippingCost = 0;

        if (method === 'Reguler') {
            shippingCost = 10000;
        } else if (method === 'Express') {
            shippingCost = 20000;
        }

        if (!method) {
            shippingCostElement.textContent = 'Pilih pengiriman';
        } else {
            shippingCostElement.textContent =
                method === 'Pickup'
                    ? 'Gratis'
                    : formatRupiah(shippingCost);
        }

        grandTotalElement.textContent =
            formatRupiah(subtotal + shippingCost);
    }

    shippingSelect.addEventListener('change', updateTotal);
    updateTotal();
</script>

</body>
</html>