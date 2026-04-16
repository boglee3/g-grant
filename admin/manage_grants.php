<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

/* ADD GRANT */
if(isset($_POST['add'])){
    $title = $_POST['title'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("
        INSERT INTO grants (program_name, amount, status)
        VALUES (?, ?, 'program')
    ");
    $stmt->bind_param("sd", $title, $amount);
    $stmt->execute();
}

/* GET GRANTS */
$result = $conn->query("SELECT * FROM grants WHERE status='program' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Grants</title>

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

/* MOBILE */
@media(max-width:768px){
    h3{font-size:20px;}
    .card{font-size:14px;}
    .btn{font-size:13px;padding:8px 12px;}

    .back-btn{
        width:100%;
        text-align:center;
        font-size:15px;
        padding:10px;
    }
}
</style>
</head>

<body>

<div class="container">

<!-- BACK BUTTON -->
<a href="index.html" class="back-btn">⬅ Back to Dashboard</a>

<h3>💰 Manage Grant Programs</h3>

<form method="POST" class="card bg-dark p-3 mb-3">
    <input name="title" class="form-control mb-2" placeholder="Grant Title" required>
    <input name="amount" class="form-control mb-2" placeholder="Amount" required>
    <button name="add" class="btn btn-warning">Add Program</button>
</form>

<?php while($g=$result->fetch_assoc()): ?>
<div class="card bg-dark p-3 mb-2">
    <b><?= htmlspecialchars($g['program_name']) ?></b>
    <p>$<?= number_format($g['amount']) ?></p>
</div>
<?php endwhile; ?>

</div>

</body>
</html>