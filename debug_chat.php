<?php
include "../config/config.php";

$result = $conn->query("DESCRIBE chat_messages");

echo "<h3>chat_messages columns</h3>";

while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "<br>";
}
?>