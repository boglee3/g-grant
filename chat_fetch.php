<?php
include "../config/config.php";

$result = $conn->query("SELECT * FROM chat_messages ORDER BY id ASC");

while($row = $result->fetch_assoc()) {

    if($row['sender_role'] == "admin"){
        echo "<div style='color:green;text-align:right;'>🟢 ADMIN: {$row['message']}</div>";
    } else {
        echo "<div style='color:black;'>👤 USER: {$row['message']}</div>";
    }
}
?>