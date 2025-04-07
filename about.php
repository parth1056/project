<?php
session_start();
$logged_in = isset($_SESSION["user_email"]) && isset($_SESSION["logged_in"]);

if ($logged_in) {
    $user_email = $_SESSION["user_email"];
    $user_name = $_SESSION["user_name"];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FitFlex</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            overflow-x: hidden;
        }

        header {
            background: black;
            width: 100%;
            height: 178px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            justify-content: space-between;
        }

        .logo {
            width: 210.35px;
            height: 130px;
        }

        .nav {
            display: flex;
            gap: 15px;
            margin-left: auto;
            margin-right: 25px;
        }

        .nav button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            height: 30px;
            cursor: pointer;
        }

        .login-btn {
            background: yellow;
            color: black;
            border: none;
            font-size: 24px;
            width: 91px;
            height: 52px;
            margin-right: 40px;
            border-radius: 10px;
            cursor: pointer;
        }

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

        .container {
            max-width: 960px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff8f0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #a0522d;
            text-align: center;
            margin-bottom: 20px;
        }

        p {
            line-height: 1.6;
            margin-bottom: 15px;
            color: #555;
        }

        .team-section {
            margin-top: 30px;
            text-align: center;
        }

        .team-member {
            display: inline-block;
            margin: 15px;
        }

        .team-member h3 {
            color: #8b4513;
            font-size: 1.2em;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .footer {
            background: #333;
            color: #ffffff;
            text-align: center;
            padding: 10px 0;
            font-size: 14px;
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <?php if ($logged_in): ?>
        <header>
            <a href="./dashboard.php"><img src="assets/logo.png" alt="FitFlex Logo" class="logo"></a>
            <nav class="nav">
                <button>Workouts</button>
                <a href="./dietpage.php"><button>Diets</button></a>
                <a href="./about.php"><button>About Us</button></a>
            </nav>
            <div class="user-menu">
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
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
                            <td><?php echo htmlspecialchars($user_name); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($user_email); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <header>
            <a href="./landing.php"><img src="assets/logo.png" alt="FitFlex Logo" class="logo"></a>
            <nav class="nav">
                <button>Workouts</button>
                <a href="./dietpage.php"><button>Diets</button></a>
                <a href="./about.php"><button>About Us</button></a>
            </nav>
            <a href="./login.html"><button class="login-btn">Login</button></a>
        </header>
    <?php endif; ?>

    <div class="container">
        <h1>About FitFlex</h1>

        <p>Namaste! At FitFlex, we believe that a healthy lifestyle should be accessible and adaptable for everyone. We are a small team of passionate individuals based right here, focused on creating workout and diet plans that fit into your daily life.</p>

        <h2>Our Mission</h2>
        <p>Our mission is simple: to empower you to achieve your fitness goals through clear, customizable plans and a supportive approach. We understand that everyone's journey is unique, and we're here to provide the tools and guidance you need, step by step.</p>

        <h2>What We Offer</h2>
        <p>FitFlex offers a range of workout routines and diet suggestions designed to cater to different needs and preferences. Whether you're looking to build strength, improve your overall fitness, or make healthier food choices, our platform provides structured plans that you can adapt to your own schedule and pace.</p>

        <h2>Our Approach</h2>
        <p>We believe in a practical and sustainable approach to fitness. Our plans are designed to be easy to understand and implement, focusing on consistency rather than quick fixes. We emphasize the importance of balanced nutrition and regular physical activity as key components of a healthy life.</p>

        <h2>Meet the Team (Just a few of us!)</h2>
        <div class="team-section">
            <div class="team-member">
                <h3>Pushkar Rai</h3>
            </div>
            <div class="team-member">
                <h3>Praneet Sharma</h3>
            </div>
            <div class="team-member">
                <h3>Parth Dhande</h3>
            </div>
        </div>

        <p>We are constantly working to improve FitFlex and provide you with the best possible resources for your fitness journey. We value your feedback and are always happy to hear from you.</p>

        <h2>Contact Us</h2>
        <p>Have any questions or suggestions? Feel free to reach out to us at <a href="mailto:support@fitflex.com">support@fitflex.com</a>.</p>
    </div>

    <footer>
        <p style="color: white;">&copy; 2025 FitFlex. All Rights Reserved.</p>
    </footer>

    <?php if ($logged_in): ?>
        <script>
            function openProfile() {
                document.getElementById("profileModal").style.display = "flex";
            }

            function closeProfile() {
                document.getElementById("profileModal").style.display = "none";
            }
        </script>
    <?php endif; ?>
</body>

</html>