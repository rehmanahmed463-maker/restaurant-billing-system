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

// Add new group
if (isset($_POST['add_group'])) {
    $group_name = $conn->real_escape_string($_POST['group_name']);
    $conn->query("INSERT INTO menu_groups (group_name) VALUES ('$group_name')");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Menu Groups</title>
  <link rel="stylesheet" href="menu-group.css">
  <style>
    body { font-family: "Segoe UI", sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
    .container { max-width: 1100px; margin: 40px auto; padding: 20px; }
    h1 { color: #155d27; margin-bottom: 10px; }
    .groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
    .group-card { background: #eaffea; border-radius: 10px; padding: 10px 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    details summary { font-size: 18px; font-weight: bold; color: #0b3d0b; cursor: pointer; padding: 5px 0; }
    details summary::-webkit-details-marker { display: none; } /* remove default arrow */
    details summary::after { content: " ▼"; font-size: 14px; }
    details[open] summary::after { content: " ▲"; }
    ul { padding-left: 20px; margin: 8px 0; }
    li { font-size: 14px; margin-bottom: 4px; }
    form { margin-bottom: 20px; }
    input, button { padding: 6px 10px; margin: 4px; border-radius: 5px; border: 1px solid #ccc; }
    button { background: #155d27; color: #fff; border: none; cursor: pointer; }
    button:hover { background: #0b3d0b; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Menu Groups</h1>
    <p>Manage your menu categories here.</p>

    <!-- Add Group Form -->
    <form method="POST">
      <input type="text" name="group_name" placeholder="Enter new category" required>
      <button type="submit" name="add_group">Add Group</button>
    </form>

    <!-- Show Groups as Dropdown Cards -->
    <div class="groups-grid">
      <?php
      $groups = $conn->query("SELECT * FROM menu_groups ORDER BY group_name ASC");
      while ($group = $groups->fetch_assoc()) {
        echo "<div class='group-card'>";
        echo "<details>";
        echo "<summary>".$group['group_name']."</summary>";

        $group_id = $group['id'];
        $items = $conn->query("SELECT * FROM menu_cards WHERE group_id = $group_id");

        if ($items && $items->num_rows > 0) {
          echo "<ul>";
          while ($item = $items->fetch_assoc()) {
            echo "<li>".$item['item_code']." - ".$item['item_name']." (₹".$item['price'].")</li>";
          }
          echo "</ul>";
        } else {
          echo "<p style='font-size:12px; color:#777;'>No items yet</p>";
        }

        echo "</details>";
        echo "</div>";
      }
      ?>
    </div>
  </div>
</body>
</html>
