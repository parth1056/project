<?php
session_start();
$conn = new mysqli("localhost", "root", "", "parth");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['reset_request'])) {
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("SELECT user_email, user_name FROM userstable WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_name'] = $user['user_name'];
        
        $code = rand(100000, 999999);
        $_SESSION['reset_code'] = $code;
        $_SESSION['reset_code_expiry'] = time() + 600; 
        

        header("Location: entercode.php");
        exit();
    } else {
        $_SESSION["message"] = "Email address not found.";
        $_SESSION["message_type"] = "error";
        header("Location: forgor.php");
        exit();
    }
}

if (isset($_POST['verify_code'])) {
    $user_code = trim($_POST['code']);
    
    if (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_email']) || !isset($_SESSION['reset_code_expiry'])) {
        $_SESSION["message"] = "Reset session expired. Please start over.";
        $_SESSION["message_type"] = "error";
        header("Location: forgor.php");
        exit();
    }
    
    if (time() > $_SESSION['reset_code_expiry']) {
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_code_expiry']);
        
        $_SESSION["message"] = "Verification code expired. Please start over.";
        $_SESSION["message_type"] = "error";
        header("Location: forgor.php");
        exit();
    }
    
    if ($user_code == $_SESSION['reset_code']) {
        $_SESSION['reset_verified'] = true;
        header("Location: newpass.php");
        exit();
    } else {
        $_SESSION["message"] = "Invalid verification code.";
        $_SESSION["message_type"] = "error";
        header("Location: entercode.php");
        exit();
    }
}

if (isset($_POST['update_password'])) {
    if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['reset_email'])) {
        $_SESSION["message"] = "Unauthorized access. Please start the reset process again.";
        $_SESSION["message_type"] = "error";
        header("Location: forgor.php");
        exit();
    }
    
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];
    
    if ($password !== $confirm_password) {
        $_SESSION["message"] = "Passwords do not match.";
        $_SESSION["message_type"] = "error";
        header("Location: newpass.php");
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $update_stmt = $conn->prepare("UPDATE userstable SET user_password = ? WHERE user_email = ?");
    $update_stmt->bind_param("ss", $hashed_password, $email);
    $result = $update_stmt->execute();
    
    if ($result) {
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_code_expiry']);
        unset($_SESSION['reset_verified']);
        unset($_SESSION['reset_name']);
        
        $_SESSION["message"] = "Your password has been updated successfully.";
        $_SESSION["message_type"] = "success";
        header("Location: login.html");
        exit();
    } else {
        $_SESSION["message"] = "Failed to update password. Please try again.";
        $_SESSION["message_type"] = "error";
        header("Location: newpass.php");
        exit();
    }
}

$conn->close();
header("Location: forgor.php");
exit();
?>