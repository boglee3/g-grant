<?php
session_start();
include __DIR__ . "/../config/config.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$result = $conn->query("
    SELECT 
        g.id,
        u.full_name,
        u.email,
        g.program_name,
        g.amount,
        g.status,
        g.created_at
    FROM grants g
    JOIN users u ON g.user_id = u.id
    ORDER BY g.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Applications</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{
    background:#f4f6fb;
    font-family:'Segoe UI', sans-serif;
}

/* SIDEBAR (SAME SYSTEM) */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#0f172a;
    color:white;
    padding:20px;
    transition:0.3s;
}

.sidebar a{
    display:block;
    color:#cbd5e1;
    padding:10px;
    text-decoration:none;
    border-radius:8px;
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

/* BACK BUTTON */
.back-btn{
    display:inline-block;
    background:#0f172a;
    color:white;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
    margin-bottom:15px;
}

/* CARD */
.card-box{
    background:white;
    padding:15px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

/* DESKTOP TABLE */
.table{
    width:100%;
}

/* MOBILE CARD VIEW */
.app-card{
    display:none;
    background:#f8fafc;
    padding:12px;
    border-radius:12px;
    margin-bottom:10px;
    border-left:4px solid #22c55e;
}

.line{
    display:flex;
    justify-content:space-between;
    padding:4px 0;
}

.label{
    color:#22c55e;
    font-weight:bold;
}

/* MOBILE FIX */
@media(max-width:768px){

    .sidebar{
        position:relative;
        width:100%;
        height:auto;
    }

    .main{
        margin-left:0;
        padding:12px;
    }

    .table{
        display:none;
    }

    .app-card{
        display:block;
    }

    .back-btn{
        width:100%;
        text-align:center;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h4>👑 ADMIN PANEL</h4>
    <a href="index.html">Dashboard</a>
    <a href="users.php">Users</a>
    <a href="applications.php">Applications</a>
    <a href="manage_grants.php">Grants</a>
</div>

<!-- MAIN -->
<div class="main">

<a href="index.html" class="back-btn">⬅ Back</a>

<div class="card-box">

    <h4>📄 Applications</h4>

    <!-- DESKTOP TABLE -->
    <div class="table-responsive">
        <table class="table table-dark table-hover mt-3">
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Email</th>
                <th>Grant</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>

            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['program_name']) ?></td>
                <td>$<?= number_format($row['amount']) ?></td>
                <td><?= strtoupper($row['status']) ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
            <?php endwhile; ?>

        </table>
    </div>

    <!-- MOBILE CARDS -->
    <?php
    $result2 = $conn->query("
        SELECT 
            g.id,
            u.full_name,
            u.email,
            g.program_name,
            g.amount,
            g.status,
            g.created_at
        FROM grants g
        JOIN users u ON g.user_id = u.id
        ORDER BY g.id DESC
    ");

    while($row = $result2->fetch_assoc()):
    ?>

    <div class="app-card">

        <div class="line">
            <span class="label">ID</span>
            <span><?= $row['id'] ?></span>
        </div>

        <div class="line">
            <span class="label">User</span>
            <span><?= htmlspecialchars($row['full_name']) ?></span>
        </div>

        <div class="line">
            <span class="label">Email</span>
            <span><?= htmlspecialchars($row['email']) ?></span>
        </div>

        <div class="line">
            <span class="label">Grant</span>
            <span><?= htmlspecialchars($row['program_name']) ?></span>
        </div>

        <div class="line">
            <span class="label">Amount</span>
            <span>$<?= number_format($row['amount']) ?></span>
        </div>

        <div class="line">
            <span class="label">Status</span>
            <span><?= strtoupper($row['status']) ?></span>
        </div>

        <div class="line">
            <span class="label">Date</span>
            <span><?= $row['created_at'] ?></span>
        </div>

    </div>

    <?php endwhile; ?>

</div>

</div>

</body>
</html>