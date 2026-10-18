<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Pastikan buyer sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

// Pastikan jumlah minimal 1
if ($quantity < 1) {
    header("Location: products.php");
    exit;
}

// Ambil data produk
$query = "
    SELECT id, stock
    FROM products
    WHERE id = '$product_id'
";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "Produk tidak ditemukan.";
    exit;
}

$product = mysqli_fetch_assoc($result);

// Cek stok
if ($quantity > $product['stock']) {
    echo "Jumlah yang dipilih melebihi stok.";
    exit;
}

// Cek apakah produk sudah ada di keranjang
$query = "
    SELECT *
    FROM cart
    WHERE user_id = '$user_id'
    AND product_id = '$product_id'
";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {

    // Jika sudah ada, tambahkan jumlah
    $cart = mysqli_fetch_assoc($result);

    $new_quantity = $cart['quantity'] + $quantity;

    if ($new_quantity > $product['stock']) {
        echo "Jumlah produk di keranjang melebihi stok.";
        exit;
    }

    $query = "
        UPDATE cart
        SET quantity = '$new_quantity'
        WHERE id = '{$cart['id']}'
    ";

} else {

    // Jika belum ada, buat item baru
    $query = "
        INSERT INTO cart
        (user_id, product_id, quantity)
        VALUES
        ('$user_id', '$product_id', '$quantity')
    ";
}

if (mysqli_query($conn, $query)) {

    header("Location: cart.php");
    exit;

} else {

    echo "Gagal menambahkan ke keranjang: " . mysqli_error($conn);
}
?>