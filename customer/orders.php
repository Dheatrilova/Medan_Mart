<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// ========================================
// CEK LOGIN BUYER
// ========================================
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'buyer'
) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ========================================
// FUNGSI BANTU
// ========================================
function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($value)
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

$status_labels = [
    'pending'   => 'Menunggu Proses',
    'paid'      => 'Sudah Dibayar',
    'shipped'   => 'Sedang Dikirim',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan'
];

// ========================================
// PROSES UPLOAD BUKTI PEMBAYARAN
// ========================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_payment_proof'])
) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

    if (!$order_id || !isset($_FILES['payment_proof'])) {
        header("Location: orders.php?error=upload_failed");
        exit;
    }

    // Pastikan pesanan milik buyer yang sedang login.
    $check = mysqli_prepare(
        $conn,
        "SELECT id, payment_method, payment_proof, status
         FROM orders
         WHERE id = ? AND user_id = ?
         LIMIT 1"
    );

    if (!$check) {
        header("Location: orders.php?error=upload_failed");
        exit;
    }

    mysqli_stmt_bind_param($check, "ii", $order_id, $user_id);
    mysqli_stmt_execute($check);
    $order_check = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
    mysqli_stmt_close($check);

    if (!$order_check) {
        header("Location: orders.php?error=invalid_order");
        exit;
    }

    if (
        strtolower((string) $order_check['payment_method']) === 'cod' ||
        $order_check['status'] !== 'pending' ||
        !empty($order_check['payment_proof'])
    ) {
        header("Location: orders.php?error=upload_not_allowed");
        exit;
    }

    $file = $_FILES['payment_proof'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: orders.php?error=upload_failed");
        exit;
    }

    if ($file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
        header("Location: orders.php?error=file_too_large");
        exit;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        header("Location: orders.php?error=invalid_file");
        exit;
    }

    // Periksa MIME berdasarkan isi file, bukan nama file.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (
        !isset($allowed_types[$mime]) ||
        @getimagesize($file['tmp_name']) === false
    ) {
        header("Location: orders.php?error=invalid_file");
        exit;
    }

    $upload_dir = __DIR__ . '/../uploads/payments/';

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        header("Location: orders.php?error=upload_failed");
        exit;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime];
    $destination = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        header("Location: orders.php?error=upload_failed");
        exit;
    }

    // Simpan nama file hanya jika pesanan masih memenuhi syarat.
    $update = mysqli_prepare(
        $conn,
        "UPDATE orders
         SET payment_proof = ?
         WHERE id = ?
           AND user_id = ?
           AND status = 'pending'
           AND LOWER(payment_method) <> 'cod'
           AND (payment_proof IS NULL OR payment_proof = '')"
    );

    if (!$update) {
        if (is_file($destination)) {
            unlink($destination);
        }
        header("Location: orders.php?error=upload_failed");
        exit;
    }

    mysqli_stmt_bind_param($update, "sii", $filename, $order_id, $user_id);
    $executed = mysqli_stmt_execute($update);
    $updated = $executed && mysqli_stmt_affected_rows($update) === 1;
    mysqli_stmt_close($update);

    if (!$updated) {
        if (is_file($destination)) {
            unlink($destination);
        }
        header("Location: orders.php?error=upload_failed");
        exit;
    }

    header("Location: orders.php?proof_uploaded=1");
    exit;
}

// ========================================
// PESAN NOTIFIKASI
// ========================================
$notice = '';
$notice_type = '';

if (isset($_GET['completed']) && $_GET['completed'] === '1') {
    $notice = 'Pesanan berhasil dikonfirmasi selesai. Terima kasih telah berbelanja!';
    $notice_type = 'success';
} elseif (isset($_GET['success'], $_GET['order_code'])) {
    $stmt_notice = mysqli_prepare(
        $conn,
        "SELECT order_code FROM orders
         WHERE user_id = ? AND order_code = ?
         LIMIT 1"
    );

    if ($stmt_notice) {
        mysqli_stmt_bind_param($stmt_notice, "is", $user_id, $_GET['order_code']);
        mysqli_stmt_execute($stmt_notice);
        $result_notice = mysqli_stmt_get_result($stmt_notice);

        if (mysqli_num_rows($result_notice) > 0) {
            $notice = 'Pesanan berhasil dibuat. Silakan pantau status pesanan kamu di halaman ini.';
            $notice_type = 'success';
        }

        mysqli_stmt_close($stmt_notice);
    }
} elseif (isset($_GET['proof_uploaded'])) {
    $notice = 'Bukti pembayaran berhasil diunggah. Silakan tunggu verifikasi admin.';
    $notice_type = 'success';
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'not_allowed':
            $notice = 'Pesanan belum dapat diselesaikan. Pastikan pesanan sudah dikirim.';
            break;
        case 'invalid_order':
            $notice = 'Data pesanan tidak valid atau bukan milik akun kamu.';
            break;
        case 'file_too_large':
            $notice = 'Ukuran gambar harus lebih dari 0 byte dan maksimal 5 MB.';
            break;
        case 'invalid_file':
            $notice = 'File tidak valid. Gunakan gambar JPG, PNG, atau WEBP.';
            break;
        case 'upload_not_allowed':
            $notice = 'Pesanan ini tidak dapat menerima upload bukti pembayaran.';
            break;
        default:
            $notice = 'Upload gagal. Periksa gambar dan coba lagi.';
            break;
    }

    $notice_type = 'error';
}

