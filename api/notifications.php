<?php
session_start();
include "../config/config.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['id'])) {
    echo json_encode([]);
    exit();
}

$data = [
    ["message" => "🎉 Your application was submitted"],
    ["message" => "💰 Wallet updated successfully"],
    ["message" => "📢 New grant available"],
    ["message" => "✅ Admin reviewed your profile"]
];

echo json_encode($data, JSON_UNESCAPED_UNICODE);