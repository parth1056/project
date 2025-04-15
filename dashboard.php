<?php
session_start();

if (!isset($_SESSION["user_email"]) || !isset($_SESSION["logged_in"])) {
  header("Location: login.html");
  exit();
}

$user_email = $_SESSION["user_email"];

$conn = new mysqli("localhost", "root", "", "parth");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT u.user_name, u.phone_number, u.user_height, u.user_weight, t.target_weight FROM userstable AS u LEFT JOIN usertarget AS t ON u.user_email = t.user_email WHERE u.user_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$user_result = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT subscription_id, start_date, plan_length, plan_price FROM usersubscription WHERE user_email = ? ORDER BY start_date DESC LIMIT 1");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$sub_result = $stmt->get_result()->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitFlex - Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .user-menu {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      margin-left: auto;
      margin-right: 40px;
      gap: 10px;
    }

    .welcome-text {
      color: white;
      font-size: 18px;
      text-align: right;
      width: 100%;
    }

    .user-actions {
      display: flex;
      justify-content: center;
      gap: 10px;
      width: 100%;
    }

    .logout-btn,
    .profile-btn {
      background: #dc3545;
      color: white;
      border: none;
      font-size: 16px;
      padding: 8px 15px;
      border-radius: 10px;
      cursor: pointer;
      text-decoration: none;
    }

    .profile-btn {
      background: #28a745;
    }

    .profile-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .profile-modal-content {
      background: white;
      width: 80%;
      max-width: 600px;
      padding: 20px;
      border-radius: 10px;
      position: relative;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .close-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #dc3545;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      cursor: pointer;
    }

    .profile-table {
      width: 100%;
      border-collapse: collapse;
      margin: 15px 0;
    }

    .profile-table th,
    .profile-table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: left;
    }

    .profile-link {
      display: block;
      margin-top: 10px;
      text-align: center;
      color: #28a745;
      text-decoration: none;
      font-weight: bold;
    }

    .profile-link:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <header>
    <img src="assets/logo.png" alt="FitFlex Logo" class="logo">
    <nav class="nav">
    <a href="./workout.php"><button>Workouts</button></a>
    <a href="./dietpage.php"><button>Diets</button></a>
    <a href="./about.php"><button>About Us</button></a>
    </nav>
    <div class="user-menu">
      <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?></span>
      <div class="user-actions">
        <button class="profile-btn" onclick="openProfile()">Profile</button>
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </div>
  </header>

  <div class="profile-modal" id="profileModal">
    <div class="profile-modal-content">
      <button class="close-btn" onclick="closeProfile()">X</button>
      <div id="profileContent">
        <h2>My Profile</h2>
        <table class="profile-table">
          <tr>
            <th>Name</th>
            <td><?php echo htmlspecialchars($user_result["user_name"]); ?></td>
          </tr>
          <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($user_email); ?></td>
          </tr>
          <tr>
            <th>Phone</th>
            <td><?php echo htmlspecialchars($user_result["phone_number"]); ?></td>
          </tr>
          <tr>
            <th>Height</th>
            <td><?php echo htmlspecialchars($user_result["user_height"]); ?> cm</td>
          </tr>
          <tr>
            <th>Weight</th>
            <td><?php echo htmlspecialchars($user_result["user_weight"]); ?> kg</td>
          </tr>
          <tr>
            <th>Target Weight</th>
            <td><?php echo htmlspecialchars($user_result["target_weight"]); ?> kg</td>
          </tr>
        </table>

        <h2>Subscription Details</h2>
        <table class="profile-table">
          <tr>
            <th>Subscription ID</th>
            <td><?php echo htmlspecialchars($sub_result["subscription_id"]); ?></td>
          </tr>
          <tr>
            <th>Start Date</th>
            <td><?php echo htmlspecialchars($sub_result["start_date"]); ?></td>
          </tr>
          <tr>
            <th>Plan Length</th>
            <td><?php echo htmlspecialchars($sub_result["plan_length"]); ?> days</td>
          </tr>
          <tr>
            <th>Plan Price</th>
            <td>₹<?php echo htmlspecialchars($sub_result["plan_price"]); ?></td>
          </tr>
        </table>

        <a href="profile.php" class="profile-link">Go to Update Information</a>
      </div>
    </div>
  </div>

  <div class="hero-section">
    <div class="hero-content">
      <h1>Customized Workout and Diet Plans</h1>
      <p class="hero-subtext">
        Transform Your Body, Elevate Your Life
        Join thousands who've achieved their fitness dreams with expert-designed plans and real results. <br>
        Tailored Workouts. Personalized Diets. Proven Success.
      </p>
    </div>
  </div>

  <h2 class="health-goals-text">Hit your health goals in 1-2-3</h2>

  <div class="step-section">
    <img src="assets/bg2.png" alt="Choose a Plan" class="step-image">
    <div class="step-text">
      <span class="step-number">1</span>
      <h3>Choose a Plan</h3>
      <p>We have 4 Plans which on choosing gives the schedules</p>
    </div>
  </div>

  <div class="step-section step-2">
    <div class="step-text">
      <span class="step-number">2</span>
      <h3>Select a Diet</h3>
      <p>Set Goals and track Your Calorie Intake and be able to customize it.</p>
    </div>
    <img src="assets/bg3.jpg" alt="Select a Diet" class="step-image step-image-right">
  </div>

  <div class="step-section step-3">
    <img src="assets/bg4.jpg" alt="Select a Workout" class="step-image step-image-3">
    <div class="step-text">
      <span class="step-number" style="margin-right: 100px;">3</span>
      <h3 style="margin-right: 100px;">Select a Workout</h3>
      <p style="margin-right: 100px;">Custom Workouts with User tracking and time flexibility.</p>
    </div>
  </div>
  <br>

  <footer>
    <p>&copy; 2025 FitFlex. All Rights Reserved.</p>
  </footer>

  <script>
    function openProfile() {
      document.getElementById("profileModal").style.display = "flex";
    }

    function closeProfile() {
      document.getElementById("profileModal").style.display = "none";
    }
  </script>

</body>

</html>