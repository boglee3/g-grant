<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Users</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
    background:#f4f6fb;
    font-family:'Segoe UI', sans-serif;
    margin:0;
}

/* MAIN */
.main{
    padding:20px;
}

/* TOP QUICK BAR */
.topbar{
    background:white;
    padding:12px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

/* BACK BUTTON */
.back-btn{
    display:inline-block;
    background:#0f172a;
    color:white;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
}

/* QUICK ADMIN BUTTON */
.quick-actions a{
    background:#22c55e;
    color:white;
    padding:6px 10px;
    border-radius:8px;
    text-decoration:none;
    font-size:12px;
}

/* CARD */
.card{
    background:white;
    padding:15px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
    margin-top:15px;
}

/* TABLE */
.table{
    width:100%;
}

/* MOBILE USER CARD */
.user-card{
    display:none;
    background:#f8fafc;
    padding:12px;
    border-radius:10px;
    margin-bottom:10px;
    border-left:4px solid #22c55e;
}

.user-line{
    display:flex;
    justify-content:space-between;
    padding:5px 0;
}

.label{
    color:#22c55e;
    font-weight:bold;
}

/* MOBILE NAV (USER STYLE ADDED) */
.mobile-nav{
    display:none;
    position:fixed;
    bottom:0;
    left:0;
    right:0;
    background:#0f172a;
    padding:10px;
    justify-content:space-around;
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

/* MOBILE */
@media(max-width:768px){

    .table{
        display:none;
    }

    .user-card{
        display:block;
    }

    .mobile-nav{
        display:flex;
    }
}

</style>
</head>

<body>

<div class="main">

<!-- TOP BAR -->
<div class="topbar">
    <h5>👥 Users</h5>

    <div class="quick-actions">
        <a href="index.html"><i class="fa fa-home"></i> Dashboard</a>
    </div>
</div>

<!-- CONTENT CARD -->
<div class="card">

<!-- TABLE -->
<div class="table-responsive">

<table class="table table-dark table-hover">
<tr>
<th>Name</th>
<th>Email</th>
<th>Balance</th>
</tr>

<?php while($u=$result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($u['full_name']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>
<td>$<?= number_format($u['balance']) ?></td>
</tr>
<?php endwhile; ?>

</table>

</div>

<!-- MOBILE CARDS -->
<?php
$result2 = $conn->query("SELECT * FROM users ORDER BY id DESC");
while($u=$result2->fetch_assoc()):
?>

<div class="user-card">

    <div class="user-line">
        <span class="label">Name</span>
        <span><?= htmlspecialchars($u['full_name']) ?></span>
    </div>

    <div class="user-line">
        <span class="label">Email</span>
        <span><?= htmlspecialchars($u['email']) ?></span>
    </div>

    <div class="user-line">
        <span class="label">Balance</span>
        <span>$<?= number_format($u['balance']) ?></span>
    </div>

</div>

<?php endwhile; ?>

</div>

</div>

<!-- MOBILE BOTTOM NAV -->
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