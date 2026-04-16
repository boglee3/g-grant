<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'];

/* ADMIN SEE ALL */
if ($role === "admin") {
    $result = $conn->query("
        SELECT t.*, u.full_name, u.email
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.id DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT * FROM transactions 
        WHERE user_id=? 
        ORDER BY id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Helper function to format payment method display
function formatPaymentMethod($method, $row) {
    switch($method) {
        case 'bank':
            return "🏦 Bank: " . ($row['bank_name'] ?? 'N/A');
        case 'crypto':
            return "₿ " . ($row['crypto_type'] ?? 'USDT') . " | " . substr($row['crypto_wallet'] ?? '', 0, 10) . "...";
        case 'cashapp':
            return "💵 CashApp: " . ($row['cashapp_tag'] ?? 'N/A');
        case 'paypal':
            return "💳 PayPal: " . ($row['paypal_email'] ?? 'N/A');
        case 'venmo':
            return "💸 Venmo: @" . ($row['venmo_username'] ?? 'N/A');
        case 'zelle':
            $zelle = $row['zelle_email'] ?? $row['zelle_phone'] ?? 'N/A';
            return "⚡ Zelle: " . $zelle;
        case 'giftcard':
            return "🎁 Gift Card: " . strtoupper($row['gift_card_type'] ?? 'N/A');
        default:
            return ucfirst($method);
    }
}

// Helper function to format fee payment method
function formatFeePayment($method, $details) {
    if (empty($details)) return ucfirst($method);
    
    // Mask sensitive info for display
    $masked = $details;
    if (strpos($details, 'Code:') !== false) {
        $masked = preg_replace('/Code:\s*\S+/', 'Code: ****', $details);
    }
    return ucfirst($method) . " - " . nl2br(htmlspecialchars(substr($masked, 0, 50)));
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Transactions | G-Grant</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
}

html {
    font-size: 18px;
}

body{
    background:#f4f6fb;
    font-family:'Segoe UI', sans-serif;
    margin:0;
    padding:0;
    line-height: 1.6;
}

/* SIDEBAR - Desktop */
.sidebar{
    width:280px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#0f172a;
    color:white;
    padding:25px;
    transition:0.3s;
    z-index:1000;
    overflow-y:auto;
}

.sidebar a{
    display:flex;
    gap:12px;
    padding:14px;
    color:#cbd5e1;
    text-decoration:none;
    border-radius:10px;
    margin-bottom:10px;
    align-items:center;
    font-size: 16px;
    font-weight: 500;
}

.sidebar a:hover, .sidebar a.active{
    background:#1e293b;
    color:white;
}

/* MAIN */
.main{
    margin-left:280px;
    padding:25px;
    transition:0.3s;
    min-height:100vh;
}

/* HEADER BAR */
.header-bar{
    background:white;
    padding:20px 25px;
    border-radius:16px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
    margin-bottom:25px;
}

/* SITE NAME HEADER */
.site-header{
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e2e8f0;
}

.site-logo{
    font-size: 36px;
    font-weight: 900;
    background:linear-gradient(135deg, #22c55e 0%, #16a34a 50%, #15803d 100%);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
    letter-spacing: -1px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.site-tagline{
    font-size: 14px;
    color: #64748b;
    margin-top: 5px;
    font-weight: 500;
}

/* TOP BAR - SIMPLIFIED */
.topbar{
    display:flex;
    justify-content:center;
    align-items:center;
    flex-wrap:wrap;
    gap:20px;
}

.back-btn{
    background:#0f172a;
    color:white;
    border:none;
    padding:14px 28px;
    border-radius:12px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:10px;
    font-size:15px;
    font-weight:700;
    transition:all 0.2s;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.back-btn:hover{
    background:#1e293b;
    color:white;
    transform:translateX(-3px);
}

/* CARD BOX */
.card-box{
    background:white;
    border-radius:20px;
    padding:30px;
    box-shadow:0 8px 25px rgba(0,0,0,0.06);
    margin-bottom:25px;
}

.card-box h5{
    color:#111827;
    font-weight:800;
    margin-bottom:25px;
    font-size:22px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* TRANSACTION CARDS - BIGGER */
.tx-card{
    background:#f8fafc;
    border-radius:16px;
    padding:28px;
    margin-bottom:20px;
    border:2px solid #e2e8f0;
    transition:all 0.2s;
}

.tx-card:hover{
    transform:translateY(-3px);
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
    border-color:#cbd5e1;
}

.tx-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    flex-wrap:wrap;
    gap:15px;
}

.tx-type{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:800;
    font-size:18px;
    text-transform:uppercase;
    letter-spacing:1px;
}

.tx-type.deposit{color:#16a34a;}
.tx-type.withdraw{color:#dc2626;}

.tx-amount{
    font-size:32px;
    font-weight:900;
}

.tx-amount.deposit{color:#16a34a;}
.tx-amount.withdraw{color:#dc2626;}

.tx-details{
    display:grid;
    grid-template-columns:repeat(2, 1fr);
    gap:20px;
    margin-top:20px;
    padding-top:20px;
    border-top:2px solid #e2e8f0;
}

.tx-detail{
    display:flex;
    flex-direction:column;
}

.tx-label{
    font-size:14px;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:0.5px;
    margin-bottom:6px;
    font-weight: 700;
}

.tx-value{
    font-size:17px;
    color:#111827;
    font-weight:700;
    word-break: break-word;
}

/* BADGES - BIGGER */
.badge{
    padding:10px 18px;
    border-radius:25px;
    font-size:13px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing: 1px;
}

.badge.pending{background:#fef3c7;color:#92400e;}
.badge.approved{background:#d1fae5;color:#065f46;}
.badge.rejected{background:#fee2e2;color:#991b1b;}

/* PROOF IMAGE */
.proof-image{
    max-width:250px;
    max-height:180px;
    border-radius:12px;
    cursor:pointer;
    border:3px solid #e2e8f0;
    margin-top:15px;
}

.proof-image:hover{
    border-color:#22c55e;
}

/* ADMIN ACTIONS - BIGGER */
.admin-actions{
    margin-top:20px;
    padding-top:20px;
    border-top:2px solid #e2e8f0;
    display:flex;
    gap:15px;
}

.btn-action{
    padding:14px 32px;
    border:none;
    border-radius:12px;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
    transition:all 0.2s;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.btn-approve{
    background:#22c55e;
    color:white;
}

.btn-approve:hover{
    background:#16a34a;
    transform:translateY(-2px);
    box-shadow: 0 6px 12px rgba(34, 197, 94, 0.3);
}

.btn-reject{
    background:#ef4444;
    color:white;
}

.btn-reject:hover{
    background:#dc2626;
    transform:translateY(-2px);
    box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
}

/* USER INFO FOR ADMIN */
.admin-user-info{
    display:flex;
    align-items:center;
    gap:15px;
    margin-bottom:20px;
    padding:20px;
    background:#f1f5f9;
    border-radius:14px;
    border: 2px solid #e2e8f0;
}

.admin-avatar{
    width:60px;
    height:60px;
    background:#3b82f6;
    color:white;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    font-size:24px;
}

/* EMPTY STATE - BIGGER */
.empty-state{
    text-align:center;
    padding:80px 30px;
}

.empty-state i{
    font-size:80px;
    color:#cbd5e1;
    margin-bottom:25px;
}

.empty-state h5{
    font-size: 24px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 10px;
}

.empty-state p{
    color:#64748b;
    font-size:18px;
    line-height: 1.6;
}

/* MOBILE NAV - BIGGER */
.mobile-nav{
    display:none;
    position:fixed;
    bottom:0;
    left:0;
    right:0;
    background:#0f172a;
    padding:15px 0;
    z-index:999;
    box-shadow:0 -5px 20px rgba(0,0,0,0.2);
}

.mobile-nav a{
    flex:1;
    text-align:center;
    color:#94a3b8;
    text-decoration:none;
    font-size:13px;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:6px;
    padding:8px;
    font-weight: 600;
}

.mobile-nav a i{
    font-size:24px;
}

.mobile-nav a:hover,
.mobile-nav a.active{
    color:#22c55e;
}

/* MOBILE STYLES - BIGGER EVERYTHING */
@media(max-width:768px){
    html {
        font-size: 20px;
    }
    
    .sidebar{
        display:none;
    }
    
    .main{
        margin-left:0;
        padding:15px;
        padding-bottom:100px;
    }
    
    .header-bar{
        padding:20px;
        border-radius:20px;
    }
    
    .site-header{
        margin-bottom: 20px;
        padding-bottom: 15px;
    }
    
    .site-logo{
        font-size: 32px;
    }
    
    .site-tagline{
        font-size: 15px;
    }
    
    .topbar{
        justify-content:center;
    }
    
    .back-btn{
        width:100%;
        justify-content:center;
        padding:16px;
        font-size:16px;
    }
    
    .mobile-nav{
        display:flex;
        justify-content:space-around;
    }
    
    .card-box{
        padding:25px;
        border-radius:20px;
    }
    
    .card-box h5{
        font-size:24px;
        margin-bottom:20px;
    }
    
    .tx-card{
        padding:25px;
        margin-bottom:20px;
    }
    
    .tx-header{
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
    }
    
    .tx-type{
        font-size:20px;
    }
    
    .tx-amount{
        font-size:36px;
    }
    
    .tx-details{
        grid-template-columns:1fr;
        gap:18px;
    }
    
    .tx-label{
        font-size:15px;
    }
    
    .tx-value{
        font-size:19px;
    }
    
    .badge{
        padding:12px 20px;
        font-size:14px;
    }
    
    .admin-actions{
        flex-direction:column;
        gap:12px;
    }
    
    .btn-action{
        width:100%;
        justify-content:center;
        padding:16px;
        font-size:17px;
    }
    
    .admin-user-info{
        flex-direction: column;
        text-align: center;
    }
    
    .proof-image{
        max-width:100%;
        width: 100%;
        height: auto;
    }
}

@media(max-width:480px){
    html {
        font-size: 22px;
    }
    
    .site-logo{
        font-size: 28px;
    }
    
    .tx-amount{
        font-size:32px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h4 style="color:#22c55e; font-size:24px; text-align:center; margin-bottom:30px;">💰 G-Grant</h4>
    
    <a href="index.html"><i class="fa fa-home"></i> Dashboard</a>
    <a href="apply.php"><i class="fa fa-hand-holding-dollar"></i> Apply</a>
    <a href="withdraw.php"><i class="fa fa-wallet"></i> Withdraw</a>
    <a href="transactions.php" class="active"><i class="fa fa-exchange-alt"></i> Transactions</a>

    <a href="../auth/logout.php" style="color:#ef4444;"><i class="fa fa-sign-out"></i> Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- HEADER BAR WITH SITE NAME -->
    <div class="header-bar">
        <div class="site-header">
            <div class="site-logo">
                <i class="fa fa-landmark"></i> G-Grant
            </div>
            <div class="site-tagline">Your Trusted Grant & Withdrawal Platform</div>
        </div>
        
        <div class="topbar">
            <?php if($role === "admin"): ?>
            <a href="../admin/index.html" class="back-btn">
                <i class="fa fa-arrow-left"></i> Back to Admin
            </a>
            <?php else: ?>
            <a href="index.html" class="back-btn">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- TRANSACTIONS -->
    <div class="card-box">
        <h5><i class="fa fa-exchange-alt text-success"></i> Transaction History</h5>
        
        <?php if($result->num_rows === 0): ?>
        
        <div class="empty-state">
            <i class="fa fa-inbox"></i>
            <h5>No Transactions Yet</h5>
            <p>Your deposits and withdrawals will appear here once you start transacting.</p>
            <?php if($role !== "admin"): ?>
            <a href="withdraw.php" class="btn btn-success btn-lg mt-4" style="padding: 15px 40px; font-size: 18px; font-weight: 700; border-radius: 12px;">
                <i class="fa fa-wallet me-2"></i>Withdraw Now
            </a>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        
        <?php while($row = $result->fetch_assoc()): 
        $isWithdrawal = $row['type'] === 'withdraw';
        $status = $row['status'] ?? 'pending';
        ?>
        
        <div class="tx-card">
            
            <?php if($role === "admin"): ?>
            <div class="admin-user-info">
                <div class="admin-avatar"><?= strtoupper(substr($row['full_name'] ?? 'U', 0, 1)) ?></div>
                <div>
                    <div style="font-weight:800; color:#111827; font-size: 20px;"><?= htmlspecialchars($row['full_name'] ?? 'Unknown') ?></div>
                    <small style="color:#64748b; font-size: 15px;"><?= htmlspecialchars($row['email'] ?? '') ?></small>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="tx-header">
                <div class="tx-type <?= $isWithdrawal ? 'withdraw' : 'deposit' ?>">
                    <i class="fa fa-<?= $isWithdrawal ? 'arrow-up' : 'arrow-down' ?>"></i>
                    <?= $isWithdrawal ? 'Withdrawal' : 'Deposit' ?>
                </div>
                <span class="badge <?= $status ?>">
                    <?= $status === 'pending' ? '⏳' : ($status === 'approved' ? '✅' : '❌') ?>
                    <?= ucfirst($status) ?>
                </span>
            </div>
            
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
                <div class="tx-amount <?= $isWithdrawal ? 'withdraw' : 'deposit' ?>">
                    <?= $isWithdrawal ? '-' : '+' ?>$<?= number_format($row['amount'] ?? 0, 2) ?>
                </div>
                <div style="color:#64748b; font-size:16px; font-weight: 600;">
                    <i class="fa fa-clock me-2"></i>
                    <?= date('M j, Y \a\t h:i A', strtotime($row['created_at'])) ?>
                </div>
            </div>
            
            <div class="tx-details">
                <div class="tx-detail">
                    <span class="tx-label"><i class="fa fa-hashtag me-1"></i>Transaction ID</span>
                    <span class="tx-value">#<?= $row['id'] ?></span>
                </div>
                
                <div class="tx-detail">
                    <span class="tx-label"><i class="fa fa-credit-card me-1"></i>Payment Method</span>
                    <span class="tx-value"><?= formatPaymentMethod($row['payment_method'] ?? '', $row) ?></span>
                </div>
                
                <?php if($isWithdrawal && !empty($row['fee_payment_details'])): ?>
                <div class="tx-detail">
                    <span class="tx-label"><i class="fa fa-receipt me-1"></i>Fee Payment</span>
                    <span class="tx-value text-warning">
                        <i class="fa fa-info-circle me-1"></i>
                        <?= ucfirst($row['pay_fee_method'] ?? 'N/A') ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($row['description'])): ?>
                <div class="tx-detail" style="grid-column:1/-1;">
                    <span class="tx-label"><i class="fa fa-align-left me-1"></i>Description</span>
                    <span class="tx-value"><?= htmlspecialchars($row['description']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($row['verification_image'])): ?>
            <div style="margin-top:20px; padding:20px; background:#f1f5f9; border-radius:12px;">
                <span class="tx-label" style="margin-bottom: 10px; display: block;">
                    <i class="fa fa-paperclip me-2"></i>Payment Proof
                </span>
                <a href="../uploads/payment_proofs/<?= htmlspecialchars($row['verification_image']) ?>" target="_blank">
                    <img src="../uploads/payment_proofs/<?= htmlspecialchars($row['verification_image']) ?>" 
                         class="proof-image" 
                         alt="Payment Proof"
                         onerror="this.style.display='none'">
                </a>
            </div>
            <?php endif; ?>
            
            <?php if($role === "admin" && $status === 'pending'): ?>
            <div class="admin-actions">
                <a href="approve_transaction.php?id=<?= $row['id'] ?>&action=approve" 
                   class="btn-action btn-approve"
                   onclick="return confirm('Approve this withdrawal?')">
                    <i class="fa fa-check"></i> Approve Payment
                </a>
                <a href="approve_transaction.php?id=<?= $row['id'] ?>&action=reject" 
                   class="btn-action btn-reject"
                   onclick="return confirm('Reject this withdrawal?')">
                    <i class="fa fa-times"></i> Reject Payment
                </a>
            </div>
            <?php endif; ?>
            
        </div>
        
        <?php endwhile; ?>
        
        <?php endif; ?>
    </div>

</div>

<!-- MOBILE NAV -->
<div class="mobile-nav">
    <a href="index.html">
        <i class="fa fa-home"></i>
        <span>Home</span>
    </a>
    <a href="apply.php">
        <i class="fa fa-hand-holding-dollar"></i>
        <span>Apply</span>
    </a>
    <a href="withdraw.php">
        <i class="fa fa-wallet"></i>
        <span>Withdraw</span>
    </a>
    <a href="transactions.php" class="active">
        <i class="fa fa-exchange-alt"></i>
        <span>History</span>
    </a>
    <a href="../auth/logout.php">
        <i class="fa fa-sign-out"></i>
        <span>Logout</span>
    </a>
</div>

</body>
</html>