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

$meal_times = ["Breakfast", "Lunch", "Dinner", "Snacks"];
$current_meal_time = "Breakfast";
if (isset($_GET["meal_time"]) && in_array($_GET["meal_time"], $meal_times)) {
    $current_meal_time = $_GET["meal_time"];
}

$feedback = "";
$apiKey = "1MjVyE4++leUa2iRMXPiOQ==aZSLs6REOYYpkFTj";

$selected_date = isset($_GET["date"]) ? $_GET["date"] : date("Y-m-d");
$is_today = ($selected_date == date("Y-m-d"));

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["query"]) && $is_today) {
    $query = trim($_POST["query"]);
    if (!empty($query)) {
        $encodedQuery = urlencode($query);
        $url = "https://api.calorieninjas.com/v1/nutrition?query=$encodedQuery";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Api-Key: $apiKey"]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $feedback = "API Request Error: " . $curl_error;
        } elseif ($httpcode != 200) {
            $feedback = "API Error: Received status code " . $httpcode;
        } else {
            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $feedback = "Error decoding API response.";
            } elseif (!empty($data["items"])) {
                $item = $data["items"][0];
                $newItem = [
                    "name" => $item["name"] ?? 'Unknown Item',
                    "calories" => $item["calories"] ?? 0,
                    "serving_size_g" => $item["serving_size_g"] ?? 100,
                    "protein_g" => $item["protein_g"] ?? 0,
                    "carbohydrates_total_g" => $item["carbohydrates_total_g"] ?? 0,
                    "fat_total_g" => $item["fat_total_g"] ?? 0,
                    "fiber_g" => $item["fiber_g"] ?? 0,
                    "quantity" => 1,
                    "meal_time" => $current_meal_time,
                    "meal_date" => $selected_date
                ];

                $stmt = $conn->prepare("INSERT INTO userdiet (user_email, calorie_intake, food_category, quantity, meal_time, meal_date, protein_g, carbohydrates_g, fat_g) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "sdsissddd",
                    $user_email,
                    $newItem["calories"],
                    $newItem["name"],
                    $newItem["quantity"],
                    $newItem["meal_time"],
                    $newItem["meal_date"],
                    $newItem["protein_g"],
                    $newItem["carbohydrates_total_g"],
                    $newItem["fat_total_g"]
                );

                if ($stmt->execute()) {
                    $feedback = htmlspecialchars(ucfirst($newItem['name'])) . " added successfully to " . htmlspecialchars($current_meal_time) . "!";
                } else {
                    $feedback = "Error saving food item to database: " . $conn->error;
                }
                $stmt->close();
            } else {
                $feedback = "Sorry, no nutritional data found for '" . htmlspecialchars($query) . "'. Try being more specific (e.g., '100g chicken breast').";
            }
        }
    } else {
        $feedback = "Please enter a food item.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?feedback=" . urlencode($feedback) . "&meal_time=" . urlencode($current_meal_time) . "&date=" . urlencode($selected_date));
    exit();
}

if (isset($_GET["delete"]) && $is_today) {
    $diet_id_to_delete = (int)$_GET["delete"];

    $stmt_fetch = $conn->prepare("SELECT food_category, meal_time FROM userdiet WHERE diet_id = ? AND user_email = ? AND meal_date = ?");
    $stmt_fetch->bind_param("iss", $diet_id_to_delete, $user_email, $selected_date);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $item_to_delete = $result_fetch->fetch_assoc();
    $stmt_fetch->close();

    if ($item_to_delete) {
        $stmt = $conn->prepare("DELETE FROM userdiet WHERE diet_id = ? AND user_email = ?");
        $stmt->bind_param("is", $diet_id_to_delete, $user_email);
        if ($stmt->execute()) {
            $feedback = htmlspecialchars(ucfirst($item_to_delete['food_category'])) . " removed from " . htmlspecialchars($item_to_delete['meal_time']) . ".";
        } else {
            $feedback = "Error removing item.";
        }
        $stmt->close();
    } else {
         $feedback = "Item not found or cannot be deleted.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?feedback=" . urlencode($feedback) . "&meal_time=" . urlencode($current_meal_time) . "&date=" . urlencode($selected_date));
    exit();
}

