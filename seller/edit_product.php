
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

/* =========================================
   CEK LOGIN SELLER
========================================= */
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'seller'
) {
    header("Location: ../login.php");
    exit;
}

$seller_id = (int) $_SESSION['user_id'];
$errors = [];

/* =========================================
   AMBIL TOKO SELLER
========================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM stores WHERE seller_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$store = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$store) {
    header("Location: store.php");
    exit;
}

$store_id = (int) $store['id'];

/* =========================================
   AMBIL ID PRODUK
========================================= */
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id || $product_id < 1) {
    header("Location: products.php?error=invalid_id");
    exit;
}

/* =========================================
   AMBIL DATA PRODUK
========================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, description, price, stock, category_id, image
     FROM products
     WHERE id = ? AND store_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "ii", $product_id, $store_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$product) {
    header("Location: products.php?error=product_not_found");
    exit;
}

/* =========================================
   AMBIL KATEGORI
========================================= */
$categories = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name ASC"
);

if (!$categories) {
    die("Gagal mengambil kategori: " . mysqli_error($conn));
}

/* =========================================
   PROSES UPDATE PRODUK
========================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_input = trim($_POST['price'] ?? '');
    $stock_input = trim($_POST['stock'] ?? '');
    $category_input = trim($_POST['category_id'] ?? '');

    $price_value = 0.0;
    $stock_value = 0;
    $category_value = 0;

    if ($name === '') {
        $errors[] = "Nama produk wajib diisi.";
    } elseif (strlen($name) > 255) {
        $errors[] = "Nama produk maksimal 255 karakter.";
    }

    if ($price_input === '' || !is_numeric($price_input)) {
        $errors[] = "Harga harus berupa angka.";
    } elseif ((float) $price_input <= 0) {
        $errors[] = "Harga harus lebih besar dari 0.";
    } else {
        $price_value = (float) $price_input;
    }

    if (
        $stock_input === '' ||
        filter_var($stock_input, FILTER_VALIDATE_INT) === false
    ) {
        $errors[] = "Stok harus berupa bilangan bulat.";
    } elseif ((int) $stock_input < 0) {
        $errors[] = "Stok tidak boleh negatif.";
    } else {
        $stock_value = (int) $stock_input;
    }

    if (
        $category_input === '' ||
        filter_var($category_input, FILTER_VALIDATE_INT) === false ||
        (int) $category_input < 1
    ) {
        $errors[] = "Silakan pilih kategori produk.";
    } else {
        $category_value = (int) $category_input;

        $check_category = mysqli_prepare(
            $conn,
            "SELECT id FROM categories WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param(
            $check_category,
            "i",
            $category_value
        );
        mysqli_stmt_execute($check_category);
        $category_result = mysqli_stmt_get_result($check_category);
        $category_exists = mysqli_fetch_assoc($category_result);
        mysqli_stmt_close($check_category);

        if (!$category_exists) {
            $errors[] = "Kategori yang dipilih tidak tersedia.";
        }
    }

    /*
       Validasi gambar dilakukan sebelum memindahkan file,
       agar gambar baru tidak tertinggal jika input lain salah.
    */
    $new_image_tmp = null;
    $new_image_name = null;

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Gambar gagal diunggah.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "Ukuran gambar maksimal 5 MB.";
        } else {
            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $image_info = @getimagesize($file['tmp_name']);
            $mime_type = $image_info['mime'] ?? '';

            if (
                !$image_info ||
                !isset($allowed_types[$mime_type])
            ) {
                $errors[] = "Format gambar harus JPG, PNG, atau WEBP.";
            } else {
                $new_image_tmp = $file['tmp_name'];
                $new_image_name = bin2hex(random_bytes(16))
                    . '.'
                    . $allowed_types[$mime_type];
            }
        }
    }

    if (empty($errors)) {
        $image_name = $product['image'];
        $upload_dir = __DIR__ . "/../uploads/products/";

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $errors[] = "Folder penyimpanan gambar gagal dibuat.";
            }
        }

        if (empty($errors) && $new_image_tmp !== null) {
            if (!is_writable($upload_dir)) {
                $errors[] = "Folder gambar tidak dapat ditulisi.";
            } elseif (
                !move_uploaded_file(
                    $new_image_tmp,
                    $upload_dir . $new_image_name
                )
            ) {
                $errors[] = "Gambar baru gagal disimpan.";
            } else {
                $image_name = $new_image_name;
            }
        }

        if (empty($errors)) {
            /*
               Hanya satu query UPDATE.
               Tipe: s = string, d = double, i = integer.
               Urutan: name, description, price, stock,
                       category_id, image, product_id, store_id.
            */
            $update = mysqli_prepare(
                $conn,
                "UPDATE products
                 SET name = ?,
                     description = ?,
                     price = ?,
                     stock = ?,
                     category_id = ?,
                     image = ?
                 WHERE id = ? AND store_id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "ssdiisii",
                $name,
                $description,
                $price_value,
                $stock_value,
                $category_value,
                $image_name,
                $product_id,
                $store_id
            );

            $success = mysqli_stmt_execute($update);
            mysqli_stmt_close($update);

            if ($success) {
                // Hapus gambar lama hanya setelah update berhasil.
                if (
                    $new_image_name !== null &&
                    !empty($product['image'])
                ) {
                    $old_image_path = $upload_dir . basename(
                        $product['image']
                    );

                    if (is_file($old_image_path)) {
                        @unlink($old_image_path);
                    }
                }

                header("Location: products.php?updated=1");
                exit;
            } else {
                // Bersihkan gambar baru jika update database gagal.
                if ($new_image_name !== null) {
                    $new_image_path = $upload_dir . $new_image_name;

                    if (is_file($new_image_path)) {
                        @unlink($new_image_path);
                    }
                }

                $errors[] = "Produk gagal diperbarui. Silakan coba lagi.";
            }
        }
    }
}

