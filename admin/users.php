
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
   FUNGSI ESCAPE
========================= */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   PENCARIAN DAN FILTER
========================= */
$keyword = trim($_GET['keyword'] ?? '');
$role = trim($_GET['role'] ?? '');

$allowed_roles = ['buyer', 'seller', 'admin'];

$sql = "SELECT id, name, email, phone, role FROM users WHERE 1=1";
$params = [];
$types = '';

if ($keyword !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search = '%' . $keyword . '%';

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

if ($role !== '' && in_array($role, $allowed_roles, true)) {
    $sql .= " AND role = ?";
    $params[] = $role;
    $types .= 's';
}

$sql .= " ORDER BY id DESC";

$stmt = mysqli_prepare($conn, $sql);
$users = [];

if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}

/* =========================
   FUNGSI STATISTIK
========================= */
function countUsers($conn, $role = null)
{
    if ($role === null) {
        $sql = "SELECT COUNT(*) AS total FROM users";
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            return 0;
        }

        $row = mysqli_fetch_assoc($result);
        return (int) ($row['total'] ?? 0);
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM users WHERE role = ?"
    );

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $role);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    mysqli_stmt_close($stmt);

    return (int) ($row['total'] ?? 0);
}

$total_users = countUsers($conn);
$total_buyers = countUsers($conn, 'buyer');
$total_sellers = countUsers($conn, 'seller');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Pengguna | MedanMart</title>

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --cream: #fff7ed;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --background: #f8fafc;
            --white: #ffffff;
            --green: #15803d;
            --blue: #2563eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.5;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           NAVBAR
        ========================= */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        }

        .navbar-inner {
            max-width: 1400px;
            min-height: 76px;
            margin: auto;
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
            font-size: 23px;
            font-weight: 800;
            white-space: nowrap;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 13px;
            background: var(--cream);
            font-size: 23px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 13px;
            border-radius: 10px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-dark);
            background: var(--cream);
        }

        .nav-link.logout {
            color: #dc2626;
        }

        .nav-link.logout:hover {
            background: #fef2f2;
        }

        /* =========================
           KONTEN UTAMA
        ========================= */
        .main {
            width: 100%;
            max-width: 1400px;
            margin: auto;
            padding: 32px;
        }

        .welcome-banner {
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            margin-bottom: 28px;
            padding: 32px;
            border-radius: 22px;
            background: linear-gradient(
                120deg,
                #f97316 0%,
                #fb923c 55%,
                #fdba74 100%
            );
            color: white;
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.12);
        }

        .welcome-banner::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: 30px;
            top: -110px;
            border: 35px solid rgba(255, 255, 255, 0.12);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-label {
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: 0.9;
        }

        .welcome-banner h1 {
            margin: 0 0 9px;
            font-size: clamp(25px, 3vw, 32px);
            line-height: 1.25;
        }

        .welcome-banner p {
            margin: 0;
            max-width: 600px;
            font-size: 14px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.93);
        }

        .admin-chip {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.17);
            backdrop-filter: blur(8px);
            font-size: 13px;
            white-space: nowrap;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: white;
            color: var(--primary-dark);
            font-size: 16px;
            font-weight: 800;
        }

        /* =========================
           STATISTIK
        ========================= */
        .section-heading {
            margin: 0 0 16px;
            font-size: 19px;
            font-weight: 750;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 17px;
            min-width: 0;
            padding: 23px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--white);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.025);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 9px 24px rgba(15, 23, 42, 0.06);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 15px;
            font-size: 24px;
        }

        .icon-orange {
            background: #fff0e3;
        }

        .icon-blue {
            background: #eff6ff;
        }

        .icon-green {
            background: #ecfdf5;
        }

        .stat-label {
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 13px;
        }

        .stat-number {
            font-size: 27px;
            line-height: 1.2;
            font-weight: 800;
            color: var(--text);
        }

        /* =========================
           PANEL DAFTAR PENGGUNA
        ========================= */
        .panel {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--white);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.025);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 23px 25px;
            border-bottom: 1px solid var(--border);
        }

        .panel-header h2 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .panel-description {
            color: var(--muted);
            font-size: 13px;
        }

        .result-count {
            flex-shrink: 0;
            padding: 7px 11px;
            border-radius: 20px;
            background: var(--cream);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
        }

        /* =========================
           FILTER PENCARIAN
        ========================= */
        .filters {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px 25px;
            border-bottom: 1px solid var(--border);
            background: #fcfcfd;
        }

        .search-input,
        .role-select {
            min-height: 44px;
            padding: 0 14px;
            border: 1px solid #dfe3e8;
            border-radius: 11px;
            outline: none;
            background: white;
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .search-input {
            flex: 1;
            min-width: 220px;
        }

        .role-select {
            min-width: 150px;
            cursor: pointer;
        }

        .search-input:focus,
        .role-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 44px;
            padding: 0 17px;
            border: 0;
            border-radius: 11px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-light {
            border: 1px solid var(--border);
            background: white;
            color: #4b5563;
        }

        .btn-light:hover {
            background: #f3f4f6;
        }

        /* =========================
           TABEL
        ========================= */
        .table-wrap {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th,
        td {
            padding: 16px 23px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            font-size: 13px;
        }

        th {
            background: #f9fafb;
            color: var(--muted);
            font-size: 11px;
            font-weight: 750;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        td {
            color: #4b5563;
        }

        tbody tr {
            transition: background 0.15s;
        }

        tbody tr:hover {
            background: #fffaf5;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .number-cell {
            color: #9ca3af;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--cream);
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 800;
        }

        .user-name {
            color: var(--text);
            font-weight: 700;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 750;
        }

        .role-badge.buyer {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .role-badge.seller {
            background: #ecfdf5;
            color: #15803d;
        }

        .role-badge.admin {
            background: #fff0e3;
            color: #c2410c;
        }

        .role-badge.unknown {
            background: #f3f4f6;
            color: #4b5563;
        }

        .empty {
            padding: 48px 20px;
            text-align: center;
            color: var(--muted);
            white-space: normal;
        }

        .empty-icon {
            display: block;
            margin-bottom: 10px;
            font-size: 32px;
        }

        .empty-title {
            margin-bottom: 5px;
            color: var(--text);
            font-size: 15px;
            font-weight: 750;
        }

        .footer-note {
            margin-top: 20px;
            color: #9ca3af;
            text-align: center;
            font-size: 12px;
        }

        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 1000px) {
            .navbar-inner {
                padding: 14px 20px;
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .nav-menu {
                width: 100%;
            }

            .nav-link {
                padding: 9px 11px;
            }

            .main {
                padding: 24px 20px;
            }

            .stats {
                gap: 13px;
            }

            .stat-card {
                padding: 18px;
                gap: 12px;
            }

            .stat-icon {
                width: 44px;
                height: 44px;
                font-size: 21px;
            }
        }

        @media (max-width: 700px) {
            .navbar-inner {
                padding: 14px 16px;
            }

            .nav-menu {
                gap: 4px;
            }

            .nav-link {
                padding: 8px 9px;
                font-size: 12px;
            }

            .main {
                padding: 20px 14px;
            }

            .welcome-banner {
                align-items: flex-start;
                flex-direction: column;
                padding: 25px 21px;
                border-radius: 17px;
            }

            .welcome-banner::after {
                right: -100px;
            }

            .admin-chip {
                padding: 9px 12px;
            }

            .stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 17px;
            }

            .stat-number {
                font-size: 24px;
            }

            .panel {
                border-radius: 15px;
            }

            .panel-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 19px;
            }

            .filters {
                align-items: stretch;
                flex-direction: column;
                padding: 17px;
            }

            .search-input,
            .role-select,
            .filters .btn {
                width: 100%;
                min-width: 0;
            }

            th,
            td {
                padding: 14px 16px;
            }
        }
    </style>
</head>

<body>

<!-- =========================
     NAVBAR
========================= -->
<nav class="navbar">
    <div class="navbar-inner">

        <a href="dashboard.php" class="brand">
            <span class="brand-icon">🛍️</span>
            <span>MedanMart</span>
        </a>

        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link">
                🏠 Dashboard
            </a>

            <a href="users.php" class="nav-link active">
                👥 Pengguna
            </a>

            <a href="orders.php" class="nav-link">
                📦 Pesanan
            </a>

            <a href="reports.php" class="nav-link">
                📊 Laporan
            </a>

            <a
                href="../logout.php"
                class="nav-link logout"
                onclick="return confirm('Apakah kamu yakin ingin logout?')"
            >
                ↪ Logout
            </a>
        </div>

    </div>
</nav>

<!-- =========================
     KONTEN UTAMA
========================= -->
<main class="main">

    <!-- Banner -->
    <section class="welcome-banner">
        <div class="welcome-content">
            <div class="welcome-label">Admin Panel</div>

            <h1>Kelola Pengguna 👥</h1>

            <p>
                Pantau data pengguna MedanMart, cari akun yang terdaftar,
                dan lihat jumlah buyer serta seller dalam satu halaman.
            </p>
        </div>

        <div class="admin-chip">
            <div class="admin-avatar">A</div>

            <div>
                <div style="font-size:11px; opacity:.85;">
                    Login sebagai
                </div>

                <strong><?= e($_SESSION['name'] ?? 'Administrator') ?></strong>
            </div>
        </div>
    </section>

    <!-- Statistik -->
    <h2 class="section-heading">Ringkasan Pengguna</h2>

    <section class="stats">

        <div class="stat-card">
            <div class="stat-icon icon-orange">👥</div>

            <div>
                <div class="stat-label">Total Pengguna</div>
                <div class="stat-number">
                    <?= number_format($total_users, 0, ',', '.') ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-blue">🛒</div>

            <div>
                <div class="stat-label">Total Buyer</div>
                <div class="stat-number">
                    <?= number_format($total_buyers, 0, ',', '.') ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-green">🏪</div>

            <div>
                <div class="stat-label">Total Seller</div>
                <div class="stat-number">
                    <?= number_format($total_sellers, 0, ',', '.') ?>
                </div>
            </div>
        </div>

    </section>

    <!-- Daftar pengguna -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Daftar Pengguna</h2>
                <div class="panel-description">
                    Data akun yang terdaftar di MedanMart.
                </div>
            </div>

            <div class="result-count">
                <?= count($users) ?> data ditemukan
            </div>
        </div>

        <!-- Form pencarian -->
        <form class="filters" method="GET" action="users.php">

            <input
                class="search-input"
                type="text"
                name="keyword"
                placeholder="Cari nama, email, atau nomor telepon..."
                value="<?= e($keyword) ?>"
            >

            <select class="role-select" name="role">
                <option value="">Semua Role</option>

                <option
                    value="buyer"
                    <?= $role === 'buyer' ? 'selected' : '' ?>
                >
                    Buyer
                </option>

                <option
                    value="seller"
                    <?= $role === 'seller' ? 'selected' : '' ?>
                >
                    Seller
                </option>

                <option
                    value="admin"
                    <?= $role === 'admin' ? 'selected' : '' ?>
                >
                    Admin
                </option>
            </select>

            <button class="btn btn-primary" type="submit">
                🔍 Cari Pengguna
            </button>

            <a href="users.php" class="btn btn-light">
                ↺ Reset
            </a>

        </form>

        <!-- Tabel pengguna -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th>Nomor Telepon</th>
                        <th>Role</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($users) > 0): ?>

                        <?php foreach ($users as $index => $user): ?>
                            <?php
                                $user_role = strtolower($user['role'] ?? '');
                                $role_class = in_array(
                                    $user_role,
                                    $allowed_roles,
                                    true
                                ) ? $user_role : 'unknown';

                                $name = trim($user['name'] ?? '');
                                $initial = $name !== ''
                                    ? strtoupper(substr($name, 0, 1))
                                    : '?';
                            ?>

                            <tr>
                                <td class="number-cell">
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?= e($initial) ?>
                                        </div>

                                        <span class="user-name">
                                            <?= e($name !== '' ? $name : '-') ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <?= e($user['email'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= e($user['phone'] ?? '-') ?>
                                </td>

                                <td>
                                    <span class="role-badge <?= e($role_class) ?>">
                                        <?= e(ucfirst($user_role ?: 'Tidak diketahui')) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty">
                                <span class="empty-icon">🔎</span>

                                <div class="empty-title">
                                    Pengguna tidak ditemukan
                                </div>

                                <div>
                                    Coba gunakan kata kunci atau filter role
                                    yang berbeda.
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

    <div class="footer-note">
        © <?= date('Y') ?> MedanMart · Admin Panel
    </div>

</main>

</body>
</html>
