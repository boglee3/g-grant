<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id'])) {
    exit("No access");
}

$sender_id = $_SESSION['id'];
$receiver_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : "";

/* basic validation */
if ($receiver_id <= 0) {
    exit("Invalid receiver");
}

if ($message === "") {
    exit("Empty message");
}

/* insert message */
$stmt = $conn->prepare("
    INSERT INTO chat_messages (sender_id, user_id, message)
    VALUES (?, ?, ?)
");

$stmt->bind_param("iis", $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    echo "sent";
} else {
    echo "failed";
}
?>