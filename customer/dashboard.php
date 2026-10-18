<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.php");
    exit;
}

$nama = $_SESSION['nama'] ?? 'Customer';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - MedanMart</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f7f8fa;
            color: #222;
        }

        .navbar {
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 7%;
            border-bottom: 1px solid #eee;
        }

        .logo {
            color: #e85d04;
            font-size: 24px;
            font-weight: bold;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-right a {
            text-decoration: none;
            color: #444;
            padding: 9px 15px;
            border-radius: 8px;
        }

        .nav-right .logout {
            background: #e85d04;
            color: white;
        }

        .container {
            width: 86%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .welcome {
            background: linear-gradient(135deg, #e85d04, #f48c06);
            color: white;
            padding: 35px;
            border-radius: 18px;
            margin-bottom: 30px;
        }

        .welcome h1 {
            margin-bottom: 10px;
            font-size: 30px;
        }

        .welcome p {
            opacity: 0.9;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-decoration: none;
            color: #222;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: 0.2s;
        }

        .card:hover {
            transform: translateY(-4px);
        }

        .card-icon {
            font-size: 38px;
            margin-bottom: 15px;
        }

        .card h3 {
            margin-bottom: 8px;
        }

        .card p {
            color: #777;
            line-height: 1.5;
            font-size: 14px;
        }

        footer {
            text-align: center;
            color: #888;
            padding: 30px;
            margin-top: 50px;
        }

        @media (max-width: 700px) {
            .cards {
                grid-template-columns: 1fr;
            }

            .navbar {
                padding: 0 5%;
            }

            .container {
                width: 90%;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">

        <div class="logo">
            🛍️ MedanMart
        </div>

        <div class="nav-right">
            <a href="store.php">Toko</a>
            <a href="cart.php">🛒 Keranjang</a>
            <a href="../logout.php" class="logout">Logout</a>
        </div>

    </nav>


    <main class="container">

        <section class="welcome">

            <h1>
                Selamat datang, <?= htmlspecialchars($nama); ?> 👋
            </h1>

            <p>
                Temukan produk yang kamu butuhkan dan lakukan
                pembelian dengan mudah di MedanMart.
            </p>

        </section>


        <section class="cards">

            <a href="store.php" class="card">

                <div class="card-icon">
                    🛍️
                </div>

                <h3>
                    Belanja Produk
                </h3>

                <p>
                    Lihat berbagai produk yang tersedia
                    dan pilih produk yang ingin kamu beli.
                </p>

            </a>


            <a href="cart.php" class="card">

                <div class="card-icon">
                    🛒
                </div>

                <h3>
                    Keranjang
                </h3>

                <p>
                    Lihat produk yang sudah kamu masukkan
                    ke dalam keranjang belanja.
                </p>

            </a>


            <a href="orders.php" class="card">

                <div class="card-icon">
                    📦
                </div>

                <h3>
                    Pesanan Saya
                </h3>

                <p>
                    Lihat riwayat dan status pesanan
                    yang sudah kamu lakukan.
                </p>

            </a>

        </section>

    </main>


    <footer>
        © 2026 MedanMart
    </footer>

</body>

</html>
