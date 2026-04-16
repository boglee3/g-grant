<?php
session_start();
include "../includes/auth.php";
include "../config/config.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = "";

if (isset($_POST['upload'])) {
    
    // Check if file was uploaded without errors
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => "File is too large (exceeds server limit)",
            UPLOAD_ERR_FORM_SIZE => "File is too large (exceeds form limit)",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was selected",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "Upload stopped by extension"
        ];
        $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $message = "Upload failed: " . ($errorMessages[$errorCode] ?? "Unknown error");
        $messageType = "error";
    } else {
        $file = $_FILES['file']['name'];
        $tmp = $_FILES['file']['tmp_name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        
        // Validate file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $message = "Invalid file type. Allowed: " . implode(', ', $allowedExtensions);
            $messageType = "error";
        }
        // Validate file size (10MB max)
        elseif ($fileSize > 10 * 1024 * 1024) {
            $message = "File is too large. Maximum size: 10MB";
            $messageType = "error";
        }
        else {
            // Create safe filename
            $safeFilename = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $file);
            $uploadDir = "../uploads/";
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $path = $uploadDir . $safeFilename;
            
            // Check if file already exists
            if (file_exists($path)) {
                $safeFilename = time() . rand(1000, 9999) . '_' . $safeFilename;
                $path = $uploadDir . $safeFilename;
            }
            
            if (move_uploaded_file($tmp, $path)) {
                $stmt = $conn->prepare("INSERT INTO uploads (user_id, file, original_name, file_size, file_type, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issis", $user_id, $safeFilename, $file, $fileSize, $fileType);
                
                if ($stmt->execute()) {
                    $message = "Uploaded successfully ✅ ($safeFilename)";
                    $messageType = "success";
                } else {
                    $message = "Database error: " . $stmt->error;
                    $messageType = "error";
                    // Remove uploaded file if DB insert failed
                    unlink($path);
                }
            } else {
                $message = "Failed to move uploaded file. Check folder permissions.";
                $messageType = "error";
            }
        }
    }
}

// Fetch user's previous uploads
$uploads = [];
$result = $conn->query("SELECT * FROM uploads WHERE user_id = $user_id ORDER BY uploaded_at DESC LIMIT 10");
if ($result) {
    $uploads = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Document</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        form { background: #f8f9fa; padding: 20px; border-radius: 8px; }
        input[type="file"] { margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .uploads-list { margin-top: 30px; }
        .upload-item { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .file-link { color: #007bff; text-decoration: none; }
        .file-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <h2>📁 Upload Document</h2>

    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <p><strong>Select file to upload:</strong></p>
        <input type="file" name="file" required accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
        <p style="color: #666; font-size: 12px;">Allowed: JPG, PNG, GIF, PDF, DOC, DOCX (Max: 10MB)</p>
        <button type="submit" name="upload">Upload</button>
    </form>

    <?php if (!empty($uploads)): ?>
    <div class="uploads-list">
        <h3>Your Recent Uploads</h3>
        <?php foreach ($uploads as $upload): ?>
        <div class="upload-item">
            <a href="../uploads/<?= htmlspecialchars($upload['file']) ?>" class="file-link" target="_blank">
                📄 <?= htmlspecialchars($upload['original_name'] ?? $upload['file']) ?>
            </a>
            <small style="color: #666;">
                (<?= number_format($upload['file_size'] / 1024, 1) ?> KB) - 
                <?= date('M d, Y H:i', strtotime($upload['uploaded_at'])) ?>
            </small>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <p style="margin-top: 20px;"><a href="index.html">← Back to Dashboard</a></p>

</body>
</html>