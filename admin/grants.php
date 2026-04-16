<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

/* ACTION HANDLER */
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if (in_array($action, ['approved','rejected','granted'])) {

        $stmt = $conn->prepare("SELECT user_id, amount FROM grants WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if ($data) {

            $stmt2 = $conn->prepare("UPDATE grants SET status=? WHERE id=?");
            $stmt2->bind_param("si", $action, $id);
            $stmt2->execute();

            if ($action === "granted") {
                $stmt3 = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id=?");
                $stmt3->bind_param("di", $data['amount'], $data['user_id']);
                $stmt3->execute();
            }
        }

        header("Location: grants.php");
        exit();
    }
}

/* DATA */
$result = $conn->query("
    SELECT g.*, u.full_name, u.email
    FROM grants g
    JOIN users u ON g.user_id = u.id
    ORDER BY g.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Grants</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{
    background:#f4f6fb;
    font-family:'Segoe UI', sans-serif;
    margin:0;
}

/* SIDEBAR (same style as user) */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#0f172a;
    color:white;
    padding:20px;
    overflow-y:auto;
}

.sidebar a{
    display:flex;
    gap:10px;
    padding:12px;
    color:#cbd5e1;
    text-decoration:none;
    border-radius:8px;
    margin-bottom:8px;
}

.sidebar a:hover{
    background:#22c55e;
    color:white;
}

/* MAIN */
.main{
    margin-left:260px;
    padding:20px;
}

/* TOPBAR */
.topbar{
    background:white;
    padding:15px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
    flex-wrap:wrap;
}

/* CARD */
.card-box{
    background:white;
    border-radius:15px;
    padding:20px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

/* BUTTONS */
.btn{
    border-radius:10px;
}

/* STATUS */
.badge{
    padding:6px 10px;
    border-radius:20px;
}
.approved{background:#d1fae5;color:#065f46;}
.rejected{background:#fecaca;color:#991b1b;}
.granted{background:#bfdbfe;color:#1e3a8a;}
.pending{background:#fde68a;color:#92400e;}

/* MOBILE NAV (same style as user dashboard) */
.mobile-nav{
    display:none;
    position:fixed;
    bottom:0;
    left:0;
    right:0;
    background:#0f172a;
    padding:10px 0;
    justify-content:space-around;
}

.mobile-nav a{
    color:#94a3b8;
    text-decoration:none;
    font-size:12px;
    display:flex;
    flex-direction:column;
    align-items:center;
}

.mobile-nav a:hover{
    color:#22c55e;
}

/* MOBILE */
@media(max-width:768px){
    .sidebar{
        display:none;
    }

    .main{
        margin-left:0;
        padding-bottom:80px;
    }

    .mobile-nav{
        display:flex;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h4>👑 Admin Panel</h4>

    <a href="index.html"><i class="fa fa-home"></i> Dashboard</a>
    <a href="users.php"><i class="fa fa-users"></i> Users</a>
    <a href="grants.php"><i class="fa fa-clock"></i> Grants</a>
    <a href="manage_grants.php"><i class="fa fa-plus"></i> Create</a>
    <a href="applications.php"><i class="fa fa-file"></i> Applications</a>
    <a href="transactions.php"><i class="fa fa-money-bill"></i> Transactions</a>
    <a href="../auth/logout.php" style="color:#ef4444;">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <button class="btn btn-dark btn-sm" onclick="alert('Add sidebar toggle if needed')">☰</button>
        <h5 class="m-0">💰 Grant Approval</h5>

        <!-- 🔥 NEW BUTTON -->
        <a href="../user/index.html" class="btn btn-success btn-sm">
            👤 Go to User Page
        </a>
    </div>

    <br>

    <div class="card-box">

        <div class="table-responsive">
        <table class="table table-hover">

        <tr>
            <th>Program</th>
            <th>User</th>
            <th>Email</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['program_name']) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>$<?= number_format($row['amount']) ?></td>
            <td><span class="badge <?= $row['status'] ?>"><?= strtoupper($row['status']) ?></span></td>
            <td>
                <a href="?action=approved&id=<?= $row['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                <a href="?action=granted&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Grant</a>
                <a href="?action=rejected&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
            </td>
        </tr>
        <?php endwhile; ?>

        </table>
        </div>

    </div>

</div>

<!-- MOBILE NAV -->
<div class="mobile-nav">
    <a href="index.html"><i class="fa fa-home"></i><span>Home</span></a>
    <a href="users.php"><i class="fa fa-users"></i><span>Users</span></a>
    <a href="grants.php"><i class="fa fa-clock"></i><span>Grants</span></a>
    <a href="../auth/logout.php"><i class="fa fa-sign-out"></i><span>Logout</span></a>
</div>

</body>
</html>