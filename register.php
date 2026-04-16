<?php
session_start();
include "../config/config.php";
include "../config/email_helper.php"; // ← ADD THIS LINE (LINE 4)

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Passwords do not match ❌";
    } else {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Email already exists ❌";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO users (full_name, email, phone, password, balance)
                VALUES (?, ?, ?, ?, 0)
            ");

            $stmt->bind_param("ssss", $name, $email, $phone, $passwordHash);

            if ($stmt->execute()) {

                // ← ADD THIS LINE (sends email to your Gmail)
                emailNewUser($name, $email, $phone);

                // redirect to LOGIN page
                header("Location: login.php?success=1");
                exit();

            } else {
                $message = "Error creating account ❌";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - G-Grant</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg, #0f172a, #020617);
}

.box{
    width:100%;
    max-width:420px;
    background: rgba(17, 24, 39, 0.92);
    padding:35px;
    border-radius:18px;
    box-shadow:0 20px 40px rgba(0,0,0,0.6);
    color:white;
}

h2{
    text-align:center;
    margin-bottom:10px;
}

.subtitle{
    text-align:center;
    font-size:13px;
    color:#9ca3af;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:14px;
    margin:8px 0;
    border-radius:10px;
    border:none;
    outline:none;
    background:#1f2937;
    color:white;
}

button{
    width:100%;
    padding:14px;
    background:#22c55e;
    border:none;
    border-radius:10px;
    font-weight:bold;
    color:white;
    cursor:pointer;
}

button:hover{
    background:#16a34a;
}

.message{
    background:#7f1d1d;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    text-align:center;
}

.link{
    text-align:center;
    margin-top:15px;
}

.link a{
    color:#22c55e;
    text-decoration:none;
}
</style>
</head>

<body>

<div class="box">

    <h2>Create Account 🚀</h2>
    <div class="subtitle">Join G-Grant Portal</div>

    <?php if ($message) echo "<div class='message'>$message</div>"; ?>

    <form method="POST">

        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="phone" placeholder="Phone Number">

        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>

        <button type="submit">Create Account</button>

    </form>

    <div class="link">
        Already have an account? <a href="login.php">Login</a>
    </div>

</div>

</body>
</html>