
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/* =========================
   CEK LOGIN SELLER
========================= */

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'seller'
) {
    header("Location: ../login.php");
    exit;
}

$seller_id = (int) $_SESSION['user_id'];

/* =========================
   VALIDASI ID PRODUK
========================= */

if (
    !isset($_GET['id']) ||
    !ctype_digit((string) $_GET['id']) ||
    (int) $_GET['id'] <= 0
) {
    header("Location: products.php");
    exit;
}

$product_id = (int) $_GET['id'];

/* =========================
   CEK PRODUK MILIK SELLER
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT products.id, products.image
     FROM products
     INNER JOIN stores
        ON products.store_id = stores.id
     WHERE products.id = ?
       AND stores.seller_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $product_id,
    $seller_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$product) {
    header("Location: products.php?error=product_not_found");
    exit;
}

/* =========================
   PROSES HAPUS PRODUK
========================= */

// Hapus data dari database terlebih dahulu.
$delete = mysqli_prepare(
    $conn,
    "DELETE products
     FROM products
     INNER JOIN stores
        ON products.store_id = stores.id
     WHERE products.id = ?
       AND stores.seller_id = ?"
);

mysqli_stmt_bind_param(
    $delete,
    "ii",
    $product_id,
    $seller_id
);

$success = mysqli_stmt_execute($delete);
$deleted_rows = mysqli_stmt_affected_rows($delete);

mysqli_stmt_close($delete);

if ($success && $deleted_rows > 0) {

    // Hapus file foto setelah data berhasil dihapus.
    if (!empty($product['image'])) {
        $upload_dir = realpath(__DIR__ . "/../uploads/products/");
        $image_name = basename($product['image']);

        if ($upload_dir !== false && $image_name !== '') {
            $image_path = $upload_dir . DIRECTORY_SEPARATOR . $image_name;

            if (is_file($image_path)) {
                @unlink($image_path);
            }
        }
    }

    header("Location: products.php?deleted=1");
    exit;
}

// Jika gagal, jangan menghapus foto.
header("Location: products.php?error=delete_failed");
exit;
?>
