<?php
session_start();
include "../config/config.php";

// CHECK LOGIN SAFELY
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
include "../includes/auth.php";
include "../config/config.php";

$user_id = $_SESSION['user_id'];

// GET APPLICATIONS WITH GRANT NAME (IMPORTANT FIX)
$stmt = $conn->prepare("
    SELECT applications.*, grants.title, grants.category, grants.amount 
    FROM applications 
    LEFT JOIN grants ON applications.grant_id = grants.id 
    WHERE applications.user_id = ?
    ORDER BY applications.id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Applications</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            margin: 0;
        }

        .container {
            width: 90%;
            margin: auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .status {
            font-weight: bold;
        }

        .pending { color: orange; }
        .approved { color: green; }
        .rejected { color: red; }
    </style>
</head>

<body>

<div class="container">

<h2>📄 My Applications</h2>

<?php
if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {
?>

<div class="card">

    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
    <p><b>Category:</b> <?php echo htmlspecialchars($row['category']); ?></p>
    <p><b>Amount:</b> ₦<?php echo htmlspecialchars($row['amount']); ?></p>

    <p class="status <?php echo $row['status']; ?>">
        Status: <?php echo strtoupper($row['status']); ?>
    </p>

    <small>Submitted: <?php echo $row['submitted_at']; ?></small>

</div>

<?php
    }

} else {
    echo "<div class='card'>No applications found yet.</div>";
}
?>

</div>

</body>
</html>