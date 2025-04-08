<?php
session_start();
if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['reset_email'])) {
    header("Location: forgor.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitFlex - New Password</title>
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
      <h2 style="color: rgb(255, 255, 255);">Create New Password</h2>
      
      <p style="color: white; margin-bottom: 20px;">
        Hi <?php echo isset($_SESSION['reset_name']) ? $_SESSION['reset_name'] : ''; ?>, please create a new password for your account.
      </p>
      
      <form action="reset.php" method="POST">
        <input type="password" name="password" placeholder="New Password" required minlength="6">
        <br><br>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
        <br><br>
        <button type="submit" name="update_password" class="sign-in-btn">Update Password</button>
      </form>
      
      <?php if (isset($_SESSION["message"])): ?>
      <p style="color: <?php echo ($_SESSION["message_type"] == "error") ? "red" : "green"; ?>; text-align: center; margin-top: 15px;">
        <?php echo $_SESSION["message"]; unset($_SESSION["message"]); unset($_SESSION["message_type"]); ?>
      </p>
      <?php endif; ?>
    </div>
  </div>
  <footer>
    <p>&copy; 2025 FitFlex. All Rights Reserved.</p>
  </footer>
</body>

</html>