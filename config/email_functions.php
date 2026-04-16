<?php
// config/email_functions.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'config.php';

// Your Gmail credentials - CHANGE THESE
define('GMAIL_USERNAME', 'your-email@gmail.com');      // Your Gmail address
define('GMAIL_APP_PASSWORD', 'your-app-password');     // Gmail App Password (not regular password)
define('ADMIN_EMAIL', 'your-email@gmail.com');          // Where to receive notifications

/**
 * Send email via Gmail SMTP
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    // Check if PHPMailer exists
    if (!file_exists('../PHPMailer/src/PHPMailer.php')) {
        // Fallback to simple mail() if PHPMailer not installed
        $headers = "From: " . ADMIN_EMAIL . "\r\n";
        $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if ($isHTML) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        return mail($to, $subject, $body, $headers);
    }
    
    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = GMAIL_USERNAME;
        $mail->Password = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom(GMAIL_USERNAME, 'G-Grant System');
        $mail->addAddress($to);
        
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notify admin of new user registration
 */
function notifyNewRegistration($userData) {
    $subject = "🎉 New User Registration - G-Grant";
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #22c55e;'>New User Registered</h2>
        
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <tr style='background: #f8fafc;'>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;'>Full Name</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($userData['full_name']) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;'>Email</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($userData['email']) . "</td>
            </tr>
            <tr style='background: #f8fafc;'>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;'>Phone</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($userData['phone'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;'>Registered At</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0;'>" . date('F j, Y g:i A') . "</td>
            </tr>
        </table>
        
        <p style='color: #64748b; font-size: 14px;'>
            <a href='https://yourdomain.com/admin/users.php' style='background: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                View in Admin Panel
            </a>
        </p>
    </div>";
    
    return sendEmail(ADMIN_EMAIL, $subject, $body);
}

/**
 * Notify admin of new transaction/withdrawal request
 */
function notifyTransaction($transactionData, $userData) {
    $type = $transactionData['type'] === 'withdraw' ? 'Withdrawal Request' : 'Deposit';
    $emoji = $transactionData['type'] === 'withdraw' ? '💸' : '💰';
    
    $subject = "$emoji New $type - $" . number_format($transactionData['amount'], 2);
    
    $paymentMethod = '';
    switch($transactionData['payment_method']) {
        case 'bank': $paymentMethod = '🏦 Bank Transfer'; break;
        case 'crypto': $paymentMethod = '₿ Cryptocurrency'; break;
        case 'cashapp': $paymentMethod = '💵 CashApp'; break;
        case 'paypal': $paymentMethod = '💳 PayPal'; break;
        case 'venmo': $paymentMethod = '💸 Venmo'; break;
        case 'zelle': $paymentMethod = '⚡ Zelle'; break;
        case 'giftcard': $paymentMethod = '🎁 Gift Card'; break;
        default: $paymentMethod = ucfirst($transactionData['payment_method']);
    }
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: " . ($transactionData['type'] === 'withdraw' ? '#dc2626' : '#16a34a') . ";'>
            $emoji New $type Request
        </h2>
        
        <div style='background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0;'>
            <h3 style='margin-top: 0; color: #111827;'>Amount: $" . number_format($transactionData['amount'], 2) . "</h3>
            <p style='color: #64748b; margin: 5px 0;'>Status: <span style='background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;'>PENDING</span></p>
        </div>
        
        <h3 style='color: #111827; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;'>User Information</h3>
        <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
            <tr>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; width: 30%;'>Name</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($userData['full_name']) . "</td>
            </tr>
            <tr style='background: #f8fafc;'>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold;'>Email</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($userData['email']) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold;'>User ID</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0;'>#" . $userData['id'] . "</td>
            </tr>
        </table>
        
        <h3 style='color: #111827; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;'>Payment Details</h3>
        <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
            <tr>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; width: 30%;'>Method</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0;'>" . $paymentMethod . "</td>
            </tr>
            <tr style='background: #f8fafc;'>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold;'>Transaction ID</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0;'>#" . $transactionData['id'] . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold;'>Date</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0;'>" . date('F j, Y g:i A', strtotime($transactionData['created_at'])) . "</td>
            </tr>";
    
    // Add payment-specific details
    if (!empty($transactionData['bank_name'])) {
        $body .= "
            <tr style='background: #f8fafc;'>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold;'>Bank Name</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($transactionData['bank_name']) . "</td>
            </tr>";
    }
    if (!empty($transactionData['crypto_wallet'])) {
        $body .= "
            <tr style='background: #f8fafc;'>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold;'>Wallet</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-family: monospace;'>" . htmlspecialchars($transactionData['crypto_wallet']) . "</td>
            </tr>";
    }
    if (!empty($transactionData['paypal_email'])) {
        $body .= "
            <tr style='background: #f8fafc;'>
                <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold;'>PayPal</td>
                <td style='padding: 10px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($transactionData['paypal_email']) . "</td>
            </tr>";
    }
    
    $body .= "
        </table>
        
        <p style='margin-top: 25px;'>
            <a href='https://yourdomain.com/admin/transactions.php' style='background: #22c55e; color: white; padding: 14px 28px; text-decoration: none; border-radius: 10px; display: inline-block; font-weight: bold;'>
                Process Transaction →
            </a>
        </p>
    </div>";
    
    return sendEmail(ADMIN_EMAIL, $subject, $body);
}

