<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";
$error = "";

/* HANDLE ADD FUNDS */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        $error = "Invalid amount ❌";
    } else {

        $stmt = $conn->prepare("SELECT id, balance FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {

            $user = $res->fetch_assoc();
            $newBalance = $user['balance'] + $amount;

            $update = $conn->prepare("UPDATE users SET balance=? WHERE id=?");
            $update->bind_param("di", $newBalance, $user['id']);
            $update->execute();

            $message = "✅ $" . number_format($amount,2) . " added successfully";

        } else {
            $error = "User not found ❌";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Funds</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#eef2f7;
}

/* WRAPPER */
.main-wrapper{
    margin-left:250px;
    padding:20px;
    display:flex;
    justify-content:center;
}

/* CONTAINER */
.container-box{
    width:100%;
    max-width:600px;
}

/* TOPBAR */
.topbar{
    background:white;
    padding:15px 20px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

/* CARD */
.card-box{
    background:white;
    padding:25px;
    margin-top:20px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

/* INPUT */
input{
    width:100%;
    padding:12px;
    margin-top:10px;
    border-radius:8px;
    border:1px solid #ddd;
}

/* BUTTON */
button{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:none;
    border-radius:8px;
    background:#22c55e;
    color:white;
    font-weight:bold;
}

/* ALERTS */
.alert-success{
    background:#dcfce7;
    color:#166534;
    padding:10px;
    border-radius:8px;
    margin-top:10px;
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
    padding:10px;
    border-radius:8px;
    margin-top:10px;
}

/* MOBILE NAV */
.mobile-nav{
    display:none;
    position:fixed;
    bottom:0;
    left:0;
    right:0;
    background:#0f172a;
    padding:10px;
    justify-content:space-around;
    color:white;
}

.mobile-nav a{
    color:white;
    text-decoration:none;
    font-size:12px;
    display:flex;
    flex-direction:column;
    align-items:center;
}

/* MOBILE */
@media(max-width:768px){
    .main-wrapper{
        margin-left:0;
        padding:10px;
    }

    .mobile-nav{
        display:flex;
    }
}

</style>
</head>

<body>

<!-- MAIN -->
<div class="main-wrapper">
<div class="container-box">

    <!-- TOPBAR -->
    <div class="topbar">
        <h5>💰 Add Funds</h5>
        <a href="index.html" class="btn btn-dark btn-sm">Dashboard</a>
    </div>

    <!-- FORM CARD -->
    <div class="card-box">

        <h5>Credit User Wallet</h5>

        <!-- ALERTS -->
        <?php if($message): ?>
            <div class="alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">

            <label>User Email</label>
            <input type="email" name="email" placeholder="Enter user email" required>

            <label>Amount ($)</label>
            <input type="number" step="0.01" name="amount" placeholder="Enter amount" required>

            <button type="submit">
                <i class="fa fa-plus"></i> Add Funds
            </button>

        </form>

    </div>

</div>
</div>

<!-- MOBILE NAV -->
<div class="mobile-nav">

    <a href="index.html">
        <i class="fa fa-home"></i>
        <span>Home</span>
    </a>

    <a href="users.php">
        <i class="fa fa-users"></i>
        <span>Users</span>
    </a>

    <a href="add_funds.php">
        <i class="fa fa-wallet"></i>
        <span>Funds</span>
    </a>

    <a href="grants.php">
        <i class="fa fa-clock"></i>
        <span>Grants</span>
    </a>

    <a href="../auth/logout.php">
        <i class="fa fa-sign-out"></i>
        <span>Logout</span>
    </a>

</div>

</body>
</html>