if (isset($_GET["update"]) && $is_today) {
    $diet_id_to_update = (int)$_GET["update"];
    $change = (int)$_GET["change"];

    $stmt_fetch = $conn->prepare("SELECT * FROM userdiet WHERE diet_id = ? AND user_email = ? AND meal_date = ?");
    $stmt_fetch->bind_param("iss", $diet_id_to_update, $user_email, $selected_date);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $food_item = $result_fetch->fetch_assoc();
    $stmt_fetch->close();

    if ($food_item) {
        $current_quantity = $food_item["quantity"];
        $new_quantity = $current_quantity + $change;

        if ($new_quantity < 1) $new_quantity = 1;

        $base_calories = ($current_quantity > 0) ? $food_item["calorie_intake"] / $current_quantity : 0;
        $base_protein = ($current_quantity > 0) ? $food_item["protein_g"] / $current_quantity : 0;
        $base_carbs = ($current_quantity > 0) ? $food_item["carbohydrates_g"] / $current_quantity : 0;
        $base_fat = ($current_quantity > 0) ? $food_item["fat_g"] / $current_quantity : 0;

        $new_total_calories = $base_calories * $new_quantity;
        $new_total_protein = $base_protein * $new_quantity;
        $new_total_carbs = $base_carbs * $new_quantity;
        $new_total_fat = $base_fat * $new_quantity;

        $stmt_update = $conn->prepare("UPDATE userdiet SET calorie_intake = ?, quantity = ?, protein_g = ?, carbohydrates_g = ?, fat_g = ? WHERE diet_id = ? AND user_email = ?");
        $stmt_update->bind_param("didddis",
            $new_total_calories, $new_quantity, $new_total_protein, $new_total_carbs, $new_total_fat,
            $diet_id_to_update, $user_email
        );

        if (!$stmt_update->execute()) {
            $feedback = "Error updating quantity.";
            error_log("Update Error: " . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        $feedback = "Item not found for update.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?feedback=" . urlencode($feedback) . "&meal_time=" . urlencode($current_meal_time) . "&date=" . urlencode($selected_date));
    exit();
}

if (isset($_GET['feedback'])) {
    $feedback = htmlspecialchars($_GET['feedback']);
}

$stmt_display = $conn->prepare("SELECT * FROM userdiet WHERE user_email = ? AND meal_date = ? AND meal_time = ? ORDER BY diet_id");
$stmt_display->bind_param("sss", $user_email, $selected_date, $current_meal_time);
$stmt_display->execute();
$result_display = $stmt_display->get_result();
$current_meal_items = [];
while ($row = $result_display->fetch_assoc()) {
    $current_meal_items[] = $row;
}
$stmt_display->close();

$totalCalories = 0;
$totalProtein = 0;
$totalCarbs = 0;
$totalFats = 0;
$targetCalories = 2000;

$stmt_goal = $conn->prepare("SELECT calorie FROM userstable WHERE user_email = ?");
if ($stmt_goal) {
    $stmt_goal->bind_param("s", $user_email);
    if ($stmt_goal->execute()) {
        $result_goal = $stmt_goal->get_result();
        if ($row_goal = $result_goal->fetch_assoc()) {
            if (isset($row_goal['calorie']) && is_numeric($row_goal['calorie']) && $row_goal['calorie'] > 0) {
                $targetCalories = (int)$row_goal['calorie'];
            }
        }
    } else {
        error_log("Error executing goal query: " . $stmt_goal->error);
    }
    $stmt_goal->close();
} else {
     error_log("Error preparing goal query: " . $conn->error);
}


$stmt_totals = $conn->prepare("SELECT calorie_intake, protein_g, carbohydrates_g, fat_g FROM userdiet WHERE user_email = ? AND meal_date = ?");
$stmt_totals->bind_param("ss", $user_email, $selected_date);
$stmt_totals->execute();
$result_totals = $stmt_totals->get_result();

while ($row = $result_totals->fetch_assoc()) {
    $totalCalories += $row["calorie_intake"] ?? 0;
    $totalProtein += $row["protein_g"] ?? 0;
    $totalCarbs += $row["carbohydrates_g"] ?? 0;
    $totalFats += $row["fat_g"] ?? 0;
}
$stmt_totals->close();

$remainingCalories = $targetCalories - $totalCalories;

$today_ts = strtotime(date("Y-m-d"));
$date_buttons = [
    strtotime("-3 days", $today_ts),
    strtotime("-2 days", $today_ts),
    strtotime("-1 day", $today_ts),
    $today_ts
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Daily Nutrition</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        header { background: black !important; width: 100% !important; height: 178px !important; display: flex !important; align-items: center !important; padding: 0 20px !important; justify-content: space-between !important; position: relative !important; margin: 0 !important; box-sizing: border-box !important; font-family: Arial, sans-serif !important; }
        header .logo { width: 210.35px !important; height: 130px !important; margin: 0 !important; padding: 0 !important; }
        header .nav { display: flex !important; gap: 15px !important; margin-left: auto !important; margin-right: 25px !important; background: transparent !important; padding: 0 !important; flex-wrap: nowrap !important; }
        header .nav button { background: none !important; border: none !important; color: white !important; font-size: 18px !important; height: 30px !important; cursor: pointer !important; margin: 0 !important; padding: 0 6px 0 6px !important; box-shadow: none !important; line-height: normal !important; font-weight: normal !important; font-family: Arial, sans-serif !important; }
        header .nav a { text-decoration: none; }
        header .user-menu { display: flex !important; flex-direction: column !important; align-items: flex-end !important; margin-left: auto !important; margin-right: 0px !important; gap: 10px !important; padding: 0 !important; padding-right: 2px !important; }
        header .welcome-text { color: white !important; font-size: 18px !important; margin: 0 !important; font-weight: normal !important; font-family: Arial, sans-serif !important; text-align: right !important; width: 100% !important; }
        header .user-actions { display: flex !important; gap: 10px !important; margin: 0 !important; padding: 0 !important; justify-content: center !important; width: 100% !important; }
        header .logout-btn, header .profile-btn { background: #dc3545 !important; color: white !important; border: none !important; font-size: 16px !important; padding: 8px 15px !important; border-radius: 10px !important; cursor: pointer !important; text-decoration: none !important; display: inline-block !important; line-height: normal !important; font-weight: normal !important; text-transform: none !important; box-shadow: none !important; font-family: Arial, sans-serif !important; }
        header .profile-btn { background: #28a745 !important; }
        .profile-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; z-index: 1000; }
        .profile-modal-content { background: white; width: 80%; max-width: 600px; padding: 20px; border-radius: 10px; position: relative; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); }
        .close-btn { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
        .profile-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .profile-table th, .profile-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .profile-link { display: block; margin-top: 10px; text-align: center; color: #28a745; text-decoration: none; font-weight: bold; }
        .profile-link:hover { text-decoration: underline; }
        .navbar-brand { font-weight: 700; font-size: 1.8rem; color: #343a40; }
        .navbar-subtitle { font-size: 0.8rem; color: #6c757d; letter-spacing: 1px; margin-top: -8px; }
        .main-content { padding-top: 2rem; }
        .section-title { font-weight: 600; font-size: 2.5rem; margin-bottom: 1.5rem; color: #343a40; }
        .date-selector { display: flex; align-items: center; }
        .date-box { background-color: #e9ecef; padding: 10px 15px; border-radius: 8px; text-align: center; margin: 0 5px; color: #495057; font-weight: 500; cursor: pointer; transition: background-color 0.2s ease; text-decoration: none; }
        .date-box:hover { background-color: #dee2e6; }
        .date-box span { display: block; font-size: 0.8rem; }
        .date-box.active { background-color: #fd7e14; color: white; }
        .calendar-btn { background-color: #e9ecef; padding: 10px 14px; border-radius: 8px; margin-right: 5px; color: #495057; cursor: pointer; transition: background-color 0.2s ease; border: none; }
        .calendar-btn:hover { background-color: #dee2e6; }
        .calendar-picker { position: relative; }
        #datePicker { position: absolute; opacity: 0; height: 1px; width: 1px; }
        .calories-bar { background-color: #e9ecef; padding: 10px; border-radius: 8px; margin-top: 1.5rem; margin-bottom: 1.5rem; font-weight: 500; }
        .meal-tabs .nav-link { color: #6c757d; border: none; border-bottom: 2px solid transparent; padding: 5px 15px; font-size: 0.9rem; }
        .meal-tabs .nav-link.active { color: #fd7e14; border-bottom: 2px solid #fd7e14; font-weight: 600; }
        .total-calories-display { background-color: #ffdd57; padding: 15px 25px; border-radius: 50px; font-weight: 600; font-size: 1.1rem; color: #343a40; display: inline-block; margin-bottom: 1.5rem; }
        .add-food-form { display: block; margin-bottom: 2rem; }
        .add-food-form .input-group { border-radius: 50px; background-color: #a3e4a3; padding: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        .add-food-form input[type="text"] { border: none; background-color: transparent; border-radius: 50px 0 0 50px; padding-left: 20px; font-weight: 500; color: #198754; }
        .add-food-form input[type="text"]::placeholder { color: #198754; opacity: 0.7; }
        .add-food-form input[type="text"]:focus { box-shadow: none; background-color: transparent; }
        .add-food-form button { border-radius: 0 50px 50px 0; background-color: #a3e4a3; border: none; color: #198754; font-weight: 600; padding: 10px 20px; transition: background-color 0.2s ease; }
        .add-food-form button:hover { background-color: #8fcf8f; }
        .food-item-card { background-color: #ffffff; border: 1px solid #e9ecef; border-radius: 10px; margin-bottom: 1rem; padding: 1rem; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
        .food-item-details h5 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.2rem; color: #343a40; }
        .food-item-details .text-muted { font-size: 0.9rem; color: #6c757d !important; }
        .nutrient-info { text-align: center; padding: 0 10px; }
        .nutrient-info:not(:last-child) { border-right: 2px solid; padding-right: 15px; }
        .nutrient-info.protein { border-color: #a3e4a3; }
        .nutrient-info.carbs { border-color: #ffdd57; }
        .nutrient-info.fats { border-color: #fd7e14; border-right: none; padding-right: 10px; }
        .nutrient-info span { display: block; font-size: 0.8rem; color: #6c757d; margin-top: 0.2rem; }
        .nutrient-info strong { font-size: 1rem; color: #343a40; }
        .quantity-controls .btn { padding: 0.2rem 0.5rem; font-size: 0.9rem; margin: 0 3px; border: 1px solid #dee2e6; color: #495057; background-color: #f8f9fa; }
        .quantity-controls .btn-delete { background-color: #f8d7da; border-color: #f5c2c7; color: #842029; }
        .quantity-controls .btn:hover { background-color: #e9ecef; }
        .quantity-controls .btn-delete:hover { background-color: #f1aeb5; border-color: #e89da5; }
        .quantity-controls span { display: inline-block; min-width: 20px; text-align: center; font-weight: 500; }
        .feedback-alert { margin-top: 1rem; margin-bottom: 1rem; font-size: 0.9rem; }
        footer { margin-top: 3rem; padding: 1rem 0; background-color: #343a40; color: #f8f9fa; text-align: center; font-size: 0.9rem; }
        .nutrient-info { text-align: center; padding: 0 10px; position: relative; }
        .nutrient-info:not(:last-child) { border-right: none; padding-right: 15px; }
        .nutrient-bar { display: inline-block; width: 4px; height: 40px; background-color: #e9ecef; position: relative; margin: 0 auto 5px auto; }
        .nutrient-bar-fill { position: absolute; bottom: 0; left: 0; width: 100%; }
        .protein-bar .nutrient-bar-fill { background-color: #a3e4a3; }
        .carbs-bar .nutrient-bar-fill { background-color: #ffdd57; }
        .fats-bar .nutrient-bar-fill { background-color: #fd7e14; }
        .past-date-notice { background-color: #ffe8cc; border-left: 4px solid #fd7e14; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9rem; color: #864400; }
        .track-macros-btn {
            background-color:rgba(227, 227, 227, 0.77);
            color: #343a40;
            border: none;
            padding: 10px 20px;
            border-radius: 28px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s ease;
            margin-left: 15px;
        }
        .track-macros-btn:hover {
            background-color: rgba(156, 156, 156, 0.77);
            color: #343a40;
        }
        .title-button-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem; 
        }
         @media (max-width: 768px) {
            .title-button-group {
                flex-direction: column; 
                align-items: flex-start;
            }
            .track-macros-btn {
                margin-left: 0; 
                margin-top: 10px; 
                align-self: center; 
            }
         }

    </style>
</head>
<body>
    <header>
        <a href="./dashboard.php"><img src="assets/logo.png" alt="FitFlex Logo" class="logo"></a>
        <nav class="nav">
            <a href="./planner.php"><button>Planner</button></a>
            <a href="./workout.php"><button>Workouts</button></a>
            <a href="./dietpage.php"><button style="background-color: #555; border-radius: 5px;">Diets</button></a>
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
                    <tr><th>Name</th><td><?php echo htmlspecialchars($_SESSION["user_name"]); ?></td></tr>
                    <tr><th>Email</th><td><?php echo htmlspecialchars($_SESSION["user_email"]); ?></td></tr>
                </table>
                <a href="profile.php" class="profile-link">Go to Update Information</a>
            </div>
        </div>
    </div>

    <div class="container main-content">
        <div class="row align-items-center">
            <div class="col-md-6 title-button-group">
                <h1 class="section-title mb-0">Daily <br> Nutrition</h1>
                 <a href="macro.php" class="track-macros-btn"><i class="fas fa-chart-pie me-2"></i>Track Macros</a>
            </div>
            <div class="col-md-6 d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                <div class="date-selector">
                    <div class="calendar-picker">
                        <button class="calendar-btn" id="calendarToggle"><i class="fas fa-calendar-alt"></i></button>
                        <input type="text" id="datePicker" value="<?= $selected_date ?>">
                    </div>
                    <?php
                    foreach ($date_buttons as $index => $ts) {
                        $date_str = date('Y-m-d', $ts);
                        $day = date('d', $ts);
                        $month = date('M', $ts);
                        $active = ($date_str == $selected_date) ? 'active' : '';
                        $label = '';
                        if ($index === 0) $label = '3 days ago'; elseif ($index === 1) $label = '2 days ago'; elseif ($index === 2) $label = 'Yesterday'; else $label = 'Today';
                        echo "<a href='?date={$date_str}&meal_time={$current_meal_time}' class='date-box {$active}'><span>{$month}</span> {$day}<span>{$label}</span></a>";
                    } ?>
                </div>
            </div>
        </div>

        <div class="row"><div class="col text-center calories-bar">Daily Remaining = <?= $targetCalories ?> - <?= round($totalCalories) ?> = <?= round($remainingCalories) ?> kcal</div></div>

        <?php if (!$is_today): ?>
        <div class="past-date-notice"><i class="fas fa-info-circle"></i> You are viewing nutrition data for <?= date('F j, Y', strtotime($selected_date)) ?>. Food entries cannot be modified for past dates.</div>
        <?php endif; ?>

        <div class="row justify-content-center mb-3"><div class="col-auto"><ul class="nav meal-tabs">
            <li class="nav-item"><a class="nav-link <?= $current_meal_time == 'Breakfast' ? 'active' : '' ?>" href="?meal_time=Breakfast&date=<?= $selected_date ?>">Breakfast</a></li>
            <li class="nav-item"><a class="nav-link <?= $current_meal_time == 'Lunch' ? 'active' : '' ?>" href="?meal_time=Lunch&date=<?= $selected_date ?>">Lunch</a></li>
            <li class="nav-item"><a class="nav-link <?= $current_meal_time == 'Dinner' ? 'active' : '' ?>" href="?meal_time=Dinner&date=<?= $selected_date ?>">Dinner</a></li>
             <li class="nav-item"><a class="nav-link <?= $current_meal_time == 'Snacks' ? 'active' : '' ?>" href="?meal_time=Snacks&date=<?= $selected_date ?>">Snacks</a></li>
        </ul></div></div>

        <div class="row align-items-center mb-4"><div class="col text-center"><div class="total-calories-display"><i class="fas fa-fire-alt me-2"></i> <?= round($totalCalories) ?> calories Today</div></div></div>

        <?php if ($is_today): ?>
        <form method="post" class="add-food-form"><div class="input-group"><input type="text" name="query" class="form-control" placeholder="Search for a food (e.g., 100g chicken breast)" required><button type="submit" class="btn"><i class="fas fa-plus me-2"></i> Add Food to <?= htmlspecialchars($current_meal_time) ?></button></div></form>
        <?php endif; ?>

        <?php if (!empty($feedback)): ?><div class="alert alert-info feedback-alert"><?= $feedback ?></div><?php endif; ?>

        <div class="row"><div class="col">
            <?php if (empty($current_meal_items)): ?>
                <div class="text-center py-5"><i class="fas fa-utensils fa-3x mb-3 text-muted"></i><p class="lead">No food items added for <?= htmlspecialchars($current_meal_time) ?> on <?= date('F j, Y', strtotime($selected_date)) ?>.</p><?php if ($is_today): ?><p>Use the search box above to add food items.</p><?php endif; ?></div>
            <?php else: ?>
                <?php foreach ($current_meal_items as $item):
                    $quantity = $item["quantity"] ?? 1;
                ?>
                <div class="food-item-card"><div class="row align-items-center">
                    <div class="col-md-4 food-item-details"><h5><?= htmlspecialchars(ucfirst($item['food_category'])) ?></h5><p class="text-muted">🔥<?= round($item['calorie_intake']) ?> kcal <?php if ($item['quantity'] > 1): ?>(<?= $item['quantity'] ?> servings)<?php endif; ?></p></div>
                    <div class="col-md-5 d-flex justify-content-around">
                        <div class="nutrient-info protein"><div class="nutrient-bar protein-bar"><div class="nutrient-bar-fill" style="height: <?= min(100, (($item['protein_g'] ?? 0) / 60) * 100) ?>%"></div></div><strong><?= round($item['protein_g'] ?? 0, 1) ?>g</strong><span>Protein</span></div>
                        <div class="nutrient-info carbs"><div class="nutrient-bar carbs-bar"><div class="nutrient-bar-fill" style="height: <?= min(100, (($item['carbohydrates_g'] ?? 0) / 300) * 100) ?>%"></div></div><strong><?= round($item['carbohydrates_g'] ?? 0, 1) ?>g</strong><span>Carbs</span></div>
                        <div class="nutrient-info fats"><div class="nutrient-bar fats-bar"><div class="nutrient-bar-fill" style="height: <?= min(100, (($item['fat_g'] ?? 0) / 70) * 100) ?>%"></div></div><strong><?= round($item['fat_g'] ?? 0, 1) ?>g</strong><span>Fats</span></div>
                    </div>
                    <div class="col-md-3 quantity-controls text-end">
                        <?php if ($is_today): ?>
                        <a href="?update=<?= $item['diet_id'] ?>&change=-1&meal_time=<?= urlencode($current_meal_time) ?>&date=<?= urlencode($selected_date) ?>" class="btn">-</a>
                        <span><?= $item['quantity'] ?></span>
                        <a href="?update=<?= $item['diet_id'] ?>&change=1&meal_time=<?= urlencode($current_meal_time) ?>&date=<?= urlencode($selected_date) ?>" class="btn">+</a>
                        <a href="?delete=<?= $item['diet_id'] ?>&meal_time=<?= urlencode($current_meal_time) ?>&date=<?= urlencode($selected_date) ?>" class="btn btn-delete"><i class="fas fa-trash-alt"></i></a>
                        <?php endif; ?>
                    </div>
                </div></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div></div>

        <div class="row mt-5"><div class="col-md-4"><div class="card mb-4"><div class="card-body"><h5 class="card-title"><i class="fas fa-chart-pie me-2"></i> Todays Nutrient Breakdown</h5><div class="d-flex justify-content-around text-center mt-4"><div><h6><?= round($totalProtein) ?>g</h6><small class="text-muted">Protein</small></div><div><h6><?= round($totalCarbs) ?>g</h6><small class="text-muted">Carbs</small></div><div><h6><?= round($totalFats) ?>g</h6><small class="text-muted">Fats</small></div></div></div></div></div><div class="col-md-8"><div class="card"><div class="card-body"><h5 class="card-title"><i class="fas fa-lightbulb me-2"></i> Tip of the Day</h5><p class="card-text">Focus on whole foods like fruits, vegetables, lean proteins, and whole grains. They provide essential nutrients your body needs and help maintain energy levels throughout the day.</p></div></div></div></div>
    </div>

    <footer><div class="container"><p>© 2025 FitFlex. All rights reserved.</p></div></footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const datePicker = flatpickr("#datePicker", { dateFormat: "Y-m-d", maxDate: "today", defaultDate: "<?= $selected_date ?>", onChange: function(selectedDates, dateStr) { window.location.href = `?date=${dateStr}&meal_time=<?= urlencode($current_meal_time) ?>`; } });
        document.getElementById('calendarToggle').addEventListener('click', function() { datePicker.open(); });
        function openProfile() { document.getElementById('profileModal').style.display = 'flex'; }
        function closeProfile() { document.getElementById('profileModal').style.display = 'none'; }
        window.addEventListener('click', function(event) { if (event.target === document.getElementById('profileModal')) { closeProfile(); } });
    </script>
</body>
</html>