/**
 * Notify user of transaction status update
 */
function notifyUserTransactionStatus($userEmail, $userName, $transactionData) {
    $status = $transactionData['status'];
    $type = $transactionData['type'] === 'withdraw' ? 'withdrawal' : 'deposit';
    
    $statusColors = [
        'approved' => ['#16a34a', '🎉'],
        'rejected' => ['#dc2626', '❌'],
        'pending' => ['#92400e', '⏳']
    ];
    
    $color = $statusColors[$status][0] ?? '#64748b';
    $emoji = $statusColors[$status][1] ?? 'ℹ️';
    
    $subject = "$emoji Your $type has been " . ucfirst($status);
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='text-align: center; padding: 30px 0;'>
            <h1 style='color: " . $color . "; font-size: 48px; margin: 0;'>" . $emoji . "</h1>
        </div>
        
        <h2 style='color: #111827; text-align: center;'>Hello " . htmlspecialchars($userName) . ",</h2>
        
        <div style='background: #f8fafc; padding: 25px; border-radius: 16px; margin: 25px 0; text-align: center;'>
            <p style='color: #64748b; margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>Transaction Status</p>
            <h3 style='color: " . $color . "; margin: 0; font-size: 28px; text-transform: uppercase;'>" . strtoupper($status) . "</h3>
            <p style='color: #111827; margin: 15px 0 0 0; font-size: 32px; font-weight: bold;'>
                $" . number_format($transactionData['amount'], 2) . "
            </p>
        </div>
        
        <p style='color: #374151; line-height: 1.6; font-size: 16px;'>";
    
    if ($status === 'approved') {
        $body .= "Great news! Your " . $type . " request has been <strong>approved</strong> and processed. The funds have been sent to your designated payment method.";
    } elseif ($status === 'rejected') {
        $body .= "We regret to inform you that your " . $type . " request has been <strong>rejected</strong>. Please contact support for more information.";
    } else {
        $body .= "Your " . $type . " request is currently <strong>pending</strong> review. You will receive another email once it's processed.";
    }
    
    $body .= "
        </p>
        
        <div style='margin: 30px 0; padding: 20px; background: white; border: 1px solid #e2e8f0; border-radius: 12px;'>
            <p style='margin: 0; color: #64748b; font-size: 14px;'><strong>Transaction ID:</strong> #" . $transactionData['id'] . "</p>
            <p style='margin: 10px 0 0 0; color: #64748b; font-size: 14px;'><strong>Date:</strong> " . date('F j, Y g:i A', strtotime($transactionData['created_at'])) . "</p>
        </div>
        
        <p style='text-align: center; margin-top: 30px;'>
            <a href='https://yourdomain.com/user/transactions.php' style='background: #0f172a; color: white; padding: 14px 28px; text-decoration: none; border-radius: 10px; display: inline-block; font-weight: bold;'>
                View My Transactions
            </a>
        </p>
        
        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 40px 0;'>
        
        <p style='color: #94a3b8; font-size: 12px; text-align: center;'>
            This is an automated message from G-Grant. Please do not reply to this email.
        </p>
    </div>";
    
    return sendEmail($userEmail, $subject, $body);
}

/**
 * Notify admin of contact form submission
 */
function notifyContactForm($contactData) {
    $subject = "📧 New Contact Form Message - G-Grant";
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #3b82f6;'>New Contact Message</h2>
        
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <tr style='background: #f8fafc;'>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold; width: 25%;'>Name</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($contactData['name']) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;'>Email</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($contactData['email']) . "</td>
            </tr>
            <tr style='background: #f8fafc;'>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;'>Subject</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($contactData['subject'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;'>Message</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0; white-space: pre-wrap;'>" . nl2br(htmlspecialchars($contactData['message'])) . "</td>
            </tr>
            <tr style='background: #f8fafc;'>
                <td style='padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;'>Sent At</td>
                <td style='padding: 12px; border: 1px solid #e2e8f0;'>" . date('F j, Y g:i A') . "</td>
            </tr>
        </table>
        
        <p>
            <a href='mailto:" . $contactData['email'] . "' style='background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                Reply to User
            </a>
        </p>
    </div>";
    
    return sendEmail(ADMIN_EMAIL, $subject, $body);
}
?>