<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "parth";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $phone = trim($_POST["phone"]);

    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        echo "<script>alert('All fields are required.'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
        exit();
    }

    $check = $conn->prepare("SELECT user_email FROM userstable WHERE user_email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email already registered. Try another.'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO userstable (user_email, user_name, user_password, phone_number) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $name, $hashed_password, $phone);

    if ($stmt->execute()) {
        $_SESSION["user_email"] = $email;
        $_SESSION["user_name"] = $name;
        $_SESSION["logged_in"] = true;

        header("Location: subscription.php");
        exit();
    } else {
        echo "<script>alert('Something went wrong. Try again.'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FitFlex - Create an Account</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f9f9f9;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .container {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      display: flex;
      width: 90%;
      max-width: 1200px;
      overflow: hidden;
    }

    .image-column {
      width: 55%;
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      grid-template-rows: 200px 300px 200px;
      gap: 10px;
      padding: 10px;
      box-sizing: border-box;
    }

    .image-box {
      background-color: #eee;
      display: flex;
      justify-content: center;
      align-items: center;
      box-sizing: border-box;
      overflow: hidden;
    }

    .image-box img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .image-box.logo-box {
      padding: 30px;
      background: linear-gradient(135deg, #ffc107, #ff9800);
      border: 2px solid black;
      grid-column: 1 / 3;
      grid-row: 2 / 3;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .image-box.logo-box img {
      width: 90%;
      max-width: 400px;
      height: auto;
      object-fit: contain;
      filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.5));
      transition: transform 0.3s ease;
      margin: 0 auto;
    }

    .image-box.logo-box img:hover {
      transform: scale(1.05);
    }

    .form-column {
      width: 45%;
      padding: 40px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: stretch;
      box-sizing: border-box;
    }

    h2 {
      font-size: 2.1em;
      color: #333;
      margin-bottom: 35px;
      text-align: left;
      font-weight: normal;
    }

    .form-group {
      margin-bottom: 25px;
    }

    .form-label {
      display: block;
      margin-bottom: 10px;
      color: #555;
      font-weight: bold;
      font-size: 1em;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 14px;
      border: none;
      border-bottom: 1px solid #ccc;
      font-size: 1em;
      color: #333;
      margin-bottom: 5px;
      box-sizing: border-box;
      border-radius: 0;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-bottom: 2px solid #333;
    }

    .error-message {
      color: red;
      font-size: 0.9em;
      margin-top: -10px;
      margin-bottom: 15px;
      display: none;
    }

    .button-primary {
      background-color: #000;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 14px 20px;
      font-size: 1.1em;
      cursor: pointer;
      width: 100%;
      margin-top: 25px;
      transition: background-color 0.3s;
      font-weight: bold;
    }

    .button-primary:hover {
      background-color: #333;
    }

    .signup-link {
      margin-top: 30px;
      text-align: center;
      font-size: 1em;
      color: #777;
    }

    .signup-link a {
      color: #007bff;
      text-decoration: none;
      font-weight: bold;
    }

    .signup-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="image-column">
      <div class="image-box top-left">
        <img src="assets/download 6.png" alt="Top Left Image">
      </div>
      <div class="image-box top-right">
        <img src="assets/download 5.png" alt="Top Right Image">
      </div>
      <div class="image-box logo-box">
        <a href="./landing.php"><img src="assets/ff 2.png" alt="FitFlex Logo"></a>
      </div>
      <div class="image-box bottom-left">
        <img src="assets/download 8.png" alt="Bottom Left Image">
      </div>
      <div class="image-box bottom-right">
        <img src="assets/download 4.png" alt="Bottom Right Image">
      </div>
    </div>
    <div class="form-column">
      <h2>Create an account</h2>
      <?php if (isset($_SESSION["error"])): ?>
      <p style="color: red; text-align: center;">
        <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
      </p>
      <?php endif; ?>

      <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <div class="form-group">
          <label for="name" class="form-label">Name</label>
          <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
          <label for="phone" class="form-label">Phone number</label>
          <input type="text" id="phone" name="phone" maxlength="10" pattern="[0-9]{10}" title="10-digit phone number"
            required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
        </div>
        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <input type="email" id="email" name="email" required>
          <p class="error-message" id="error-email" style="display: none;">Email already exists!</p>
        </div>
        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="button-primary">Create Account</button>
      </form>
      <div class="signup-link">
        Already have an account? <a href="./login.html">Sign in</a>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const params = new URLSearchParams(window.location.search);
      if (params.has("error") && params.get("error") === "email_exists") {
        document.getElementById("error-email").style.display = "block";
      }
    });
  </script>
</body>
</html>