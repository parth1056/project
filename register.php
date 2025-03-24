<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parth";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
$conn->query($sql);

// Select the database
$conn->select_db($dbname);

// SQL to create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS userstable (
        user_email VARCHAR(100) NOT NULL PRIMARY KEY,
        user_name VARCHAR(100) NOT NULL,
        user_age INT(11) DEFAULT NULL,
        user_gender VARCHAR(15) DEFAULT NULL,
        user_password VARCHAR(100) DEFAULT NULL,
        user_height DOUBLE DEFAULT NULL,
        user_weight DOUBLE DEFAULT NULL,
        phone_number VARCHAR(10) DEFAULT NULL,
        subscription_status TINYINT(1) DEFAULT 0
    )",
    "CREATE TABLE IF NOT EXISTS userdiet (
        diet_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(100) DEFAULT NULL,
        calorie_intake FLOAT NOT NULL,
        food_category VARCHAR(50) NOT NULL,
        FOREIGN KEY (user_email) REFERENCES userstable(user_email) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS userpayment (
        payment_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(100) NOT NULL,
        payment_method VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_email) REFERENCES userstable(user_email) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS usersubscription (
        subscription_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(100) DEFAULT NULL,
        start_date DATE NOT NULL,
        plan_length INT(11) NOT NULL,
        plan_price INT(11) NOT NULL,
        FOREIGN KEY (user_email) REFERENCES userstable(user_email) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS usertarget (
        target_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(100) NOT NULL,
        target_weight FLOAT NOT NULL,
        FOREIGN KEY (user_email) REFERENCES userstable(user_email) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS workoutplanner (
        planner_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(100) DEFAULT NULL,
        preferred_days INT(11) NOT NULL,
        workout_count INT(11) NOT NULL,
        FOREIGN KEY (user_email) REFERENCES userstable(user_email) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS plannermusclegroup (
        planner_id INT(11) NOT NULL,
        muscle_group ENUM('Chest','Legs','Arms','Core','Shoulder','Back') NOT NULL,
        selected TINYINT(1) DEFAULT 1,
        PRIMARY KEY (planner_id, muscle_group),
        FOREIGN KEY (planner_id) REFERENCES workoutplanner(planner_id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS userworkout (
        workout_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        planner_id INT(11) DEFAULT NULL,
        muscle_group ENUM('Chest','Legs','Arms','Core','Shoulder','Back') DEFAULT NULL,
        set_count INT(11) NOT NULL,
        repetitions INT(11) NOT NULL,
        calories_burnt FLOAT NOT NULL,
        FOREIGN KEY (planner_id, muscle_group) REFERENCES plannermusclegroup(planner_id, muscle_group) ON DELETE CASCADE
    )"
];

// Execute each table creation query
foreach ($tables as $sql) {
    $conn->query($sql);
}
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
?>