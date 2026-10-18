<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/* =========================
   CEK LOGIN ADMIN
========================= */
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

/* =========================
   LOAD DOMPDF
========================= */
$autoload = __DIR__ . "/../vendor/autoload.php";

if (!file_exists($autoload)) {
    die(
        "Dompdf belum terpasang. Buka terminal pada folder MedanMart " .
        "lalu jalankan: composer require dompdf/dompdf"
    );
}

require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

/* =========================
   FUNGSI BANTU
========================= */
function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function rupiah($angka)
{
    return 'Rp ' . number_format(
        (float)$angka,
        0,
        ',',
        '.'
    );
}

function statusLabel($status)
{
    $labels = [
        'pending'   => 'Menunggu Pembayaran',
        'paid'      => 'Sudah Dibayar',
        'shipped'   => 'Sedang Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan'
    ];

    return $labels[$status] ?? ucfirst((string)$status);
}

/* =========================
   FILTER TANGGAL
========================= */
$start_date = trim($_GET['start_date'] ?? '');
$end_date   = trim($_GET['end_date'] ?? '');
$error = '';

function validDate($date)
{
    if ($date === '') {
        return true;
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    return $dateObject !== false
        && $dateObject->format('Y-m-d') === $date;
}

if (!validDate($start_date) || !validDate($end_date)) {
    $error = 'Format tanggal tidak valid.';
} elseif (
    $start_date !== '' &&
    $end_date !== '' &&
    $start_date > $end_date
) {
    $error = 'Tanggal awal tidak boleh melebihi tanggal akhir.';
}

/* =========================
   AMBIL DATA PESANAN
========================= */
$orders = [];
$total_orders = 0;
$total_value = 0;

if ($error === '') {
    $query = "
        SELECT
            orders.id,
            orders.order_code,
            orders.subtotal,
            orders.shipping_cost,
            orders.total,
            orders.payment_method,
            orders.status,
            orders.created_at,
            orders.recipient_name AS buyer_name
        FROM orders
        WHERE orders.status != 'cancelled'";

    /*
       Tanggal sudah divalidasi dengan format YYYY-MM-DD
       sebelum digunakan dalam query.
    */
    if ($start_date !== '') {
        $query .= "
            AND orders.created_at >= '" . $start_date . " 00:00:00'
        ";
    }

    if ($end_date !== '') {
        $query .= "
            AND orders.created_at < DATE_ADD(
                '" . $end_date . " 00:00:00',
                INTERVAL 1 DAY
            )
        ";
    }

    $query .= " ORDER BY orders.created_at DESC";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Gagal mengambil data laporan: " . e(mysqli_error($conn)));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
        $total_value += (float)$row['total'];
    }

    $total_orders = count($orders);
}

/* =========================
   PERIODE LAPORAN
========================= */
if ($start_date !== '' && $end_date !== '') {
    $periode = date('d-m-Y', strtotime($start_date))
        . ' sampai '
        . date('d-m-Y', strtotime($end_date));
} elseif ($start_date !== '') {
    $periode = 'Mulai ' . date('d-m-Y', strtotime($start_date));
} elseif ($end_date !== '') {
    $periode = 'Sampai ' . date('d-m-Y', strtotime($end_date));
} else {
    $periode = 'Semua Periode';
}

$tanggal_cetak = date('d-m-Y H:i');

/* =========================
   BANGUN TABEL PDF
========================= */
$tableRows = '';

if (empty($orders) && $error === '') {
    $tableRows = '
        <tr>
            <td colspan="9" class="empty">
                Tidak ada data pesanan pada periode ini.
            </td>
        </tr>
    ';
} else {
    foreach ($orders as $index => $row) {
        $tanggalPesanan = strtotime($row['created_at']);

        $tanggalFormat = $tanggalPesanan !== false
            ? date('d-m-Y H:i', $tanggalPesanan)
            : $row['created_at'];

        $tableRows .= '
            <tr>
                <td class="center">' . ($index + 1) . '</td>
                <td>' . e($row['order_code']) . '</td>
                <td>' . e($row['buyer_name']) . '</td>
                <td class="money">' . rupiah($row['subtotal']) . '</td>
                <td class="money">' . rupiah($row['shipping_cost']) . '</td>
                <td class="money total">' . rupiah($row['total']) . '</td>
                <td>' . e($row['payment_method']) . '</td>
                <td>' . e(statusLabel($row['status'])) . '</td>
                <td>' . e($tanggalFormat) . '</td>
            </tr>
        ';
    }
}

