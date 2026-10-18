
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/* =========================================
   CEK LOGIN SELLER
========================================= */

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'seller'
) {
    header("Location: ../login.php");
    exit;
}

$seller_id = (int) $_SESSION['user_id'];

$message = "";
$message_type = "";

/* =========================================
   AMBIL TOKO SELLER
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, store_name
     FROM stores
     WHERE seller_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);

$store_result = mysqli_stmt_get_result($stmt);
$store = mysqli_fetch_assoc($store_result);

mysqli_stmt_close($stmt);

if (!$store) {
    header("Location: store.php");
    exit;
}

$store_id = (int) $store['id'];

/* =========================================
   NOTIFIKASI DARI HALAMAN LAIN
========================================= */

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $message = "Produk berhasil diperbarui.";
    $message_type = "success";
} elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $message = "Produk berhasil dihapus.";
    $message_type = "success";
} elseif (isset($_GET['error'])) {
    $errors = [
        'product_not_found' => "Produk tidak ditemukan atau bukan milik toko kamu.",
        'delete_failed' => "Produk gagal dihapus. Silakan coba lagi."
    ];

    $error_key = (string) $_GET['error'];

    if (isset($errors[$error_key])) {
        $message = $errors[$error_key];
        $message_type = "error";
    }
}

/* =========================================
   TAMBAH PRODUK
========================================= */

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? "");
    $description = trim($_POST['description'] ?? "");
    $price = $_POST['price'] ?? "";
    $stock = $_POST['stock'] ?? "";
    $category_id = $_POST['category_id'] ?? "";

    $image_name = "";
    $new_image_path = null;
    $errors = [];

    // Validasi input teks
    if ($name === "") {
        $errors[] = "Nama produk wajib diisi.";
    }

    if (strlen($name) > 255) {
        $errors[] = "Nama produk maksimal 255 karakter.";
    }

    if ($description === "") {
        $errors[] = "Deskripsi produk wajib diisi.";
    }

    if (!is_numeric($price) || (float) $price < 0) {
        $errors[] = "Harga harus berupa angka minimal 0.";
    }

    if (
        filter_var($stock, FILTER_VALIDATE_INT) === false ||
        (int) $stock < 0
    ) {
        $errors[] = "Stok harus berupa bilangan bulat minimal 0.";
    }

    if (
        filter_var($category_id, FILTER_VALIDATE_INT) === false ||
        (int) $category_id <= 0
    ) {
        $errors[] = "Silakan pilih kategori produk.";
    } else {
        $category_id = (int) $category_id;

        $check_category = mysqli_prepare(
            $conn,
            "SELECT id FROM categories WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $check_category,
            "i",
            $category_id
        );

        mysqli_stmt_execute($check_category);
        $category_result = mysqli_stmt_get_result($check_category);

        if (mysqli_num_rows($category_result) === 0) {
            $errors[] = "Kategori yang dipilih tidak ditemukan.";
        }

        mysqli_stmt_close($check_category);
    }

    // Siapkan folder foto
    $upload_dir = __DIR__ . "/../uploads/products/";

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
            $errors[] = "Folder penyimpanan foto gagal dibuat.";
        }
    }

    // Foto produk wajib diunggah saat menambahkan produk
    if (
        !isset($_FILES['image']) ||
        $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE
    ) {
        $errors[] = "Silakan pilih foto produk.";
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload foto gagal. Silakan coba lagi.";
    } else {
        $file = $_FILES['image'];

        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "Ukuran foto maksimal 5 MB.";
        } else {
            $allowed = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'webp' => 'image/webp'
            ];

            $extension = strtolower(
                pathinfo($file['name'], PATHINFO_EXTENSION)
            );

            $image_info = @getimagesize($file['tmp_name']);

            if (
                !isset($allowed[$extension]) ||
                $image_info === false ||
                !isset($image_info['mime']) ||
                $image_info['mime'] !== $allowed[$extension]
            ) {
                $errors[] = "Foto harus berupa JPG, JPEG, PNG, atau WEBP yang valid.";
            } elseif (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                $errors[] = "Folder foto tidak dapat ditulisi.";
            } else {
                $image_name = bin2hex(random_bytes(16)) . "." . $extension;
                $new_image_path = $upload_dir . $image_name;

                if (!move_uploaded_file($file['tmp_name'], $new_image_path)) {
                    $errors[] = "Foto gagal disimpan ke folder upload.";
                    $new_image_path = null;
                    $image_name = "";
                }
            }
        }
    }

    // Simpan produk ke database
    if (empty($errors)) {
        $price_value = (float) $price;
        $stock_value = (int) $stock;

        $insert = mysqli_prepare(
            $conn,
            "INSERT INTO products
                (store_id, category_id, name, description, price, stock, image)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $insert,
            "iissdis",
            $store_id,
            $category_id,
            $name,
            $description,
            $price_value,
            $stock_value,
            $image_name
        );

        $success = mysqli_stmt_execute($insert);
        mysqli_stmt_close($insert);

        if ($success) {
            header("Location: products.php?added=1");
            exit;
        }

        // Bersihkan foto jika penyimpanan database gagal
        if ($new_image_path !== null && is_file($new_image_path)) {
            unlink($new_image_path);
        }

        $errors[] = "Produk gagal disimpan. Silakan coba lagi.";
    }

    if (!empty($errors)) {
        $message = implode(" ", $errors);
        $message_type = "error";
    }
}

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $message = "Produk baru berhasil ditambahkan.";
    $message_type = "success";
}

