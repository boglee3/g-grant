<?php 
session_start();
include "../config/config.php";

if (!isset($_SESSION['id'])) {
    exit("No access");
}

$user_id = intval($_GET['user_id'] ?? 0);
$admin_id = $_SESSION['id'];

$result = $conn->query("
    SELECT * 
    FROM chat_messages 
    ORDER BY id ASC
");

while ($row = $result->fetch_assoc()) {

    $message = htmlspecialchars($row['message'] ?? '');

    $sender = $row['sender_id'] ?? 0;
    $target = $row['user_id'] ?? 0;

    // SAME CONNECTION LOGIC (NOW MATCHES USER SIDE)
    if (
        ($sender == $admin_id && $target == $user_id) ||
        ($sender == $user_id && $target == $admin_id)
    ) {

        if ($sender == $admin_id) {
            echo "<div class='msg admin'>🟢 ADMIN: {$message}</div>";
        } else {
            echo "<div class='msg user'>👤 USER: {$message}</div>";
        }
    }
}
?>