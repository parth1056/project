<?php
session_start();
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_code'])) {
    header("Location: forgor.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitFlex - Verify Code</title>
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
    .verification-code {
      background: rgba(0, 0, 0, 0.2);
      color: white;
      padding: 10px;
      border-radius: 5px;
      font-size: 24px;
      margin: 15px 0;
      letter-spacing: 2px;
    }
    .code-input {
      font-size: 18px;
      letter-spacing: 5px;
      text-align: center;
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
      <h2 style="color: rgb(255, 255, 255);">Verify Your Identity</h2>
      <p style="color: white; margin-bottom: 10px;">We've sent a verification code to:<br><?php echo $_SESSION['reset_email']; ?></p>
      
      <div style="margin: 15px 0;">
        <p style="color: white; font-size: 14px; margin-bottom: 5px;">Your verification code is:</p>
        <div class="verification-code"><?php echo $_SESSION['reset_code']; ?></div>
        <p style="color: white; font-size: 12px;">(In a real app, this would be sent to your email)</p>
      </div>
      
      <form action="reset.php" method="POST">
        <input type="text" name="code" placeholder="Enter code" required class="code-input" maxlength="6" pattern="[0-9]{6}">
        <button type="submit" name="verify_code" class="sign-in-btn">Verify</button>
      </form>
      
      <?php if (isset($_SESSION["message"])): ?>
      <p style="color: <?php echo ($_SESSION["message_type"] == "error") ? "red" : "green"; ?>; text-align: center; margin-top: 15px;">
        <?php echo $_SESSION["message"]; unset($_SESSION["message"]); unset($_SESSION["message_type"]); ?>
      </p>
      <?php endif; ?>
      
      <a href="forgor.php" class="back-link" style="color: white !important;">Back</a>
    </div>
  </div>
  <footer>
    <p>&copy; 2025 FitFlex. All Rights Reserved.</p>
  </footer>
</body>

</html>