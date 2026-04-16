<?php
include "config/config.php";

$email = "hammed123aa@gmail.com";

// check if already admin
$check = $conn->prepare("SELECT id FROM admins WHERE email=?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("Already an admin 👑");
}

// move user into admin table
$user = $conn->prepare("SELECT full_name, email, password FROM users WHERE email=?");
$user->bind_param("s", $email);
$user->execute();
$result = $user->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("User not found");
}

// insert into admin table
$stmt = $conn->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $data['full_name'], $data['email'], $data['password']);

if ($stmt->execute()) {
    echo "You are now admin 👑";
} else {
    echo "Failed to promote user";
}
?>