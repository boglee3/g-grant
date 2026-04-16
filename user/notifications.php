<?php
session_start();
include __DIR__ . "/../config/config.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Notifications</title>

<style>
body{
    margin:0;
    font-family:system-ui;
    background:#050816;
    color:#e5e7eb;
}

.container{
    max-width:900px;
    margin:auto;
    padding:20px;
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#111827;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    background:#111827;
    border-radius:12px;
    overflow:hidden;
}

th, td{
    padding:14px;
    text-align:left;
    border-bottom:1px solid #1f2937;
}

th{
    background:#1f2937;
    color:#fbbf24;
}

/* BADGE STYLE */
.badge{
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}

/* TYPE COLORS */
.success{background:#16a34a;color:white;}
.info{background:#2563eb;color:white;}
.warning{background:#f59e0b;color:black;}

/* EMPTY STATE */
.empty{
    text-align:center;
    padding:20px;
    color:#9ca3af;
}

/* RESPONSIVE */
@media(max-width:768px){
    table, th, td{
        font-size:14px;
    }
}
</style>
</head>

<body>

<div class="container">

<div class="header">
    <h2>🔔 Notifications</h2>
    <a href="index.html" style="color:#22c55e;text-decoration:none;">← Back</a>
</div>

<table id="notifTable">
    <tr>
        <th>Message</th>
        <th>Type</th>
        <th>Status</th>
    </tr>

    <tr>
        <td colspan="3" class="empty">Loading notifications...</td>
    </tr>
</table>

</div>

<script>
function loadNotifications(){
    fetch("../api/notifications.php")
    .then(res => res.json())
    .then(data => {

        let table = document.getElementById("notifTable");

        if(!data || data.length === 0){
            table.innerHTML = `
                <tr>
                    <th>Message</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
                <tr><td colspan="3" class="empty">No notifications found</td></tr>
            `;
            return;
        }

        let html = `
            <tr>
                <th>Message</th>
                <th>Type</th>
                <th>Status</th>
            </tr>
        `;

        data.forEach(n => {

            let type = "info";
            if(n.message.includes("Wallet")) type = "success";
            if(n.message.includes("submitted")) type = "info";
            if(n.message.includes("reviewed")) type = "warning";

            html += `
                <tr>
                    <td>${n.message}</td>
                    <td><span class="badge ${type}">${type.toUpperCase()}</span></td>
                    <td>Unread</td>
                </tr>
            `;
        });

        table.innerHTML = html;
    });
}

loadNotifications();
setInterval(loadNotifications, 5000);
</script>

</body>
</html>