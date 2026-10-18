
<?php
session_start();
require_once "config/database.php";

$message = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE email='$email' LIMIT 1"
    );

    if ($query && mysqli_num_rows($query) === 1) {
        $user = mysqli_fetch_assoc($query);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit;
            } elseif ($user['role'] === 'seller') {
                header("Location: seller/dashboard.php");
                exit;
            } elseif ($user['role'] === 'buyer') {
                header("Location: user/dashboard.php");
                exit;
            } else {
                $message = "Role akun tidak dikenali.";
            }
        } else {
            $message = "Password yang kamu masukkan salah.";
        }
    } else {
        $message = "Email tidak ditemukan. Periksa kembali email kamu.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f97316">
    <title>Login - MedanMart</title>

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --cream: #fff7ed;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --background: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            padding: 30px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, #ffedd5, transparent 38%),
                var(--background);
        }

        a {
            text-decoration: none;
        }

        .auth-layout {
            width: 100%;
            max-width: 960px;
            min-height: 550px;
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            overflow: hidden;
            background: white;
            border: 1px solid rgba(229, 231, 235, 0.8);
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(31, 41, 55, 0.09);
        }

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 38px;
            background: linear-gradient(145deg, #fff7ed, #ffedd5);
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .logo-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: white;
            font-size: 23px;
        }

        .logo span:last-child span {
            color: var(--primary);
        }

        .brand-content {
            position: relative;
            z-index: 1;
            margin-block: 55px;
        }

        .brand-label {
            display: inline-block;
            margin-bottom: 18px;
            padding: 7px 12px;
            border: 1px solid #fed7aa;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.65);
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .brand-content h1 {
            margin-bottom: 18px;
            font-size: clamp(29px, 3.3vw, 39px);
            line-height: 1.2;
            letter-spacing: -1.2px;
        }

        .brand-content h1 span {
            color: var(--primary);
        }

        .brand-content p {
            max-width: 340px;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.9;
        }

        .brand-illustration {
            width: 145px;
            height: 125px;
            display: grid;
            place-items: center;
            align-self: center;
            margin: 0 auto;
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.75);
            box-shadow: 0 15px 35px rgba(124, 68, 22, 0.07);
            font-size: 66px;
        }

        .brand-footer {
            margin-top: 30px;
            color: #78716c;
            font-size: 12px;
        }

        .form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 55px 52px;
        }

        .form-heading {
            margin-bottom: 30px;
        }

        .form-heading h2 {
            margin-bottom: 10px;
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .form-heading p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-size: 13px;
            font-weight: 700;
        }

        .form-group input {
            width: 100%;
            height: 49px;
            padding: 0 15px;
            border: 1px solid var(--border);
            border-radius: 11px;
            outline: none;
            background: white;
            color: var(--text);
            font: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.11);
        }

        .submit-button {
            width: 100%;
            min-height: 49px;
            margin-top: 5px;
            border: none;
            border-radius: 11px;
            background: var(--primary);
            color: white;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .submit-button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .message {
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-bottom {
            margin-top: 25px;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }

        .form-bottom a {
            color: var(--primary-dark);
            font-weight: 800;
        }

        .form-bottom a:hover {
            text-decoration: underline;
        }

        .back-home {
            display: block;
            margin-top: 25px;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        .back-home:hover {
            color: var(--primary-dark);
        }

        @media (max-width: 720px) {
            body {
                padding: 20px 14px;
            }

            .auth-layout {
                max-width: 460px;
                min-height: auto;
                grid-template-columns: 1fr;
                border-radius: 20px;
            }

            .brand-panel {
                padding: 24px;
            }

            .brand-content {
                margin-block: 35px 20px;
            }

            .brand-content h1 {
                font-size: 30px;
            }

            .brand-illustration {
                width: 85px;
                height: 75px;
                margin-top: 20px;
                border-radius: 18px;
                font-size: 42px;
            }

            .brand-footer {
                margin-top: 25px;
            }

            .form-panel {
                padding: 35px 25px;
            }

            .form-heading h2 {
                font-size: 26px;
            }
        }

        @media (max-width: 360px) {
            .brand-panel {
                padding: 20px;
            }

            .form-panel {
                padding: 28px 19px;
            }
        }
    </style>
</head>

<body>

    <main class="auth-layout">

        <section class="brand-panel">
            <a href="index.php" class="logo">
                <span class="logo-icon">🛍️</span>
                <span>Medan<span>Mart</span></span>
            </a>

            <div class="brand-content">
                <div class="brand-label">SELAMAT DATANG KEMBALI</div>

                <h1>
                    Belanja Mudah,<br>
                    <span>Semua Lebih Praktis.</span>
                </h1>

                <p>
                    Masuk ke akun MedanMart untuk melanjutkan belanja,
                    memantau pesanan, atau mengelola toko kamu.
                </p>

                <div class="brand-illustration" aria-hidden="true">
                    🛒
                </div>
            </div>

            <p class="brand-footer">
                © 2026 MedanMart · Marketplace Lokal
            </p>
        </section>


        <section class="form-panel">
            <div class="form-heading">
                <h2>Login Akun</h2>
                <p>
                    Masukkan email dan password untuk melanjutkan.
                </p>
            </div>

            <?php if ($message !== ""): ?>
                <div class="message" role="alert">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" name="login" class="submit-button">
                    Login Sekarang →
                </button>
            </form>

            <div class="form-bottom">
                Belum punya akun?
                <a href="register.php">Daftar sekarang</a>
            </div>

            <a href="index.php" class="back-home">
                ← Kembali ke halaman utama
            </a>
        </section>

    </main>

</body>
</html>
