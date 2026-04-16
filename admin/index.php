<?php
session_start();
include __DIR__ . "/../config/config.php";

if (file_exists(__DIR__ . "/../includes/user.php")) {
    include __DIR__ . "/../includes/user.php";
}

if (!isset($_SESSION['id']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

/* STATS */
$users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc();
$grants = $conn->query("SELECT COUNT(*) as total FROM grants WHERE status='pending'")->fetch_assoc();
$transactions = $conn->query("SELECT COUNT(*) as total FROM transactions")->fetch_assoc();

/* GRANTS */
$result = $conn->query("
    SELECT g.*, u.full_name, u.email
    FROM grants g
    JOIN users u ON g.user_id = u.id
    WHERE g.status='pending'
    ORDER BY g.id DESC
");

$adminName = "Daniel James Smith";
$adminBalance = 50000000;
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#eef2f7;
    overflow-x:hidden;
}

/* =========================
   DESKTOP SIDEBAR (ONLY)
========================= */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:linear-gradient(180deg,#0f172a,#020617);
    padding:25px 15px;
    color:white;
    overflow-y:auto;
    z-index:10;
    border-right:1px solid rgba(255,255,255,0.05);
}

.sidebar h4{
    font-size:20px;
    margin-bottom:25px;
    font-weight:800;
}

.sidebar a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    color:#94a3b8;
    text-decoration:none;
    border-radius:10px;
    margin-bottom:8px;
    transition:0.3s;
}

.sidebar a:hover{
    background:#22c55e;
    color:white;
    transform:translateX(5px);
}

.sidebar a.active{
    background:#22c55e;
    color:white;
}

/* MAIN */
.main-wrapper{
    margin-left:260px;
    padding:30px;
}

/* HEADER */
.big-header{
    background:linear-gradient(135deg,#020617,#0f172a);
    color:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 15px 40px rgba(0,0,0,0.2);
    margin-bottom:25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

.big-header h2{
    font-size:28px;
    font-weight:800;
}

.balance-box{
    background:#22c55e;
    color:#022c22;
    padding:14px 20px;
    border-radius:12px;
    font-weight:700;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
}

/* CARD */
.card{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
    text-align:center;
}

/* PANEL */
.panel{
    background:white;
    margin-top:25px;
    padding:25px;
    border-radius:20px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

/* GRANT ITEM */
.grant-item{
    background:#f8fafc;
    padding:15px;
    margin-top:12px;
    border-radius:12px;
    border-left:4px solid #22c55e;
}

/* BADGES */
.badge{
    padding:6px 10px;
    border-radius:20px;
}
.pending{background:#78350f;color:#fde68a;}

/* =========================
   MOBILE - COMPLETELY UNTOUCHED
========================= */
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
    z-index:999;
}

.mobile-nav a{
    color:white;
    text-decoration:none;
    font-size:12px;
    display:flex;
    flex-direction:column;
    align-items:center;
}

@media(max-width:768px){
    .sidebar{display:none;}
    .main-wrapper{margin-left:0;}
    .grid{grid-template-columns:repeat(2,1fr);}
    .mobile-nav{display:flex;}
}

/* =========================
   FIX BLACK OVERLAY (DESKTOP ONLY)
========================= */
@media(min-width:769px){
    .modal-backdrop{
        display:none !important;
    }
    body.modal-open{
        overflow:auto !important;
        padding-right:0 !important;
    }

    /* kill unknown full-screen blockers */
    div{
        pointer-events:auto;
    }
}

</style>
</head>

<body>

<div class="sidebar">
    <h4>💰 Admin Panel</h4>

    <a href="index.html" class="active"><i class="fa fa-home"></i> Dashboard</a>
    <a href="users.php"><i class="fa fa-users"></i> Users</a>
    <a href="add_funds.php"><i class="fa fa-wallet"></i> Add Funds</a>
    <a href="applications.php"><i class="fa fa-file-alt"></i> Applications</a>
    <a href="chat.php"><i class="fa fa-comments"></i> Chat</a>
    <a href="grants.php"><i class="fa fa-clock"></i> Grants</a>
    <a href="manage_grants.php"><i class="fa fa-plus-circle"></i> Manage Grants</a>
    <a href="transactions.php"><i class="fa fa-money-bill-wave"></i> Transactions</a>

    <hr style="border-color:rgba(255,255,255,0.1);">

    <a href="../auth/logout.php" style="color:#ef4444;">
        <i class="fa fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="main-wrapper">

    <div class="big-header">
        <div>
            <h2>📊 Admin Dashboard</h2>
            <p>Welcome back, <b><?= $adminName ?></b></p>
        </div>

        <div class="balance-box">
            💰 $<?= number_format($adminBalance) ?>
        </div>
    </div>

    <div class="grid">

        <div class="card">
            <h6>Users</h6>
            <h3><?= $users['total'] ?></h3>
        </div>

        <div class="card">
            <h6>Pending Grants</h6>
            <h3><?= $grants['total'] ?></h3>
        </div>

        <div class="card">
            <h6>Transactions</h6>
            <h3><?= $transactions['total'] ?></h3>
        </div>

        <div class="card">
            <h6>Quick Access</h6>
            <a href="users.php" class="btn btn-dark btn-sm w-100 mb-1">Users</a>
            <a href="add_funds.php" class="btn btn-success btn-sm w-100">Add Funds</a>
        </div>

    </div>

    <div class="panel">
        <h5>📌 Grant Applications</h5>

        <?php while($row = $result->fetch_assoc()): ?>
        <div class="grant-item">

            <b><?= htmlspecialchars($row['full_name']) ?></b><br>
            📧 <?= htmlspecialchars($row['email']) ?><br>
            💰 $<?= number_format($row['amount']) ?><br>

            <span class="badge pending">
                <?= strtoupper($row['status']) ?>
            </span>

        </div>
        <?php endwhile; ?>

    </div>

</div>

<!-- MOBILE NAV (UNCHANGED EXACTLY) -->
<div class="mobile-nav">
    <a href="index.html">Home</a>
    <a href="users.php">Users</a>
    <a href="chat.php">Chat</a>
    <a href="add_funds.php">Funds</a>
    <a href="../auth/logout.php">Logout</a>
</div>

</body>
</html>