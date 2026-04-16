 <?php
session_start();
include "../config/config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // =========================
    // ADMIN LOGIN
    // =========================
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email=? AND password=MD5(?)");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $adminRes = $stmt->get_result();

    if ($adminRes && $adminRes->num_rows > 0) {

        $admin = $adminRes->fetch_assoc();

        $_SESSION['id'] = $admin['id'];
        $_SESSION['name'] = $admin['username'];
        $_SESSION['role'] = "admin";

        header("Location: ../admin/index.html");
        exit();
    }

    // =========================
    // USER LOGIN
    // =========================
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userRes = $stmt->get_result();

    if ($userRes && $userRes->num_rows > 0) {

        $user = $userRes->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            // reset attempts safely
            $reset = $conn->prepare("UPDATE users SET login_attempts=0 WHERE id=?");
            $reset->bind_param("i", $user['id']);
            $reset->execute();

            $_SESSION['id'] = $user['id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['role'] = "user";

            header("Location: ../user/index.html");
            exit();

        } else {

            $update = $conn->prepare("
                UPDATE users 
                SET login_attempts = login_attempts + 1, last_attempt=NOW()
                WHERE id=?
            ");
            $update->bind_param("i", $user['id']);
            $update->execute();

            $error = "Invalid email or password ❌";
        }

    } else {
        $error = "Account not found ❌";
    }
}
?>    
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial;}
body{
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg,#0f172a,#1e293b);
}
.box{
    width:100%;
    max-width:380px;
    background:#111827;
    padding:30px;
    border-radius:12px;
    color:white;
}
input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border:none;
    border-radius:8px;
    background:#1f2937;
    color:white;
}
button{
    width:100%;
    padding:12px;
    background:#22c55e;
    border:none;
    border-radius:8px;
    color:white;
}
.error{
    background:#7f1d1d;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
}
a{color:#22c55e;}
</style>
</head>

<body>

<div class="box">

<h2>Welcome Back</h2>

<?php if($error) echo "<div class='error'>$error</div>"; ?>

<form method="POST">
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

<div style="text-align:center;margin-top:10px;">
    Don’t have an account?
    <a href="register.php">Register</a>
</div>

</div>

</body>
</html>