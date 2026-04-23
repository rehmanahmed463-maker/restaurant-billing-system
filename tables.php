<?php
// --- DB connection ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hotel_db";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- Defaults ---
$type = isset($_GET['type']) ? $_GET['type'] : "Restaurant";
$tables = [];
$current_group = null;

// --- Fetch group info ---
$sql = "SELECT * FROM table_groups WHERE name='$type' LIMIT 1";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    $current_group = $res->fetch_assoc();
    for ($i=$current_group['start_number']; $i<=$current_group['end_number']; $i++) {
        $statusRes = $conn->query("SELECT status FROM pos_tables WHERE table_no=$i");
        $status = ($statusRes && $statusRes->num_rows > 0)
            ? $statusRes->fetch_assoc()['status']
            : "free";
        $tables[] = ["no"=>$i, "status"=>$status];
    }
}

// --- Update Range ---
if (isset($_POST['update_range'])) {
    $start = intval($_POST['start']);
    $end   = intval($_POST['end']);
    if ($start > 0 && $end >= $start) {
        $conn->query("UPDATE table_groups SET start_number=$start,end_number=$end WHERE name='$type'");
        header("Location: tables.php?type=$type");
        exit;
    }
}

// --- Delete Section ---
if (isset($_POST['delete_section'])) {
    $conn->query("DELETE FROM table_groups WHERE name='$type'");
    header("Location: tables.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo $type; ?> Tables</title>
<style>
body { font-family: Arial, sans-serif; background:#f8f8f8; padding:20px; }
h2 { color:#155d27; margin-bottom:20px; }
.grid { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
.table-btn {
  width:100px; height:60px;
  display:flex; align-items:center; justify-content:center;
  border-radius:6px; font-weight:bold; cursor:pointer;
  border:2px solid;
}
.free { background:#eaffea; border-color:#155d27; color:#155d27; }
.running { background:#ffd6d6; border-color:#c62828; color:#c62828; }
.form-box { background:#fff; padding:15px; border:1px solid #ccc; border-radius:6px; }
label { margin-right:5px; }
input { padding:6px; margin:5px; border:1px solid #ccc; border-radius:4px; }
button { padding:6px 12px; border:none; border-radius:4px; cursor:pointer; }
.update { background:#155d27; color:#fff; }
.delete { background:#c62828; color:#fff; margin-top:10px; }
</style>
</head>
<body>

<h2><?php echo $type; ?> Tables</h2>

<div class="grid">
<?php foreach($tables as $t): ?>
  <div class="table-btn <?php echo $t['status']; ?>">
    Table <?php echo $t['no']; ?><br>(<?php echo ucfirst($t['status']); ?>)
  </div>
<?php endforeach; ?>
</div>

<?php if ($current_group): ?>
<div class="form-box">
  <form method="post">
    <label>Start No:</label>
    <input type="number" name="start" value="<?php echo $current_group['start_number']; ?>" required>
    <label>End No:</label>
    <input type="number" name="end" value="<?php echo $current_group['end_number']; ?>" required>
    <button type="submit" name="update_range" class="update">Update</button>
  </form>

  <form method="post" onsubmit="return confirm('Delete this section?');">
    <button type="submit" name="delete_section" class="delete">Delete Section</button>
  </form>
</div>
<?php endif; ?>

</body>
</html>
