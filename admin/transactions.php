<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'];

/* ADMIN SEE ALL TRANSACTIONS */
if ($role === "admin") {
    $result = $conn->query("
        SELECT t.*, u.full_name, u.email
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.id DESC
    ");
} 
/* USER SEE ONLY THEIR OWN */
else {
    $stmt = $conn->prepare("
        SELECT * FROM transactions 
        WHERE user_id=? 
        ORDER BY id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Transactions</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#0f172a;
    color:white;
    font-family:Segoe UI;
}

.container{
    padding:20px;
}

/* CARD */
.card-box{
    background:#111827;
    padding:15px;
    border-radius:12px;
}

/* BACK BUTTON */
.back-btn{
    display:inline-block;
    background:#22c55e;
    color:white;
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
    margin-bottom:15px;
    font-weight:bold;
}

/* ROW ITEM */
.txn{
    background:#1f2937;
    padding:12px;
    border-radius:10px;
    margin-bottom:10px;
}

/* MOBILE */
@media(max-width:768px){
    h3{font-size:20px;}
    .txn{font-size:14px;}
    .back-btn{
        width:100%;
        text-align:center;
    }
}
</style>
</head>

<body>

<div class="container">

<a href="index.html" class="back-btn">⬅ Back</a>

<h3>💰 Transaction History</h3>

<div class="card-box">

<?php while($row = $result->fetch_assoc()): ?>

    <div class="txn">

        <?php if ($role === "admin"): ?>
            <b>User:</b> <?= htmlspecialchars($row['full_name']) ?> (<?= htmlspecialchars($row['email']) ?>)<br>
        <?php endif; ?>

        <b>Type:</b> <?= strtoupper($row['type']) ?><br>
        <b>Amount:</b> $<?= number_format($row['amount']) ?><br>
        <b>Description:</b> <?= htmlspecialchars($row['description']) ?><br>
        <small><?= $row['created_at'] ?></small>

    </div>

<?php endwhile; ?>

</div>

</div>

</body>
</html>