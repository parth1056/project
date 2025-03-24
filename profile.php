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
            $stmt = $conn->prepare("UPDATE usertarget SET target_weight = ? WHERE user_email = ?");
            $stmt->bind_param("ds", $new_target_weight, $user_email);
            $stmt->execute();
            $stmt->close();
            echo $new_target_weight;
            exit();
        }
    }

    if (isset($_POST["action"]) && $_POST["action"] === "update_profile") {
        $updates = [];
        $types = "";
        $params = [];

        $fields = [
            "user_name" => "s",
            "phone_number" => "s",
            "user_height" => "d"
        ];

        foreach ($fields as $field => $type) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                $updates[] = "$field = ?";
                $types .= $type;
                $params[] = $_POST[$field];
            }
        }

        if (!empty($updates)) {
            $params[] = $user_email;
            $types .= "s";

            $query = "UPDATE userstable SET " . implode(", ", $updates) . " WHERE user_email = ?";
            
            $stmt = $conn->prepare($query);
            
            $bindParams = array_merge([$stmt, $types], $params);
            call_user_func_array('mysqli_stmt_bind_param', $bindParams);
            
            $result = $stmt->execute();
            $stmt->close();

            if ($result) {
                echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update profile"]);
            }
            exit();
        }
    }
}

$stmt = $conn->prepare("SELECT u.user_name, u.phone_number, u.user_height, u.user_weight, t.target_weight FROM userstable AS u LEFT JOIN usertarget AS t ON u.user_email = t.user_email WHERE u.user_email = ?");
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
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
            position: relative;
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
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
        }
        .update-btn:hover {
            background-color: #218838;
        }
        .editable-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .weight-input, .target-input, .editable-input {
            display: none;
            flex-grow: 1;
            margin-right: 10px;
            padding: 5px;
            font-size: 1em;
        }
        .error-message {
            color: red;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Profile</h2>
        <table>
            <tr><th>Name</th><td class="editable-container">
                <span id="name-display"><?php echo htmlspecialchars($user_result["user_name"]); ?></span>
                <input type="text" id="name-input" class="editable-input" maxlength="50">
                <button id="update-btn-name" class="update-btn">Update</button>
            </td></tr>
            <tr><th>Email</th><td><?php echo htmlspecialchars($user_email); ?></td></tr>
            <tr><th>Phone</th><td class="editable-container">
                <span id="phone-display"><?php echo htmlspecialchars($user_result["phone_number"]); ?></span>
                <input type="tel" id="phone-input" class="editable-input" maxlength="15">
                <button id="update-btn-phone" class="update-btn">Update</button>
            </td></tr>
            <tr><th>Height</th><td class="editable-container">
                <span id="height-display"><?php echo htmlspecialchars($user_result["user_height"]); ?> cm</span>
                <input type="number" id="height-input" class="editable-input" min="50" max="250">
                <button id="update-btn-height" class="update-btn">Update</button>
            </td></tr>
            <tr>
                <th>Weight</th>
                <td class="editable-container">
                    <span id="weight-display"><?php echo htmlspecialchars($user_result["user_weight"]); ?> kg</span>
                    <input type="number" id="weight-input" class="weight-input" min="1">
                    <button id="update-btn-weight" class="update-btn">Update</button>
                </td>
            </tr>
            <tr>
                <th>Target Weight</th>
                <td class="editable-container">
                    <span id="target-display"><?php echo htmlspecialchars($user_result["target_weight"]); ?> kg</span>
                    <input type="number" id="target-input" class="target-input" min="1">
                    <button id="update-btn-target" class="update-btn">Update</button>
                </td>
            </tr>
        </table>

        <h2>Subscription Details</h2>
        <table>
            <tr><th>Subscription ID</th><td><?php echo htmlspecialchars($sub_result["subscription_id"]); ?></td></tr>
            <tr><th>Start Date</th><td><?php echo htmlspecialchars($sub_result["start_date"]); ?></td></tr>
            <tr><th>Plan Length</th><td><?php echo htmlspecialchars($sub_result["plan_length"]); ?> days</td></tr>
            <tr><th>Plan Price</th><td>₹<?php echo htmlspecialchars($sub_result["plan_price"]); ?></td></tr>
        </table>
    </div>

    <script>
        function setupUpdateButton(updateBtnId, displayId, inputId, postField, validator = null) {
            let updateBtn = document.getElementById(updateBtnId);
            let display = document.getElementById(displayId);
            let input = document.getElementById(inputId);

            updateBtn.addEventListener("click", function() {
                display.style.display = "none";
                updateBtn.style.display = "none";
                input.style.display = "inline-block";
                input.value = display.innerText.split(" ")[0];
                input.focus();
            });

            input.addEventListener("keypress", function(event) {
                if (event.key === "Enter") {
                    let newValue = this.value;
                    
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
                        display.style.display = "inline-block";
                        updateBtn.style.display = "inline-block";
                        input.style.display = "none";
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update');
                    });
                }
            });
        }

        setupUpdateButton("update-btn-weight", "weight-display", "weight-input", "new_weight", 
            (value) => !isNaN(value) && value > 0);
        setupUpdateButton("update-btn-target", "target-display", "target-input", "new_target_weight", 
            (value) => !isNaN(value) && value > 0);

        function setupEditableUpdate(btnId, displayId, inputId, field) {
            let updateBtn = document.getElementById(btnId);
            let display = document.getElementById(displayId);
            let input = document.getElementById(inputId);

            updateBtn.addEventListener("click", function() {
                display.style.display = "none";
                updateBtn.style.display = "none";
                input.style.display = "inline-block";
                input.value = display.innerText;
                input.focus();
            });

            input.addEventListener("keypress", function(event) {
                if (event.key === "Enter") {
                    let newValue = this.value.trim();
                    
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
                            display.innerText = newValue;
                            display.style.display = "inline-block";
                            updateBtn.style.display = "inline-block";
                            input.style.display = "none";
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update');
                    });
                }
            });
        }

        setupEditableUpdate("update-btn-name", "name-display", "name-input", "user_name");
        setupEditableUpdate("update-btn-phone", "phone-display", "phone-input", "phone_number");
        setupEditableUpdate("update-btn-height", "height-display", "height-input", "user_height");
    </script>
</body>
</html>