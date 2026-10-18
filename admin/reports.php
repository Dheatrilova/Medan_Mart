
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
   FUNGSI BANTU
========================= */
function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
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

function statusClass($status)
{
    $classes = [
        'pending'   => 'pending',
        'paid'      => 'paid',
        'shipped'   => 'shipped',
        'completed' => 'completed',
        'cancelled' => 'cancelled'
    ];

    return $classes[$status] ?? 'default';
}

/* =========================
   FILTER TANGGAL
========================= */
$start_date = trim($_GET['start_date'] ?? '');
$end_date   = trim($_GET['end_date'] ?? '');
$date_error = '';

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
    $date_error = 'Format tanggal tidak valid.';
} elseif (
    $start_date !== '' &&
    $end_date !== '' &&
    $start_date > $end_date
) {
    $date_error = 'Tanggal awal tidak boleh melebihi tanggal akhir.';
}

/* =========================
   AMBIL DATA LAPORAN
========================= */
$orders = [];
$total_orders = 0;
$total_sales = 0;

if ($date_error === '') {
    $query = "
        SELECT
            orders.recipient_name AS buyer_name,
            orders.subtotal,
            orders.shipping_cost,
            orders.total,
            orders.payment_method,
            orders.status,
            orders.created_at           
        FROM orders
            WHERE orders.status != 'cancelled'";

    /*
       Tanggal sudah divalidasi dengan format YYYY-MM-DD,
       sehingga nilai tanggal yang digunakan di query
       hanya boleh memiliki format tersebut.
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

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
            $total_sales += (float)$row['total'];
        }

        $total_orders = count($orders);
    }
}

/* =========================
   LINK EXPORT
========================= */
$exportParams = http_build_query([
    'start_date' => $start_date,
    'end_date'   => $end_date
]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Laporan Pesanan | MedanMart Admin</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #F8FAFC;
            color: #1E293B;
            line-height: 1.6;
        }

        /* NAVBAR */
        .navbar {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            padding: 17px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .brand {
            color: #F97316;
            font-size: 23px;
            font-weight: 800;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav-menu a {
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 9px 13px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: #FFF7ED;
            color: #EA580C;
        }

        .nav-menu a.logout {
            color: #DC2626;
        }

        /* KONTEN */
        .container {
            width: 90%;
            max-width: 1400px;
            margin: 35px auto;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #0F172A;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #64748B;
            font-size: 14px;
        }

        /* RINGKASAN */
        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 23px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.03);
        }

        .stat-label {
            font-size: 14px;
            color: #64748B;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 27px;
            font-weight: 800;
            color: #0F172A;
            overflow-wrap: anywhere;
        }

        .stat-card.orange {
            border-left: 4px solid #F97316;
        }

        .stat-card.green {
            border-left: 4px solid #16A34A;
        }

        .stat-note {
            color: #94A3B8;
            font-size: 12px;
            margin-top: 7px;
        }

        /* PANEL */
        .panel {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.03);
        }

        .panel-heading {
            margin-bottom: 20px;
        }

        .panel-heading h2 {
            font-size: 19px;
            color: #0F172A;
            margin-bottom: 4px;
        }

        .panel-heading p {
            font-size: 13px;
            color: #64748B;
        }

        /* FILTER */
        .filter-form {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            flex: 1;
            min-width: 180px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 700;
            color: #475569;
        }

        .form-group input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            background: #FFFFFF;
            color: #334155;
        }

        .form-group input:focus {
            outline: none;
            border-color: #F97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        .filter-actions {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 11px 15px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s;
            white-space: nowrap;
        }

        .btn-orange {
            background: #F97316;
            color: #FFFFFF;
        }

        .btn-orange:hover {
            background: #EA580C;
        }

        .btn-light {
            background: #F1F5F9;
            color: #475569;
        }

        .btn-light:hover {
            background: #E2E8F0;
        }

        .btn-green {
            background: #16A34A;
            color: #FFFFFF;
        }

        .btn-green:hover {
            background: #15803D;
        }

        .btn-red {
            background: #DC2626;
            color: #FFFFFF;
        }

        .btn-red:hover {
            background: #B91C1C;
        }

        .error-message {
            background: #FEF2F2;
            color: #B91C1C;
            border: 1px solid #FECACA;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 14px;
            margin-bottom: 18px;
        }

        /* TABEL */
        .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .table-toolbar h2 {
            font-size: 19px;
            color: #0F172A;
        }

        .table-toolbar p {
            font-size: 13px;
            color: #64748B;
            margin-top: 3px;
        }

        .export-actions {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
        }

        table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
            background: #FFFFFF;
        }

        thead {
            background: #FFF7ED;
        }

        th {
            color: #9A3412;
            font-size: 12px;
            text-align: left;
            padding: 15px 13px;
            white-space: nowrap;
            font-weight: 800;
            border-bottom: 1px solid #FED7AA;
        }

        td {
            padding: 15px 13px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13px;
            color: #475569;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #FFFAF5;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .order-code {
            font-weight: 700;
            color: #EA580C;
            white-space: nowrap;
        }

        .buyer-name {
            color: #0F172A;
            font-weight: 600;
        }

        .money {
            white-space: nowrap;
            font-weight: 600;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status.pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .status.paid {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .status.shipped {
            background: #EDE9FE;
            color: #6D28D9;
        }

        .status.completed {
            background: #DCFCE7;
            color: #166534;
        }

        .status.cancelled {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status.default {
            background: #F1F5F9;
            color: #475569;
        }

        .empty-state {
            text-align: center;
            padding: 45px 20px;
            color: #64748B;
        }

        .empty-state strong {
            display: block;
            font-size: 16px;
            color: #334155;
            margin-bottom: 5px;
        }

        .empty-state p {
            font-size: 13px;
        }

        /* FOOTER */
        .footer {
            text-align: center;
            padding: 25px 15px;
            color: #94A3B8;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 5%;
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-menu {
                width: 100%;
            }

            .nav-menu a {
                padding: 8px 10px;
                font-size: 12px;
            }

            .container {
                width: 92%;
                margin: 25px auto;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .stats {
                grid-template-columns: 1fr;
                gap: 13px;
            }

            .stat-card {
                padding: 19px;
            }

            .stat-value {
                font-size: 23px;
            }

            .panel {
                padding: 18px;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group {
                min-width: 100%;
            }

            .filter-actions,
            .export-actions {
                width: 100%;
            }

            .filter-actions .btn,
            .export-actions .btn {
                flex: 1;
            }

            .table-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media print {
            .navbar,
            .filter-panel,
            .export-actions,
            .footer {
                display: none !important;
            }

            body {
                background: #FFFFFF;
            }

            .container {
                width: 100%;
                margin: 0;
            }

            .panel,
            .stat-card {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="dashboard.php" class="brand">MedanMart Admin</a>

    <div class="nav-menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="orders.php">Pesanan</a>
        <a href="user.php">Pengguna</a>
        <a href="report.php" class="active">Laporan</a>
        <a href="../logout.php" class="logout">Logout</a>
    </div>
</nav>

<main class="container">

    <!-- HEADER -->
    <div class="page-header">
        <h1>Laporan Pesanan</h1>
        <p>
            Lihat ringkasan dan riwayat pesanan MedanMart berdasarkan
            periode tanggal yang dipilih.
        </p>
    </div>

    <!-- FILTER TANGGAL -->
    <section class="panel filter-panel">
        <div class="panel-heading">
            <h2>Filter Laporan</h2>
            <p>Pilih tanggal awal dan akhir untuk melihat laporan pada periode tertentu.</p>
        </div>

        <?php if ($date_error !== ''): ?>
            <div class="error-message">
                <?= e($date_error); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="report.php" class="filter-form">

            <div class="form-group">
                <label for="start_date">Tanggal Awal</label>
                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    value="<?= e($start_date); ?>"
                >
            </div>

            <div class="form-group">
                <label for="end_date">Tanggal Akhir</label>
                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    value="<?= e($end_date); ?>"
                >
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-orange">
                    Terapkan Filter
                </button>

                <a href="report.php" class="btn btn-light">
                    Reset
                </a>
            </div>

        </form>
    </section>

    <!-- RINGKASAN -->
    <section class="stats">

        <div class="stat-card orange">
            <div class="stat-label">Total Pesanan</div>
            <div class="stat-value">
                <?= number_format($total_orders, 0, ',', '.'); ?>
            </div>
            <div class="stat-note">
                Pesanan yang tidak berstatus dibatalkan
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-label">Total Nilai Pesanan</div>
            <div class="stat-value">
                <?= rupiah($total_sales); ?>
            </div>
            <div class="stat-note">
                Akumulasi nilai pesanan pada hasil filter, bukan khusus pembayaran yang sudah diterima
            </div>
        </div>

    </section>

    <!-- TABEL LAPORAN -->
    <section class="panel">

        <div class="table-toolbar">
            <div>
                <h2>Detail Pesanan</h2>
                <p>
                    Menampilkan <?= number_format($total_orders, 0, ',', '.'); ?>
                    data pesanan
                </p>
            </div>

            <div class="export-actions">
                <a
                    href="export_excel.php?<?= e($exportParams); ?>"
                    class="btn btn-green"
                >
                    Export Excel
                </a>

                <a
                    href="export_pdf.php?<?= e($exportParams); ?>"
                    class="btn btn-red"
                    target="_blank"
                    rel="noopener"
                >
                    Export PDF
                </a>

                <button
                    type="button"
                    class="btn btn-light"
                    onclick="window.print()"
                >
                    Cetak
                </button>
            </div>
        </div>

        <?php if ($date_error !== ''): ?>

            <div class="empty-state">
                <strong>Filter tanggal belum valid</strong>
                <p>Periksa kembali tanggal awal dan tanggal akhir.</p>
            </div>

        <?php elseif (empty($orders)): ?>

            <div class="empty-state">
                <strong>Belum ada data pesanan</strong>
                <p>
                    Tidak ditemukan pesanan yang sesuai dengan periode tanggal yang dipilih.
                </p>
            </div>

        <?php else: ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Pesanan</th>
                            <th>Nama Pembeli</th>
                            <th>Subtotal</th>
                            <th>Ongkir</th>
                            <th>Total</th>
                            <th>Metode Pembayaran</th>
                            <th>Status</th>
                            <th>Tanggal Pesanan</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($orders as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>

                                <td class="order-code">
                                    <?= e($row['order_code']); ?>
                                </td>

                                <td class="buyer-name">
                                    <?= e($row['buyer_name']); ?>
                                </td>

                                <td class="money">
                                    <?= rupiah($row['subtotal']); ?>
                                </td>

                                <td class="money">
                                    <?= rupiah($row['shipping_cost']); ?>
                                </td>

                                <td class="money">
                                    <?= rupiah($row['total']); ?>
                                </td>

                                <td>
                                    <?= e($row['payment_method']); ?>
                                </td>

                                <td>
                                    <span class="status <?= e(statusClass($row['status'])); ?>">
                                        <?= e(statusLabel($row['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    $timestamp = strtotime($row['created_at']);
                                    echo $timestamp !== false
                                        ? date('d-m-Y H:i', $timestamp)
                                        : e($row['created_at']);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

</main>

<footer class="footer">
    &copy; <?= date('Y'); ?> MedanMart. Admin Panel.
</footer>

</body>
</html>
