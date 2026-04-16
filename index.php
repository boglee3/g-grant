<?php
session_start();
include "../config/config.php";

/* AUTH */
$user_id = $_SESSION['id'] ?? null;

if (!$user_id || ($_SESSION['role'] ?? '') !== "user") {
    header("Location: ../auth/login.php");
    exit();
}

/* USER DATA */
$stmt = $conn->prepare("SELECT full_name, balance, profile FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$name = $user['full_name'] ?? "User";
$balance = $user['balance'] ?? 0;

$profile = $user['profile'] ?? "default.png";

/* GRANTS */
$grants = $conn->query("SELECT * FROM grants WHERE user_id=$user_id ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>G-Grant | Dashboard</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{
    margin:0;
    font-family:Segoe UI;
    background:#f4f6fb;
}

/* SIDEBAR */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    left:0;
    top:0;
    background:#0f172a;
    color:white;
    padding:20px;
}

.sidebar a{
    display:block;
    padding:10px;
    margin:6px 0;
    color:#cbd5e1;
    text-decoration:none;
    border-radius:8px;
}
.sidebar a:hover{background:#1e293b;color:white;}

.profile{text-align:center;margin-bottom:20px;}
.profile img{
    width:70px;height:70px;border-radius:50%;
    border:2px solid #22c55e;
}

/* MAIN */
.main{
    margin-left:260px;
    padding:20px;
    min-height:100vh;
}

/* TOPBAR */
.topbar{
    background:white;
    padding:15px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

/* USER */
.user-info{
    display:flex;
    align-items:center;
    gap:10px;
}

.user-avatar{
    width:40px;height:40px;border-radius:50%;
    border:2px solid #22c55e;
}

/* BALANCE */
.balance-box{
    background:#0f172a;
    color:white;
    padding:10px 15px;
    border-radius:10px;
}
.balance-box span{color:#22c55e;}

/* CARDS */
.card-box{
    background:white;
    padding:15px;
    border-radius:12px;
    margin-top:15px;
}

/* LIVE FEED */
.feed{
    background:#f8fafc;
    padding:10px;
    border-left:3px solid #22c55e;
    margin-top:8px;
    border-radius:8px;
}

/* MOBILE FIX */
@media(max-width:768px){

    .sidebar{display:none;}

    .main{
        margin-left:0;
        padding:15px;
        padding-bottom:90px;
    }

    /* STACK TOPBAR */
    .topbar{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
    }

    /* MAKE EACH LINE SEPARATE */
    .topbar-title{
        width:100%;
        font-size:18px;
        font-weight:bold;
    }

    .user-info{
        width:100%;
        background:#f1f5f9;
        padding:10px;
        border-radius:10px;
    }

    .balance-box{
        width:100%;
        text-align:center;
        font-size:18px;
    }
}

/* MOBILE NAV */
.mobile-nav{
    display:none;
    position:fixed;
    bottom:0;
    left:0;
    right:0;
    background:#0f172a;
    padding:10px 0;
    justify-content:space-around;
}

.mobile-nav a{
    color:#94a3b8;
    font-size:12px;
    text-decoration:none;
    display:flex;
    flex-direction:column;
    align-items:center;
}

.mobile-nav a.active,
.mobile-nav a:hover{color:#22c55e;}

@media(max-width:768px){
    .mobile-nav{display:flex;}
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h4>💰 G-Grant</h4>

    <div class="profile">
        <img src="../uploads/<?= $profile ?>">
        <div><?= htmlspecialchars($name) ?></div>
    </div>

    <a href="#">Dashboard</a>
    <a href="profile.php">Profile Settings</a>
    <a href="apply.php">Apply</a>
    <a href="withdraw.php">Withdraw</a>
    <a href="transactions.php">Transactions</a>
    <a href="chat.php">Chat</a>
    <a href="../auth/logout.php" style="color:red;">Logout</a>

</div>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">

        <div class="topbar-title">G-Grant Dashboard</div>

        <div class="user-info">
            <img src="../uploads/<?= htmlspecialchars($profile) ?>" 
     onerror="this.src='../uploads/default.png'" class="user-avatar">
                 <div>
                <b><?= htmlspecialchars($name) ?></b><br>
                <small>Welcome back 👋</small>
            </div>
        </div>

        <div class="balance-box">
            Balance: <span>$<?= number_format($balance) ?></span>
        </div>

    </div>

    <!-- ACTIONS -->
    <div class="card-box">
        <h5>Wallet Actions</h5>
        <a href="apply.php" class="btn btn-success w-100">Apply for Grant</a><br><br>
        <a href="withdraw.php" class="btn btn-warning w-100">Withdraw</a>
    </div>

    <!-- GRANTS -->
    <div class="card-box">
        <h5>Your Grants</h5>

        <?php while($g = $grants->fetch_assoc()): ?>
            <div class="border p-2 mt-2">
                <b><?= $g['program_name'] ?></b><br>
                $<?= number_format($g['amount']) ?><br>
                <span><?= strtoupper($g['status']) ?></span>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- LIVE FEED -->
    <div class="card-box">
        <h5>🔥 Live Payment Feed</h5>
        <div id="liveFeed"></div>
    </div>

</div>

<!-- MOBILE NAV -->
<div class="mobile-nav">
    <a href="#" class="active"><i class="fa fa-home"></i><span>Home</span></a>
    <a href="apply.php"><i class="fa fa-hand-holding-dollar"></i><span>Apply</span></a>
    <a href="profile.php"><i class="fa fa-user"></i><span>Profile</span></a>
    <a href="withdraw.php"><i class="fa fa-wallet"></i><span>Withdraw</span></a>
    <a href="transactions.php"><i class="fa fa-exchange-alt"></i><span>History</span></a>
</div>

<script>
let names = [
"James Smith (UK)","Michael Brown (USA)","David Johnson (Canada)",
"Chris Wilson (Germany)","Daniel Miller (Australia)","John Williams (USA)",
"Robert Jones (UK)","William Garcia (Spain)","Joseph Martinez (Mexico)",
"Charles Anderson (USA)","Thomas Taylor (UK)","Henry Moore (Canada)",
"Matthew Jackson (Australia)","Andrew White (South Africa)","Joshua Harris (USA)",
"Samuel Clark (UK)","Benjamin Lewis (Germany)","Ethan Walker (USA)",
"Alexander Hall (Canada)","Ryan Allen (Australia)","Nathan Young (USA)",
"Isaac King (UK)","Gabriel Wright (USA)","Caleb Scott (Canada)",
"Logan Green (Australia)","Owen Adams (USA)","Liam Baker (UK)",
"Noah Nelson (USA)","Mason Carter (Canada)","Elijah Mitchell (Australia)",
"Lucas Perez (Spain)","Aiden Roberts (USA)","Sebastian Turner (UK)",
"Jack Phillips (USA)","Leo Campbell (Canada)","Julian Parker (Australia)",
"Isaiah Evans (USA)","Aaron Edwards (UK)","Connor Collins (USA)",
"Dominic Stewart (Canada)","Brandon Sanchez (USA)","Tyler Morris (UK)",
"Zachary Rogers (USA)","Kevin Reed (Canada)","Jason Cook (Australia)",
"Eric Morgan (USA)","Adam Bell (UK)","Brian Murphy (USA)",
"Jonathan Bailey (Canada)","Justin Rivera (USA)","Kyle Cooper (UK)",
"Jordan Richardson (USA)","Dylan Cox (Canada)","Austin Howard (USA)",
"Trevor Ward (UK)","Sean Torres (USA)","Victor Peterson (Canada)",
"Diego Gray (USA)","Luis Ramirez (Mexico)","Carlos Flores (Spain)",
"Jorge Gutierrez (Mexico)","Hugo Alvarez (Spain)","Marco Delgado (Italy)",
"Antonio Russo (Italy)","Luca Romano (Italy)","Giovanni Ferrari (Italy)",
"Mateo Silva (Brazil)","Lucas Santos (Brazil)","Pedro Costa (Brazil)",
"Rafael Oliveira (Brazil)","Thiago Pereira (Brazil)","Andre Souza (Brazil)",
"Felix Schmidt (Germany)","Jonas Weber (Germany)","Leon Wagner (Germany)",
"Maximilian Becker (Germany)","Finn Schulz (Germany)","Erik Hoffmann (Germany)",
"Oliver Hansen (Denmark)","Noah Johansen (Denmark)","Elias Olsen (Norway)",
"William Berg (Sweden)","Lucas Eriksson (Sweden)","Oscar Larsson (Sweden)",
"Arjun Sharma (India)","Ravi Patel (India)","Amit Kumar (India)",
"Vikram Singh (India)","Sanjay Gupta (India)","Rahul Verma (India)",
"Chen Wei (China)","Li Ming (China)","Zhang Lei (China)",
"Wang Jun (China)","Liu Yang (China)","Hassan Ali (UAE)",
"Omar Hassan (Egypt)","Ahmed Mohamed (Egypt)","Yusuf Ibrahim (Nigeria)",
"Chinedu Okafor (Nigeria)","Ibrahim Musa (Nigeria)","Tunde Adebayo (Nigeria)"
];

let banks = [
"PayPal","Cash App","Zelle","Venmo","Stripe",
"Wise","Revolut","Skrill","Payoneer","Apple Pay",
"Google Pay","Chime","N26","Monzo","Ally Bank",
"Bank Transfer","Crypto Wallet","Flutterwave","Paystack","Western Union"
];

/* ICON MAPPING (NEW) */
let bankIcons = {
    "PayPal":"💙",
    "Cash App":"💵",
    "Zelle":"⚡",
    "Venmo":"💳",
    "Stripe":"💜",
    "Wise":"🌍",
    "Revolut":"🏦",
    "Skrill":"💰",
    "Payoneer":"🟠",
    "Apple Pay":"🍎",
    "Google Pay":"🅶",
    "Chime":"🎵",
    "N26":"🏛️",
    "Monzo":"🔶",
    "Ally Bank":"🏦",
    "Bank Transfer":"🏦",
    "Crypto Wallet":"🪙",
    "Flutterwave":"🌊",
    "Paystack":"📦",
    "Western Union":"🌎"
};

function livePayment(){
    const box = document.getElementById("liveFeed");

    let amount = Math.floor(Math.random() * (35000 - 10000 + 1)) + 10000;
    let name = names[Math.floor(Math.random()*names.length)];
    let bank = banks[Math.floor(Math.random()*banks.length)];
    let icon = bankIcons[bank] || "💸";

    let div = document.createElement("div");
    div.className = "feed";

    div.style.animation = "fadeIn 0.4s ease";

    div.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <b style="color:#16a34a;">$${amount.toLocaleString()}</b> sent to <b>${name}</b>
            </div>
            <div>${icon}</div>
        </div>
        <small style="color:#64748b;">
            via ${bank} • ${new Date().toLocaleTimeString()}
        </small>
    `;

    box.prepend(div);

    if(box.children.length > 7){
        box.removeChild(box.lastChild);
    }
}

/* SMOOTHER INTERVAL */
setInterval(livePayment, 2200);

/* SIMPLE ANIMATION */
const style = document.createElement('style');
style.innerHTML = `
@keyframes fadeIn {
    from {opacity:0; transform:translateY(-5px);}
    to {opacity:1; transform:translateY(0);}
}`;
document.head.appendChild(style);
</script>

</body>
</html