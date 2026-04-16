<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== "user") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

/*
=========================
GRANT PROGRAMS + FEES
=========================
*/
$programs = [

    ["name" => "Small Business Recovery Grant (USA)", "amount" => 10000, "fee" => 150],
    ["name" => "UK SME Growth Fund", "amount" => 15000, "fee" => 200],
    ["name" => "Canada Startup Support Grant", "amount" => 20000, "fee" => 250],
    ["name" => "EU Innovation Development Grant", "amount" => 25000, "fee" => 300],

    ["name" => "Youth Empowerment Fund", "amount" => 12000, "fee" => 120],
    ["name" => "Community Development Grant", "amount" => 14000, "fee" => 180],
    ["name" => "Women Empowerment Initiative", "amount" => 16000, "fee" => 200],

    ["name" => "Agricultural Support Grant", "amount" => 18000, "fee" => 220],
    ["name" => "Rural Farmers Assistance Program", "amount" => 22000, "fee" => 280],

    ["name" => "International Student Support Grant", "amount" => 13000, "fee" => 150],
    ["name" => "Higher Education Financial Aid Grant", "amount" => 17000, "fee" => 200],

    ["name" => "Disability Support Grant", "amount" => 15000, "fee" => 160],
    ["name" => "Paralysis Recovery Assistance Fund", "amount" => 20000, "fee" => 220],
    ["name" => "Medical Emergency Relief Grant", "amount" => 18000, "fee" => 200],
    ["name" => "Chronic Illness Support Program", "amount" => 16000, "fee" => 180],

    ["name" => "Affordable Housing Assistance Grant", "amount" => 25000, "fee" => 350],
    ["name" => "Home Relief Support Fund", "amount" => 20000, "fee" => 250],

    ["name" => "Global Development Assistance Grant", "amount" => 30000, "fee" => 400],
    ["name" => "UN Support Relief Fund", "amount" => 35000, "fee" => 500],

    ["name" => "Emergency Hardship Grant", "amount" => 12000, "fee" => 100],
    ["name" => "Low-Income Family Support Grant", "amount" => 14000, "fee" => 130]

];

$user_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $index = intval($_POST['program']);

    if (!isset($programs[$index])) {
        $message = "❌ Invalid selection";
    } else {

        $program = $programs[$index];

        $check = $conn->prepare("SELECT id FROM grants WHERE user_id=? AND status='pending'");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "⏳ You already have a pending application";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO grants (user_id, program_name, amount, status)
                VALUES (?, ?, ?, 'pending')
            ");

            $stmt->bind_param("isd",
                $user_id,
                $program['name'],
                $program['amount']
            );

            if ($stmt->execute()) {
                $message = "✅ APPLICATION COMPLETED for <b>{$program['name']}</b>";
            } else {
                $message = "❌ Error submitting application: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Apply Grant</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    margin:0;
    background:#0b1220;
    color:white;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial;
}

/* HEADER */
.header{
    text-align:center;
    padding:18px;
    font-size:1.4rem;
    font-weight:800;
    color:#22c55e;
}

/* BOX */
.box{
    max-width:750px;
    margin:auto;
    padding:15px;
}

/* MESSAGE */
.msg{
    background:#065f46;
    padding:12px;
    border-radius:10px;
    text-align:center;
    margin-bottom:15px;
    font-size:1rem;
}

/* SELECT */
select{
    width:100%;
    padding:16px;
    border-radius:12px;
    background:#111827;
    color:white;
    border:1px solid #374151;
    font-size:1rem;
}

/* FEE BOX */
.fee-box{
    margin-top:15px;
    padding:15px;
    background:#1f2937;
    border-radius:12px;
    font-size:1rem;
    border-left:4px solid #22c55e;
}

/* BUTTON */
button{
    width:100%;
    padding:16px;
    margin-top:15px;
    border:none;
    border-radius:12px;
    background:#22c55e;
    color:white;
    font-weight:700;
    font-size:1.05rem;
}

/* BACK */
a{
    display:block;
    text-align:center;
    margin-top:15px;
    color:#22c55e;
    text-decoration:none;
    font-size:1rem;
}

/* MOBILE FIX (IMPORTANT) */
@media (max-width:768px){

    body{
        font-size:14px;
    }

    .header{
        font-size:1.2rem;
        padding:14px;
    }

    select, button{
        font-size:0.95rem;
        padding:14px;
    }

    .fee-box{
        font-size:0.95rem;
    }
}
</style>
</head>

<body>

<div class="header">🌐Grant Portal</div>

<div class="box">

<?php if ($message) echo "<div class='msg'>$message</div>"; ?>

<form method="POST">

    <select name="program" id="programSelect" onchange="showFee()" required>
        <option value="">-- Choose Grant --</option>

        <?php foreach ($programs as $index => $p): ?>
            <option value="<?= $index ?>"
                data-amount="<?= $p['amount'] ?>"
                data-fee="<?= $p['fee'] ?>">
                <?= $p['name'] ?> — $<?= number_format($p['amount']) ?>
            </option>
        <?php endforeach; ?>

    </select>

    <div class="fee-box" id="feeBox">
        Select a grant to view details
    </div>

    <button type="submit">🚀 Apply Now</button>

</form>

<a href="index.html">← Back to Dashboard</a>

</div>

<script>
function showFee(){
    let select = document.getElementById("programSelect");
    let option = select.options[select.selectedIndex];

    let amount = option.getAttribute("data-amount");
    let fee = option.getAttribute("data-fee");

    if(amount && fee){
        document.getElementById("feeBox").innerHTML =
        "💰 Amount: $" + amount + "<br>⚙ Fee: $" + fee;
    } else {
        document.getElementById("feeBox").innerHTML =
        "Select a grant to view details";
    }
}
</script>

</body>
</html>