<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| 1. CEK LOGIN SELLER
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'seller'
) {
    header("Location: ../login.php");
    exit;
}

$seller_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| 2. PASTIKAN REQUEST POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}

$order_id = (int) ($_POST['order_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');

if ($order_id <= 0) {
    header("Location: orders.php?error=" . urlencode("ID pesanan tidak valid."));
    exit;
}

/*
|--------------------------------------------------------------------------
| 3. VALIDASI STATUS YANG DIIZINKAN
|--------------------------------------------------------------------------
*/

$allowed_status = [
    'pending',
    'paid',
    'shipped',
    'completed',
    'cancelled'
];

if (!in_array($new_status, $allowed_status, true)) {
    header("Location: orders.php?error=" . urlencode("Status pesanan tidak valid."));
    exit;
}

/*
|--------------------------------------------------------------------------
| 4. MULAI TRANSAKSI
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conn);

try {

    /*
    |--------------------------------------------------------------------------
    | 5. AMBIL TOKO MILIK SELLER
    |--------------------------------------------------------------------------
    */

    $stmt_store = mysqli_prepare(
        $conn,
        "SELECT id
         FROM stores
         WHERE seller_id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt_store,
        "i",
        $seller_id
    );

    mysqli_stmt_execute($stmt_store);

    $store_result = mysqli_stmt_get_result($stmt_store);
    $store = mysqli_fetch_assoc($store_result);

    mysqli_stmt_close($stmt_store);

    if (!$store) {
        throw new Exception("Toko seller tidak ditemukan.");
    }

    $store_id = (int) $store['id'];

    /*
    |--------------------------------------------------------------------------
    | 6. PERIKSA PESANAN DAN STATUS SAAT INI
    |--------------------------------------------------------------------------
    |
    | Pesanan harus memiliki minimal satu produk dari toko seller.
    | Status pesanan dikunci selama transaksi berlangsung.
    |
    */

    $stmt_order = mysqli_prepare(
        $conn,
        "SELECT o.id, o.status
         FROM orders o
         WHERE o.id = ?
           AND EXISTS (
               SELECT 1
               FROM order_details od
               INNER JOIN products p
                   ON p.id = od.product_id
               WHERE od.order_id = o.id
                 AND p.store_id = ?
           )
         LIMIT 1
         FOR UPDATE"
    );

    mysqli_stmt_bind_param(
        $stmt_order,
        "ii",
        $order_id,
        $store_id
    );

    mysqli_stmt_execute($stmt_order);

    $order_result = mysqli_stmt_get_result($stmt_order);
    $order = mysqli_fetch_assoc($order_result);

    mysqli_stmt_close($stmt_order);

    if (!$order) {
        throw new Exception(
            "Pesanan tidak ditemukan atau bukan pesanan dari toko Anda."
        );
    }

    $current_status = $order['status'];

    /*
    |--------------------------------------------------------------------------
    | 7. TENTUKAN PERPINDAHAN STATUS
    |--------------------------------------------------------------------------
    |
    | pending   -> paid / shipped / cancelled
    | paid      -> shipped / cancelled
    | shipped   -> completed
    | completed -> tidak dapat diubah
    | cancelled -> tidak dapat diubah
    |
    | Status paid tetap tersedia untuk mengikuti sistem yang sudah ada.
    |
    */

    $status_transitions = [
        'pending' => [
            'paid',
            'shipped',
            'cancelled'
        ],

        'paid' => [
            'shipped',
            'cancelled'
        ],

        'shipped' => [
            'completed'
        ],

        'completed' => [],

        'cancelled' => []
    ];

    if (!array_key_exists($current_status, $status_transitions)) {
        throw new Exception(
            "Status pesanan saat ini tidak dikenali."
        );
    }

    if ($current_status === $new_status) {
        throw new Exception(
            "Pesanan sudah memiliki status tersebut."
        );
    }

    if (
        !in_array(
            $new_status,
            $status_transitions[$current_status],
            true
        )
    ) {
        throw new Exception(
            "Perubahan status dari " .
            $current_status .
            " menjadi " .
            $new_status .
            " tidak diizinkan."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 8. PERBARUI STATUS PESANAN
    |--------------------------------------------------------------------------
    */

    $stmt_update = mysqli_prepare(
        $conn,
        "UPDATE orders
         SET status = ?
         WHERE id = ? AND status = ?"
    );

    mysqli_stmt_bind_param(
        $stmt_update,
        "sis",
        $new_status,
        $order_id,
        $current_status
    );

    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception(
            "Gagal memperbarui status pesanan."
        );
    }

    if (mysqli_stmt_affected_rows($stmt_update) !== 1) {
        mysqli_stmt_close($stmt_update);

        throw new Exception(
            "Status pesanan berubah. Silakan muat ulang halaman."
        );
    }

    mysqli_stmt_close($stmt_update);

    /*
    |--------------------------------------------------------------------------
    | 9. SIMPAN PERUBAHAN
    |--------------------------------------------------------------------------
    */

    mysqli_commit($conn);

    header(
        "Location: orders.php?success=1"
    );

    exit;

} catch (Throwable $e) {

    mysqli_rollback($conn);

    header(
        "Location: orders.php?error=" .
        urlencode($e->getMessage())
    );

    exit;
}
?>
