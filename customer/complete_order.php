```php
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Pastikan buyer sudah login
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'buyer'
) {
    header("Location: ../login.php");
    exit;
}

// Proses hanya melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$order_id = filter_input(
    INPUT_POST,
    'order_id',
    FILTER_VALIDATE_INT
);

// Validasi ID pesanan
if (!$order_id || $order_id <= 0) {
    header("Location: orders.php?error=invalid_order");
    exit;
}

// Buyer hanya bisa menyelesaikan pesanan miliknya
// yang statusnya sudah shipped
$stmt = mysqli_prepare(
    $conn,
    "UPDATE orders
     SET status = 'completed'
     WHERE id = ?
       AND user_id = ?
       AND status = 'shipped'"
);

if (!$stmt) {
    header("Location: orders.php?error=update_failed");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $order_id,
    $user_id
);

$success = mysqli_stmt_execute($stmt);
$updated = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);

// Jika status berhasil diubah
if ($success && $updated === 1) {
    header("Location: orders.php?completed=1");
    exit;
}

// Jika pesanan tidak ditemukan atau belum dikirim
header("Location: orders.php?error=not_allowed");
exit;
?>
```