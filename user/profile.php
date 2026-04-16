<?php
session_start();
include "../config/config.php";

/* AUTH */
$user_id = $_SESSION['id'] ?? null;

if (!$user_id || ($_SESSION['role'] ?? '') !== "user") {
    header("Location: ../auth/login.php");
    exit();
}

/* GET USER */
$stmt = $conn->prepare("SELECT full_name, profile FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$name = $user['full_name'] ?? "";
$profile = $user['profile'] ?? "default.png";

/* UPDATE PROFILE */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $new_name = trim($_POST['full_name']);

    /* IMAGE UPLOAD */
    if (!empty($_FILES['profile']['name'])) {

        $file = $_FILES['profile'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png','webp'];

        if (in_array($ext, $allowed)) {

            $new_file = "user_" . $user_id . "_" . time() . "." . $ext;
            $path = "../uploads/" . $new_file;

            move_uploaded_file($file['tmp_name'], $path);

            /* UPDATE NAME + IMAGE */
            $stmt = $conn->prepare("UPDATE users SET full_name=?, profile=? WHERE id=?");
            $stmt->bind_param("ssi", $new_name, $new_file, $user_id);
            $stmt->execute();

        }

    } else {

        /* UPDATE ONLY NAME */
        $stmt = $conn->prepare("UPDATE users SET full_name=? WHERE id=?");
        $stmt->bind_param("si", $new_name, $user_id);
        $stmt->execute();
    }

    header("Location: profile.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Profile Settings</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6fb;
    font-family:Segoe UI;
}

/* CONTAINER */
.profile-box{
    max-width:500px;
    margin:40px auto;
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 10px 25px rgba(0,0,0,0.05);
}

/* IMAGE */
.profile-img{
    width:100px;
    height:100px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #22c55e;
}

/* BUTTON */
.btn-save{
    background:#22c55e;
    border:none;
}
.btn-save:hover{
    background:#16a34a;
}
</style>
</head>

<body>

<div class="profile-box text-center">

    <h4>👤 Profile Settings</h4>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">Profile updated successfully!</div>
    <?php endif; ?>

    <img src="../uploads/<?= $profile ?>" class="profile-img mb-3"
         onerror="this.src='../uploads/default.png'">

    <form method="POST" enctype="multipart/form-data">

        <div class="mb-3 text-start">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control"
                   value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <div class="mb-3 text-start">
            <label>Profile Picture</label>
            <input type="file" name="profile" class="form-control">
        </div>

        <button class="btn btn-save w-100">Update Profile</button>

    </form>

    <br>
    <a href="index.html" class="btn btn-secondary w-100">⬅ Back to Dashboard</a>

</div>

</body>
</html>