/* =========================================
   NILAI FORM
========================================= */
$form_name = $_POST['name'] ?? $product['name'];
$form_description = $_POST['description'] ?? $product['description'];
$form_price = $_POST['price'] ?? $product['price'];
$form_stock = $_POST['stock'] ?? $product['stock'];
$form_category = $_POST['category_id'] ?? $product['category_id'];

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
    <title>Edit Produk | MedanMart</title>

    <style>
        :root {
            --primary: #F97316;
            --primary-dark: #EA580C;
            --cream: #FFF7ED;
            --text: #1F2937;
            --muted: #6B7280;
            --border: #E5E7EB;
            --background: #F8FAFC;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
        }

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 18px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            color: var(--primary-dark);
            font-size: 23px;
            font-weight: 800;
            text-decoration: none;
        }

        .back-link {
            color: var(--text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-link:hover {
            color: var(--primary-dark);
        }

        .container {
            width: min(900px, 92%);
            margin: 36px auto;
        }

        .page-heading {
            margin-bottom: 22px;
        }

        .page-heading h1 {
            margin: 0 0 8px;
            font-size: clamp(25px, 4vw, 32px);
        }

        .page-heading p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .form-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: clamp(20px, 4vw, 32px);
            box-shadow: 0 8px 28px rgba(31, 41, 55, 0.05);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 14px;
        }

        .alert-error {
            background: #FEF2F2;
            color: #B91C1C;
            border: 1px solid #FECACA;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .form-group {
            min-width: 0;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-size: 14px;
            font-weight: 700;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
            font: inherit;
            font-size: 14px;
            color: var(--text);
            background: var(--white);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        textarea {
            min-height: 130px;
            resize: vertical;
            line-height: 1.6;
        }

        .help-text {
            display: block;
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .current-image {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .current-image img {
            width: 115px;
            height: 115px;
            object-fit: cover;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--cream);
        }

        .image-description {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 44px;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            color: var(--white);
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            color: var(--text);
            background: var(--white);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--background);
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 16px 5%;
            }

            .container {
                margin: 25px auto;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .full-width {
                grid-column: auto;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="../index.php" class="brand">MedanMart</a>
        <a href="products.php" class="back-link">
            &larr; Kembali ke Produk
        </a>
    </nav>

    <main class="container">
        <div class="page-heading">
            <h1>Edit Produk</h1>
            <p>Perbarui informasi produk tokomu melalui formulir berikut.</p>
        </div>

        <section class="form-card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Produk belum berhasil diperbarui.</strong>
                    <ul style="margin-bottom: 0;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="name">Nama Produk</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="255"
                            value="<?= e($form_name) ?>"
                            placeholder="Masukkan nama produk"
                            required
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Deskripsi Produk</label>
                        <textarea
                            id="description"
                            name="description"
                            placeholder="Jelaskan detail produk"
                        ><?= e($form_description) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Harga (Rp)</label>
                        <input
                            type="number"
                            id="price"
                            name="price"
                            min="1"
                            step="any"
                            value="<?= e($form_price) ?>"
                            placeholder="Contoh: 50000"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="stock">Stok Produk</label>
                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            min="0"
                            step="1"
                            value="<?= e($form_stock) ?>"
                            placeholder="Contoh: 10"
                            required
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="category_id">Kategori Produk</label>
                        <select
                            id="category_id"
                            name="category_id"
                            required
                        >
                            <option value="">-- Pilih Kategori --</option>

                            <?php
                            mysqli_data_seek($categories, 0);
                            while ($category = mysqli_fetch_assoc($categories)):
                            ?>
                                <option
                                    value="<?= (int) $category['id'] ?>"
                                    <?= (string) $form_category === (string) $category['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="image">Foto Produk</label>

                        <?php if (!empty($product['image'])): ?>
                            <div class="current-image">
                                <img
                                    src="../uploads/products/<?= rawurlencode(basename($product['image'])) ?>"
                                    alt="Foto produk saat ini"
                                >
                                <div class="image-description">
                                    Foto produk saat ini.<br>
                                    Pilih gambar baru jika ingin menggantinya.
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="help-text">
                                Produk ini belum memiliki foto.
                            </p>
                        <?php endif; ?>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                        <span class="help-text">
                            Format JPG, PNG, atau WEBP. Maksimal 5 MB.
                            Kosongkan jika tidak ingin mengganti foto.
                        </span>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="products.php" class="btn btn-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
