<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hotel_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$item = null;

// ✅ Step 1: Fetch item by code
if (isset($_POST['fetch_item'])) {
    $item_code = intval($_POST['item_code']);
    $result = $conn->query("SELECT * FROM menu_cards WHERE item_code = $item_code");
    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        $message = "<p class='error'>❌ Item not found with code $item_code.</p>";
    }
}

// ✅ Step 2: Update item price
if (isset($_POST['update_price'])) {
    $item_code = intval($_POST['item_code']);
    $new_price = floatval($_POST['new_price']);

    if ($new_price > 0) {
        $sql = "UPDATE menu_cards SET price = $new_price WHERE item_code = $item_code";
        if ($conn->query($sql)) {
            $message = "<p class='success'>✅ Price updated successfully for Item Code $item_code!</p>";
        } else {
            $message = "<p class='error'>❌ Error: ".$conn->error."</p>";
        }
    } else {
        $message = "<p class='error'>❌ Enter a valid price.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Item Rate</title>
  <link rel="stylesheet" href="menu-card.css">
  <style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .item-box { border: 1px solid #ccc; padding: 15px; margin-top: 15px; border-radius: 8px; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Change Item Rate</h1>
    <p>Update item price using item code.</p>

    <!-- Show messages -->
    <?php if (!empty($message)) echo $message; ?>

    <!-- Step 1: Enter Item Code -->
    <form method="POST" class="menu-form">
      <input type="number" name="item_code" placeholder="Enter Item Code" required>
      <button type="submit" name="fetch_item">Fetch Item</button>
    </form>

    <!-- Step 2: If item found, show update form -->
    <?php if ($item): ?>
      <div class="item-box">
        <p><strong>Item Code:</strong> <?php echo $item['item_code']; ?></p>
        <p><strong>Item Name:</strong> <?php echo $item['item_name']; ?></p>
        <p><strong>Current Price:</strong> ₹<?php echo $item['price']; ?></p>

        <form method="POST" class="menu-form">
          <input type="hidden" name="item_code" value="<?php echo $item['item_code']; ?>">
          <input type="number" name="new_price" step="0.01" placeholder="Enter New Price" required>
          <button type="submit" name="update_price">Update Price</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
