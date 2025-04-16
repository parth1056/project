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

$baseGoal = 2000;
$totalFoodCalories = 0;
$today_date = date('Y-m-d');

$stmt_goal = $conn->prepare("SELECT calorie FROM userstable WHERE user_email = ?");
if ($stmt_goal) {
    $stmt_goal->bind_param("s", $user_email);
    $stmt_goal->execute();
    $result_goal = $stmt_goal->get_result();
    if ($row_goal = $result_goal->fetch_assoc()) {
        if (isset($row_goal['calorie']) && is_numeric($row_goal['calorie']) && $row_goal['calorie'] > 0) {
            $baseGoal = (int)$row_goal['calorie'];
        }
    }
    $stmt_goal->close();
} else {
    error_log("Failed to prepare goal statement: " . $conn->error);
}

$stmt_food = $conn->prepare("SELECT SUM(calorie_intake) as total_food_calories FROM userdiet WHERE user_email = ? AND meal_date = ?");
if($stmt_food) {
    $stmt_food->bind_param("ss", $user_email, $selected_date);
    $stmt_food->execute();
    $result_food = $stmt_food->get_result();
    if ($row_food = $result_food->fetch_assoc()) {
        $totalFoodCalories = (int)($row_food['total_food_calories'] ?? 0);
    }
    $stmt_food->close();
} else {
    error_log("Failed to prepare food total statement: " . $conn->error);
}

$remainingCalories = $baseGoal - $totalFoodCalories;


$weight_history_labels = [];
$weight_history_data = [];

