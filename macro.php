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
$userName = $_SESSION["user_name"] ?? 'User';

$selected_date = isset($_GET["date"]) ? $_GET["date"] : date("Y-m-d");
$today_ts = strtotime(date("Y-m-d"));
$selected_ts = strtotime($selected_date);

$date_buttons = [];
$start_ts = strtotime("-2 days", $selected_ts);
for ($i = 0; $i < 5; $i++) {
    $date_buttons[] = strtotime("+$i days", $start_ts);
}

$totalCalories = 0;
$totalProtein = 0;
$totalCarbs = 0;
$totalFats = 0;
$meals = ['Breakfast' => [], 'Lunch' => [], 'Dinner' => [], 'Snacks' => []];

$targetCalories = 2000; // Default value
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


$stmt = $conn->prepare("SELECT food_category, calorie_intake, protein_g, carbohydrates_g, fat_g, meal_time, quantity FROM userdiet WHERE user_email = ? AND meal_date = ? ORDER BY meal_time, diet_id");
$stmt->bind_param("ss", $user_email, $selected_date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $quantity = max(1, (int)($row["quantity"] ?? 1));
    $calories = $row["calorie_intake"] ?? 0;
    $protein = $row["protein_g"] ?? 0;
    $carbs = $row["carbohydrates_g"] ?? 0;
    $fat = $row["fat_g"] ?? 0;

    $totalCalories += $calories;
    $totalProtein += $protein;
    $totalCarbs += $carbs;
    $totalFats += $fat;

    $meal_time = $row['meal_time'];
    if (!isset($meals[$meal_time])) {
         $meals[$meal_time] = [];
    }

     $base_protein = $quantity > 0 ? $protein / $quantity : 0;
     $base_fat = $quantity > 0 ? $fat / $quantity : 0;
     $base_carbs = $quantity > 0 ? $carbs / $quantity : 0;

    $meals[$meal_time][] = [
        'name' => $row['food_category'],
        'protein_g' => round($base_protein, 1),
        'fat_g' => round($base_fat, 1),
        'carbohydrates_g' => round($base_carbs, 1),
        'quantity' => $quantity
    ];
}
$stmt->close();
$conn->close();


