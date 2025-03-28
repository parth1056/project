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
      padding-right: 60px;
    }
    
    .user-actions {
      display: flex;
      gap: 10px;
    }
    
    .logout-btn, .profile-btn {
      background: #dc3545;
      color: white;
      border: none;
      font-size: 16px;
      padding: 8px 15px;
      border-radius: 10px;
      cursor: pointer;
      text-decoration: none;
      margin-right: 10px;
    }
    
    .profile-btn {
      background: #28a745;
    }
    
    .update-button {
      background: #007bff;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .update-input {
      padding: 5px;
      width: 80px;
      border: 1px solid #ccc;
      border-radius: 5px;
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
    }

    .profile-table th, .profile-table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: left;
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
          <tr><th>Name</th><td><?php echo htmlspecialchars($user_result["user_name"]); ?></td></tr>
          <tr><th>Email</th><td><?php echo htmlspecialchars($user_email); ?></td></tr>
          <tr><th>Phone</th><td><?php echo htmlspecialchars($user_result["phone_number"]); ?></td></tr>
          <tr><th>Height</th><td><?php echo htmlspecialchars($user_result["user_height"]); ?> cm</td></tr>
          <tr><th>Weight</th><td><?php echo htmlspecialchars($user_result["user_weight"]); ?> kg</td></tr>
          <tr><th>Target Weight</th><td><?php echo htmlspecialchars($user_result["target_weight"]); ?> kg</td></tr>
        </table>

        <h2>Subscription Details</h2>
        <table class="profile-table">
          <tr><th>Subscription ID</th><td><?php echo htmlspecialchars($sub_result["subscription_id"]); ?></td></tr>
          <tr><th>Start Date</th><td><?php echo htmlspecialchars($sub_result["start_date"]); ?></td></tr>
          <tr><th>Plan Length</th><td><?php echo htmlspecialchars($sub_result["plan_length"]); ?> days</td></tr>
          <tr><th>Plan Price</th><td>₹<?php echo htmlspecialchars($sub_result["plan_price"]); ?></td></tr>
        </table>
      </div>
    </div>
  </div>

  <div class="hero-section">
    <div class="hero-content">
      <h1>Customized Workout and Diet Plans</h1>
      <p class="hero-subtext">
        CNN underscored says, "an enormous variety of workout programs," 
        "high quality instruction," "multitude of fitness styles."<br>
        Join thousands of customers who've transformed their lives.
      </p>
    </div>
  </div>

  <h2 class="health-goals-text">Hit your health goals in 1-2-3</h2>
  
  <div class="weight-update-section">
    <h3>Current Weight: <span id="weightDisplay"><?php echo htmlspecialchars($user_result["user_weight"]); ?>kg</span></h3>
    <button class="update-button" onclick="transformUpdateButton('weight')">Update</button>
    <br><br>
    <h3>Target Weight: <span id="targetWeightDisplay"><?php echo htmlspecialchars($user_result["target_weight"]); ?>kg</span></h3>
    <button class="update-button" onclick="transformUpdateButton('targetWeight')">Update</button>
  </div>

  <footer>
    <p>&copy; 2025 FitFlex. All Rights Reserved.</p>
  </footer>

  <script>
    function transformUpdateButton(type) {
      let displaySpan = document.getElementById(type + "Display");
      let button = event.target;

      let input = document.createElement("input");
      input.type = "number";
      input.className = "update-input";
      input.value = displaySpan.innerText.replace("kg", "");

      let saveButton = document.createElement("button");
      saveButton.innerText = "Save";
      saveButton.className = "update-button";
      saveButton.style.background = "#28a745";
      saveButton.onclick = function () {
        let newValue = input.value.trim();
        if (newValue !== "" && !isNaN(newValue)) {
          displaySpan.innerText = newValue + "kg";
          
          let formData = new FormData();
          formData.append(type === 'weight' ? 'new_weight' : 'new_target_weight', newValue);
          
          fetch('', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            console.log('Weight updated successfully');
          })
          .catch(error => {
            console.error('Error updating weight:', error);
          });
        }
        button.style.display = "inline-block";
        input.remove();
        saveButton.remove();
      };

      button.style.display = "none";
      displaySpan.after(input);
      input.after(saveButton);
      input.focus();
    }

    function openProfile() {
      document.getElementById("profileModal").style.display = "flex";
    }

    function closeProfile() {
      document.getElementById("profileModal").style.display = "none";
    }
  </script>

</body>
</html>