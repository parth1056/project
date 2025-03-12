<?php
session_start();
$conn = new mysqli("localhost", "root", "", "parth");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        echo "<script>alert('Email and password are required.'); window.location.href='login.html';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT user_email, user_password FROM userstable WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user["user_password"])) {
            $_SESSION["user_email"] = $user["user_email"];
            $_SESSION["logged_in"] = true;
            
            header("Location: subscription.php");
            exit();
        } else {
            echo "<script>alert('Invalid email or password.'); window.location.href='login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid email or password.'); window.location.href='login.html';</script>";
        exit();
    }

    $stmt->close();
} else {
    header("Location: login.html");
    exit();
}

$conn->close();
?>