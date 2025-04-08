<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitFlex - Forgot Password</title>
  <link rel="stylesheet" href="styles1.css">
  <style>
    .reset-container {
      background: rgba(1, 96, 1, 0.4);
      padding: 40px;
      border-radius: 20px;
      width: 300px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      text-align: center;
      border: 1px solid black;
    }
    .back-link {
      display: block;
      margin-top: 15px;
      color: white;
      text-decoration: none;
    }

  </style>
</head>

<body>
  <div class="login-page">
    <div class="reset-container">
      <a href="./landing.php"><img src="assets/logo.png" alt="FitFlex Logo" class="old-logo"></a>
      <h2 style="color: rgb(255, 255, 255);">Reset Password</h2>
      <p style="color: white; margin-bottom: 20px;">Enter your email address to reset your password.</p>
      
      <form action="reset.php" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit" name="reset_request" class="sign-in-btn">Continue</button>
      </form>
      
      <?php if (isset($_SESSION["message"])): ?>
      <p style="color: <?php echo ($_SESSION["message_type"] == "error") ? "red" : "green"; ?>; text-align: center; margin-top: 15px;">
        <?php echo $_SESSION["message"]; unset($_SESSION["message"]); unset($_SESSION["message_type"]); ?>
      </p>
      <?php endif; ?>
      
      <a href="login.html" class="back-link" style="color: white !important;">Back to Login</a>
    </div>
  </div>
  <footer>
    <p>&copy; 2025 FitFlex. All Rights Reserved.</p>
  </footer>
</body>

</html>