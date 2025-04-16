<?php
session_start();
$conn = new mysqli("localhost", "root", "", "parth");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        echo "<script>alert('Email and password are required.'); window.location.href='login.html';</script>";
        exit();
    }

    if ($email === "admin@fitflex.com" && $password === "admin12345") {
        header("Location: admin.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT user_email, user_password, user_name FROM userstable WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["user_password"])) {
            $_SESSION["user_email"] = $user["user_email"];
            $_SESSION["user_name"] = $user["user_name"];
            $_SESSION["logged_in"] = true;

            $subQuery = $conn->prepare("SELECT * FROM usersubscription WHERE user_email = ?");
            $subQuery->bind_param("s", $email);
            $subQuery->execute();
            $subResult = $subQuery->get_result();

            if ($subResult->num_rows === 0) {
                header("Location: subscription.php");
                exit();
            }

            $targetQuery = $conn->prepare("SELECT target_weight FROM userstable WHERE user_email = ?");
            $targetQuery->bind_param("s", $email);
            $targetQuery->execute();
            $targetResult = $targetQuery->get_result();

            if ($targetResult->num_rows > 0) {
                $targetRow = $targetResult->fetch_assoc();
                if ($targetRow['target_weight'] == 0 || $targetRow['target_weight'] === NULL) {
                    header("Location: details.php");
                    exit();
                }
            }

            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid email or password.'); window.location.href='login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid email or password.'); window.location.href='login.html';</script>";
        exit();
    }

    $stmt->close();
} else {
    header("Location: login.html");
    exit();
}

$conn->close();
