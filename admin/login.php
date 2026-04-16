<?php
session_start();
include "../config/config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email === "" || $password === "") {
        $message = "❌ Fill all fields";
    } else {

        $stmt = $conn->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {

            $admin = $result->fetch_assoc();

            // password check (supports old + new system)
            $valid =
                password_verify($password, $admin['password']) ||
                $admin['password'] === md5($password);

            if ($valid) {

                // 🔥 FIXED SESSION (consistent with dashboard)
                $_SESSION['id'] = $admin['id'];
                $_SESSION['username'] = $admin['username']; // FIXED
                $_SESSION['role'] = "admin";

                header("Location: ../admin/index.html");
                exit();

            } else {
                $message = "❌ Wrong password";
            }

        } else {
            $message = "❌ Admin not found";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#0f172a;
    font-family:'Segoe UI', sans-serif;
}

/* LOGIN CARD */
.login-box{
    width:100%;
    max-width:400px;
    background:white;
    padding:30px;
    border-radius:16px;
    box-shadow:0 10px 30px rgba(0,0,0,0.2);
}

/* TITLE */
.login-box h2{
    text-align:center;
    margin-bottom:20px;
    font-weight:800;
    color:#0f172a;
}

/* INPUT */
.form-control{
    padding:12px;
    border-radius:10px;
}

/* BUTTON */
.btn-login{
    background:#22c55e;
    border:none;
    padding:12px;
    font-weight:bold;
    border-radius:10px;
}

.btn-login:hover{
    background:#16a34a;
}

/* MESSAGE */
.alert{
    font-size:14px;
    border-radius:10px;
}
</style>
</head>

<body>

<div class="login-box">

    <h2>👑 Admin Login</h2>

    <?php if($message): ?>
        <div class="alert alert-danger text-center">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="email" name="email" class="form-control mb-3"
               placeholder="Admin Email" required>

        <input type="password" name="password" class="form-control mb-3"
               placeholder="Password" required>

        <button class="btn btn-login w-100">Login</button>

    </form>

</div>

</body>
</html>