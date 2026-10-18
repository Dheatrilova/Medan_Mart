
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Pastikan hanya seller yang sudah login dapat mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit;
}

$seller_id = (int) $_SESSION['user_id'];
$pesan = "";
$status_pesan = "";

// Fungsi untuk mengambil data toko milik seller
function ambilToko($conn, $seller_id)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM stores WHERE seller_id = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $seller_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $store = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $store ?: null;
}

// Ambil data toko saat halaman dibuka
$store = ambilToko($conn, $seller_id);

// Proses penyimpanan informasi toko
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {

    $store_name = trim($_POST['store_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($store_name === '' || $description === '' || $address === '') {
        $pesan = "Semua kolom wajib diisi.";
        $status_pesan = "error";

        // Pertahankan data yang sudah diketik jika validasi gagal
        $store = [
            'store_name' => $store_name,
            'description' => $description,
            'address' => $address
        ];
    } else {

        if ($store) {
            // Perbarui toko yang sudah ada
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE stores
                 SET store_name = ?, description = ?, address = ?
                 WHERE seller_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $store_name,
                $description,
                $address,
                $seller_id
            );

        } else {
            // Buat toko baru
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO stores
                 (seller_id, store_name, description, address)
                 VALUES (?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "isss",
                $seller_id,
                $store_name,
                $description,
                $address
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Informasi toko berhasil disimpan!";
            $status_pesan = "success";

            // Ambil kembali data terbaru dari database
            $store = ambilToko($conn, $seller_id);
        } else {
            $pesan = "Toko gagal disimpan. Silakan coba kembali.";
            $status_pesan = "error";
        }

        mysqli_stmt_close($stmt);
    }
}

// Escape output sebelum ditampilkan ke halaman
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Toko - MedanMart</title>

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
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .navbar-inner {
            width: min(1120px, 92%);
            min-height: 76px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            color: var(--text);
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            background: var(--cream);
            border-radius: 13px;
            font-size: 23px;
        }

        .brand-name span {
            color: var(--primary-dark);
        }

        .back-nav {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 700;
            padding: 10px 14px;
            border-radius: 9px;
            transition: background 0.2s ease;
        }

        .back-nav:hover {
            background: var(--cream);
        }

        /* MAIN */

        .container {
            width: min(820px, 92%);
            margin: 38px auto;
        }

        .page-heading {
            margin-bottom: 23px;
        }

        .page-label {
            display: inline-block;
            padding: 5px 11px;
            margin-bottom: 10px;
            border-radius: 30px;
            background: var(--cream);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .page-heading h1 {
            margin-bottom: 8px;
            font-size: clamp(25px, 4vw, 32px);
            line-height: 1.35;
        }

        .page-heading p {
            color: var(--muted);
            font-size: 15px;
        }

        /* FORM CARD */

        .card {
            padding: 32px;
            border: 1px solid var(--border);
            border-radius: 19px;
            background: var(--white);
            box-shadow: 0 6px 24px rgba(15, 23, 42, 0.045);
        }

        .card-heading {
            padding-bottom: 21px;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border);
        }

        .card-heading h2 {
            margin-bottom: 5px;
            font-size: 19px;
        }

        .card-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        /* NOTIFICATION */

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            margin-bottom: 23px;
            border: 1px solid transparent;
            border-radius: 11px;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .alert.success {
            color: #166534;
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .alert.error {
            color: #b91c1c;
            background: #fef2f2;
            border-color: #fecaca;
        }

        .alert-icon {
            flex-shrink: 0;
            font-weight: 800;
        }

        /* FORM FIELDS */

        .form-group {
            margin-bottom: 23px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-size: 14px;
            font-weight: 700;
        }

        .optional-hint {
            color: var(--muted);
            font-weight: 400;
        }

        input,
        textarea {
            display: block;
            width: 100%;
            max-width: 100%;
            padding: 13px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            color: var(--text);
            font-family: inherit;
            font-size: 15px;
            line-height: 1.6;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input {
            min-height: 49px;
        }

        textarea {
            min-height: 125px;
            resize: vertical;
        }

        input::placeholder,
        textarea::placeholder {
            color: #9ca3af;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.13);
        }

        /* BUTTONS */

        .form-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 4px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 47px;
            padding: 12px 20px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .btn-primary {
            color: var(--white);
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            color: var(--text);
            background: var(--white);
            border-color: var(--border);
        }

        .btn-secondary:hover {
            background: var(--background);
        }

        /* FOOTER */

        .footer {
            padding: 22px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        /* RESPONSIVE */

        @media (max-width: 600px) {
            .navbar-inner {
                min-height: 68px;
            }

            .brand {
                gap: 8px;
                font-size: 20px;
            }

            .brand-icon {
                width: 39px;
                height: 39px;
                font-size: 21px;
            }

            .back-nav {
                padding: 9px 8px;
                font-size: 13px;
            }

            .container {
                margin: 26px auto;
            }

            .page-heading {
                margin-bottom: 19px;
            }

            .page-heading p {
                font-size: 14px;
            }

            .card {
                padding: 23px 19px;
                border-radius: 15px;
            }

            .card-heading {
                padding-bottom: 18px;
                margin-bottom: 21px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 360px) {
            .brand {
                font-size: 18px;
            }

            .brand-icon {
                width: 35px;
                height: 35px;
                font-size: 19px;
            }

            .back-nav {
                font-size: 12px;
            }

            .card {
                padding: 20px 15px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="navbar-inner">

            <a href="dashboard.php" class="brand" aria-label="Dashboard MedanMart">
                <span class="brand-icon">🛍️</span>
                <span class="brand-name">Medan<span>Mart</span></span>
            </a>

            <a href="dashboard.php" class="back-nav">
                <span aria-hidden="true">←</span>
                Dashboard
            </a>

        </div>
    </header>

    <main class="container">

        <!-- PAGE HEADING -->
        <section class="page-heading">
            <span class="page-label">PENGATURAN TOKO</span>
            <h1>🏪 Kelola Toko</h1>
            <p>
                Lengkapi informasi toko agar pembeli mengenal usaha
                dan mengetahui alamat tokomu.
            </p>
        </section>

        <!-- FORM CARD -->
        <section class="card">

            <div class="card-heading">
                <h2>Informasi Toko</h2>
                <p>
                    Isi informasi berikut dengan data toko yang benar.
                </p>
            </div>

            <?php if ($pesan !== ''): ?>
                <div
                    class="alert <?= e($status_pesan); ?>"
                    role="status"
                    aria-live="polite"
                >
                    <span class="alert-icon" aria-hidden="true">
                        <?= $status_pesan === 'success' ? '✓' : '!'; ?>
                    </span>

                    <span><?= e($pesan); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="store_name">Nama Toko</label>

                    <input
                        type="text"
                        id="store_name"
                        name="store_name"
                        placeholder="Contoh: Dhea Snack Medan"
                        value="<?= e($store['store_name'] ?? ''); ?>"
                        maxlength="255"
                        autocomplete="organization"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi Toko</label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Ceritakan tentang toko, produk, atau keunggulan UMKM kamu..."
                        required
                    ><?= e($store['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="address">Alamat Toko</label>

                    <textarea
                        id="address"
                        name="address"
                        placeholder="Masukkan alamat lengkap toko..."
                        autocomplete="street-address"
                        required
                    ><?= e($store['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="simpan" class="btn btn-primary">
                        <span aria-hidden="true">💾</span>
                        Simpan Toko
                    </button>

                    <a href="dashboard.php" class="btn btn-secondary">
                        Batal
                    </a>
                </div>

            </form>

        </section>

    </main>

    <footer class="footer">
        &copy; <?= date('Y'); ?> MedanMart. Belanja mudah, belanja nyaman.
    </footer>

</body>
</html>
