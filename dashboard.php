<?php
session_start();

if (!isset($_SESSION["user_email"]) || !isset($_SESSION["logged_in"])) {
    header("Location: login.html");
    exit();
}

$user_email = $_SESSION["user_email"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitFlex - Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    
    .user-info {
      display: flex;
      align-items: center;
      margin-left: auto;
      margin-right: 25px;
    }
    
    .welcome-text {
      color: white;
      font-size: 18px;
      margin-right: 15px;
    }
    
    .logout-btn {
      background: #dc3545;
      color: white;
      border: none;
      font-size: 18px;
      padding: 8px 15px;
      border-radius: 10px;
      cursor: pointer;
    }
    
    .hero-content .no-button-space {
      height: 50px;
    }
  </style>
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
    <div class="user-info">
      <span class="welcome-text">Welcome, <?php echo htmlspecialchars($user_email); ?></span>
      <a href="logout.php"><button class="logout-btn">Logout</button></a>
    </div>
  </header>
  
  <div class="hero-section">
    <div class="hero-content">
      <h1>Customized Workout and Diet Plans</h1>
      <p class="hero-subtext">
        CNN underscored says, "an enormous variety of workout programs," 
        "high quality instruction," "multitude of fitness styles."<br>
        Join thousands of customers who've transformed their lives.
      </p>
      <div class="no-button-space"></div>
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