<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id'])) {
    exit("No access");
}

$user_id = $_SESSION['id'];
$admin_id = intval($_GET['user_id']);

$result = $conn->query("
    SELECT * FROM chat_messages ORDER BY id ASC
");

while ($row = $result->fetch_assoc()) {

    $msg = htmlspecialchars($row['message']);

    $sender = $row['sender_id'] ?? 0;
$receiver = $row['user_id'] ?? 0;

    if (
        ($sender == $user_id && $receiver == $admin_id) ||
        ($sender == $admin_id && $receiver == $user_id)
    ) {

        if ($sender == $admin_id) {
            echo "<div class='msg admin'>🟢 ADMIN: $msg</div>";
        } else {
            echo "<div class='msg user'>👤 YOU: $msg</div>";
        }
    }
}
?>