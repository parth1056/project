<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitFlex</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header>
    <img src="assets/logo.png" alt="FitFlex Logo" class="logo">
    <span class="brand-name">FitFlex</span>
    <nav class="nav">
      <button>Workouts</button>
      <button>Diets</button>
      <button>About Us</button>
    </nav>
    <a href="./login.html"><button class="login-btn">Login</button></a>
  </header>
  
  <div class="hero-section">
    <div class="hero-content">
      <h1>Customized Workout and Diet Plans</h1>
      <p class="hero-subtext">
        CNN underscored says, “an enormous variety of workout programs,” 
        “high quality instruction,” “multitude of fitness styles.”<br>
        Join thousands of customers who've transformed their lives.
      </p>
      <a href="./registration_page.html"><button class="join-btn">Join Us</button></a>
    </div>
  </div>

  <h2 class="health-goals-text">Hit your health goals in 1-2-3</h2>
  
  <div class="step-section">
    <img src="assets/bg2.png" alt="Choose a Plan" class="step-image">
    <div class="step-text">
      <span class="step-number">1</span>
      <h3>Choose a Plan</h3>
      <p>We have 3 Plans which on choosing give the Schedules</p>
    </div>
  </div>

  <div class="step-section step-2">
    <div class="step-text">
      <span class="step-number">2</span>
      <h3>Select a Diet</h3>
      <p>Set Goals and track Your Calorie Intake and be able to customize it.</p>
    </div>
    <img src="assets/bg3.png" alt="Select a Diet" class="step-image step-image-right">
  </div>

  <div class="step-section step-3">
    <img src="assets/bg4.png" alt="Select a Workout" class="step-image step-image-3">
    <div class="step-text">
      <span class="step-number">3</span>
      <h3>Select a Workout</h3>
      <p>Custom Workouts with User tracking and time flexibility.</p>
    </div>
  </div>
  
  <footer>
    <p>&copy; 2025 FitFlex. All Rights Reserved.</p>
  </footer>
</body>
</html>

<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parth";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
$conn->query($sql);

$conn->select_db($dbname);

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

foreach ($tables as $sql) {
    $conn->query($sql);
}
$conn->close();
?>