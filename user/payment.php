<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: " . __DIR__ . "/dashboard.php");
    exit;
}

$order_id = intval($_GET['id']);

$query = "
    SELECT *
    FROM orders
    WHERE id = '$order_id'
    AND user_id = '$user_id'
";

$result = mysqli_query($conn, $query);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo "Pesanan tidak ditemukan.";
    exit;
}

// COD tidak perlu upload bukti
if ($order['payment_method'] == 'cod') {
    header("Location: orders.php");
    exit;
}

$pesan = "";

if (isset($_POST['upload'])) {

    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] != 0) {
        $pesan = "Silakan pilih file bukti pembayaran.";
    } else {

        $file = $_FILES['payment_proof'];

        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];

        $extension = strtolower(
            pathinfo($file_name, PATHINFO_EXTENSION)
        );

        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowed)) {

            $pesan = "Format file harus JPG, JPEG, atau PNG.";

        } elseif ($file_size > 2 * 1024 * 1024) {

            $pesan = "Ukuran file maksimal 2 MB.";

        } else {

            $new_name = "payment_" . $order_id . "_" . time() . "." . $extension;

            $upload_path = "../uploads/payments/" . $new_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {

                // Update orders
                $query_update = "
                    UPDATE orders
                    SET payment_proof = '$new_name'
                    WHERE id = '$order_id'
                    AND user_id = '$user_id'
                ";

                mysqli_query($conn, $query_update);

                // Update payments
                $query_payment = "
                    UPDATE payments
                    SET proof = '$new_name',
                        status = 'pending'
                    WHERE order_id = '$order_id'
                ";

                mysqli_query($conn, $query_payment);

                $pesan = "Bukti pembayaran berhasil diupload!";
            } else {

                $pesan = "Gagal mengupload bukti pembayaran.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Upload Pembayaran - MedanMart</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
        }

        .container {
            width: 600px;
            max-width: 90%;
            margin: 60px auto;
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .order-info {
            background: #f1f3f5;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .order-info p {
            margin: 8px 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #198754;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #157347;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #0d6efd;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>💳 Upload Bukti Pembayaran</h1>

    <div class="order-info">

        <p>
            <strong>Kode Pesanan:</strong>
            <?= htmlspecialchars($order['order_code']); ?>
        </p>

        <p>
            <strong>Metode Pembayaran:</strong>
            <?= strtoupper($order['payment_method']); ?>
        </p>

        <p>
            <strong>Total:</strong>
            Rp <?= number_format($order['total'], 0, ',', '.'); ?>
        </p>

    </div>

    <?php if ($pesan != ""): ?>

        <div class="<?= strpos($pesan, 'berhasil') !== false ? 'success' : 'error'; ?>">
            <?= $pesan; ?>
        </div>

    <?php endif; ?>

    <?php if (empty($order['payment_proof'])): ?>

        <form method="POST" enctype="multipart/form-data">

            <label>
                Pilih Bukti Pembayaran
            </label>

            <input
                type="file"
                name="payment_proof"
                accept=".jpg,.jpeg,.png"
                required
            >

            <small>
                Format JPG, JPEG, PNG. Maksimal 2 MB.
            </small>

            <button type="submit" name="upload">
                📤 Upload Bukti Pembayaran
            </button>

        </form>

    <?php else: ?>

        <div class="success">
            Bukti pembayaran sudah diupload dan sedang menunggu verifikasi.
        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back">
        ← Kembali ke Dashboard
    </a>

</div>

</body>
</html>