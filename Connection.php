<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log function
function logMessage($message) {
    file_put_contents('debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Initialize inventory array if it doesn't exist in the session
if (!isset($_SESSION['inventory'])) {
    $_SESSION['inventory'] = [
        ['ID' => 1, 'Name' => 'Iphone 15', 'Category' => 'Smart Ph', 'Price' => 30.00, 'Quantity' => 20 ],
    ];
}

$message = '';
$debug_info = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    logMessage("Received POST request: " . print_r($_POST, true));
    $debug_info['post_data'] = $_POST;

    if (isset($_POST['search_name'])) {
        // Search operation
        $search_name = strtolower(trim($_POST['search_name']));
        $search_results = array_filter($_SESSION['inventory'], function($item) use ($search_name) {
            return strpos(strtolower($item['Name']), $search_name) !== false;
        });
        if (!empty($search_results)) {
            $response['search_results'] = array_values($search_results);
            $message = "Search results found.";
        } else {
            $message = "No products found.";
        }
    } elseif (isset($_POST['edit']) && isset($_POST['id'])) {
        // Fetch item for editing
        $id = intval($_POST['id']);
        $item = array_filter($_SESSION['inventory'], function($item) use ($id) {
            return $item['ID'] == $id;
        });
        if (!empty($item)) {
            $response['item'] = reset($item);
            $message = "Item fetched for editing.";
        } else {
            $message = "Item not found.";
        }
    } elseif (isset($_POST['name']) && isset($_POST['category']) && isset($_POST['price']) && isset($_POST['quantity'])) {
        // Add or update item
        $name = trim($_POST['name']);
        $category = trim($_POST['category']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);

        if (empty($name) || empty($category) || $price <= 0 || $quantity < 0) {
            $message = "Invalid input. All fields are required, price must be positive, and quantity must be non-negative.";
        } else {
            if (empty($_POST['id'])) {
                // Add new item
                $new_id = max(array_column($_SESSION['inventory'], 'ID')) + 1;
                $_SESSION['inventory'][] = [
                    'ID' => $new_id,
                    'Name' => $name,
                    'Category' => $category,
                    'Price' => $price,
                    'Quantity' => $quantity
                ];
                $message = "Item added successfully.";
            } else {
                // Update existing item
                $id = intval($_POST['id']);
                foreach ($_SESSION['inventory'] as &$item) {
                    if ($item['ID'] == $id) {
                        $item['Name'] = $name;
                        $item['Category'] = $category;
                        $item['Price'] = $price;
                        $item['Quantity'] = $quantity;
                        break;
                    }
                }
                $message = "Item updated successfully.";
            }
        }
    } elseif (isset($_POST['delete'])) {
        // Delete operation
        $id = intval($_POST['id']);
        $_SESSION['inventory'] = array_filter($_SESSION['inventory'], function($item) use ($id) {
            return $item['ID'] != $id;
        });
        $message = "Item deleted successfully.";
    }
}

// Generate inventory table HTML
$table_html = '<table class="inventory-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Category</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>';

if (!empty($_SESSION['inventory'])) {
    foreach ($_SESSION['inventory'] as $row) {
        $table_html .= "<tr>
            <td>{$row['ID']}</td>
            <td>{$row['Name']}</td>
            <td>{$row['Category']}</td>
            <td>$" . number_format($row['Price'], 2) . "</td>
            <td>{$row['Quantity']}</td>
            <td>
                <div class='button-container'>
                    <button class='edit-btn' onclick='editItem({$row['ID']})'>Edit</button>
                    <button class='delete-btn' onclick='deleteItem({$row['ID']})'>Delete</button>
                </div>
            </td>
        </tr>";
    }
} else {
    $table_html .= "<tr><td colspan='6'>No items in inventory</td></tr>";
}

$table_html .= '</tbody></table>';

// Get low stock items
$lowStockItems = array_filter($_SESSION['inventory'], function($item) {
    return $item['Quantity'] < 10;
});
$lowStockItems = array_map(function($item) {
    return ['name' => $item['Name'], 'quantity' => $item['Quantity']];
}, $lowStockItems);

// Send JSON response
header('Content-Type: application/json');
$response['message'] = $message;
$response['inventory_table'] = $table_html;
$response['lowStockItems'] = array_values($lowStockItems);
$response['debug_info'] = $debug_info;
echo json_encode($response);
logMessage("Sent response: " . print_r($response, true));
exit;
