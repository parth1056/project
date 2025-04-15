<?php
session_start();
if (!isset($_SESSION["user_email"]) || !isset($_SESSION["logged_in"])) {
    header("Location: login.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parth";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$currentUserEmail = $_SESSION["user_email"];
$userName = $_SESSION["user_name"] ?? 'User';

$allExercisesData = [];
$exercisesByMuscleGroup = [
    'Chest' => [],
    'Back' => [],
    'Shoulder' => [],
    'Arms' => [],
    'Legs' => [],
    'Core' => []
];
$allExerciseNamesSet = [];

$jsonFilePath = __DIR__ . '/exercises.json';

if (file_exists($jsonFilePath)) {
    $jsonContent = file_get_contents($jsonFilePath);
    $decodedData = json_decode($jsonContent, true);

    if (is_array($decodedData)) {
        $allExercisesData = $decodedData;

        foreach ($allExercisesData as $exercise) {
            if (isset($exercise['name']) && isset($exercise['primaryMuscles']) && is_array($exercise['primaryMuscles'])) {
                $exerciseName = trim($exercise['name']);
                $allExerciseNamesSet[] = strtolower($exerciseName);

                foreach ($exercise['primaryMuscles'] as $muscle) {
                    $muscleLower = strtolower(trim($muscle));
                    $targetGroup = null;
                    if (in_array($muscleLower, ['chest', 'pectorals'])) $targetGroup = 'Chest';
                    elseif (in_array($muscleLower, ['lats', 'middle back', 'lower back', 'traps'])) $targetGroup = 'Back';
                    elseif ($muscleLower == 'shoulders') $targetGroup = 'Shoulder';
                    elseif (in_array($muscleLower, ['biceps', 'triceps', 'forearms'])) $targetGroup = 'Arms';
                    elseif (in_array($muscleLower, ['quadriceps', 'hamstrings', 'glutes', 'calves', 'adductors', 'abductors'])) $targetGroup = 'Legs';
                    elseif ($muscleLower == 'abdominals') $targetGroup = 'Core';

                    if ($targetGroup && isset($exercisesByMuscleGroup[$targetGroup])) {
                        if (!in_array($exerciseName, $exercisesByMuscleGroup[$targetGroup])) {
                            $exercisesByMuscleGroup[$targetGroup][] = $exerciseName;
                        }
                    }
                }
            }
        }
        foreach ($exercisesByMuscleGroup as $group => $exercises) {
            sort($exercisesByMuscleGroup[$group]);
        }
        $allExerciseNamesSet = array_unique($allExerciseNamesSet);
    } else {
        error_log("Failed to decode exercises.json");
    }
} else {
    error_log("exercises.json not found at: " . $jsonFilePath);
}

$defaultPlans = [
    3 => [
        [
            'day' => 1,
            'display_group' => 'Push (Chest, Shoulders, Triceps)',
            'db_groups' => ['Chest', 'Shoulder', 'Arms'],
            'exercises' => ['Barbell Bench Press - Medium Grip', 'Barbell Shoulder Press', 'Dumbbell Flyes', 'Side Lateral Raise', 'Triceps Pushdown'],
            'image' => 'images/push_day.png'
        ],
        [
            'day' => 2,
            'display_group' => 'Pull (Back, Biceps)',
            'db_groups' => ['Back', 'Arms'],
            'exercises' => ['Pullups', 'Bent Over Barbell Row', 'Seated Cable Rows', 'Barbell Curl', 'Hammer Curls'],
            'image' => 'images/pull_day.png'
        ],
        [
            'day' => 3,
            'display_group' => 'Legs & Core',
            'db_groups' => ['Legs', 'Core'],
            'exercises' => ['Barbell Squat', 'Romanian Deadlift', 'Leg Press', 'Standing Calf Raises', 'Crunches', 'Plank'],
            'image' => 'images/legs_core_day.png'
        ],
    ],
    4 => [
        [
            'day' => 1,
            'display_group' => 'Upper Body A',
            'db_groups' => ['Chest', 'Back', 'Shoulder', 'Arms'],
            'exercises' => ['Incline Dumbbell Press', 'Bent Over Barbell Row', 'Barbell Shoulder Press', 'Wide-Grip Lat Pulldown', 'Dumbbell Bicep Curl'],
            'image' => 'images/upper_body.png'
        ],
        [
            'day' => 2,
            'display_group' => 'Lower Body A',
            'db_groups' => ['Legs', 'Core'],
            'exercises' => ['Barbell Squat', 'Lying Leg Curls', 'Leg Extensions', 'Standing Calf Raises', 'Hanging Leg Raise'],
            'image' => 'images/lower_body.png'
        ],
        [
            'day' => 3,
            'display_group' => 'Upper Body B',
            'db_groups' => ['Chest', 'Back', 'Shoulder', 'Arms'],
            'exercises' => ['Barbell Bench Press - Medium Grip', 'Pullups', 'Side Lateral Raise', 'Seated Cable Rows', 'Triceps Pushdown'],
            'image' => 'images/upper_body.png'
        ],
        [
            'day' => 4,
            'display_group' => 'Lower Body B',
            'db_groups' => ['Legs', 'Core'],
            'exercises' => ['Barbell Deadlift', 'Leg Press', 'Good Morning', 'Seated Calf Raise', 'Plank'],
            'image' => 'images/lower_body.png'
        ],
    ],
    5 => [
        [
            'day' => 1,
            'display_group' => 'Chest and Shoulders',
            'db_groups' => ['Chest', 'Shoulder'],
            'exercises' => ['Barbell Incline Bench Press - Medium Grip', 'Dumbbell Flyes', 'Cable Crossover', 'Side Lateral Raise', 'Barbell Shoulder Press'],
            'image' => 'images/chest_shoulders.png'
        ],
        [
            'day' => 2,
            'display_group' => 'Back',
            'db_groups' => ['Back'],
            'exercises' => ['Wide-Grip Lat Pulldown', 'Bent Over Barbell Row', 'One-Arm Dumbbell Row', 'Seated Cable Rows', 'Face Pull'],
            'image' => 'images/back_day.png'
        ],
        [
            'day' => 3,
            'display_group' => 'Arms',
            'db_groups' => ['Arms'],
            'exercises' => ['Barbell Curl', 'Hammer Curls', 'Close-Grip Barbell Bench Press', 'Triceps Pushdown', 'Standing Dumbbell Triceps Extension'],
            'image' => 'images/arms_day.png'
        ],
        [
            'day' => 4,
            'display_group' => 'Legs',
            'db_groups' => ['Legs'],
            'exercises' => ['Barbell Squat', 'Dumbbell Lunges', 'Leg Press', 'Lying Leg Curls', 'Standing Calf Raises'],
            'image' => 'images/legs_day.png'
        ],
        [
            'day' => 5,
            'display_group' => 'Core',
            'db_groups' => ['Core'],
            'exercises' => ['Crunches', 'Leg Pull-In', 'Russian Twist', 'Plank', 'Side Bridge'],
            'image' => 'images/core_day.png'
        ],
    ],
    6 => [
        [
            'day' => 1,
            'display_group' => 'Push A',
            'db_groups' => ['Chest', 'Shoulder', 'Arms'],
            'exercises' => ['Barbell Bench Press - Medium Grip', 'Barbell Shoulder Press', 'Incline Dumbbell Press', 'Side Lateral Raise', 'Triceps Pushdown'],
            'image' => 'images/push_day.png'
        ],
        [
            'day' => 2,
            'display_group' => 'Pull A',
            'db_groups' => ['Back', 'Arms'],
            'exercises' => ['Pullups', 'Bent Over Barbell Row', 'Face Pull', 'Barbell Curl', 'Hammer Curls'],
            'image' => 'images/pull_day.png'
        ],
        [
            'day' => 3,
            'display_group' => 'Legs A',
            'db_groups' => ['Legs', 'Core'],
            'exercises' => ['Barbell Squat', 'Romanian Deadlift', 'Leg Press', 'Standing Calf Raises', 'Crunches'],
            'image' => 'images/legs_day.png'
        ],
        [
            'day' => 4,
            'display_group' => 'Push B',
            'db_groups' => ['Chest', 'Shoulder', 'Arms'],
            'exercises' => ['Barbell Incline Bench Press - Medium Grip', 'Seated Dumbbell Press', 'Dumbbell Flyes', 'Front Dumbbell Raise', 'EZ-Bar Skullcrusher'],
            'image' => 'images/push_day.png'
        ],
        [
            'day' => 5,
            'display_group' => 'Pull B',
            'db_groups' => ['Back', 'Arms'],
            'exercises' => ['Barbell Deadlift', 'Seated Cable Rows', 'Wide-Grip Lat Pulldown', 'Preacher Curl', 'Reverse Flyes'],
            'image' => 'images/pull_day.png'
        ],
        [
            'day' => 6,
            'display_group' => 'Legs B',
            'db_groups' => ['Legs', 'Core'],
            'exercises' => ['Front Barbell Squat', 'Lying Leg Curls', 'Leg Extensions', 'Seated Calf Raise', 'Plank'],
            'image' => 'images/legs_day.png'
        ],
    ],
    7 => [
        [
            'day' => 1,
            'display_group' => 'Chest',
            'db_groups' => ['Chest'],
            'exercises' => ['Barbell Bench Press - Medium Grip', 'Incline Dumbbell Press', 'Dips - Chest Version', 'Cable Crossover'],
            'image' => 'images/chest_day.png'
        ],
        [
            'day' => 2,
            'display_group' => 'Back',
            'db_groups' => ['Back'],
            'exercises' => ['Pullups', 'T-Bar Row with Handle', 'One-Arm Dumbbell Row', 'Seated Cable Rows'],
            'image' => 'images/back_day.png'
        ],
        [
            'day' => 3,
            'display_group' => 'Shoulders',
            'db_groups' => ['Shoulder'],
            'exercises' => ['Barbell Shoulder Press', 'Side Lateral Raise', 'Front Dumbbell Raise', 'Reverse Flyes'],
            'image' => 'images/shoulders_day.png'
        ],
        [
            'day' => 4,
            'display_group' => 'Legs',
            'db_groups' => ['Legs'],
            'exercises' => ['Barbell Squat', 'Leg Press', 'Romanian Deadlift', 'Lying Leg Curls', 'Standing Calf Raises'],
            'image' => 'images/legs_day.png'
        ],
        [
            'day' => 5,
            'display_group' => 'Arms',
            'db_groups' => ['Arms'],
            'exercises' => ['Barbell Curl', 'Hammer Curls', 'Close-Grip Barbell Bench Press', 'EZ-Bar Skullcrusher', 'Triceps Pushdown'],
            'image' => 'images/arms_day.png'
        ],
        [
            'day' => 6,
            'display_group' => 'Core & Calves',
            'db_groups' => ['Core', 'Legs'],
            'exercises' => ['Hanging Leg Raise', 'Crunches', 'Russian Twist', 'Plank', 'Seated Calf Raise', 'Standing Calf Raises'],
            'image' => 'images/core_calves_day.png'
        ],
        [
            'day' => 7,
            'display_group' => 'Active Recovery',
            'db_groups' => [],
            'exercises' => ['Rope Jumping', 'Stretching', 'Foam Rolling'], 
            'image' => 'images/recovery_day.png'
        ],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_day_exercises') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An error occurred.'];

    if (!isset($_SESSION['user_email'])) {
        $response['message'] = 'User not logged in.';
        echo json_encode($response);
        exit;
    }
    $currentUserEmail = $_SESSION['user_email'];

    $plannerId = isset($_POST['planner_id']) ? (int)$_POST['planner_id'] : 0;
    $dayNumber = isset($_POST['day_number']) ? (int)$_POST['day_number'] : 0;
    $exercises = isset($_POST['exercises']) && is_array($_POST['exercises']) ? $_POST['exercises'] : [];

    if ($plannerId > 0 && $dayNumber > 0 && $currentUserEmail) {
        $conn->begin_transaction();
        try {
            $stmt_delete = $conn->prepare("DELETE FROM planned_exercises WHERE planner_id = ? AND day_number = ?");
            if (!$stmt_delete) throw new Exception("Prepare failed (delete): " . $conn->error);
            $stmt_delete->bind_param("ii", $plannerId, $dayNumber);
            if (!$stmt_delete->execute()) throw new Exception("Execute failed (delete): " . $stmt_delete->error);
            $stmt_delete->close();

            $stmt_insert = $conn->prepare("INSERT INTO planned_exercises (planner_id, day_number, exercise_id, sort_order, sets, reps) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt_insert) throw new Exception("Prepare failed (insert): " . $conn->error);

            $sortOrder = 0;
            foreach ($exercises as $exerciseName) {
                $exerciseName = trim($exerciseName);
                if (!empty($exerciseName)) {
                    $sets = 3;
                    $reps = '8-12';
                    $stmt_insert->bind_param("iissis", $plannerId, $dayNumber, $exerciseName, $sortOrder, $sets, $reps);
                    if (!$stmt_insert->execute()) throw new Exception("Execute failed while inserting '$exerciseName': " . $stmt_insert->error);
                    $sortOrder++;
                }
            }
            $stmt_insert->close();

            $conn->commit();
            $response = ['success' => true, 'message' => 'Workout for Day ' . $dayNumber . ' updated successfully!'];
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = "Database error: " . $e->getMessage();
            error_log("Save Error for user $currentUserEmail: " . $e->getMessage());
        }
    } else {
        $response['message'] = 'Invalid data received or user not properly identified.';
    }

    echo json_encode($response);
    $conn->close();
    exit;
}

$selectedDays = isset($_GET['days']) ? (int)$_GET['days'] : 5;
if ($selectedDays < 3 || $selectedDays > 7) $selectedDays = 5;

$currentPlan = [];
$userPlannerId = null;

$stmt_find_planner = $conn->prepare("SELECT planner_id FROM workoutplanner WHERE user_email = ? AND plan_days = ?");
if ($stmt_find_planner) {
    $stmt_find_planner->bind_param("si", $currentUserEmail, $selectedDays);
    $stmt_find_planner->execute();
    $result_planner = $stmt_find_planner->get_result();
    if ($row_planner = $result_planner->fetch_assoc()) {
        $userPlannerId = $row_planner['planner_id'];
    }
    $stmt_find_planner->close();

    if ($userPlannerId === null) {
        $stmt_create_planner = $conn->prepare("INSERT INTO workoutplanner (user_email, plan_days) VALUES (?, ?)");
        if ($stmt_create_planner) {
            $stmt_create_planner->bind_param("si", $currentUserEmail, $selectedDays);
            if ($stmt_create_planner->execute()) {
                $userPlannerId = $conn->insert_id;
            } else {
                error_log("Failed to create planner for $currentUserEmail: " . $stmt_create_planner->error);
            }
            $stmt_create_planner->close();
        } else {
            error_log("Failed to prepare create planner statement: " . $conn->error);
        }
    }
} else {
    error_log("Failed to prepare find planner statement: " . $conn->error);
}

$defaultPlanForSelectedDays = $defaultPlans[$selectedDays] ?? $defaultPlans[5];
$savedExercisesDetails = [];

if ($userPlannerId) {
    $stmt_fetch_exercises = $conn->prepare("SELECT day_number, exercise_id, sets, reps FROM planned_exercises WHERE planner_id = ? ORDER BY day_number, sort_order");
    if ($stmt_fetch_exercises) {
        $stmt_fetch_exercises->bind_param("i", $userPlannerId);
        $stmt_fetch_exercises->execute();
        $result_exercises = $stmt_fetch_exercises->get_result();
        while ($row_ex = $result_exercises->fetch_assoc()) {
            $savedExercisesDetails[$row_ex['day_number']][$row_ex['exercise_id']] = [
                'sets' => $row_ex['sets'],
                'reps' => $row_ex['reps']
            ];
        }
        $stmt_fetch_exercises->close();
    } else {
        error_log("Failed to prepare fetch exercises statement: " . $conn->error);
    }
}

foreach ($defaultPlanForSelectedDays as $index => $defaultDayData) {
    $dayNum = $defaultDayData['day'];
    $dayExercisesArray = [];

    $exerciseNamesToProcess = [];
    $hasSavedDataForDay = false;
    if (isset($savedExercisesDetails[$dayNum]) && !empty($savedExercisesDetails[$dayNum])) {
        $checkStmt = $conn->prepare("SELECT 1 FROM planned_exercises WHERE planner_id = ? AND day_number = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("ii", $userPlannerId, $dayNum);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                $exerciseNamesToProcess = array_keys($savedExercisesDetails[$dayNum]);
                $hasSavedDataForDay = true;
            }
            $checkStmt->close();
        } else {
            error_log("Failed to prepare check exercises statement: " . $conn->error);
        }
    }

    if (!$hasSavedDataForDay) {
        $exerciseNamesToProcess = $defaultDayData['exercises'];
    }


    foreach ($exerciseNamesToProcess as $exName) {
        $sets = '3';
        $reps = '8-12';

        if ($hasSavedDataForDay && isset($savedExercisesDetails[$dayNum][$exName])) {
            $sets = $savedExercisesDetails[$dayNum][$exName]['sets'];
            $reps = $savedExercisesDetails[$dayNum][$exName]['reps'];
        }

        $dayExercisesArray[] = [
            'name' => $exName,
            'sets' => $sets,
            'reps' => $reps
        ];
    }

    $currentPlan[] = [
        'day' => $dayNum,
        'display_group' => $defaultDayData['display_group'],
        'exercises' => $dayExercisesArray,
        'image' => $defaultDayData['image'],
        'db_groups' => $defaultDayData['db_groups']
    ];
}

$conn->close(); 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Workout Planner</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        header {
            background: black !important;
            width: 100% !important;
            height: 178px !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 20px !important;
            justify-content: space-between !important;
            position: relative !important;
            margin: 0 !important;
            box-sizing: border-box !important;
            font-family: Arial, sans-serif !important;
        }

        header .logo {
            width: 210.35px !important;
            height: 130px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        header .nav {
            display: flex !important;
            gap: 15px !important;
            margin-left: auto !important;
            margin-right: 25px !important;
            background: transparent !important;
            padding: 0 !important;
            flex-wrap: nowrap !important;
        }

        header .nav button {
            background: none !important;
            border: none !important;
            color: white !important;
            font-size: 18px !important;
            height: 30px !important;
            cursor: pointer !important;
            margin: 0 !important;
            padding: 0 6px 0 6px !important;
            box-shadow: none !important;
            line-height: normal !important;
            font-weight: normal !important;
            font-family: Arial, sans-serif !important;
        }

        header .user-menu {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-end !important;
            margin-left: auto !important;
            margin-right: 0px !important;
            gap: 10px !important;
            padding: 0 !important;
            padding-right: 2px !important;
        }

        header .welcome-text {
            color: white !important;
            font-size: 18px !important;
            margin: 0 !important;
            font-weight: normal !important;
            font-family: Arial, sans-serif !important;
            text-align: right !important;
            width: 100% !important;
        }

        header .user-actions {
            display: flex !important;
            gap: 10px !important;
            margin: 0 !important;
            padding: 0 !important;
            justify-content: center !important;
            width: 100% !important;
        }

        header .logout-btn,
        header .profile-btn {
            background: #dc3545 !important;
            color: white !important;
            border: none !important;
            font-size: 16px !important;
            padding: 8px 15px !important;
            border-radius: 10px !important;
            cursor: pointer !important;
            text-decoration: none !important;
            display: inline-block !important;
            line-height: normal !important;
            font-weight: normal !important;
            text-transform: none !important;
            box-shadow: none !important;
            font-family: Arial, sans-serif !important;
        }

        header .profile-btn {
            background: #28a745 !important;
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
        :root {
            --background-color: #fdfaf6;
            --card-background: #ffffff;
            --text-color: #333;
            --text-light: #555;
            --accent-color: #edebeb;
            --border-radius-main: 25px;
            --border-radius-card: 15px;
            --box-shadow-light: 0 4px 10px rgba(0, 0, 0, 0.05);
            --box-shadow-active: 0 2px 5px rgba(0, 0, 0, 0.1);
            --filter-active-bg: #e0e0e0;
            --filter-active-text: #2d2d2d;
            --suggestion-hover-bg: #f0f0f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .planner-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }

        h1.planner-title {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 30px;
            color: #2d2d2d;
        }

        .day-selector {
            background-color: var(--accent-color);
            border-radius: var(--border-radius-main);
            padding: 5px;
            margin-bottom: 40px;
            display: inline-block;
        }

        .day-selector a {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            color: var(--text-light);
            border-radius: 20px;
            transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
            font-weight: 500;
        }

        .day-selector a.active,
        .day-selector a:hover {
            background-color: var(--card-background);
            color: var(--text-color);
            box-shadow: var(--box-shadow-active);
        }

        .workout-grid {
            display: grid;
            gap: 25px;
        }

        .workout-card {
            cursor: pointer;
            background-color: var(--card-background);
            border-radius: var(--border-radius-card);
            padding: 20px;
            box-shadow: var(--box-shadow-light);
            display: grid;
            grid-template-columns: 100px 1fr 1fr auto;
            gap: 15px 25px;
            align-items: start;
            min-height: 120px;
            transition: transform 0.2s ease-in-out;
        }

        .workout-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }

        .workout-card img.icon {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
            grid-row: 1 / span 2;
            align-self: center;
            margin-top: 5px;
            pointer-events: none;
        }

        .workout-card .title-section {
            grid-column: 2 / 3;
            pointer-events: none;
        }

        .workout-card .exercises-col-1 {
            grid-column: 3 / 4;
            pointer-events: none;
        }

        .workout-card .exercises-col-2 {
            grid-column: 4 / 5;
            pointer-events: none;
        }

        .workout-card h3 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
            font-weight: 600;
            color: #2d2d2d;
        }

        .workout-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .workout-card li {
            margin-bottom: 6px;
            font-size: 0.95em;
            color: var(--text-light);
        }

        .edit-button {
            grid-column: 5 / 6;
            grid-row: 1 / span 2;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            align-self: center;
            transition: opacity 0.2s ease;
            z-index: 5;
        }

        .edit-button:hover {
            opacity: 0.7;
        }

        .edit-button svg {
            width: 20px;
            height: 20px;
            fill: #888;
        }

        .edit-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s ease-out;
        }

        .edit-modal-content {
            background-color: var(--card-background);
            margin: 7% auto;
            padding: 30px;
            border-radius: var(--border-radius-card);
            max-width: 650px;
            width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideIn 0.3s ease-out;
        }

        .edit-modal-close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .edit-modal-close:hover,
        .edit-modal-close:focus {
            color: var(--text-color);
        }

        .edit-modal h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #2d2d2d;
        }

        .edit-modal-body {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        #modal-exercise-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--accent-color);
            border-radius: 5px;
            padding: 10px;
        }

        #modal-exercise-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.95em;
        }

        #modal-exercise-list li:last-child {
            border-bottom: none;
        }

        #modal-exercise-list li.empty-message {
            justify-content: center;
            color: #888;
            font-style: italic;
        }

        .remove-exercise {
            background: none;
            border: none;
            color: #f06a6a;
            cursor: pointer;
            font-size: 1.2em;
            padding: 0 5px;
            line-height: 1;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .remove-exercise:hover {
            opacity: 1;
        }

        .add-exercise-container {
            position: relative;
            border: 1px solid #e0e0e0;
            padding: 15px;
            border-radius: 8px;
        }

        .add-exercise-container h4,
        .current-exercises-container h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1em;
            color: #444;
        }

        .filter-section {
            margin-bottom: 10px;
        }

        .filter-section span {
            font-weight: 500;
            margin-right: 10px;
            font-size: 0.9em;
            color: var(--text-light);
        }

        .filter-button {
            background-color: var(--accent-color);
            border: none;
            border-radius: 15px;
            padding: 5px 12px;
            margin: 0 5px 5px 0;
            cursor: pointer;
            font-size: 0.85em;
            color: var(--text-light);
            transition: background-color 0.2s, color 0.2s;
        }

        .filter-button.active {
            background-color: var(--filter-active-bg);
            color: var(--filter-active-text);
            font-weight: 500;
        }

        .add-exercise-input-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        #new-exercise-name {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.95em;
        }

        #add-exercise-button {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
            font-size: 0.95em;
            background-color: #5cb85c;
            color: white;
            flex-shrink: 0;
        }

        #add-exercise-button:hover {
            background-color: #4cae4c;
        }

        #exercise-suggestions {
            display: none;
            position: absolute;
            border: 1px solid #ccc;
            border-top: none;
            background-color: white;
            max-height: 150px;
            overflow-y: auto;
            width: calc(100% - 92px);
            left: 0;
            top: 100%;
            z-index: 1001;
            border-radius: 0 0 5px 5px;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
        }

        #exercise-suggestions div {
            padding: 8px 12px;
            cursor: pointer;
            font-size: 0.9em;
        }

        #exercise-suggestions div:hover {
            background-color: var(--suggestion-hover-bg);
        }

        .edit-modal-footer {
            margin-top: 25px;
            text-align: right;
        }

        #modal-message {
            margin-top: 15px;
            font-size: 0.9em;
            text-align: center;
            min-height: 1.2em;
        }

        .message-success {
            color: green;
        }

        .message-error {
            color: red;
        }

        .modal-button {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
            font-size: 0.95em;
        }

        .button-primary {
            background-color: #5cb85c;
            color: white;
        }

        .button-primary:hover {
            background-color: #4cae4c;
        }

        .button-secondary {
            background-color: var(--accent-color);
            color: var(--text-light);
            margin-left: 10px;
        }

        .button-secondary:hover {
            background-color: #ddd;
        }

        .detail-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            animation: fadeIn 0.3s ease-out;
        }

        .detail-modal-content {
            background-color: var(--card-background);
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--border-radius-card);
            max-width: 800px;
            width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideIn 0.3s ease-out;
        }

        .detail-modal-close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .detail-modal-close:hover,
        .detail-modal-close:focus {
            color: var(--text-color);
        }

        .detail-modal h2 {
            margin-top: 0;
            margin-bottom: 25px;
            color: #2d2d2d;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        #detail-exercise-list {
            max-height: 70vh;
            overflow-y: auto;
            padding-right: 15px;
        }

        .detail-exercise-item {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 20px;
            align-items: start;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .detail-exercise-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-exercise-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .detail-exercise-item .info h4 {
            margin: 0 0 8px 0;
            font-size: 1.2em;
            font-weight: 600;
        }

        .detail-exercise-item .info p {
            margin: 0 0 10px 0;
            font-size: 1em;
            color: var(--text-light);
            line-height: 1.4;
        }

        .detail-exercise-item .info strong {
            color: var(--text-color);
        }

        .detail-exercise-item .info .instructions {
            font-size: 0.9em;
            margin-top: 15px;
        }

        .detail-exercise-item .info .instructions strong {
            display: block;
            margin-bottom: 5px;
            font-size: 1em;
        }

        .detail-exercise-item .info .instructions ol {
            padding-left: 20px;
            margin: 0;
        }

        .detail-exercise-item .info .instructions li {
            margin-bottom: 5px;
        }

        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 15px 0;
            margin-top: 40px;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .workout-card {
                grid-template-columns: 80px 1fr auto;
                grid-template-rows: auto 1fr;
                gap: 10px 15px;
                padding: 15px;
            }

            .workout-card img.icon {
                grid-row: 1 / span 2;
                width: 60px;
                height: 60px;
                align-self: center;
                margin-top: 0;
            }

            .workout-card .title-section {
                grid-column: 2 / 3;
                grid-row: 1 / 2;
                align-self: end;
            }

            .workout-card .exercises-col-1,
            .workout-card .exercises-col-2 {
                grid-column: 2 / 3;
                grid-row: 2 / 3;
                align-self: start;
                padding-left: 0;
            }

            .workout-card .exercises-col-1 ul,
            .workout-card .exercises-col-2 ul {
                display: block;
            }

            .workout-card .exercises-col-2 {
                margin-top: 0;
            }

            .workout-card .exercises-col-1 li,
            .workout-card .exercises-col-2 li {
                margin-bottom: 5px;
            }

            .edit-button {
                grid-column: 3 / 4;
                grid-row: 1 / span 2;
                align-self: center;
            }

            .edit-modal-content {
                margin: 10% auto;
                max-width: 90%;
            }

            #exercise-suggestions {
                width: calc(100% - 80px);
            }

            .user-menu {
                margin-right: 10px;
            }

            nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            header {
                flex-wrap: wrap;
                height: auto !important;
                padding-bottom: 10px !important;
            }
        }

        @media (max-width: 480px) {
            h1.planner-title {
                font-size: 2em;
            }

            .day-selector a {
                padding: 8px 12px;
                font-size: 0.9em;
            }

            .workout-card h3 {
                font-size: 1em;
            }

            .workout-card li {
                font-size: 0.9em;
            }

            .edit-button svg {
                width: 18px;
                height: 18px;
            }

            .edit-modal-content {
                padding: 20px;
                margin-top: 5%;
            }

            #modal-exercise-list {
                max-height: 150px;
            }

            .filter-section {
                text-align: center;
            }

            .filter-button {
                margin: 2px;
            }

            .add-exercise-input-group {
                flex-direction: column;
            }

            #exercise-suggestions {
                width: 100%;
                position: static;
                border: 1px solid #ccc;
                border-radius: 5px;
                margin-top: 5px;
            }

            .edit-modal-footer {
                text-align: center;
            }

            .button-secondary {
                margin-left: 0;
                margin-top: 10px;
            }

            nav button,
            nav a button {
                padding: 8px;
            }

            .user-menu {
                flex-direction: row;
                align-items: center;
                width: 100%;
                justify-content: space-between;
                margin: 10px 0 0 0;
            }

            .welcome-text {
                text-align: left;
                width: auto;
            }

            .user-actions {
                width: auto;
            }

            .detail-exercise-item {
                grid-template-columns: 1fr;
            }

            .detail-exercise-item img {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <header>
        <a href="./dashboard.php"><img src="assets/logo.png" alt="FitFlex Logo" class="logo"></a>
        <nav class="nav">
            <button style="background-color: #555; border-radius: 5px;">Workouts</button>
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
            <button class="close-btn" onclick="closeProfile()" title="Close">×</button>
            <div id="profileContent">
                <h2>My Profile</h2>
                <table class="profile-table">
                    <tr>
                        <th>Name</th>
                        <td><?php echo htmlspecialchars($userName); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($currentUserEmail); ?></td>
                    </tr>
                </table>
                <a href="profile.php" class="profile-link">Go to Update Information</a>
            </div>
        </div>
    </div>

    <!-- Planner Container -->
    <div class="planner-container">
        <h1 class="planner-title">Workout Planner</h1>

        <div class="day-selector">
            <?php for ($i = 3; $i <= 7; $i++): ?>
                <a href="?days=<?php echo $i; ?>" class="<?php echo ($i == $selectedDays) ? 'active' : ''; ?>">
                    <?php echo $i; ?> Day
                </a>
            <?php endfor; ?>
        </div>

        <div class="workout-grid">
            <?php foreach ($currentPlan as $dayData): ?>
                <?php
                $displayExercises = array_map(function ($ex) {
                    return $ex['name'];
                }, $dayData['exercises']);
                $exerciseCount = count($displayExercises);
                $splitPoint = ceil($exerciseCount / 2);
                $col1Exercises = array_slice($displayExercises, 0, $splitPoint);
                $col2Exercises = array_slice($displayExercises, $splitPoint);
                $exercisesJson = htmlspecialchars(json_encode($dayData['exercises']), ENT_QUOTES, 'UTF-8');
                $dbGroupsJson = htmlspecialchars(json_encode($dayData['db_groups']), ENT_QUOTES, 'UTF-8');
                $dayTitleJson = htmlspecialchars(json_encode($dayData['display_group']), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="workout-card"
                    data-day="<?php echo $dayData['day']; ?>"
                    data-planner-id="<?php echo $userPlannerId ?? 0; ?>"
                    data-day-title='<?php echo $dayTitleJson; ?>'
                    data-exercises='<?php echo $exercisesJson; ?>'
                    data-db-groups='<?php echo $dbGroupsJson; ?>'
                    title="Click to see Day <?php echo $dayData['day']; ?> details">

                    <img src="<?php echo htmlspecialchars($dayData['image'] ?? 'images/default_icon.png'); ?>" alt="<?php echo htmlspecialchars($dayData['display_group']); ?>" class="icon">

                    <div class="title-section">
                        <h3>Day <?php echo $dayData['day']; ?>: <?php echo htmlspecialchars($dayData['display_group']); ?></h3>
                    </div>

                    <div class="exercises-col-1">
                        <ul class="exercise-list">
                            <?php foreach ($col1Exercises as $exerciseName): ?>
                                <li><?php echo htmlspecialchars($exerciseName); ?></li>
                            <?php endforeach; ?>
                            <?php if (empty($col1Exercises) && empty($col2Exercises)) echo "<li>No exercises planned.</li>"; ?>
                        </ul>
                    </div>

                    <div class="exercises-col-2">
                        <ul class="exercise-list">
                            <?php foreach ($col2Exercises as $exerciseName): ?>
                                <li><?php echo htmlspecialchars($exerciseName); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <button class="edit-button" title="Edit Plan for Day <?php echo $dayData['day']; ?>">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                        </svg>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editDayModal" class="edit-modal">
        <div class="edit-modal-content">
            <span class="edit-modal-close" title="Close">×</span>
            <h2 id="modal-title">Edit Workout Day</h2>
            <div class="edit-modal-body">
                <div class="current-exercises-container">
                    <h4>Current Exercises:</h4>
                    <ul id="modal-exercise-list"></ul>
                </div>
                <div class="add-exercise-container">
                    <h4>Add New Exercise:</h4>
                    <div class="filter-section" id="modal-filter-section">
                        <span>Filter by:</span>
                    </div>
                    <div class="add-exercise-input-group">
                        <input type="text" id="new-exercise-name" placeholder="Start typing exercise..." autocomplete="off">
                        <button id="add-exercise-button" class="modal-button button-primary">Add</button>
                    </div>
                    <div id="exercise-suggestions">
                    </div>
                </div>
                <div id="modal-message"></div>
            </div>
            <div class="edit-modal-footer">
                <input type="hidden" id="modal-planner-id">
                <input type="hidden" id="modal-day-number">
                <button id="save-changes-button" class="modal-button button-primary">Save Changes</button>
                <button id="cancel-button" class="modal-button button-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="detail-modal">
        <div class="detail-modal-content">
            <span class="detail-modal-close" title="Close">×</span>
            <h2 id="detail-modal-title">Workout Details</h2>
            <div id="detail-exercise-list">
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 FitFlex. All Rights Reserved.</p>
    </footer>
    <script>
        function openProfile() {
            document.getElementById("profileModal").style.display = "flex";
        }

        function closeProfile() {
            document.getElementById("profileModal").style.display = "none";
        }
        window.addEventListener('click', function(event) {
            const profileModal = document.getElementById('profileModal');
            if (event.target == profileModal) {
                closeProfile();
            }
        });


        const allExercisesByGroup = <?php echo json_encode($exercisesByMuscleGroup); ?>;
        const allMuscleGroups = Object.keys(allExercisesByGroup);
        const allValidExerciseNames = new Set(<?php echo json_encode(array_map('strtolower', array_values($allExerciseNamesSet))); ?>);
        const allExercisesDetails = <?php echo json_encode($allExercisesData); ?>;
        const exerciseImageBasePath = 'exercises/';

        const editModal = document.getElementById('editDayModal');
        const modalTitle = document.getElementById('modal-title');
        const modalExerciseList = document.getElementById('modal-exercise-list');
        const newExerciseInput = document.getElementById('new-exercise-name');
        const addExerciseButton = document.getElementById('add-exercise-button');
        const saveChangesButton = document.getElementById('save-changes-button');
        const cancelButton = editModal.querySelector('#cancel-button');
        const closeModalButton = editModal.querySelector('.edit-modal-close');
        const modalPlannerIdInput = document.getElementById('modal-planner-id');
        const modalDayNumberInput = document.getElementById('modal-day-number');
        const modalMessage = document.getElementById('modal-message');
        const modalFilterSection = document.getElementById('modal-filter-section');
        const exerciseSuggestions = document.getElementById('exercise-suggestions');

        let currentDayDbGroups = [];

        function openEditModal() {
            modalMessage.textContent = '';
            modalMessage.className = '';
            editModal.style.display = 'block';
            newExerciseInput.value = '';
            exerciseSuggestions.style.display = 'none';
            populateFilters();
            updateSuggestions(); 
        }

        function closeEditModal() {
            editModal.style.display = 'none';
        }
        closeModalButton.onclick = closeEditModal;
        cancelButton.onclick = closeEditModal;
        window.addEventListener('click', function(event) { 
            if (event.target == editModal) {
                closeEditModal();
            }
            if (!newExerciseInput.contains(event.target) && !exerciseSuggestions.contains(event.target) && !addExerciseButton.contains(event.target)) {
                if (exerciseSuggestions) exerciseSuggestions.style.display = 'none';
            }
        });

        let blurTimeout;
        newExerciseInput.addEventListener('blur', () => {
            clearTimeout(blurTimeout);
            blurTimeout = setTimeout(() => {
                if (exerciseSuggestions && document.activeElement !== exerciseSuggestions && !exerciseSuggestions.contains(document.activeElement)) {
                    exerciseSuggestions.style.display = 'none';
                }
            }, 150);
        });
        newExerciseInput.addEventListener('focus', () => {
            clearTimeout(blurTimeout);
            updateSuggestions();
            if (exerciseSuggestions && exerciseSuggestions.childElementCount > 0) {
                exerciseSuggestions.style.display = 'block';
            }
        });

        document.addEventListener('DOMContentLoaded', () => { 
            document.querySelectorAll('.edit-button').forEach(button => {
                button.addEventListener('click', (event) => {
                    event.stopPropagation();

                    const card = button.closest('.workout-card');
                    const day = card.dataset.day;
                    const plannerId = card.dataset.plannerId;
                    const dayTitle = JSON.parse(card.dataset.dayTitle);
                    let exercisesData = [];
                    currentDayDbGroups = [];

                    try {
                        exercisesData = JSON.parse(card.dataset.exercises);
                    } catch (e) {
                        console.error("Error parsing exercises JSON:", e);
                        alert("Error loading exercise data.");
                        return;
                    }

                    try {
                        currentDayDbGroups = JSON.parse(card.dataset.dbGroups);
                    } catch (e) {
                        console.error("Error parsing DB groups JSON:", e);
                    }

                    if (!plannerId || plannerId === '0') {
                        alert("Cannot edit: Planner information missing or invalid.");
                        return;
                    }

                    modalTitle.textContent = `Edit Day ${day}: ${dayTitle}`;
                    modalPlannerIdInput.value = plannerId;
                    modalDayNumberInput.value = day;
                    modalExerciseList.innerHTML = '';

                    const exerciseNames = exercisesData.map(ex => ex.name);
                    if (exerciseNames.length > 0) {
                        exerciseNames.forEach(exName => addExerciseToList(exName, false));
                    }
                    updateEmptyMessage();

                    openEditModal();
                });
            });
        });


        function populateFilters() {
            modalFilterSection.innerHTML = '<span>Filter by:</span>';
            const allButton = document.createElement('button');
            allButton.textContent = 'All';
            allButton.className = 'filter-button';
            allButton.dataset.group = 'All';
            allButton.onclick = () => toggleFilter(allButton);
            modalFilterSection.appendChild(allButton);

            allMuscleGroups.forEach(group => {
                if (allExercisesByGroup[group] && allExercisesByGroup[group].length > 0) {
                    const btn = document.createElement('button');
                    btn.textContent = group;
                    btn.className = 'filter-button';
                    btn.dataset.group = group;
                    if (currentDayDbGroups.includes(group)) {
                        btn.classList.add('active');
                    }
                    btn.onclick = () => toggleFilter(btn);
                    modalFilterSection.appendChild(btn);
                }
            });

            const activeFilters = modalFilterSection.querySelectorAll('.filter-button.active');
            if (activeFilters.length === 0 || currentDayDbGroups.length === 0) {
                if (allButton) allButton.classList.add('active');
            } else {
                if (allButton) allButton.classList.remove('active');
            }
        }

        function toggleFilter(button) {
            const group = button.dataset.group;
            const allButton = modalFilterSection.querySelector('.filter-button[data-group="All"]');

            if (group === 'All') {
                modalFilterSection.querySelectorAll('.filter-button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
            } else {
                if (allButton) allButton.classList.remove('active');
                button.classList.toggle('active');
                if (!modalFilterSection.querySelector('.filter-button.active')) {
                    if (allButton) allButton.classList.add('active');
                }
            }
            updateSuggestions();
        }

        function updateSuggestions() {
            const searchTerm = newExerciseInput.value.toLowerCase().trim();
            const activeFilterButtons = modalFilterSection.querySelectorAll('.filter-button.active');
            let activeGroups = [];
            activeFilterButtons.forEach(btn => activeGroups.push(btn.dataset.group));

            if (!exerciseSuggestions) return;
            exerciseSuggestions.innerHTML = '';
            let suggestionsFound = 0; 
            const groupsToSearch = activeGroups.includes('All') ? allMuscleGroups : activeGroups;

            const currentExercises = new Set();
            modalExerciseList.querySelectorAll('li').forEach(li => {
                if (!li.classList.contains('empty-message') && li.firstChild) {
                    currentExercises.add(li.firstChild.textContent.trim());
                }
            });

            groupsToSearch.forEach(group => {
                if (allExercisesByGroup[group]) {
                    allExercisesByGroup[group].forEach(exercise => {
                        if (suggestionsFound < 10 && 
                            exercise.toLowerCase().includes(searchTerm) &&
                            !currentExercises.has(exercise)) {
                            const div = document.createElement('div');
                            div.textContent = exercise;
                            div.onmousedown = (e) => {
                                e.preventDefault();
                                addExerciseToList(exercise, true);
                                if (exerciseSuggestions) exerciseSuggestions.style.display = 'none';
                            };
                            exerciseSuggestions.appendChild(div);
                            suggestionsFound++;
                        }
                    });
                }
            });
            exerciseSuggestions.style.display = (suggestionsFound > 0 && (searchTerm.length > 0 || document.activeElement === newExerciseInput)) ? 'block' : 'none';
        }
        newExerciseInput.addEventListener('input', updateSuggestions);

        function addExerciseToList(exerciseName, clearInput = false) {
            let exists = false;
            modalExerciseList.querySelectorAll('li').forEach(li => {
                if (li.firstChild && li.firstChild.textContent.trim().toLowerCase() === exerciseName.toLowerCase()) {
                    exists = true;
                }
            });
            if (exists) {
                if (clearInput) newExerciseInput.value = '';
                modalMessage.textContent = `"${exerciseName}" is already in the list.`;
                modalMessage.className = 'message-error';
                setTimeout(() => {
                    modalMessage.textContent = '';
                    modalMessage.className = '';
                }, 2000);
                return;
            }

            const li = document.createElement('li');
            li.textContent = exerciseName;
            const removeBtn = document.createElement('button');
            removeBtn.innerHTML = '×';  
            removeBtn.className = 'remove-exercise';
            removeBtn.title = 'Remove exercise';
            removeBtn.onclick = function() {
                li.remove();
                updateEmptyMessage();
                updateSuggestions();
            }; 

            const emptyMsg = modalExerciseList.querySelector('li.empty-message');
            if (emptyMsg) {
                emptyMsg.remove();
            }

            li.appendChild(removeBtn);
            modalExerciseList.appendChild(li);

            if (clearInput) {
                newExerciseInput.value = '';
                updateSuggestions();  
            }
            modalMessage.textContent = '';
            updateEmptyMessage();
            updateSuggestions();
        }

        function updateEmptyMessage() {
            if (modalExerciseList.children.length === 0 || (modalExerciseList.children.length === 1 && modalExerciseList.firstChild.classList.contains('empty-message'))) {
                if (!modalExerciseList.querySelector('.empty-message')) {
                    modalExerciseList.innerHTML = '<li class="empty-message">No exercises added yet.</li>';
                }
            } else {
                const emptyMsg = modalExerciseList.querySelector('li.empty-message');
                if (emptyMsg) emptyMsg.remove();
            }
        }

        addExerciseButton.addEventListener('click', () => {
            const newName = newExerciseInput.value.trim();
            if (newName) {
                if (allValidExerciseNames.has(newName.toLowerCase())) {
                    addExerciseToList(newName, true);
                } else {
                    modalMessage.textContent = `Exercise "${newName}" not found.`;
                    modalMessage.className = 'message-error';
                    setTimeout(() => {
                        modalMessage.textContent = '';
                        modalMessage.className = '';
                    }, 3000);
                }
            }
        });

        newExerciseInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const firstSuggestion = exerciseSuggestions.querySelector('div');
                if (exerciseSuggestions.style.display === 'block' && firstSuggestion) {
                    firstSuggestion.dispatchEvent(new MouseEvent('mousedown'));
                } else {
                    const newName = newExerciseInput.value.trim();
                    if (newName) {
                        if (allValidExerciseNames.has(newName.toLowerCase())) {
                            addExerciseButton.click(); 
                        } else {
                            modalMessage.textContent = `Exercise "${newName}" not found.`;
                            modalMessage.className = 'message-error';
                            setTimeout(() => {
                                modalMessage.textContent = '';
                                modalMessage.className = '';
                            }, 3000);
                        }
                    }
                }
            }
        });

        const detailModal = document.getElementById('detailModal');
        const detailModalTitle = document.getElementById('detail-modal-title');
        const detailExerciseList = document.getElementById('detail-exercise-list');
        const closeDetailModalButton = detailModal.querySelector('.detail-modal-close');

        function openDetailModal() {
            detailModal.style.display = 'block';
        }

        function closeDetailModal() {
            detailModal.style.display = 'none';
            detailExerciseList.innerHTML = '';
        }
        closeDetailModalButton.onclick = closeDetailModal;
        window.addEventListener('click', function(event) {
            if (event.target == detailModal) {
                closeDetailModal();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.workout-card').forEach(card => {
                card.addEventListener('click', (event) => {
                    if (event.target.closest('.edit-button')) {
                        return; 
                    }

                    const day = card.dataset.day;
                    const dayTitle = JSON.parse(card.dataset.dayTitle);
                    let exercises = [];
                    try {
                        exercises = JSON.parse(card.dataset.exercises);
                    } catch (e) {
                        console.error("Error parsing exercises data for detail view:", e);
                        alert("Could not load exercise details.");
                        return;
                    }

                    detailModalTitle.textContent = `Day ${day}: ${dayTitle}`;
                    detailExerciseList.innerHTML = '';

                    if (exercises.length === 0) {
                        detailExerciseList.innerHTML = '<p style="text-align: center; color: #888;">No exercises planned for this day.</p>';
                    } else {
                        exercises.forEach(exercisePlan => {
                            const exerciseDetails = allExercisesDetails.find(ex => ex.name === exercisePlan.name);

                            const itemDiv = document.createElement('div');
                            itemDiv.className = 'detail-exercise-item';

                            const img = document.createElement('img');
                            if (exerciseDetails && exerciseDetails.images && exerciseDetails.images.length > 0) {
                                img.src = exerciseImageBasePath + exerciseDetails.images[0]; 
                                img.alt = exercisePlan.name;
                            } else {
                                img.src = 'images/default_icon.png'; 
                                img.alt = 'No image available';
                            }
                            itemDiv.appendChild(img);

                            const infoDiv = document.createElement('div');
                            infoDiv.className = 'info';

                            const nameH4 = document.createElement('h4');
                            nameH4.textContent = exercisePlan.name;
                            infoDiv.appendChild(nameH4);

                            const setsRepsP = document.createElement('p');
                            setsRepsP.innerHTML = `<strong>Sets:</strong> ${exercisePlan.sets}    <strong>Reps:</strong> ${exercisePlan.reps}`;
                            infoDiv.appendChild(setsRepsP);

                            if (exerciseDetails && exerciseDetails.instructions && exerciseDetails.instructions.length > 0) {
                                const instructionsDiv = document.createElement('div');
                                instructionsDiv.className = 'instructions';
                                instructionsDiv.innerHTML = '<strong>Instructions:</strong>';
                                const ol = document.createElement('ol');
                                exerciseDetails.instructions.forEach(step => {
                                    const li = document.createElement('li');
                                    li.textContent = step;
                                    ol.appendChild(li);
                                });
                                instructionsDiv.appendChild(ol);
                                infoDiv.appendChild(instructionsDiv);
                            }

                            itemDiv.appendChild(infoDiv);
                            detailExerciseList.appendChild(itemDiv);
                        });
                    }
                    openDetailModal();
                });
            });
        });


        saveChangesButton.addEventListener('click', () => {
            const plannerId = modalPlannerIdInput.value;
            const dayNumber = modalDayNumberInput.value;
            const exerciseElements = modalExerciseList.querySelectorAll('li');
            const exercises = [];

            exerciseElements.forEach(li => {
                if (!li.classList.contains('empty-message') && li.firstChild) {
                    const exerciseName = li.firstChild.textContent.trim();
                    if (exerciseName) {
                        exercises.push(exerciseName);
                    }
                }
            });

            modalMessage.textContent = 'Saving...';
            modalMessage.className = '';

            const formData = new FormData();
            formData.append('action', 'save_day_exercises');
            formData.append('planner_id', plannerId);
            formData.append('day_number', dayNumber);
            exercises.forEach((ex, index) => {
                formData.append(`exercises[${index}]`, ex);
            });

            fetch('workout.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        modalMessage.textContent = data.message;
                        modalMessage.className = 'message-success';
                        setTimeout(() => {
                            closeEditModal();
                            location.reload();
                        }, 1500);
                    } else {
                        modalMessage.textContent = `Error: ${data.message || 'Unknown error'}`;
                        modalMessage.className = 'message-error';
                    }
                })
                .catch(error => {
                    console.error('Save Error:', error);
                    modalMessage.textContent = 'An network or server error occurred while saving.';
                    modalMessage.className = 'message-error';
                });
        });

        document.addEventListener('DOMContentLoaded', updateEmptyMessage);
    </script>

</body>

</html>