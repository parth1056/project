<?php
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "parth");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_email = $_SESSION["user_email"];
$user_name = $_SESSION["user_name"];
$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $age = filter_input(INPUT_POST, "age", FILTER_VALIDATE_INT, ["options" => ["min_range" => 15, "max_range" => 80]]);
    $gender = $_POST["gender"] ?? null;
    $height = filter_input(INPUT_POST, "height", FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 100, "max_range" => 250]]);
    $weight = filter_input(INPUT_POST, "weight", FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 30, "max_range" => 300]]);
    $target = $_POST["target"] ?? null;
    $activity = $_POST["activity"] ?? null;
    $target_weight = filter_input(INPUT_POST, "target_weight", FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 30, "max_range" => 300]]);

    if ($age === false || empty($gender) || $height === false || $weight === false || empty($target) || empty($activity) || $target_weight === false) {
        $error = "All fields are required and must be valid.";
    } else {

        if ($target === 'fat-loss' && $target_weight >= $weight) {
             $error = 'For fat loss, target weight should be less than current weight';
        } elseif ($target === 'muscle-gain' && $target_weight <= $weight) {
             $error = 'For muscle gain, target weight should be greater than current weight';
        }

        if ($error === null) {
             $conn->begin_transaction();
             try {
                 $stmt_user = $conn->prepare("UPDATE userstable SET user_age = ?, user_gender = ?, user_height = ?, user_weight = ?, target_weight = ? WHERE user_email = ?");
                 if (!$stmt_user) throw new Exception("Prepare failed (userstable): " . $conn->error);
                 $stmt_user->bind_param("issdds", $age, $gender, $height, $weight, $target_weight, $user_email); 
                 if (!$stmt_user->execute()) throw new Exception("Execute failed (userstable): " . $stmt_user->error);
                 $stmt_user->close();


                 $plan_days_val = 3; 
                 switch ($activity) {
                     case 'bmr': $plan_days_val = 1; break; 
                     case 'sedentary': $plan_days_val = 3; break; 
                     case 'light-exercise': $plan_days_val = 4; break; 
                     case 'moderate-exercise': $plan_days_val = 5; break; 
                     case 'active-exercise': $plan_days_val = 6; break; 
                     case 'very-active-exercise': $plan_days_val = 7; break; 
                 }
                 $exercises_per_day_val = 5; 

                 $stmt_planner_check = $conn->prepare("SELECT planner_id FROM workoutplanner WHERE user_email = ?");
                 if (!$stmt_planner_check) throw new Exception("Prepare failed (planner check): " . $conn->error);
                 $stmt_planner_check->bind_param("s", $user_email);
                 $stmt_planner_check->execute();
                 $result_planner = $stmt_planner_check->get_result();
                 $planner_id = null;

                 if ($result_planner->num_rows > 0) {
                      $planner_row = $result_planner->fetch_assoc();
                      $planner_id = $planner_row['planner_id'];
                      $stmt_planner = $conn->prepare("UPDATE workoutplanner SET plan_days = ?, exercises_per_day = ? WHERE planner_id = ?");
                       if (!$stmt_planner) throw new Exception("Prepare failed (planner update): " . $conn->error);
                      $stmt_planner->bind_param("iii", $plan_days_val, $exercises_per_day_val, $planner_id);
                 } else {
                      $stmt_planner = $conn->prepare("INSERT INTO workoutplanner (user_email, plan_days, exercises_per_day) VALUES (?, ?, ?)");
                      if (!$stmt_planner) throw new Exception("Prepare failed (planner insert): " . $conn->error);
                      $stmt_planner->bind_param("sii", $user_email, $plan_days_val, $exercises_per_day_val);
                 }

                 if (!$stmt_planner->execute()) throw new Exception("Execute failed (workoutplanner): " . $stmt_planner->error);
                 if ($planner_id === null) {
                    $planner_id = $conn->insert_id;
                 }
                 $stmt_planner->close();
                 $stmt_planner_check->close();

                 if ($result_planner->num_rows === 0 && $planner_id) {
                     $muscle_groups = array('Chest', 'Legs', 'Arms', 'Core', 'Shoulder', 'Back');
                     $stmt_muscle = $conn->prepare("INSERT INTO plannermusclegroup (planner_id, muscle_group, selected) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE selected=selected");
                     if (!$stmt_muscle) throw new Exception("Prepare failed (plannermusclegroup): " . $conn->error);

                     foreach ($muscle_groups as $muscle) {
                         $stmt_muscle->bind_param("is", $planner_id, $muscle);
                         if (!$stmt_muscle->execute()) {
                              error_log("Execute failed (plannermusclegroup) for muscle " . $muscle . ": " . $stmt_muscle->error);
                         }
                     }
                     $stmt_muscle->close();
                 }

                 $conn->commit();
                 header("Location: dashboard.php");
                 exit();

             } catch (Exception $e) {
                 $conn->rollback();
                 $error = "An error occurred: " . $e->getMessage();
                 error_log("Transaction failed: " . $e->getMessage());
             }
        }
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
            font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;
            display: flex; flex-direction: column; align-items: center; overflow-x: hidden;
        }
        header {
            background: black; width: 100%; height: 178px; display: flex; align-items: center;
            padding: 0 20px; justify-content: flex-start;
        }
        .logo { width: 210.35px; height: 130px; padding-left: 20px; }
        .form-container {
            background-color: #fff; padding: 30px 40px; border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); margin-top: 30px;
            max-width: 800px; width: 95%; box-sizing: border-box;
        }
        h2 {
            color: #333; font-size: 1.6em; margin-top: 0; margin-bottom: 15px; text-align: center;
        }
        p.form-description {
            color: #777; text-align: center; margin-bottom: 25px; font-size: 1em; line-height: 1.4;
        }
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block; margin-bottom: 8px; color: #555; font-weight: bold; font-size: 0.95em;
        }
        .radio-group { display: flex; align-items: center; gap: 20px; }
        .radio-group label { margin: 0; font-weight: normal; color: #666; font-size: 0.95em; }
        input[type="radio"] { margin-right: 5px; }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em;
            color: #444; box-sizing: border-box;
        }
        select {
            appearance: none; -webkit-appearance: none; -moz-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="currentColor" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
            background-repeat: no-repeat; background-position-x: calc(100% - 10px); background-position-y: center;
            padding-right: 30px;
        }
        select::-ms-expand { display: none; }
        .button-row { display: flex; justify-content: space-between; margin-top: 30px; }
        .form-button {
            padding: 10px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em;
            color: white; background-color: #6c757d; transition: background-color 0.3s;
            text-decoration: none; display: inline-block; text-align: center;
        }
        .form-button.next { background-color: #7952b3; }
        .form-button:hover { opacity: 0.9; }
        .error-message {
            color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;
            padding: 10px; margin-bottom: 20px; text-align: center;
        }
        .user-info { display: flex; align-items: center; margin-left: auto; color: white; }
        .logout-btn {
            background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px;
            margin-left: 10px; text-decoration: none; font-size: 0.9em; margin-right: 20px;
        }
        @media (max-width: 768px) {
            .form-container { padding: 20px; }
            header { flex-direction: column; text-align: center; height: auto; padding-bottom: 15px;}
            .logo { margin-bottom: 10px; padding-left: 0; }
            .user-info { margin: 10px auto; flex-direction: column; }
            .logout-btn { margin-top: 5px; margin-left: 0; margin-right: 0; }
            input[type="text"], input[type="number"], select { width: 100%; }
        }
    </style>
</head>

<body>
    <header>
        <img src="assets/logo.png" alt="FitFlex Logo" class="logo">
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="form-container">
        <h2>ENTER YOUR BODY DETAILS</h2>
        <p class="form-description">
            INPUT ALL YOUR DETAILS TO ACHIEVE YOUR TARGET AND RECEIVE A PLAN FOR THE SAME
        </p>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="detailsForm">
            <div class="form-group">
                <label for="age" class="form-label">Age</label>
                <input type="number" id="age" name="age" placeholder="Ages 15-80" required min="15" max="80" step="1">
            </div>

            <div class="form-group">
                <label class="form-label">Gender :</label>
                <div class="radio-group">
                    <label><input type="radio" name="gender" value="male" required> Male</label>
                    <label><input type="radio" name="gender" value="female" checked required> Female</label>
                </div>
            </div>

            <div class="form-group">
                <label for="height" class="form-label">Height (cm)</label>
                <input type="number" id="height" name="height" placeholder="Enter your height in cm" required min="100" max="250" step="0.1">
            </div>

            <div class="form-group">
                <label for="weight" class="form-label">Weight (kg)</label>
                <input type="number" id="weight" name="weight" placeholder="Enter your weight in kg" required min="30" max="300" step="0.1">
            </div>

            <div class="form-group">
                <label for="target_weight" class="form-label">Target Weight (kg)</label>
                <input type="number" id="target_weight" name="target_weight" placeholder="Enter your target weight in kg" required min="30" max="300" step="0.1">
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
                <label for="activity" class="form-label">Activity Level</label>
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
        const detailsForm = document.getElementById('detailsForm');
        const ageInput = document.getElementById('age');
        const heightInput = document.getElementById('height');
        const weightInput = document.getElementById('weight');
        const targetWeightInput = document.getElementById('target_weight');
        const targetSelect = document.getElementById('target');

        function updateTargetWeightSuggestion() {
             const weight = parseFloat(weightInput.value);
             const target = targetSelect.value;

             if (!isNaN(weight) && weight >= 30 && weight <= 300) {
                 if (target === 'fat-loss') {
                     targetWeightInput.value = (weight * 0.9).toFixed(1);
                 } else if (target === 'muscle-gain') {
                     targetWeightInput.value = (weight * 1.1).toFixed(1);
                 } else { 
                     targetWeightInput.value = weight.toFixed(1);
                 }
             }
        }

        detailsForm.addEventListener('submit', function (e) {
            const age = parseInt(ageInput.value, 10);
            const height = parseFloat(heightInput.value);
            const weight = parseFloat(weightInput.value);
            const targetWeight = parseFloat(targetWeightInput.value);
            const target = targetSelect.value;
            let errors = [];

            if (isNaN(age) || age < 15 || age > 80) {
                errors.push('Please enter a valid age between 15 and 80.');
            }
            if (isNaN(height) || height < 100 || height > 250) {
                errors.push('Please enter a valid height in cm (100-250).');
            }
            if (isNaN(weight) || weight < 30 || weight > 300) {
                errors.push('Please enter a valid weight in kg (30-300).');
            }
            if (isNaN(targetWeight) || targetWeight < 30 || targetWeight > 300) {
                errors.push('Please enter a valid target weight in kg (30-300).');
            }
            if (!isNaN(weight) && !isNaN(targetWeight)) {
                if (target === 'fat-loss' && targetWeight >= weight) {
                    errors.push('For fat loss, target weight must be less than current weight.');
                }
                if (target === 'muscle-gain' && targetWeight <= weight) {
                    errors.push('For muscle gain, target weight must be greater than current weight.');
                }
            }


            if (errors.length > 0) {
                const errorDiv = document.querySelector('.error-message');
                 if (errorDiv) {
                      errorDiv.textContent = errors.join('\n');
                      errorDiv.style.display = 'block';
                 } else {
                      alert(errors.join('\n'));
                 }
                e.preventDefault(); 
            } else {
                 const errorDiv = document.querySelector('.error-message');
                 if (errorDiv) {
                      errorDiv.style.display = 'none';
                      errorDiv.textContent = '';
                 }
            }
        });

        targetSelect.addEventListener('change', updateTargetWeightSuggestion);
        weightInput.addEventListener('input', updateTargetWeightSuggestion);

        document.addEventListener('DOMContentLoaded', updateTargetWeightSuggestion);

    </script>
</body>
</html>