/* =========================
   TEMPLATE PDF
========================= */
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<style>
    @page {
        size: A4 landscape;
        margin: 25px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        color: #1E293B;
        font-size: 9px;
    }

    .header {
        border-bottom: 3px solid #F97316;
        padding-bottom: 13px;
        margin-bottom: 18px;
    }

    .brand {
        color: #F97316;
        font-size: 23px;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .subtitle {
        color: #64748B;
        font-size: 10px;
    }

    .report-title {
        text-align: center;
        margin: 20px 0;
    }

    .report-title h2 {
        font-size: 17px;
        margin-bottom: 6px;
        color: #0F172A;
    }

    .report-title p {
        color: #64748B;
        margin: 3px 0;
    }

    .summary {
        width: 100%;
        margin-bottom: 18px;
    }

    .summary td {
        width: 50%;
        background: #FFF7ED;
        border: 1px solid #FED7AA;
        padding: 12px;
    }

    .summary-label {
        color: #9A3412;
        font-size: 9px;
        margin-bottom: 5px;
    }

    .summary-value {
        font-size: 16px;
        font-weight: bold;
        color: #0F172A;
    }

    table.data {
        width: 100%;
        border-collapse: collapse;
    }

    table.data th {
        background: #F97316;
        color: #FFFFFF;
        padding: 9px 6px;
        border: 1px solid #EA580C;
        text-align: left;
        font-size: 8px;
    }

    table.data td {
        padding: 8px 6px;
        border: 1px solid #E2E8F0;
        vertical-align: middle;
        font-size: 8px;
    }

    table.data tr:nth-child(even) {
        background: #FFF7ED;
    }

    .center {
        text-align: center;
    }

    .money {
        text-align: right;
        white-space: nowrap;
    }

    .total {
        font-weight: bold;
    }

    .empty {
        text-align: center;
        padding: 20px;
        color: #64748B;
    }

    .error {
        background: #FEF2F2;
        color: #B91C1C;
        border: 1px solid #FECACA;
        padding: 12px;
        margin: 15px 0;
    }

    .note {
        margin-top: 13px;
        font-size: 8px;
        color: #64748B;
    }

    .footer {
        margin-top: 22px;
        padding-top: 10px;
        border-top: 1px solid #E2E8F0;
        color: #64748B;
        font-size: 8px;
        text-align: center;
    }
</style>
</head>

<body>

<div class="header">
    <div class="brand">MedanMart</div>
    <div class="subtitle">
        Laporan administrasi pesanan
    </div>
</div>

<div class="report-title">
    <h2>LAPORAN PESANAN</h2>
    <p>Periode: ' . e($periode) . '</p>
    <p>Tanggal dicetak: ' . e($tanggal_cetak) . '</p>
</div>
';

if ($error !== '') {
    $html .= '
        <div class="error">' . e($error) . '</div>
    ';
} else {
    $html .= '
        <table class="summary">
            <tr>
                <td>
                    <div class="summary-label">TOTAL PESANAN</div>
                    <div class="summary-value">'
                        . number_format($total_orders, 0, ',', '.') .
                    '</div>
                </td>

                <td>
                    <div class="summary-label">TOTAL NILAI PESANAN</div>
                    <div class="summary-value">'
                        . rupiah($total_value) .
                    '</div>
                </td>
            </tr>
        </table>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%;">No.</th>
                    <th style="width: 11%;">Kode Pesanan</th>
                    <th style="width: 13%;">Pembeli</th>
                    <th style="width: 12%;">Subtotal</th>
                    <th style="width: 10%;">Ongkir</th>
                    <th style="width: 12%;">Total</th>
                    <th style="width: 10%;">Pembayaran</th>
                    <th style="width: 14%;">Status</th>
                    <th style="width: 14%;">Tanggal</th>
                </tr>
            </thead>
            <tbody>'
                . $tableRows .
            '</tbody>
        </table>

        <div class="note">
            Catatan: pesanan berstatus dibatalkan tidak disertakan.
            Total nilai pesanan mencakup seluruh pesanan yang tidak dibatalkan,
            termasuk pesanan yang belum dibayar.
        </div>
    ';
}

$html .= '
<div class="footer">
    MedanMart &mdash; Laporan Pesanan Admin
</div>

</body>
</html>
';

/* =========================
   GENERATE PDF
========================= */
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

/*
   Attachment false berarti PDF ditampilkan di browser.
   Jika ingin langsung diunduh, ubah false menjadi true.
*/
$filename = 'laporan_pesanan_' . date('Ymd_His') . '.pdf';

$dompdf->stream($filename, [
    'Attachment' => false
]);

exit;