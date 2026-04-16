<?php
// Email functions for G-Grant

// Your Gmail credentials - SAME AS YOUR WITHDRAW.PHP
$GMAIL_USERNAME = "hammed123aa@gmail.com";
$GMAIL_APP_PASSWORD = "acup waim qsmz zicm";
$ADMIN_EMAIL = "hammed123aa@gmail.com";

// SMTP EMAIL FUNCTION (copied from your withdraw.php)
function sendGmailSMTP($username, $password, $to, $subject, $htmlBody, $textBody) {
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
    $headers .= "From: G-Grant <$username>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    
    $emailContent = $headers . "\r\n";
    $emailContent .= "--$boundary\r\n";
    $emailContent .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $emailContent .= $textBody . "\r\n\r\n";
    $emailContent .= "--$boundary\r\n";
    $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $emailContent .= $htmlBody . "\r\n\r\n";
    $emailContent .= "--$boundary--\r\n.\r\n";
    
    fputs($socket, $emailContent);
    $line = fgets($socket, 515);
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return (substr($line, 0, 3) == '250');
}

// Email when NEW USER registers
function emailNewUser($name, $email, $phone) {
    global $GMAIL_USERNAME, $GMAIL_APP_PASSWORD, $ADMIN_EMAIL;
    
    $subject = "🎉 New User Registered - G-Grant";
    
    $html = "
    <html>
    <body style='font-family:Arial,sans-serif;background:#f3f4f6;'>
        <div style='max-width:600px;margin:20px auto;background:white;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1);'>
            <div style='background:#22c55e;color:white;padding:20px;text-align:center;'>
                <h2>New User Registration</h2>
            </div>
            <div style='padding:30px;'>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            </div>
        </div>
    </body>
    </html>";
    
    $text = "New User: $name\nEmail: $email\nPhone: $phone\nTime: " . date('Y-m-d H:i:s');
    
    return sendGmailSMTP($GMAIL_USERNAME, $GMAIL_APP_PASSWORD, $ADMIN_EMAIL, $subject, $html, $text);
}

// Email when someone WITHDRAWS (uses your existing function from withdraw.php)
function emailWithdrawal($user_name, $user_email, $amount, $method) {
    global $GMAIL_USERNAME, $GMAIL_APP_PASSWORD, $ADMIN_EMAIL;
    
    $subject = "💸 New Withdrawal Request - $" . number_format($amount, 2);
    
    $html = "
    <html>
    <body style='font-family:Arial,sans-serif;background:#f3f4f6;'>
        <div style='max-width:600px;margin:20px auto;background:white;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1);'>
            <div style='background:#ef4444;color:white;padding:20px;text-align:center;'>
                <h2>New Withdrawal Request!</h2>
            </div>
            <div style='padding:30px;'>
                <h3 style='color:#111827;'>Amount: $" . number_format($amount, 2) . "</h3>
                <p><strong>User:</strong> $user_name</p>
                <p><strong>Email:</strong> $user_email</p>
                <p><strong>Method:</strong> " . strtoupper($method) . "</p>
                <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                <hr>
                <p>Login to admin panel to approve or reject</p>
            </div>
        </div>
    </body>
    </html>";
    
    $text = "Withdrawal: $" . number_format($amount, 2) . "\nUser: $user_name\nEmail: $user_email\nMethod: $method";
    
    return sendGmailSMTP($GMAIL_USERNAME, $GMAIL_APP_PASSWORD, $ADMIN_EMAIL, $subject, $html, $text);
}

// Email user when status changes (APPROVED or REJECTED)
function emailStatusUpdate($user_email, $user_name, $amount, $status) {
    global $GMAIL_USERNAME, $GMAIL_APP_PASSWORD;
    
    $isApproved = ($status === 'approved');
    $color = $isApproved ? '#16a34a' : '#dc2626';
    $emoji = $isApproved ? '✅' : '❌';
    $subject = "$emoji Your withdrawal is " . strtoupper($status);
    
    $html = "
    <html>
    <body style='font-family:Arial,sans-serif;background:#f3f4f6;'>
        <div style='max-width:600px;margin:20px auto;background:white;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1);'>
            <div style='background:$color;color:white;padding:20px;text-align:center;'>
                <h2>Withdrawal " . ucfirst($status) . "</h2>
            </div>
            <div style='padding:30px;text-align:center;'>
                <h1 style='font-size:48px;margin:0;'>$emoji</h1>
                <h3>Hello $user_name,</h3>
                <p style='font-size:18px;'>Your withdrawal of <strong>$" . number_format($amount, 2) . "</strong></p>
                <p style='font-size:24px;font-weight:bold;color:$color;'>IS " . strtoupper($status) . "</p>
                <p>" . ($isApproved ? 'Your funds have been sent!' : 'Please contact support for details.') . "</p>
                <p style='color:#64748b;'>Time: " . date('Y-m-d H:i:s') . "</p>
            </div>
        </div>
    </body>
    </html>";
    
    $text = "Hello $user_name, Your withdrawal of $" . number_format($amount, 2) . " is $status.";
    
    return sendGmailSMTP($GMAIL_USERNAME, $GMAIL_APP_PASSWORD, $user_email, $subject, $html, $text);
}
?>