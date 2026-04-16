<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] != "admin") {
    exit("unauthorized");
}

$app_id = $_POST['id'];
$status = $_POST['status'];

/* update application */
$stmt = $conn->prepare("UPDATE applications SET status=? WHERE id=?");
$stmt->bind_param("si", $status, $app_id);
$stmt->execute();

/* if approved → add wallet money */
if ($status == "approved") {

    $app = $conn->query("SELECT user_id FROM applications WHERE id=$app_id")->fetch_assoc();
    $user_id = $app['user_id'];

    $amount = rand(1000, 5000);

    $conn->query("UPDATE wallet SET balance = balance + $amount WHERE user_id=$user_id");

    $conn->query("INSERT INTO transactions(user_id, amount, type, status)
    VALUES ($user_id, $amount, 'grant', 'success')");
}

echo "ok";