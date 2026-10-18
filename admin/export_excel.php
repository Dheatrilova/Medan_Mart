```php
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| CEK LOGIN ADMIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL FILTER TANGGAL
|--------------------------------------------------------------------------
*/

$start_date = isset($_GET['start_date'])
    ? $_GET['start_date']
    : '';

$end_date = isset($_GET['end_date'])
    ? $_GET['end_date']
    : '';


/*
|--------------------------------------------------------------------------
| QUERY DATA
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        orders.order_code,
        users.name AS buyer_name,
        orders.subtotal,
        orders.shipping_cost,
        orders.total,
        orders.payment_method,
        orders.status,
        orders.created_at
    FROM orders
    JOIN users
        ON orders.user_id = users.id
    WHERE orders.status != 'cancelled'
";


/*
|--------------------------------------------------------------------------
| FILTER TANGGAL
|--------------------------------------------------------------------------
*/

if (!empty($start_date)) {

    $start_date = mysqli_real_escape_string(
        $conn,
        $start_date
    );

    $query .= "
        AND DATE(orders.created_at) >= '$start_date'
    ";
}


if (!empty($end_date)) {

    $end_date = mysqli_real_escape_string(
        $conn,
        $end_date
    );

    $query .= "
        AND DATE(orders.created_at) <= '$end_date'
    ";
}


$query .= "
    ORDER BY orders.created_at DESC
";


$result = mysqli_query($conn, $query);

if (!$result) {
    die(
        "Gagal mengambil data: "
        . mysqli_error($conn)
    );
}


/*
|--------------------------------------------------------------------------
| HEADER FILE EXCEL
|--------------------------------------------------------------------------
*/

header(
    "Content-Type: application/vnd.ms-excel"
);

header(
    "Content-Disposition: attachment; filename=laporan_penjualan_medanmart.xls"
);

header(
    "Pragma: no-cache"
);

header(
    "Expires: 0"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <style>

        table {
            border-collapse: collapse;
        }

        th {
            background: #198754;
            color: white;
            font-weight: bold;
            border: 1px solid #000;
            padding: 8px;
        }

        td {
            border: 1px solid #000;
            padding: 8px;
        }

    </style>

</head>

<body>


<h2>
    Laporan Penjualan MedanMart
</h2>


<?php if (!empty($start_date) || !empty($end_date)): ?>

    <p>

        Periode:

        <?= !empty($start_date)
            ? htmlspecialchars($start_date)
            : 'Semua'; ?>

        sampai

        <?= !empty($end_date)
            ? htmlspecialchars($end_date)
            : 'Semua'; ?>

    </p>

<?php endif; ?>


<table>

    <thead>

        <tr>

            <th>
                No
            </th>

            <th>
                Kode Pesanan
            </th>

            <th>
                Pembeli
            </th>

            <th>
                Subtotal
            </th>

            <th>
                Ongkir
            </th>

            <th>
                Total
            </th>

            <th>
                Pembayaran
            </th>

            <th>
                Status
            </th>

            <th>
                Tanggal
            </th>

        </tr>

    </thead>


    <tbody>


        <?php

        $no = 1;
        $grand_total = 0;

        while ($order = mysqli_fetch_assoc($result)):

            $grand_total += $order['total'];

        ?>


            <tr>

                <td>
                    <?= $no++; ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $order['order_code']
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $order['buyer_name']
                    ); ?>
                </td>

                <td>
                    <?= $order['subtotal']; ?>
                </td>

                <td>
                    <?= $order['shipping_cost']; ?>
                </td>

                <td>
                    <?= $order['total']; ?>
                </td>

                <td>
                    <?= strtoupper(
                        htmlspecialchars(
                            $order['payment_method']
                        )
                    ); ?>
                </td>

                <td>
                    <?= ucfirst(
                        htmlspecialchars(
                            $order['status']
                        )
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $order['created_at']
                    ); ?>
                </td>

            </tr>


        <?php endwhile; ?>


        <tr>

            <td
                colspan="5"
                style="font-weight:bold;"
            >
                TOTAL PENJUALAN
            </td>

            <td
                style="font-weight:bold;"
            >
                <?= $grand_total; ?>
            </td>

            <td colspan="3"></td>

        </tr>


    </tbody>

</table>


</body>

</html>
```