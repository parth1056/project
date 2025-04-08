<?php
$conn = new mysqli("localhost", "root", "", "parth");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$query_result = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['custom_query'])) {
    $sql = $_POST['custom_query'];
    $query_result = $conn->query($sql);
}

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) $tables[] = $row[0];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Parth DB</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 5px; }
        textarea { width: 100%; height: 100px; }
        .table-section { margin-top: 40px; }
        input[type="submit"] { margin-top: 10px; }
    </style>
</head>
<body>

<h1>Admin Panel - Database: parth</h1>

<div style="position: absolute; top: 20px; right: 20px;">
    <form action="login.html" method="GET" >
        <input type="submit" value="Logout" style="padding: 10px 20px; font-size: 16px; background-color: aliceblue; border-radius: 5px; cursor: pointer;">
    </form>
</div>

<h3>Run Custom SQL</h3>
<form method="POST">
    <label for="queryPicker">Common Queries:</label>
    <select id="queryPicker" onchange="fillQuery(this.value)">
        <option value="">-- Select a query --</option>
        <option value="SELECT * FROM userstable;">SELECT all from userstable</option>
        <option value="SELECT * FROM userdiet WHERE user_email = 'example@email.com';">SELECT with condition</option>
        <option value="INSERT INTO userstable (user_email, user_name, user_age, user_gender, user_password, user_height, user_weight, phone_number, subscription_status) VALUES ('john@example.com', 'John', 25, 'Male', 'pass123', 175, 70, '1234567890', 0);">INSERT example</option>
        <option value="UPDATE userdiet SET calorie_intake = 2100, protein_g = 100 WHERE diet_id = 1;">UPDATE example</option>
        <option value="DELETE FROM userworkout WHERE workout_id = 5;">DELETE example</option>
    </select>

    <br><br>
    <textarea name="custom_query" id="customQueryBox" placeholder="e.g., SELECT * FROM userstable" rows="6" cols="80"></textarea>
    <br><input type="submit" value="Run Query">
</form>

<script>
function fillQuery(query) {
    document.getElementById('customQueryBox').value = query;
}
</script>


<?php
if ($query_result !== null) {
    if ($query_result === TRUE) {
        echo "<p>Query executed successfully.</p>";
    } else {
        if ($query_result->num_rows > 0) {
            echo "<h4>Query Result:</h4><table><tr>";
            while ($field = $query_result->fetch_field()) {
                echo "<th>{$field->name}</th>";
            }
            echo "</tr>";
            while ($row = $query_result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $val) echo "<td>{$val}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No results.</p>";
        }
    }
}
?>

<?php foreach ($tables as $table): ?>
<div class="table-section">
    <h3><?php echo $table; ?></h3>
    <?php
    $res = $conn->query("SELECT * FROM `$table`");
    if ($res->num_rows > 0) {
        echo "<table><tr>";
        while ($col = $res->fetch_field()) echo "<th>{$col->name}</th>";
        echo "</tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $cell) echo "<td>{$cell}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in this table.</p>";
    }
    ?>
</div>
<?php endforeach; ?>

</body>
</html>
