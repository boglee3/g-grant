<?php
session_start();
include __DIR__ . "/../config/config.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];

/* CHECK TABLE EXISTS FIRST */
$checkTable = $conn->query("SHOW TABLES LIKE 'wallet'");

if ($checkTable->num_rows == 0) {
    die("❌ Wallet table does not exist. Run SQL setup first.");
}

/* GET WALLET */
$stmt = $conn->prepare("SELECT balance FROM wallet WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$wallet = $res->fetch_assoc();

/* CREATE IF NOT EXISTS */
if (!$wallet) {
    $default_balance = 1000.00;

    $insert = $conn->prepare("INSERT INTO wallet (user_id, balance) VALUES (?, ?)");
    $insert->bind_param("id", $user_id, $default_balance);
    $insert->execute();

    $wallet = ['balance' => $default_balance];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Wallet</title>
<style>
body{
    margin:0;
    font-family:system-ui;
    background:#050816;
    color:white;
}

.container{
    max-width:800px;
    margin:auto;
    padding:20px;
}

.card{
    background:#111827;
    padding:25px;
    border-radius:12px;
    text-align:center;
    margin-top:40px;
}

.balance{
    font-size:50px;
    color:#22c55e;
    font-weight:bold;
}
</style>
</head>

<body>

<div class="container">

<div class="card">
<h2>💳 Wallet Balance</h2>
<div class="balance">
$<?php echo number_format($wallet['balance'], 2); ?>
</div>
</div>

</div>

</body>
</html>