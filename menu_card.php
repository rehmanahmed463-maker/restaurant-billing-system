<?php
session_start();

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hotel_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 🔹 Handle AJAX search request for billing.php
if (isset($_POST['search_term'])) {
    $q = $conn->real_escape_string($_POST['search_term']);
    $sql = "SELECT item_code, item_name, price 
            FROM menu_cards 
            WHERE item_name LIKE '%$q%' OR item_code LIKE '%$q%' 
            ORDER BY item_name ASC LIMIT 12";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<div class='suggestion-item' 
                    data-code='".$row['item_code']."' 
                    data-name='".$row['item_name']."' 
                    data-price='".$row['price']."'>
                    ".$row['item_code']." - ".$row['item_name']." (₹".$row['price'].")
                  </div>";
        }
    } else {
        echo "<div class='suggestion-item no-item'>No items found</div>";
    }
    exit; // Stop further rendering of the page
}

// 🔹 Normal page logic for admin interface
$message = "";

// Find next item_code automatically
$next_code = 1; // default if table is empty
$result = $conn->query("SELECT MAX(item_code) AS max_code FROM menu_cards");
if ($result && $row = $result->fetch_assoc()) {
    if (!empty($row['max_code'])) {
        $next_code = $row['max_code'] + 1;
    }
}

// Add new item from admin form
if (isset($_POST['add_item'])) {
    $item_code  = intval($_POST['item_code']); // auto-filled
    $item_name  = $conn->real_escape_string($_POST['item_name']);
    $price      = floatval($_POST['price']);
    $group_id   = intval($_POST['group_id']);

    if (!empty($item_name) && $price > 0 && $group_id > 0) {
        $sql = "INSERT INTO menu_cards (item_code, item_name, price, group_id) 
                VALUES ('$item_code', '$item_name', '$price', '$group_id')";
        if ($conn->query($sql)) {
            $message = "<p class='success'>Item added successfully!</p>";
            $next_code = $item_code + 1; // update next code for new entry
        } else {
            $message = "<p class='error'>Error: ".$conn->error."</p>";
        }
    } else {
        $message = "<p class='error'>Please fill all fields correctly.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Menu Card</title>
  <link rel="stylesheet" href="menu-car.css">
</head>
<body>
  <div class="container">
    <h1>Menu Card</h1>
    <p>Add menu items here.</p>

    <!-- Show success/error message -->
    <?php if (!empty($message)) echo $message; ?>

    <!-- Add Item Form -->
    <form method="POST" class="menu-form">
      <input type="number" name="item_code" value="<?php echo $next_code; ?>" readonly>
      <input type="text" name="item_name" placeholder="Item Name" required>
      <input type="number" name="price" step="0.01" placeholder="Price" required>

      <select name="group_id" required>
        <option value="">-- Select Group --</option>
        <?php
        $groups = $conn->query("SELECT * FROM menu_groups ORDER BY group_name ASC");
        while ($row = $groups->fetch_assoc()) {
            echo "<option value='".$row['id']."'>".$row['group_name']."</option>";
        }
        ?>
      </select>

      <button type="submit" name="add_item">Add Item</button>
    </form>
  </div>
</body>
</html>
