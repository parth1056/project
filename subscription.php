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

$stmt = $conn->prepare("SELECT user_height, user_weight FROM userstable WHERE user_email = ? AND user_height IS NOT NULL AND user_weight IS NOT NULL");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header("Location: dashboard.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex Plan Selection</title>
    <style>
        body {
            font-family: 'Arial', sans-serif; 
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
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
            width: 164.35px;
            height: 130px;
            padding-left: 20px;
        }

        .brand-name {
            color: white;
            font-size: 40px;
            font-weight: bold;
            display: flex;
            align-items: center;
            margin-left: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            margin-left: auto;
            color: white;
            margin-right: 25px;
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
        }

        h1 {
            color: #333;
            margin-bottom: 25px; 
            font-size: 2em;
            letter-spacing: 1px; 
        }

        .plans-container {
            display: flex;
            gap: 15px;
            max-width: 1200px;
            padding: 15px; 
        }

        .plan-box {
            background-color: #fff;
            border-radius: 5px; 
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 15px;
            width: 25%;
            box-sizing: border-box;
        }

        .plan-box h2 {
            color: #333;
            font-size: 1.3em;
            margin-top: 0;
            margin-bottom: 10px; 
            font-weight: bold;
        }

        .price {
            font-size: 2.2em;
            color: #ffc107; 
            margin: 8px 0; 
            font-weight: bold;
        }

        .price sup, .price span {
            font-size: 0.45em; 
            vertical-align: top;
        }

        .description {
            color: #666;
            margin-bottom: 15px; 
            font-size: 0.9em;
            line-height: 1.3; 
        }

        .features {
            list-style: none;
            padding: 0;
            margin-bottom: 15px;
        }

        .features li {
            margin-bottom: 6px;
            color: #555;
            font-size: 0.9em;
            line-height: 1.2; 
        }

        .features li::before {
            content: '✔ '; 
            color: green;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }

        .subscribe-btn {
            background-color: #ffc107;
            color: black;
            border: none;
            padding: 8px 18px; 
            border-radius: 4px; 
            cursor: pointer;
            font-size: 0.95em;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .subscribe-btn:hover {
            background-color: #e6ac00; 
        }

        .plan-box.elite-plan {
            background-color: #fff5cc; 
            border: 2px solid #ffc107; 
        }
        .plan-box.elite-plan .price {
            color: #ffc107; 
        }
        .plan-box.elite-plan .subscribe-btn {
            background-color: #ffc107;
        }

        @media (max-width: 900px) {
            .plans-container {
                flex-direction: column;
                align-items: center;
            }
            .plan-box {
                width: 90%;
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
        <span class="brand-name">FitFlex</span>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user_email); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <h1>CHOOSE YOUR PLAN</h1>

    <div class="plans-container">
        <div class="plan-box">
            <h2>Starter Plan</h2>
            <p class="price"><sup>₹</sup>0</p>
            <p class="description">Free Trial for new users to understand the concept of Scheduled Exercises and Balanced Diet</p>
            <ul class="features">
                <li>30 Days</li>
                <li>Daily Workout Plans</li>
                <li>1 Account</li>
                <li>Static Diet</li>
            </ul>
            <a href="details.php" class="subscribe-btn">Subscribe</a>
        </div>

        <div class="plan-box">
            <h2>Beginner Plan</h2>
            <p class="price"><sup>₹</sup>500</p>
            <p class="description">Great Plan for Beginners with minor experience to get a jumpstart.</p>
            <ul class="features">
                <li>90 Days</li>
                <li>Daily Workout Plans</li>
                <li>2 Accounts</li>
                <li>Static Diet</li>
                <li>Diet Tracker</li>
            </ul>
            <a href="details.php" class="subscribe-btn">Subscribe</a>
        </div>

        <div class="plan-box elite-plan">
            <h2>Elite Plan</h2>
            <p class="price"><sup>₹</sup>5000</p>
            <p class="description">Plan focused on Individuals for body transformation with fast results.</p>
            <ul class="features">
                <li>365 Days</li>
                <li>Daily Workout Plans</li>
                <li>5 Accounts</li>
                <li>Dynamic Diet</li>
                <li>Diet Tracker</li>
                <li>Personal Coach</li>
            </ul>
            <a href="details.php" class="subscribe-btn">Subscribe</a>
        </div>

        <div class="plan-box">
            <h2>Advanced Plan</h2>
            <p class="price"><sup>₹</sup>1000</p>
            <p class="description">Plan for Intermediate Individuals.</p>
            <ul class="features">
                <li>180 Days</li>
                <li>Daily Workout Plans</li>
                <li>5 Accounts</li>
                <li>Dynamic Diet</li>
                <li>Diet Tracker</li>
            </ul>
            <a href="details.php" class="subscribe-btn">Subscribe</a>
        </div>
    </div>

</body>
</html>