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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["new_weight"])) {
        $new_weight = floatval($_POST["new_weight"]);
        if ($new_weight > 0) {
            $stmt = $conn->prepare("UPDATE userstable SET user_weight = ? WHERE user_email = ?");
            $stmt->bind_param("ds", $new_weight, $user_email);
            $stmt->execute();
            $stmt->close();
            echo $new_weight;
            exit();
        }
    }

    if (isset($_POST["new_target_weight"])) {
        $new_target_weight = floatval($_POST["new_target_weight"]);
        if ($new_target_weight > 0) {
            $stmt = $conn->prepare("UPDATE userstable SET target_weight = ? WHERE user_email = ?");
            $stmt->bind_param("ds", $new_target_weight, $user_email);
            $stmt->execute();
            $stmt->close();
            echo $new_target_weight;
            exit();
        }
    }

    if (isset($_POST["action"]) && $_POST["action"] === "update_profile") {
        $response = ["status" => "error", "message" => "No fields to update"];

        if (isset($_POST["user_name"]) && !empty($_POST["user_name"])) {
            $user_name = $_POST["user_name"];
            $stmt = $conn->prepare("UPDATE userstable SET user_name = ? WHERE user_email = ?");
            $stmt->bind_param("ss", $user_name, $user_email);
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Name updated successfully", "field" => "user_name", "value" => $user_name];
            }
            $stmt->close();
        }

        if (isset($_POST["phone_number"]) && !empty($_POST["phone_number"])) {
            $phone_number = $_POST["phone_number"];
            $stmt = $conn->prepare("UPDATE userstable SET phone_number = ? WHERE user_email = ?");
            $stmt->bind_param("ss", $phone_number, $user_email);
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Phone updated successfully", "field" => "phone_number", "value" => $phone_number];
            }
            $stmt->close();
        }

        if (isset($_POST["user_height"]) && !empty($_POST["user_height"])) {
            $user_height = floatval($_POST["user_height"]);
            $stmt = $conn->prepare("UPDATE userstable SET user_height = ? WHERE user_email = ?");
            $stmt->bind_param("ds", $user_height, $user_email);
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Height updated successfully", "field" => "user_height", "value" => $user_height];
            }
            $stmt->close();
        }

        echo json_encode($response);
        exit();
    }
}

