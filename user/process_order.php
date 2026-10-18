<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data dari form checkout
$recipient_name = mysqli_real_escape_string($conn, $_POST['recipient_name']);
$phone = mysqli_real_escape_string($conn, $_POST['phone']);
$address = mysqli_real_escape_string($conn, $_POST['address']);
$shipping_method = mysqli_real_escape_string($conn, $_POST['shipping_method']);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

// Ambil isi keranjang langsung dari database
$query = "
    SELECT 
        cart.product_id,
        cart.quantity,
        products.name,
        products.price,
        products.stock
    FROM cart
    JOIN products ON cart.product_id = products.id
    WHERE cart.user_id = '$user_id'
";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "Keranjang kosong.";
    exit;
}

$subtotal = 0;
$cart_items = [];

// Cek stok dan hitung subtotal
while ($item = mysqli_fetch_assoc($result)) {

    if ($item['quantity'] > $item['stock']) {
        echo "Stok produk " . $item['name'] . " tidak mencukupi.";
        exit;
    }

    $item_subtotal = $item['price'] * $item['quantity'];

    $subtotal += $item_subtotal;

    $cart_items[] = [
        'product_id' => $item['product_id'],
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'subtotal' => $item_subtotal
    ];
}

// Ongkir dummy
$shipping_cost = 15000;

$total = $subtotal + $shipping_cost;

// Buat kode pesanan
$order_code = "MM" . date("YmdHis") . rand(100, 999);

// Mulai transaksi database
mysqli_begin_transaction($conn);

try {

    // 1. Simpan data pesanan
    $query_order = "
        INSERT INTO orders
        (
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
        )
        VALUES
        (
            '$user_id',
            '$order_code',
            '$recipient_name',
            '$phone',
            '$address',
            '$shipping_method',
            '$shipping_cost',
            '$subtotal',
            '$total',
            '$payment_method',
            'pending'
        )
    ";

    if (!mysqli_query($conn, $query_order)) {
        throw new Exception("Gagal menyimpan pesanan.");
    }

    // Ambil ID pesanan yang baru dibuat
    $order_id = mysqli_insert_id($conn);


    // 2. Simpan detail pesanan
    foreach ($cart_items as $item) {

        $product_id = $item['product_id'];
        $product_name = mysqli_real_escape_string($conn, $item['name']);
        $price = $item['price'];
        $quantity = $item['quantity'];
        $item_subtotal = $item['subtotal'];

        $query_detail = "
            INSERT INTO order_details
            (
                order_id,
                product_id,
                product_name,
                price,
                quantity,
                subtotal
            )
            VALUES
            (
                '$order_id',
                '$product_id',
                '$product_name',
                '$price',
                '$quantity',
                '$item_subtotal'
            )
        ";

        if (!mysqli_query($conn, $query_detail)) {
            throw new Exception("Gagal menyimpan detail pesanan.");
        }


        // 3. Kurangi stok produk
        $query_stock = "
            UPDATE products
            SET stock = stock - '$quantity'
            WHERE id = '$product_id'
        ";

        if (!mysqli_query($conn, $query_stock)) {
            throw new Exception("Gagal mengurangi stok.");
        }
    }


    // 4. Simpan data pembayaran
    $query_payment = "
        INSERT INTO payments
        (
            order_id,
            payment_method,
            amount,
            status
        )
        VALUES
        (
            '$order_id',
            '$payment_method',
            '$total',
            'pending'
        )
    ";

    if (!mysqli_query($conn, $query_payment)) {
        throw new Exception("Gagal menyimpan data pembayaran.");
    }


    // 5. Kosongkan keranjang
    $query_cart = "
        DELETE FROM cart
        WHERE user_id = '$user_id'
    ";

    if (!mysqli_query($conn, $query_cart)) {
        throw new Exception("Gagal mengosongkan keranjang.");
    }


    // Jika semuanya berhasil
    mysqli_commit($conn);

    // Arahkan ke halaman sukses
    header("Location: order_success.php?id=$order_id");
    exit;

} catch (Exception $e) {

    // Batalkan semua perubahan jika terjadi error
    mysqli_rollback($conn);

    echo "Terjadi kesalahan: " . $e->getMessage();
}
?>