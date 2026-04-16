<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id'])) {
    exit("No access");
}
$admin_id = $_SESSION['id'];



/* --------------------------
   HANDLE MESSAGE SEND
---------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = intval($_POST['user_id']);
    $message = trim($_POST['message']);

    if ($message != '') {

        $stmt = $conn->prepare("
            INSERT INTO chat_messages (sender_id, user_id, message)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param("iis", $admin_id, $user_id, $message);
        $stmt->execute();

        echo "sent";
        exit();
    }

    exit("Empty message");
}

/* --------------------------
   GET USERS (FIXED MISSING QUERY)
---------------------------*/
$users = $conn->query("SELECT id, full_name FROM users");
?>

<!DOCTYPE html>
<html>
<head>
<title>Chat Panel</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#eef2f7;
    font-family:'Segoe UI', sans-serif;
}

.wrapper{
    max-width:1200px;
    margin:auto;
    padding:20px;
}

.topbar{
    background:white;
    padding:15px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
}

.grid{
    display:grid;
    grid-template-columns:220px 1fr;
    gap:15px;
    margin-top:20px;
}

.user-box{
    background:white;
    padding:10px;
    border-radius:12px;
    height:70vh;
    overflow-y:auto;
}

.user-item{
    padding:8px;
    background:#f8fafc;
    margin-bottom:6px;
    border-radius:8px;
    cursor:pointer;
    border-left:3px solid #22c55e;
    font-size:13px;
}

.chat-box{
    background:white;
    padding:15px;
    border-radius:12px;
    display:flex;
    flex-direction:column;
    height:70vh;
}

.messages{
    flex:1;
    overflow-y:auto;
    background:#f8fafc;
    padding:10px;
    border-radius:10px;
}

.msg{
    padding:8px;
    margin-bottom:8px;
    border-radius:8px;
    max-width:70%;
}

.admin{
    background:#0f172a;
    color:white;
    margin-left:auto;
}

.user{
    background:#e2e8f0;
}

.input-box{
    display:flex;
    gap:10px;
    margin-top:10px;
}

input{
    flex:1;
    padding:10px;
}

button{
    background:#22c55e;
    border:none;
    color:white;
    padding:10px 15px;
}
</style>
</head>

<body>

<div class="wrapper">

<div class="topbar">
    <h5>💬 Chat Panel</h5>
    <a href="index.html">⬅ Dashboard</a>
</div>

<div class="grid">

    <!-- USERS -->
    <div class="user-box">
        <h6>Users</h6>

        <?php while($u = $users->fetch_assoc()): ?>
            <div class="user-item" onclick="openChat(<?= $u['id'] ?>)">
                <?= htmlspecialchars($u['full_name']) ?>
            </div>
        <?php endwhile; ?>

    </div>

    <!-- CHAT -->
    <div class="chat-box">

        <h6>Conversation</h6>

        <div class="messages" id="messages">
            Select a user to start chat...
        </div>

        <form class="input-box" onsubmit="sendMessage(event)">
            <input type="hidden" name="user_id" id="user_id">
            <input type="text" name="message" id="message" required>
            <button type="submit">Send</button>
        </form>

    </div>

</div>

</div>

<script>

let currentUser = null;

function openChat(id){
    currentUser = id;
    document.getElementById("user_id").value = id;
    loadChat();
}

function loadChat(){
    if(!currentUser) return;

    fetch("chat_fetch.php?user_id=" + currentUser)
    .then(res => res.text())
    .then(data => {
        document.getElementById("messages").innerHTML = data;
    });
}
function sendMessage(e){
    e.preventDefault();

    if(!currentUser){
        alert("Select a user first");
        return;
    }

    let formData = new FormData();
    formData.append("user_id", currentUser);
    formData.append("message", document.getElementById("message").value);

    fetch("chat.php", {
        method: "POST",
        body: formData
    }).then(() => {
        document.getElementById("message").value = "";
        loadChat();
    });
}

</script>

</body>
</html>