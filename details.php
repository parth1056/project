<?php
session_start();

if (!isset($_SESSION["user_email"]) || !isset($_SESSION["logged_in"])) {
  header("Location: login.html");
  exit();
}

$user_email = $_SESSION["user_email"];
$user_name = $_SESSION["user_name"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $conn = new mysqli("localhost", "root", "", "parth");

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $age = filter_var($_POST["age"], FILTER_SANITIZE_NUMBER_INT);
  $gender = $_POST["gender"];
  $height = filter_var($_POST["height"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
  $weight = filter_var($_POST["weight"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
  $target = $_POST["target"];
  $activity = $_POST["activity"];
  $target_weight = filter_var($_POST["target_weight"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

  if (empty($age) || empty($gender) || empty($height) || empty($weight) || empty($target) || empty($activity) || empty($target_weight)) {
    $error = "All fields are required";
  } else {
    $stmt = $conn->prepare("UPDATE userstable SET user_age = ?, user_gender = ?, user_height = ?, user_weight = ? WHERE user_email = ?");
    $stmt->bind_param("issds", $age, $gender, $height, $weight, $user_email);

    if ($stmt->execute()) {
      $stmt = $conn->prepare("INSERT INTO usertarget (user_email, target_weight) VALUES (?, ?)");
      $stmt->bind_param("sd", $user_email, $target_weight);
      $stmt->execute();

      $preferred_days = 3;
      switch ($activity) {
        case 'bmr':
          $preferred_days = 1;
          break;
        case 'sedentary':
          $preferred_days = 2;
          break;
        case 'light-exercise':
          $preferred_days = 3;
          break;
        case 'moderate-exercise':
          $preferred_days = 4;
          break;
        case 'active-exercise':
          $preferred_days = 5;
          break;
        case 'very-active-exercise':
          $preferred_days = 6;
          break;
      }

      $stmt = $conn->prepare("INSERT INTO workoutplanner (user_email, preferred_days, workout_count) VALUES (?, ?, 0)");
      $stmt->bind_param("si", $user_email, $preferred_days);

      if ($stmt->execute()) {
        $planner_id = $conn->insert_id;

        $muscle_groups = array('Chest', 'Legs', 'Arms', 'Core', 'Shoulder', 'Back');
        $stmt = $conn->prepare("INSERT INTO plannermusclegroup (planner_id, muscle_group) VALUES (?, ?)");

        foreach ($muscle_groups as $muscle) {
          $stmt->bind_param("is", $planner_id, $muscle);
          $stmt->execute();
        }

        header("Location: dashboard.php");
        exit();
      }
    }

    $error = "An error occurred. Please try again.";
  }

  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FitFlex - Enter Your Body Details</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
      display: flex;
      flex-direction: column;
      align-items: center;
      overflow-x: hidden;
    }

    header {
      background: black;
      width: 100%;
      height: 178px;
      display: flex;
      align-items: center;
      padding: 0 20px;
      justify-content: flex-start;
    }

    .logo {
      width: 210.35px;
      height: 130px;
      padding-left: 20px;
    }

    .form-container {
      background-color: #fff;
      padding: 30px 40px;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      margin-top: 30px;
      max-width: 800px;
      width: 95%;
      box-sizing: border-box;
    }

    h2 {
      color: #333;
      font-size: 1.6em;
      margin-top: 0;
      margin-bottom: 15px;
      text-align: center;
    }

    p.form-description {
      color: #777;
      text-align: center;
      margin-bottom: 25px;
      font-size: 1em;
      line-height: 1.4;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      color: #555;
      font-weight: bold;
      font-size: 0.95em;
    }

    .radio-group {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .radio-group label {
      margin: 0;
      font-weight: normal;
      color: #666;
      font-size: 0.95em;
    }

    input[type="radio"] {
      margin-right: 5px;
    }

    input[type="text"],
    select {
      width: calc(100% - 22px);
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1em;
      color: #444;
      box-sizing: border-box;
    }

    select {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url('data:image/svg+xml;utf8,<svg fill="currentColor" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
      background-repeat: no-repeat;
      background-position-x: 100%;
      background-position-y: 5px;
      padding-right: 30px;
    }

    select::-ms-expand {
      display: none;
    }

    .button-row {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
    }

    .form-button {
      padding: 10px 25px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1em;
      color: white;
      background-color: #6c757d;
      transition: background-color 0.3s;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .form-button.next {
      background-color: #7952b3;
    }

    .form-button:hover {
      opacity: 0.9;
    }

    .error-message {
      color: #dc3545;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      border-radius: 4px;
      padding: 10px;
      margin-bottom: 20px;
      text-align: center;
    }

    .user-info {
      display: flex;
      align-items: center;
      margin-left: auto;
      color: white;
    }

    .logout-btn {
      background-color: #dc3545;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 4px;
      margin-left: 10px;
      text-decoration: none;
      font-size: 0.9em;
      margin-right: 20px;
    }

    @media (max-width: 768px) {
      .form-container {
        padding: 20px;
      }

      header {
        flex-direction: column;
        text-align: center;
      }

      .logo {
        margin-bottom: 10px;
      }

      .user-info {
        margin: 10px auto;
        flex-direction: column;
      }

      .logout-btn {
        margin-top: 5px;
        margin-left: 0;
      }
    }
  </style>
</head>

<body>
  <header>
    <img src="assets/logo.png" alt="FitFlex Logo" class="logo">
    <div class="user-info">
      <span>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?></span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="form-container">
    <h2>ENTER YOUR BODY DETAILS</h2>
    <p class="form-description">
      INPUT ALL YOUR DETAILS TO ACHIEVE YOUR TARGET AND RECEIVE A PLAN FOR THE SAME
    </p>

    <?php if (isset($error)): ?>
      <div class="error-message">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="age" class="form-label">Age</label>
        <input type="text" id="age" name="age" placeholder="Ages 15-80" required>
      </div>

      <div class="form-group">
        <label class="form-label">Gender :</label>
        <div class="radio-group">
          <label><input type="radio" name="gender" value="male"> Male</label>
          <label><input type="radio" name="gender" value="female" checked> Female</label>
        </div>
      </div>

      <div class="form-group">
        <label for="height" class="form-label">Height (cm)</label>
        <input type="text" id="height" name="height" placeholder="Enter your height in cm" required>
      </div>

      <div class="form-group">
        <label for="weight" class="form-label">Weight (kg)</label>
        <input type="text" id="weight" name="weight" placeholder="Enter your weight in kg" required>
      </div>

      <div class="form-group">
        <label for="target_weight" class="form-label">Target Weight (kg)</label>
        <input type="text" id="target_weight" name="target_weight" placeholder="Enter your target weight in kg" required>
      </div>

      <div class="form-group">
        <label for="target" class="form-label">Target</label>
        <select id="target" name="target" required>
          <option value="fat-loss">Fat Loss</option>
          <option value="body-recomposition" selected>Body Recomposition</option>
          <option value="muscle-gain">Muscle Gain</option>
        </select>
      </div>

      <div class="form-group">
        <label for="activity" class="form-label">Activity</label>
        <select id="activity" name="activity" required>
          <option value="bmr">Basal Metabolic Rate (BMR)</option>
          <option value="sedentary" selected>Sedentary: little or no exercise</option>
          <option value="light-exercise">Light exercise 1-3 times/week</option>
          <option value="moderate-exercise">Moderate: exercise 4-5 times/week</option>
          <option value="active-exercise">Active: daily exercise or intense exercise 3-4 times/week</option>
          <option value="very-active-exercise">Very Active: intense exercise 6-7 times/week</option>
        </select>
      </div>

      <div class="button-row">
        <a href="login.html" class="form-button">Back</a>
        <button type="submit" class="form-button next">Submit</button>
      </div>
    </form>
  </div>

  <script>
    document.querySelector('form').addEventListener('submit', function(e) {
      const age = document.getElementById('age').value;
      const height = document.getElementById('height').value;
      const weight = document.getElementById('weight').value;
      const target_weight = document.getElementById('target_weight').value;
      const target = document.getElementById('target').value;

      if (isNaN(age) || age < 15 || age > 80) {
        alert('Please enter a valid age between 15 and 80');
        e.preventDefault();
        return;
      }

      if (isNaN(height) || height < 100 || height > 250) {
        alert('Please enter a valid height in cm (100-250)');
        e.preventDefault();
        return;
      }

      if (isNaN(weight) || weight < 30 || weight > 300) {
        alert('Please enter a valid weight in kg (30-300)');
        e.preventDefault();
        return;
      }

      if (isNaN(target_weight) || target_weight < 30 || target_weight > 300) {
        alert('Please enter a valid target weight in kg (30-300)');
        e.preventDefault();
        return;
      }

      if (target === 'fat-loss' && parseFloat(target_weight) >= parseFloat(weight)) {
        alert('For fat loss, target weight should be less than current weight');
        e.preventDefault();
        return;
      }

      if (target === 'muscle-gain' && parseFloat(target_weight) <= parseFloat(weight)) {
        alert('For muscle gain, target weight should be greater than current weight');
        e.preventDefault();
        return;
      }
    });

    document.getElementById('target').addEventListener('change', function() {
      const weight = parseFloat(document.getElementById('weight').value);
      const target = this.value;
      const targetWeightField = document.getElementById('target_weight');

      if (!isNaN(weight)) {
        if (target === 'fat-loss') {
          targetWeightField.value = (weight * 0.9).toFixed(1);
        } else if (target === 'muscle-gain') {
          targetWeightField.value = (weight * 1.1).toFixed(1);
        } else {
          targetWeightField.value = weight.toFixed(1);
        }
      }
    });

    document.getElementById('weight').addEventListener('change', function() {
      const weight = parseFloat(this.value);
      const target = document.getElementById('target').value;
      const targetWeightField = document.getElementById('target_weight');

      if (!isNaN(weight)) {
        if (target === 'fat-loss') {
          targetWeightField.value = (weight * 0.9).toFixed(1);
        } else if (target === 'muscle-gain') {
          targetWeightField.value = (weight * 1.1).toFixed(1);
        } else {
          targetWeightField.value = weight.toFixed(1);
        }
      }
    });
  </script>
</body>

</html>