$stmt_history = $conn->prepare("SELECT weight, log_date FROM user_weight_history WHERE user_email = ? ORDER BY log_date ASC");
if ($stmt_history) {
    $stmt_history->bind_param("s", $user_email);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    while ($row_history = $result_history->fetch_assoc()) {
        $weight_history_labels[] = date("M j", strtotime($row_history['log_date']));
        $weight_history_data[] = $row_history['weight'];
    }
    $stmt_history->close();
} else {
    error_log("Failed to prepare weight history statement: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="styles.css">
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
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; color: #343a40; margin: 0; padding: 0; }
        .planner-main-content { max-width: 960px; margin: 30px auto; padding: 20px; }
        .page-title { font-family: 'Playfair Display', serif; text-align: center; font-size: 2.8rem; margin-bottom: 40px; color: #2c3e50; }
        .calorie-widget { background-color: #ffffff; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); display: flex; align-items: center; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin-bottom: 50px; border: 1px solid #e7e7e7; }
        .calorie-widget h2 { font-size: 1.8rem; font-weight: 700; color: #343a40; width: 100%; margin-bottom: 10px; text-align: left; }
        .calorie-widget .subtitle { font-size: 0.9rem; color: #6c757d; width: 100%; margin-bottom: 25px; text-align: left; }
        .calorie-circle-container { position: relative; width: 200px; height: 200px; margin: 0 auto 20px auto; }
        .calorie-circle-bg { fill: none; stroke: #e6e6e6; stroke-width: 15; }
        .calorie-circle-progress { fill: none; stroke: #007bff; stroke-width: 15; stroke-linecap: round; transform: rotate(-90deg); transform-origin: 50% 50%; transition: stroke-dashoffset 0.5s ease-out; }
        .calorie-circle-inner { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
        .calorie-remaining-value { display: block; font-size: 2.8rem; font-weight: 600; color: #343a40; line-height: 1.1; }
        .calorie-remaining-label { display: block; font-size: 0.9rem; color: #6c757d; margin-top: 5px; }
        .calorie-breakdown { display: flex; flex-direction: column; gap: 18px; min-width: 200px; }
        .breakdown-row { display: flex; align-items: center; gap: 15px; }
        .breakdown-row i { font-size: 1.5rem; color: #007bff; width: 30px; text-align: center; }
        .breakdown-row .fa-utensils { color: #28a745; }
        .breakdown-row .fa-bullseye { color: #dc3545; }
        .breakdown-info { display: flex; flex-direction: column; }
        .breakdown-label { font-size: 0.9rem; color: #6c757d; margin-bottom: 2px; }
        .breakdown-value { font-size: 1.4rem; font-weight: 600; color: #343a40; }
        .weight-graph-container { background-color: #ffffff; border-radius: 15px; padding: 25px 30px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); margin-bottom: 50px; border: 1px solid #e7e7e7; }
        .graph-title { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.8rem; margin-bottom: 20px; color: #2c3e50; text-align: center; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; }
        #weightChartCanvas { max-width: 100%; height: auto; }
        .no-data-message { text-align: center; color: #888; padding: 30px 10px; font-style: italic; }
        .planner-navigation { display: flex; justify-content: space-around; gap: 20px; margin-top: 40px; flex-wrap: wrap; }
        .planner-nav-button { flex: 1; min-width: 150px; padding: 15px 20px; font-size: 1.1rem; font-weight: 600; text-align: center; text-decoration: none; border-radius: 8px; transition: all 0.3s ease; color: white; border: none; }
        .planner-nav-button.food { background-color: #28a745; }
        .planner-nav-button.macros { background-color: #ffc107; color: #333; }
        .planner-nav-button.exercise { background-color: #0d6efd; }
        .planner-nav-button:hover { opacity: 0.85; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        footer { background-color: #333; color: white; text-align: center; padding: 15px 0; margin-top: 40px; font-size: 0.9em; }
        @media (max-width: 768px) { .calorie-widget { flex-direction: column; align-items: center; text-align: center; } .calorie-breakdown { align-items: center; } .breakdown-row { justify-content: center; width: 100%;} .planner-main-content { padding: 15px;} .page-title { font-size: 2.2rem;} .planner-navigation { flex-direction: column; gap: 15px; align-items: stretch;} header { flex-wrap: wrap; height: auto !important; padding-bottom: 10px !important;} }
        @media (max-width: 480px) { .calorie-circle-container { width: 160px; height: 160px; } .calorie-remaining-value { font-size: 2.2rem; } .breakdown-value { font-size: 1.2rem; } .user-menu { flex-direction: row; align-items: center; width: 100%; justify-content: space-between; margin: 10px 0 0 0;} .welcome-text { text-align: left; width: auto;} .user-actions { width: auto;} }
    </style>
</head>
<body>
    <header>
        <a href="./dashboard.php"><img src="assets/logo.png" alt="FitFlex Logo" class="logo"></a>
        <nav class="nav">
            <a href="./planner.php"><button style="background-color: #555; border-radius: 5px;">Planner</button></a>
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

    <div class="container planner-main-content">
        <h1 class="page-title">Planner</h1>

        <div class="calorie-widget">
            <h2>Calories</h2>
            <p class="subtitle">Remaining = Goal - Food</p>
            <div class="calorie-circle-container">
                 <svg viewBox="0 0 100 100" width="200" height="200">
                    <circle class="calorie-circle-bg" cx="50" cy="50" r="42"></circle>
                    <circle class="calorie-circle-progress" cx="50" cy="50" r="42"
                            stroke-dasharray="264"
                            stroke-dashoffset="<?php echo $baseGoal > 0 ? 264 * (1 - max(0, $remainingCalories) / $baseGoal) : 264; ?>"
                            >
                    </circle>
                </svg>
                <div class="calorie-circle-inner">
                    <span class="calorie-remaining-value"><?= round($remainingCalories) ?></span>
                    <span class="calorie-remaining-label">Remaining</span>
                </div>
            </div>
            <div class="calorie-breakdown">
                <div class="breakdown-row">
                    <i class="fas fa-bullseye"></i>
                    <div class="breakdown-info">
                        <span class="breakdown-label">Base Goal</span>
                        <span class="breakdown-value"><?= $baseGoal ?></span>
                    </div>
                </div>
                <div class="breakdown-row">
                    <i class="fas fa-utensils"></i>
                     <div class="breakdown-info">
                        <span class="breakdown-label">Food</span>
                        <span class="breakdown-value"><?= round($totalFoodCalories) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="weight-graph-container">
            <h2 class="graph-title">Weight Progress</h2>
            <?php if (!empty($weight_history_data)): ?>
                <canvas id="weightChartCanvas"></canvas>
            <?php else: ?>
                <p class="no-data-message">No weight history logged yet. Update your weight in your profile to start tracking.</p>
            <?php endif; ?>
        </div>

        <div class="planner-navigation">
            <a href="dietpage.php" class="planner-nav-button food">
                <i class="fas fa-apple-alt me-2"></i> Food Diary
            </a>
            <a href="macro.php" class="planner-nav-button macros">
                 <i class="fas fa-chart-pie me-2"></i> Track Macros
            </a>
             <a href="workout.php" class="planner-nav-button exercise">
                 <i class="fas fa-dumbbell me-2"></i> Exercise
             </a>
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

        document.addEventListener('DOMContentLoaded', () => {
            const progressCircle = document.querySelector('.calorie-circle-progress');
            const baseGoal = <?= $baseGoal ?>;
            const remaining = <?= round($remainingCalories) ?>;
            const circumference = 2 * Math.PI * 42;

            let progressOffset = circumference;
            if (baseGoal > 0) {
                const progressRatio = Math.max(0, remaining) / baseGoal;
                progressOffset = circumference * (1 - progressRatio);
                progressOffset = Math.max(0, Math.min(circumference, progressOffset));
            }

            if(progressCircle) {
                setTimeout(() => {
                     progressCircle.style.strokeDashoffset = progressOffset;
                }, 10);
            }

            const weightLabels = <?php echo json_encode($weight_history_labels); ?>;
            const weightData = <?php echo json_encode($weight_history_data); ?>;
            const weightChartCanvas = document.getElementById('weightChartCanvas');

            if (weightChartCanvas && weightData.length > 0) {
                const ctx = weightChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: weightLabels,
                        datasets: [{
                            label: 'Weight (kg)',
                            data: weightData,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#0d6efd',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.2,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: false,
                                title: { display: true, text: 'Weight (kg)', font: { size: 14, family: 'Poppins'} },
                                ticks: { font: { family: 'Poppins' } },
                                grid: { color: '#e9ecef' }
                            },
                            x: {
                                title: { display: true, text: 'Date', font: { size: 14, family: 'Poppins'} },
                                ticks: { font: { family: 'Poppins' } },
                                grid: { display: false }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                titleFont: { family: 'Poppins', size: 14 },
                                bodyFont: { family: 'Poppins', size: 12 },
                                padding: 10,
                                cornerRadius: 4,
                                displayColors: false,
                                 callbacks: {
                                    label: function(context) {
                                        return ` Weight: ${context.parsed.y} kg`;
                                    }
                                }
                            }
                        },
                        interaction: {
                             mode: 'index',
                             intersect: false
                        }
                    }
                });
            }
        });

    </script>
</body>
</html>