/* =========================================
   AMBIL KATEGORI
========================================= */

$categories = mysqli_query(
    $conn,
    "SELECT id, name
     FROM categories
     ORDER BY name ASC"
);

/* =========================================
   AMBIL PRODUK MILIK SELLER
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        products.id,
        products.name,
        products.description,
        products.price,
        products.stock,
        products.image,
        categories.name AS category_name
     FROM products
     LEFT JOIN categories
        ON products.category_id = categories.id
     WHERE products.store_id = ?
     ORDER BY products.id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $store_id);
mysqli_stmt_execute($stmt);

$products_result = mysqli_stmt_get_result($stmt);

$products = [];

while ($row = mysqli_fetch_assoc($products_result)) {
    $products[] = $row;
}

mysqli_stmt_close($stmt);

$total_products = count($products);
$total_stock = 0;

foreach ($products as $item) {
    $total_stock += (int) $item['stock'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Produk - MedanMart</title>

    <style>
        :root {
            --primary: #F97316;
            --primary-dark: #EA580C;
            --primary-light: #FFF7ED;
            --background: #F8FAFC;
            --surface: #FFFFFF;
            --text: #1F2937;
            --muted: #6B7280;
            --border: #E5E7EB;
            --success: #15803D;
            --success-bg: #F0FDF4;
            --danger: #DC2626;
            --danger-bg: #FEF2F2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .navbar-inner {
            max-width: 1200px;
            min-height: 68px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            color: var(--primary-dark);
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
        }

        .nav-links a {
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--primary-dark);
        }

        .page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 24px 48px;
        }

        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 24px;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 7px;
            padding: 5px 11px;
            border-radius: 999px;
            background: var(--primary-light);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
        }

        h1 {
            margin: 0;
            font-size: 29px;
            line-height: 1.3;
        }

        .subtitle {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            padding: 20px 22px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 13px;
        }

        .stat-number {
            display: block;
            margin-top: 5px;
            font-size: 27px;
            font-weight: 800;
            line-height: 1.3;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(280px, 0.78fr) minmax(0, 1.6fr);
            align-items: start;
            gap: 22px;
        }

        .card {
            min-width: 0;
            padding: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, 0.035);
        }

        .card-heading {
            margin-bottom: 22px;
        }

        .card-heading h2 {
            margin: 0;
            font-size: 19px;
            line-height: 1.4;
        }

        .card-heading p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        input,
        textarea,
        select {
            width: 100%;
            min-width: 0;
            padding: 11px 12px;
            border: 1px solid #D1D5DB;
            border-radius: 9px;
            background: #FFFFFF;
            color: var(--text);
            font: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        input[type="file"] {
            padding: 9px;
            font-size: 12px;
        }

        .hint {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            min-height: 40px;
            padding: 10px 14px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-primary {
            width: 100%;
            color: #FFFFFF;
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            color: var(--text);
            background: #FFFFFF;
            border-color: var(--border);
        }

        .btn-secondary:hover {
            background: #F9FAFB;
        }

        .btn-edit {
            color: var(--primary-dark);
            background: var(--primary-light);
        }

        .btn-edit:hover {
            background: #FFEDD5;
        }

        .btn-delete {
            color: var(--danger);
            background: var(--danger-bg);
        }

        .btn-delete:hover {
            background: #FEE2E2;
        }

        .notice {
            margin-bottom: 22px;
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 14px;
        }

        .notice.success {
            color: #166534;
            background: var(--success-bg);
            border: 1px solid #BBF7D0;
        }

        .notice.error {
            color: #991B1B;
            background: var(--danger-bg);
            border: 1px solid #FECACA;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
            background: #FFFFFF;
        }

        th,
        td {
            padding: 13px 14px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
            font-size: 13px;
        }

        th {
            background: #F9FAFB;
            color: #4B5563;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #FFFCF9;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 190px;
        }

        .product-image {
            display: block;
            width: 54px;
            height: 54px;
            flex-shrink: 0;
            object-fit: cover;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #F9FAFB;
        }

        .image-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 10px;
            text-align: center;
        }

        .product-name {
            display: block;
            max-width: 200px;
            overflow-wrap: anywhere;
            color: var(--text);
            font-weight: 700;
        }

        .product-description {
            display: block;
            max-width: 200px;
            margin-top: 3px;
            overflow: hidden;
            color: var(--muted);
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .price {
            color: var(--primary-dark);
            font-weight: 700;
            white-space: nowrap;
        }

        .stock-badge {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            background: #F3F4F6;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .actions .btn {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 12px;
        }

        .empty-state {
            padding: 32px 16px;
            color: var(--muted);
            text-align: center;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 5px;
            color: var(--text);
            font-size: 15px;
        }

        .empty-state p {
            margin: 0;
            font-size: 13px;
        }

        .table-note {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 950px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .navbar-inner {
                align-items: flex-start;
                flex-direction: column;
                padding: 15px 17px;
            }

            .nav-links {
                gap: 13px;
            }

            .page {
                padding: 23px 13px 35px;
            }

            h1 {
                font-size: 25px;
            }

            .stats {
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 23px;
            }

            .card {
                padding: 19px 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="navbar-inner">
        <a href="dashboard.php" class="brand">MedanMart</a>

        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="store.php">Toko Saya</a>
            <a href="products.php" class="active">Kelola Produk</a>
            <a href="orders.php">Pesanan Masuk</a>
        </nav>
    </div>
</header>

<main class="page">

    <div class="page-heading">
        <div>
            <span class="eyebrow">Area Seller</span>
            <h1>Kelola Produk</h1>
            <p class="subtitle">
                Atur produk, harga, stok, dan foto di toko
                <?= htmlspecialchars($store['store_name']) ?>.
            </p>
        </div>
    </div>

    <?php if ($message !== ""): ?>
        <div class="notice <?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <span class="stat-label">Total Produk</span>
            <strong class="stat-number">
                <?= number_format($total_products, 0, ',', '.') ?>
            </strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">Total Stok Produk</span>
            <strong class="stat-number">
                <?= number_format($total_stock, 0, ',', '.') ?>
            </strong>
        </div>
    </section>

    <div class="content-grid">

        <!-- FORM TAMBAH PRODUK -->
        <section class="card">
            <div class="card-heading">
                <h2>Tambah Produk</h2>
                <p>Isi detail produk yang ingin kamu jual.</p>
            </div>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="name">Nama Produk</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="255"
                        placeholder="Contoh: Tas Kuliah"
                        value="<?= isset($_POST['add_product']) ? htmlspecialchars($_POST['name'] ?? '') : '' ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Pilih Kategori --</option>

                        <?php
                        mysqli_data_seek($categories, 0);
                        while ($category = mysqli_fetch_assoc($categories)):
                        ?>
                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= isset($_POST['category_id']) &&
                                    (string) $_POST['category_id'] === (string) $category['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea
                        id="description"
                        name="description"
                        placeholder="Jelaskan detail produk..."
                        required
                    ><?= isset($_POST['add_product']) ? htmlspecialchars($_POST['description'] ?? '') : '' ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Harga (Rp)</label>
                        <input
                            type="number"
                            id="price"
                            name="price"
                            min="0"
                            step="any"
                            placeholder="25000"
                            value="<?= isset($_POST['add_product']) ? htmlspecialchars($_POST['price'] ?? '') : '' ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="stock">Stok</label>
                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            min="0"
                            step="1"
                            placeholder="10"
                            value="<?= isset($_POST['add_product']) ? htmlspecialchars($_POST['stock'] ?? '') : '' ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Foto Produk</label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        required
                    >
                    <small class="hint">
                        JPG, JPEG, PNG, atau WEBP. Maksimal 5 MB.
                    </small>
                </div>

                <button
                    type="submit"
                    name="add_product"
                    class="btn btn-primary"
                >
                    + Tambahkan Produk
                </button>

            </form>
        </section>

        <!-- DAFTAR PRODUK -->
        <section class="card">
            <div class="card-heading">
                <h2>Daftar Produk</h2>
                <p>
                    <?= number_format($total_products, 0, ',', '.') ?>
                    produk terdaftar di toko kamu.
                </p>
            </div>

            <?php if ($total_products > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($products as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <?php if (!empty($item['image'])): ?>
                                                <img
                                                    class="product-image"
                                                    src="../uploads/products/<?= rawurlencode(basename($item['image'])) ?>"
                                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                                    loading="lazy"
                                                >
                                            <?php else: ?>
                                                <div class="product-image image-placeholder">
                                                    Tanpa foto
                                                </div>
                                            <?php endif; ?>

                                            <div>
                                                <span class="product-name">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                </span>
                                                <span class="product-description">
                                                    <?= htmlspecialchars($item['description']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($item['category_name'] ?? 'Tanpa kategori') ?>
                                    </td>

                                    <td class="price">
                                        Rp <?= number_format((float) $item['price'], 0, ',', '.') ?>
                                    </td>

                                    <td>
                                        <span class="stock-badge">
                                            <?= number_format((int) $item['stock'], 0, ',', '.') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions">
                                            <a
                                                class="btn btn-edit"
                                                href="edit_product.php?id=<?= (int) $item['id'] ?>"
                                            >
                                                Edit
                                            </a>

                                            <button
                                                type="button"
                                                class="btn btn-delete"
                                                onclick="hapusProduk(<?= (int) $item['id'] ?>)"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="table-note">
                    Jika tampilan tabel terlalu lebar di layar kecil,
                    geser tabel ke samping untuk melihat kolom lainnya.
                </p>

            <?php else: ?>
                <div class="empty-state">
                    <strong>Belum ada produk</strong>
                    <p>Tambahkan produk pertama kamu melalui formulir di sebelah kiri.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<script>
    function hapusProduk(id) {
        const yakin = confirm(
            "Apakah kamu yakin ingin menghapus produk ini?\n\n" +
            "Produk yang dihapus tidak akan muncul lagi di daftar."
        );

        if (yakin) {
            window.location.href = "delete_product.php?id=" + encodeURIComponent(id);
        }
    }
</script>

</body>
</html>
