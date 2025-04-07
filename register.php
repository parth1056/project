<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parth";
$conn = new mysqli($servername, $username, $password, $dbname);
session_start();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = trim($_POST["name"]);
$email = trim($_POST["email"]);
$password = trim($_POST["password"]);
$phone = trim($_POST["phone"]);

if (empty($name) || empty($email) || empty($password) || empty($phone)) {
    echo "<script>alert('All fields are required.'); window.location.href='registration_page.html';</script>";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Invalid email format.'); window.location.href='registration_page.html';</script>";
    exit();
}

$check = $conn->prepare("SELECT user_email FROM userstable WHERE user_email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "<script>alert('Email already registered. Try another.'); window.location.href='registration_page.html';</script>";
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO userstable (user_email, user_name, user_password, phone_number) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $email, $name, $hashed_password, $phone);

if ($stmt->execute()) {
    $_SESSION["user_email"] = $email;
    $_SESSION["user_name"] = $name;
    $_SESSION["logged_in"] = true;

    header("Location: subscription.php");
    exit();
} else {
    echo "<script>alert('Something went wrong. Try again.'); window.location.href='registration_page.html';</script>";
    exit();
}

$stmt->close();
$conn->close();
