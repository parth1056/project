<?php
session_start();

$logged_in = isset($_SESSION["user_email"]) && isset($_SESSION["logged_in"]);

if ($logged_in) {
    $user_email = $_SESSION["user_email"];
    $userName = $_SESSION["user_name"] ?? 'User'; 
} else {
    $user_email = null;
    $userName = 'Guest';
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
        .login-btn { background: yellow; color: black; border: none; font-size: 24px; width: 91px; height: 52px; margin-right: 40px; border-radius: 10px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;}

        body { margin: 0; padding: 0; font-family: Arial, sans-serif; overflow-x: hidden; background-color: #fff8f0; }
        .container { max-width: 960px; margin: 20px auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        h1, h2 { color: #a0522d; text-align: center; margin-bottom: 20px; font-family: 'Playfair Display', serif; }
        h1 { font-size: 2.5rem; }
        h2 { font-size: 1.8rem; margin-top: 30px;}
        p { line-height: 1.7; margin-bottom: 18px; color: #555; font-size: 1.05em; }
        .team-section { margin-top: 30px; text-align: center; }
        .team-member { display: inline-block; margin: 15px; padding: 10px; background-color: #fdfaf6; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .team-member h3 { color: #8b4513; font-size: 1.2em; margin-top: 0; margin-bottom: 5px; font-weight: 600; }
        footer { background: #333; color: #ffffff; text-align: center; padding: 15px 0; font-size: 14px; margin-top: 30px; width: 100%; }
        a { color: #a0522d; text-decoration: none;}
        a:hover { text-decoration: underline;}

        @media (max-width: 768px) {
             header { flex-wrap: wrap; height: auto !important; padding-bottom: 10px !important;}
             .container { padding: 15px; }
             h1 { font-size: 2rem;}
             h2 { font-size: 1.5rem;}
             .team-member { display: block; margin: 10px auto; max-width: 200px;}
        }
        @media (max-width: 480px) {
             .user-menu { flex-direction: row; align-items: center; width: 100%; justify-content: space-between; margin: 10px 0 0 0;}
             .welcome-text { text-align: left; width: auto;}
             .user-actions { width: auto;}
         }

    </style>
</head>

<body>
    <header>
        <a href="./dashboard.php"><img src="assets/logo.png" alt="FitFlex Logo" class="logo"></a>
        <nav class="nav">
            <a href="./planner.php"><button>Planner</button></a>
            <a href="./workout.php"><button>Workouts</button></a>
            <a href="./dietpage.php"><button>Diets</button></a>
            <button style="background-color: #555; border-radius: 5px;">About Us</button>
        </nav>
        <?php if ($logged_in): ?>
            <div class="user-menu">
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <div class="user-actions">
                    <button class="profile-btn" onclick="openProfile()">Profile</button>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.html" class="login-btn">Login</a>
        <?php endif; ?>
    </header>

    <?php if ($logged_in): ?>
        <div class="profile-modal" id="profileModal">
            <div class="profile-modal-content">
                <button class="close-btn" onclick="closeProfile()">X</button>
                <div id="profileContent">
                    <h2>My Profile</h2>
                    <table class="profile-table">
                        <tr>
                            <th>Name</th>
                            <td><?php echo htmlspecialchars($userName); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($user_email); ?></td>
                        </tr>
                    </table>
                     <a href="profile.php" class="profile-link">Go to Update Information</a>
                </div>
            </div>
        </div>
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
        <p style="color: white;">© 2025 FitFlex. All Rights Reserved.</p>
    </footer>

    <?php if ($logged_in): ?>
        <script>
            function openProfile() {
                document.getElementById("profileModal").style.display = "flex";
            }

            function closeProfile() {
                document.getElementById("profileModal").style.display = "none";
            }
             window.addEventListener('click', function(event) {
                 const modal = document.getElementById('profileModal');
                 if (event.target === modal) {
                     closeProfile();
                 }
             });
        </script>
    <?php endif; ?>
</body>
</html>