<?php
session_start();
include "../config/config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(["balance" => 0]);
    exit();
}

$user_id = $_SESSION['id'];

/* GET WALLET */
$stmt = $conn->prepare("SELECT balance FROM wallet WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

/* IF NO WALLET */
if (!$row) {
    $balance = 1000;

    $insert = $conn->prepare("INSERT INTO wallet (user_id, balance) VALUES (?, ?)");
    $insert->bind_param("id", $user_id, $balance);
    $insert->execute();

    $row = ["balance" => $balance];
}

echo json_encode([
    "balance" => $row['balance']
]);