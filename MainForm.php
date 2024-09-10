<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lab_act2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables to store user input
$name = $category = $price = $quantity = '';
$error = '';
$report = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    // Check if all fields are filled
    if (empty($name) || empty($category) || $price === false || $quantity === false) {
        $error = "Please fill all fields with valid information.";
    } else {
        // Prepare SQL statement
        $sql = "INSERT INTO stock (Name, Category, Price, Quantity) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $name, $category, $price, $quantity);

        // Execute the statement
        if ($stmt->execute()) {
            $report = "New item added successfully.\n\n";
        } else {
            $error = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Fetch all data from the stock table
$sql = "SELECT * FROM stock";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $report .= "Current Stock:\n\n";
    $report .= sprintf("%-5s %-20s %-15s %-10s %-10s %-15s\n", "ID", "Name", "Category", "Price", "Quantity", "Status");
    $report .= str_repeat("-", 80) . "\n";

    $lowStockItems = array();

    while($row = $result->fetch_assoc()) {
        // Check if all required keys exist in the row
        $id = isset($row["ID"]) ? $row["ID"] : "N/A";
        $name = isset($row["Name"]) ? substr($row["Name"], 0, 20) : "N/A";
        $category = isset($row["Category"]) ? substr($row["Category"], 0, 15) : "N/A";
        $price = isset($row["Price"]) ? $row["Price"] : 0;
        $quantity = isset($row["Quantity"]) ? $row["Quantity"] : 0;

        $status = $quantity > 10 ? "In Stock" : "<span class='low-stock'>Low Stock</span>";
        $report .= sprintf("%-5s %-20s %-15s $%-9.2f %-10d %s\n", 
            $id, 
            $name, 
            $category, 
            $price, 
            $quantity,
            $status
        );

        // Add to lowStockItems if quantity is less than 10
        if ($quantity < 10) {
            $lowStockItems[] = array(
                'name' => $name,
                'quantity' => $quantity
            );
        }
    }
} else {
    $report .= "No items in stock or error in fetching data.";
}

// Add error reporting
if ($conn->error) {
    $report .= "\nDatabase error: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Input Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        form {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .report {
            background: #eaf2f8;
            border: 1px solid #3498db;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: monospace;
        }
        .low-stock {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
    <script>
        // Convert PHP array to JavaScript array
        var lowStockItems = <?php echo json_encode($lowStockItems); ?>;

        function showLowStockAlerts() {
            lowStockItems.forEach(function(item) {
                alert("Low stock alert: " + item.name + " has only " + item.quantity + " items left!");
            });
        }

        // Call the function when the page loads
        window.onload = showLowStockAlerts;
    </script>
</head>
<body>
    <h2>Stock Input Form</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="name">Product Name:</label>
        <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
        
        <label for="category">Category:</label>
        <input type="text" id="category" name="category" value="<?php echo $category; ?>" required>
        
        <label for="price">Price:</label>
        <input type="number" id="price" name="price" step="0.01" value="<?php echo $price; ?>" required>
        
        <label for="quantity">Quantity:</label>
        <input type="number" id="quantity" name="quantity" value="<?php echo $quantity; ?>" required>
        
        <input type="submit" value="Submit">
    </form>

    <?php if (!empty($report)): ?>
        <div class="report">
            <h3>Report Summary</h3>
            <pre><?php echo $report; ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>
