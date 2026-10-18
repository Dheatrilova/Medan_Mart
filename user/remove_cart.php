<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: " . __DIR__ . "/../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: " . __DIR__ . "/cart.php");
    exit;
}

$cart_id = intval($_GET['id']);

$query = "
    DELETE FROM cart
    WHERE id = '$cart_id'
    AND user_id = '$user_id'
";

mysqli_query($conn, $query);

header("Location: cart.php");
exit;
?>