// ========================================
// AMBIL PESANAN BUYER
// ========================================
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        order_code,
        recipient_name,
        phone,
        address,
        shipping_method,
        shipping_cost,
        subtotal,
        total,
        payment_method,
        payment_proof,
        status,
        created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY id DESC"
);

if (!$stmt) {
    die("Gagal menyiapkan data pesanan.");
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - MedanMart</title>

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

        .header {
            background: #ff7a00;
            color: white;
            padding: 24px 5%;
        }

        .header h1 {
            margin-bottom: 6px;
        }

        .header p {
            opacity: .95;
            line-height: 1.5;
        }

        .container {
            width: 90%;
            max-width: 1050px;
            margin: 30px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 22px;
            color: #ff7a00;
            text-decoration: none;
            font-weight: bold;
        }

        .notice {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .notice.success {
            background: #e8f8ed;
            color: #218838;
            border: 1px solid #b7e4c7;
        }

        .notice.error {
            background: #fff0f0;
            color: #a52834;
            border: 1px solid #f1b9be;
        }

        .order-card {
            background: white;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .06);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .order-code {
            font-size: 20px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .date {
            color: #777;
            font-size: 13px;
            margin-top: 7px;
        }

        .status {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .section-title {
            font-size: 16px;
            margin-bottom: 12px;
        }

        .info {
            background: #fafafa;
            border-radius: 10px;
            padding: 17px;
            margin-bottom: 22px;
        }

        .info p {
            margin-bottom: 10px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .info p:last-child { margin-bottom: 0; }

        .product-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            padding: 13px 0;
            border-bottom: 1px solid #eee;
        }

        .product-row:last-child { border-bottom: none; }

        .product-name {
            font-weight: bold;
            line-height: 1.5;
        }

        .product-detail {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
        }

        .product-price {
            color: #ff7a00;
            font-weight: bold;
            white-space: nowrap;
        }

        .summary {
            border-top: 1px solid #eee;
            margin-top: 15px;
            padding-top: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .summary-row.grand-total {
            color: #ff7a00;
            font-size: 20px;
            font-weight: bold;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px dashed #ddd;
        }

        .payment-upload {
            margin-top: 22px;
            padding: 18px;
            border: 1px solid #ffdfbf;
            background: #fff8f0;
            border-radius: 12px;
            line-height: 1.6;
        }

        .payment-upload p { margin-bottom: 12px; }

        .payment-upload input[type="file"] {
            display: block;
            width: 100%;
            margin: 12px 0;
            padding: 10px;
            background: white;
            border: 1px solid #ffdfbf;
            border-radius: 8px;
        }

        .btn-upload,
        .btn-complete {
            border: none;
            background: #ff7a00;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-upload:hover,
        .btn-complete:hover { background: #e66d00; }

        .upload-success {
            color: #218838;
            font-weight: bold;
        }

        .proof-preview {
            display: block;
            max-width: 100%;
            max-height: 260px;
            object-fit: contain;
            object-position: left;
            margin: 12px 0;
            border: 1px solid #eee;
            border-radius: 10px;
            background: white;
        }

        .proof-link {
            color: #c65300;
            font-weight: bold;
            text-decoration: none;
        }

        .proof-link:hover { text-decoration: underline; }

        .payment-upload small {
            display: block;
            margin-top: 10px;
            color: #777;
        }

        .complete-box {
            margin-top: 22px;
            padding: 18px;
            background: #fff8f0;
            border: 1px solid #ffdfbf;
            border-radius: 12px;
        }

        .complete-box p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 14px;
        }

        .completed-message {
            margin-top: 20px;
            padding: 14px;
            background: #e8f8ed;
            color: #218838;
            border: 1px solid #b7e4c7;
            border-radius: 10px;
            font-weight: bold;
            line-height: 1.5;
        }

        .empty {
            background: white;
            padding: 45px 20px;
            text-align: center;
            border-radius: 14px;
            color: #777;
            line-height: 1.7;
        }

        .empty h2 {
            color: #333;
            margin-bottom: 8px;
        }

        @media (max-width: 600px) {
            .header { padding: 22px 5%; }
            .container { width: 94%; margin-top: 22px; }
            .order-card { padding: 18px; }
            .product-row { flex-direction: column; }
            .summary-row.grand-total { font-size: 17px; }
            .btn-complete, .btn-upload { width: 100%; }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🛍️ Pesanan Saya</h1>
        <p>Pantau status dan detail pesanan belanja kamu di MedanMart.</p>
    </div>

    <div class="container">
        <a href="dashboard.php" class="back">← Kembali ke Dashboard</a>

        <?php if ($notice !== ''): ?>
            <div class="notice <?= e($notice_type); ?>">
                <?= e($notice); ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($orders_result) === 0): ?>
            <div class="empty">
                <h2>Belum Ada Pesanan</h2>
                <p>Pesanan yang kamu buat akan muncul di halaman ini.</p>
            </div>
        <?php else: ?>

            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-code"><?= e($order['order_code']); ?></div>
                            <div class="date">
                                <?= e(date('d M Y, H:i', strtotime($order['created_at']))); ?>
                            </div>
                        </div>

                        <span class="status status-<?= e($order['status']); ?>">
                            <?= e($status_labels[$order['status']] ?? $order['status']); ?>
                        </span>
                    </div>

                    <h3 class="section-title">Informasi Penerima</h3>
                    <div class="info">
                        <p><strong>Nama:</strong> <?= e($order['recipient_name']); ?></p>
                        <p><strong>No. HP:</strong> <?= e($order['phone']); ?></p>
                        <p><strong>Alamat:</strong><br><?= nl2br(e($order['address'])); ?></p>
                        <p><strong>Pengiriman:</strong> <?= e($order['shipping_method'] ?: '-'); ?></p>
                        <p><strong>Metode Pembayaran:</strong> <?= e(strtoupper($order['payment_method'])); ?></p>
                    </div>

                    <h3 class="section-title">Ringkasan Pembelian</h3>

                    <?php
                    $detail_stmt = mysqli_prepare(
                        $conn,
                        "SELECT product_name, price, quantity, subtotal
                         FROM order_details
                         WHERE order_id = ?
                         ORDER BY id ASC"
                    );
                    ?>

                    <?php if (!$detail_stmt): ?>
                        <p>Detail produk tidak dapat dimuat.</p>
                    <?php else: ?>
                        <?php
                        mysqli_stmt_bind_param($detail_stmt, "i", $order['id']);
                        mysqli_stmt_execute($detail_stmt);
                        $details_result = mysqli_stmt_get_result($detail_stmt);
                        ?>

                        <?php while ($detail = mysqli_fetch_assoc($details_result)): ?>
                            <div class="product-row">
                                <div>
                                    <div class="product-name"><?= e($detail['product_name']); ?></div>
                                    <div class="product-detail">
                                        <?= (int) $detail['quantity']; ?> × <?= rupiah($detail['price']); ?>
                                    </div>
                                </div>
                                <div class="product-price"><?= rupiah($detail['subtotal']); ?></div>
                            </div>
                        <?php endwhile; ?>

                        <?php mysqli_stmt_close($detail_stmt); ?>
                    <?php endif; ?>

                    <div class="summary">
                        <div class="summary-row">
                            <span>Subtotal Produk</span>
                            <span><?= rupiah($order['subtotal']); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Ongkos Kirim</span>
                            <span><?= rupiah($order['shipping_cost']); ?></span>
                        </div>
                        <div class="summary-row grand-total">
                            <span>Total Pembayaran</span>
                            <span><?= rupiah($order['total']); ?></span>
                        </div>
                    </div>

                    <?php if (strtolower((string) $order['payment_method']) === 'cod'): ?>
                        <div class="payment-upload">
                            <h3 class="section-title">💳 Bukti Pembayaran</h3>
                            <p>Pembayaran menggunakan COD. Bukti pembayaran tidak perlu diunggah saat ini.</p>
                        </div>
                    <?php elseif (!empty($order['payment_proof'])): ?>
                        <div class="payment-upload">
                            <h3 class="section-title">📷 Bukti Pembayaran</h3>
                            <p class="upload-success">✓ Bukti pembayaran sudah diunggah. Menunggu verifikasi admin.</p>
                            <a
                                class="proof-link"
                                href="../uploads/payments/<?= rawurlencode(basename(str_replace('\\', '/', $order['payment_proof']))); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >Lihat bukti pembayaran ↗</a>
                        </div>
                    <?php elseif ($order['status'] === 'pending'): ?>
                        <div class="payment-upload">
                            <h3 class="section-title">📷 Upload Bukti Pembayaran</h3>
                            <p>Pilih gambar bukti transfer atau pembayaran QRIS untuk pesanan ini.</p>

                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">

                                <input
                                    type="file"
                                    name="payment_proof"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    required
                                >

                                <button
                                    type="submit"
                                    name="upload_payment_proof"
                                    class="btn-upload"
                                >Upload Bukti Pembayaran</button>
                            </form>

                            <small>Format JPG, PNG, atau WEBP. Ukuran maksimal 5 MB.</small>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'shipped'): ?>
                        <div class="complete-box">
                            <p>
                                Pesanan kamu sedang dikirim. Jika barang sudah kamu terima,
                                tekan tombol berikut untuk menyelesaikan pesanan.
                            </p>
                            <form
                                action="complete_order.php"
                                method="POST"
                                onsubmit="return confirm('Apakah kamu yakin sudah menerima pesanan ini?');"
                            >
                                <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">
                                <button type="submit" class="btn-complete">✓ Pesanan Diterima</button>
                            </form>
                        </div>
                    <?php elseif ($order['status'] === 'completed'): ?>
                        <div class="completed-message">
                            ✓ Pesanan sudah selesai. Terima kasih telah berbelanja di MedanMart!
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

        <?php endif; ?>
    </div>
</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
