<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];
$name = $_SESSION['name'];
$admin_id = 1; // adjust if your admin id is different
?>

<!DOCTYPE html>
<html>
<head>
<title>User Chat</title>

<style>
body{
    font-family: Arial;
    background:#f1f5f9;
}

.chat-box{
    width:90%;
    max-width:700px;
    margin:30px auto;
    background:white;
    padding:15px;
    border-radius:10px;
}

.messages{
    height:400px;
    overflow-y:auto;
    background:#f8fafc;
    padding:10px;
    border-radius:8px;
}

.msg{
    margin:8px 0;
    padding:8px;
    border-radius:8px;
    max-width:70%;
}

.admin{
    background:#0f172a;
    color:white;
}

.user{
    background:#e2e8f0;
    margin-left:auto;
    text-align:right;
}

.input{
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
    color:white;
    border:none;
    padding:10px 15px;
}
</style>
</head>

<body>

<div class="chat-box">

<h3>Chat with Admin</h3>

<div class="messages" id="messages">
Loading...
</div>

<div class="input">
    <input type="text" id="message" placeholder="Type message...">
    <button onclick="sendMsg()">Send</button>
</div>

</div>

<script>

let admin_id = <?php echo $admin_id; ?>;

function loadChat(){
   fetch("chat_fetch.php?user_id=" + admin_id)
    .then(res => res.text())
    .then(data => {
        document.getElementById("messages").innerHTML = data;
    });
}

function sendMsg(){
    let msg = document.getElementById("message").value;

    fetch("send_message.php", {
        method: "POST",
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: "user_id=" + admin_id + "&message=" + msg
    }).then(() => {
        document.getElementById("message").value = "";
        loadChat();
    });
}

setInterval(loadChat, 2000);
loadChat();

</script>

</body>
</html>