$targetProtein = 100;
$targetFat = 70;
$targetCarbs = 230;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Track Macros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
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
        .close-btn { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 16px; line-height: 1;}
        .profile-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .profile-table th, .profile-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .profile-link { display: block; margin-top: 10px; text-align: center; color: #28a745; text-decoration: none; font-weight: bold; }
        .profile-link:hover { text-decoration: underline; }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 0; }
        .main-content { padding: 30px; max-width: 800px; margin: 20px auto; }
        .page-title { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 2.8rem; margin-bottom: 25px; color: #2c3e50; text-align: center; }
        .date-navigation { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .date-tiles { display: flex; gap: 8px; }
        .date-box { background-color: #fff; padding: 8px 10px; border-radius: 6px; text-align: center; color: #555; font-weight: 500; cursor: pointer; transition: all 0.2s ease; text-decoration: none; border: 1px solid #e0e0e0; min-width: 55px; }
        .date-box:hover:not(.disabled) { background-color: #f0f0f0; border-color: #d0d0d0; transform: translateY(-2px); }
        .date-box span { display: block; font-size: 0.7rem; line-height: 1.1; text-transform: uppercase; color: #888; }
        .date-box .day-number { font-size: 1.2rem; font-weight: 600; margin: 3px 0; color: #444; }
        .date-box.active { background-color: #e74c3c; color: white; border-color: #e74c3c; transform: translateY(0); }
        .date-box.active span, .date-box.active .day-number { color: white; }
        .date-box.disabled { background-color: #f9f9f9; border-color: #f0f0f0; color: #bbb; cursor: default; pointer-events: none; }
        .date-box.disabled span { color: #ccc; }
        .date-box.disabled .day-number { color: #bbb; }
        .calendar-btn { background-color: #fff; padding: 10px 14px; border-radius: 6px; color: #555; cursor: pointer; transition: background-color 0.2s ease; border: 1px solid #e0e0e0; margin-left: 10px; }
        .calendar-btn:hover { background-color: #f0f0f0; }
        .calendar-picker { position: relative; }
        #datePicker { position: absolute; opacity: 0; height: 1px; width: 1px; top: 0; left: 0; }
        .summary-card { background-color: #ffffff; padding: 20px 30px; border-radius: 8px; margin-bottom: 40px; box-shadow: 0 3px 8px rgba(0,0,0,0.07); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; border: 1px solid #eee; }
        .summary-card h2 { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.6rem; margin: 0 0 15px 0; color: #2c3e50; width: 100%; text-align: left; border-bottom: 1px solid #f0f0f0; padding-bottom: 8px; }
        .summary-card .calorie-info { font-size: 1.2rem; font-weight: 500; color: #333; flex-basis: 48%; margin-bottom: 10px; }
        .summary-card .macro-info { text-align: right; font-size: 1rem; color: #555; flex-basis: 48%; line-height: 1.7; margin-bottom: 10px; }
        .summary-card .macro-info span { display: block; }
        .summary-card .macro-info strong { color: #333; font-weight: 600; margin-right: 5px; }
        .meals-section h2 { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 2rem; margin-bottom: 25px; color: #2c3e50; text-align: center; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; }
        .meal-block { background-color: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .meal-block h3 { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.4rem; margin-bottom: 12px; color: #34495e; border-bottom: 1px solid #f0f0f0; padding-bottom: 6px; }
        .meal-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #f0f0f0; }
        .meal-item:last-child { border-bottom: none; }
        .meal-item .food-name { font-size: 0.95rem; color: #444; flex-grow: 1; margin-right: 15px; }
        .meal-item .macros { display: flex; gap: 12px; font-size: 0.85rem; color: #888; white-space: nowrap; }
        .meal-item .macros span { min-width: 50px; text-align: right;}
        .no-items-message { color: #999; font-style: italic; padding: 10px 0; text-align: center; font-size: 0.9rem; }
        footer { background-color: #333; color: white; text-align: center; padding: 15px 0; margin-top: 40px; font-size: 0.9em; }
        @media (max-width: 768px) { .summary-card .calorie-info, .summary-card .macro-info { flex-basis: 100%; text-align: left;} .date-navigation { flex-direction: column; align-items: stretch; gap: 15px;} .date-tiles { justify-content: center; flex-wrap: wrap;} header { flex-wrap: wrap; height: auto !important; padding-bottom: 10px !important;} }
        @media (max-width: 480px) { .page-title { font-size: 2rem; } .summary-card h2 { font-size: 1.5rem;} .summary-card .calorie-info {font-size: 1rem;} .summary-card .macro-info {font-size: 0.9rem;} .meals-section h2 { font-size: 1.6rem;} .meal-block h3 { font-size: 1.2rem;} .meal-item { flex-direction: column; align-items: flex-start; } .meal-item .macros { margin-top: 5px; width: 100%; justify-content: space-between; gap: 5px; } .user-menu { flex-direction: row; align-items: center; width: 100%; justify-content: space-between; margin: 10px 0 0 0;} .welcome-text { text-align: left; width: auto;} .user-actions { width: auto;} }
    </style>
</head>
<body>
    <header>
        <a href="./dashboard.php"><img src="assets/logo.png" alt="FitFlex Logo" class="logo"></a>
        <nav class="nav">
            <a href="./planner.php"><button>Planner</button></a>
            <a href="./workout.php"><button>Workouts</button></a>
            <a href="./dietpage.php"><button>Diets</button></a>
            <a href="./about.php"><button>About Us</button></a>
        </nav>
        <div class="user-menu">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($userName); ?></span>
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
                    <tr><th>Name</th><td><?php echo htmlspecialchars($userName); ?></td></tr>
                    <tr><th>Email</th><td><?php echo htmlspecialchars($user_email); ?></td></tr>
                </table>
                <a href="profile.php" class="profile-link">Go to Update Information</a>
            </div>
        </div>
    </div>

    <div class="container main-content">
        <h1 class="page-title">Track Macros</h1>

        <div class="date-navigation">
            <div class="date-tiles">
                 <?php
                    foreach ($date_buttons as $ts) {
                        $date_str = date('Y-m-d', $ts);
                        $day = date('d', $ts);
                        $month = date('M', $ts);
                        $is_future = ($ts > $today_ts);
                        $active = ($date_str == $selected_date) ? 'active' : '';
                        $disabled_class = $is_future ? 'disabled' : '';
                        $link = $is_future ? '#' : "?date={$date_str}";
                        $title_attr = $is_future ? 'title="Cannot view future dates"' : '';

                        echo "<a href='{$link}' class='date-box {$active} {$disabled_class}' {$title_attr}>";
                        echo "<span>{$month}</span> <span class='day-number'>{$day}</span>";
                        echo "</a>";
                    }
                ?>
            </div>
             <div class="calendar-picker">
                <button class="calendar-btn" id="calendarToggle" title="Select Date">
                    <i class="fas fa-calendar-alt"></i>
                </button>
                <input type="text" id="datePicker" value="<?= $selected_date ?>" aria-label="Date Picker">
            </div>
        </div>

        <div class="summary-card">
            <h2>Daily Summary</h2>
            <div class="calorie-info">
                Total Calories: <strong><?= round($totalCalories) ?> / <?= $targetCalories ?></strong>
            </div>
            <div class="macro-info">
                <span>Protein: <strong><?= round($totalProtein) ?>g</strong> / <?= $targetProtein ?>g</span>
                <span>Fats: <strong><?= round($totalFats) ?>g</strong> / <?= $targetFat ?>g</span>
                <span>Carbs: <strong><?= round($totalCarbs) ?>g</strong> / <?= $targetCarbs ?>g</span>
            </div>
        </div>

        <div class="meals-section">
            <h2>Today's Meals</h2>
            <?php
            $meal_order = ['Breakfast', 'Lunch', 'Dinner', 'Snacks'];

            foreach ($meal_order as $meal_name):
                 if (array_key_exists($meal_name, $meals)):
            ?>
                <div class="meal-block">
                    <h3><?= htmlspecialchars($meal_name) ?></h3>
                    <?php if (!empty($meals[$meal_name])): ?>
                        <?php foreach ($meals[$meal_name] as $item): ?>
                            <div class="meal-item">
                                <span class="food-name"><?= htmlspecialchars(ucfirst($item['name'])) ?><?php echo ($item['quantity'] > 1) ? ' (' . $item['quantity'] . ')' : ''; ?></span>
                                <div class="macros">
                                    <span>P: <?= $item['protein_g'] ?>g</span>
                                    <span>F: <?= $item['fat_g'] ?>g</span>
                                    <span>C: <?= $item['carbohydrates_g'] ?>g</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-items-message">No items logged for <?= htmlspecialchars($meal_name) ?>.</p>
                    <?php endif; ?>
                </div>
            <?php
                 endif;
            endforeach;
            ?>
        </div>

    </div>

    <footer>
        <p>© 2025 FitFlex. All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openProfile() {
            document.getElementById('profileModal').style.display = 'flex';
        }
        function closeProfile() {
            document.getElementById('profileModal').style.display = 'none';
        }
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('profileModal');
            if (event.target === modal) {
                closeProfile();
            }
        });

        const datePicker = flatpickr("#datePicker", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            defaultDate: "<?= $selected_date ?>",
            onChange: function(selectedDates, dateStr, instance) {
                window.location.href = `?date=${dateStr}`;
            }
        });

        document.getElementById('calendarToggle').addEventListener('click', function() {
            datePicker.open();
        });
    </script>
</body>
</html>