$stmt = $conn->prepare("SELECT u.user_name, u.phone_number, u.user_height, u.user_weight, t.target_weight FROM userstable AS u LEFT JOIN userstable AS t ON u.user_email = t.user_email WHERE u.user_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$user_result = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT subscription_id, start_date, plan_length, plan_price FROM usersubscription WHERE user_email = ? ORDER BY start_date DESC LIMIT 1");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$sub_result = $stmt->get_result()->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        .edit-row {
            display: flex;
            align-items: center;
            margin-top: 5px;
        }

        .update-btn {
            display: inline-block;
            padding: 5px 10px;
            background-color: #28a745;
            color: white;
            border: none;
            text-decoration: none;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
            margin-left: 10px;
            font-size: 12px;
        }

        .update-btn:hover {
            background-color: #218838;
        }

        .weight-input,
        .target-input,
        .editable-input {
            display: none;
            padding: 5px;
            font-size: 1em;
            width: calc(100% - 100px);
        }

        .data-display {
            display: block;
            margin-bottom: 5px;
        }

        .error-message {
            color: red;
            display: none;
            font-size: 12px;
            margin-top: 5px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #333;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>My Profile</h2>
        <table>
            <tr>
                <th>Name</th>
                <td>
                    <span id="name-display" class="data-display"><?php echo htmlspecialchars($user_result["user_name"]); ?></span>
                    <div class="edit-row">
                        <input type="text" id="name-input" class="editable-input" maxlength="50">
                        <button id="update-btn-name" class="update-btn">Edit</button>
                    </div>
                </td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($user_email); ?></td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>
                    <span id="phone-display" class="data-display"><?php echo htmlspecialchars($user_result["phone_number"]); ?></span>
                    <div class="edit-row">
                        <input type="tel" id="phone-input" class="editable-input" maxlength="10">
                        <button id="update-btn-phone" class="update-btn">Edit</button>
                    </div>
                    <div id="phone-error" class="error-message">Please enter a valid 10-digit phone number</div>
                </td>
            </tr>
            <tr>
                <th>Height</th>
                <td>
                    <span id="height-display" class="data-display"><?php echo htmlspecialchars($user_result["user_height"]); ?> cm</span>
                    <div class="edit-row">
                        <input type="number" id="height-input" class="editable-input" min="50" max="250">
                        <button id="update-btn-height" class="update-btn">Edit</button>
                    </div>
                </td>
            </tr>
            <tr>
                <th>Weight</th>
                <td>
                    <span id="weight-display" class="data-display"><?php echo htmlspecialchars($user_result["user_weight"]); ?> kg</span>
                    <div class="edit-row">
                        <input type="number" id="weight-input" class="weight-input" min="1">
                        <button id="update-btn-weight" class="update-btn">Edit</button>
                    </div>
                </td>
            </tr>
            <tr>
                <th>Target Weight</th>
                <td>
                    <span id="target-display" class="data-display"><?php echo htmlspecialchars($user_result["target_weight"]); ?> kg</span>
                    <div class="edit-row">
                        <input type="number" id="target-input" class="target-input" min="1">
                        <button id="update-btn-target" class="update-btn">Edit</button>
                    </div>
                </td>
            </tr>
        </table>

        <h2>Subscription Details</h2>
        <table>
            <tr>
                <th>Subscription ID</th>
                <td><?php echo isset($sub_result["subscription_id"]) ? htmlspecialchars($sub_result["subscription_id"]) : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Start Date</th>
                <td><?php echo isset($sub_result["start_date"]) ? htmlspecialchars($sub_result["start_date"]) : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Plan Length</th>
                <td><?php echo isset($sub_result["plan_length"]) ? htmlspecialchars($sub_result["plan_length"]) . ' days' : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Plan Price</th>
                <td><?php echo isset($sub_result["plan_price"]) ? '₹' . htmlspecialchars($sub_result["plan_price"]) : 'N/A'; ?></td>
            </tr>
        </table>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        function setupUpdateButton(updateBtnId, displayId, inputId, postField, validator = null) {
            let updateBtn = document.getElementById(updateBtnId);
            let display = document.getElementById(displayId);
            let input = document.getElementById(inputId);

            updateBtn.addEventListener("click", function() {
                if (input.style.display === "none" || input.style.display === "") {
                    input.style.display = "inline-block";
                    updateBtn.textContent = "Save";
                    input.value = display.innerText.split(" ")[0];
                    input.focus();
                } else {
                    let newValue = input.value.trim();

                    if (validator && !validator(newValue)) {
                        alert("Invalid input");
                        return;
                    }

                    let formData = new FormData();
                    formData.append(postField, newValue);

                    fetch("", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            let displayText = data + (postField.includes('height') ? " cm" : " kg");
                            display.innerText = displayText;
                            input.style.display = "none";
                            updateBtn.textContent = "Edit";
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to update');
                        });
                }
            });

            input.addEventListener("keypress", function(event) {
                if (event.key === "Enter") {
                    updateBtn.click();
                }
            });
        }

        setupUpdateButton("update-btn-weight", "weight-display", "weight-input", "new_weight",
            (value) => !isNaN(value) && value > 0);
        setupUpdateButton("update-btn-target", "target-display", "target-input", "new_target_weight",
            (value) => !isNaN(value) && value > 0);

        function setupEditableUpdate(btnId, displayId, inputId, field, validator = null) {
            let updateBtn = document.getElementById(btnId);
            let display = document.getElementById(displayId);
            let input = document.getElementById(inputId);
            let errorDisplay = document.getElementById(field + "-error");

            updateBtn.addEventListener("click", function() {
                if (input.style.display === "none" || input.style.display === "") {
                    input.style.display = "inline-block";
                    updateBtn.textContent = "Save";
                    input.value = display.innerText;
                    input.focus();
                    if (errorDisplay) {
                        errorDisplay.style.display = "none";
                    }
                } else {
                    let newValue = input.value.trim();

                    if (validator && !validator(newValue)) {
                        if (errorDisplay) {
                            errorDisplay.style.display = "block";
                        } else {
                            alert('Invalid input');
                        }
                        return;
                    }

                    let formData = new FormData();
                    formData.append("action", "update_profile");
                    formData.append(field, newValue);

                    fetch("", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "success") {
                                display.innerText = data.value;
                                input.style.display = "none";
                                updateBtn.textContent = "Edit";
                                if (errorDisplay) {
                                    errorDisplay.style.display = "none";
                                }
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to update. Please try again.');
                        });
                }
            });

            input.addEventListener("keypress", function(event) {
                if (event.key === "Enter") {
                    updateBtn.click();
                }
            });
        }

        setupEditableUpdate("update-btn-name", "name-display", "name-input", "user_name");
        setupEditableUpdate("update-btn-phone", "phone-display", "phone-input", "phone_number",
            function(value) {
                return /^\d{10}$/.test(value);
            });
        setupEditableUpdate("update-btn-height", "height-display", "height-input", "user_height",
            function(value) {
                return !isNaN(value) && value >= 50 && value <= 250;
            });

        document.getElementById("phone-input").addEventListener("input", function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>

</html>