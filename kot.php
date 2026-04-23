<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';

$table_no = $_GET['table'] ?? '';
$bill_id = $_GET['bill_id'] ?? '';

// Get latest order for table if no bill_id provided
if (!$bill_id && $table_no) {
    $result = $conn->query("SELECT id FROM bills WHERE table_no = $table_no ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $bill_id = $result->fetch_assoc()['id'];
    }
}

if (!$bill_id) {
    die("No KOT found");
}

// Get bill and items
$bill = $conn->query("SELECT * FROM bills WHERE id = $bill_id")->fetch_assoc();
$items = $conn->query("SELECT * FROM order_items WHERE bill_id = $bill_id");

if (!$bill) {
    die("KOT not found");
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Kitchen Order Ticket</title>
<style>
body {
    font-family: 'Courier New', monospace;
    margin: 0;
    padding: 20px;
    background: white;
    font-size: 12px;
    line-height: 1.4;
}

.kot-container {
    max-width: 300px;
    margin: 0 auto;
    border: 2px solid #000;
    padding: 10px;
}

.header {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.header h1 {
    margin: 0;
    font-size: 16px;
    font-weight: bold;
}

.header h2 {
    margin: 5px 0;
    font-size: 14px;
    font-weight: bold;
}

.info-section {
    margin-bottom: 15px;
    border-bottom: 1px solid #000;
    padding-bottom: 10px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
}

.items-section {
    margin-bottom: 15px;
}

.item-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    padding: 2px 0;
}

.item-name {
    flex: 1;
    font-weight: bold;
}

.item-qty {
    margin-left: 10px;
    font-weight: bold;
}

.footer {
    text-align: center;
    border-top: 2px solid #000;
    padding-top: 10px;
    font-size: 11px;
}

@media print {
    body {
        padding: 5px;
    }
    
    .kot-container {
        border: 1px solid #000;
        max-width: none;
    }
    
    .no-print {
        display: none;
    }
}
</style>
</head>
<body>
<div class="kot-container">
    <div class="header">
        <h1>🏨 HOTEL RESTAURANT</h1>
        <h2>KITCHEN ORDER TICKET</h2>
    </div>

    <div class="info-section">
        <div class="info-row">
            <strong>KOT No:</strong>
            <span><?php echo $bill['id']; ?></span>
        </div>
        <div class="info-row">
            <strong>Table No:</strong>
            <span><?php echo $bill['table_no']; ?></span>
        </div>
        <div class="info-row">
            <strong>Date:</strong>
            <span><?php echo date('d/m/Y', strtotime($bill['date'])); ?></span>
        </div>
        <div class="info-row">
            <strong>Time:</strong>
            <span><?php echo date('H:i:s'); ?></span>
        </div>
        <?php if ($bill['customer_name']): ?>
        <div class="info-row">
            <strong>Customer:</strong>
            <span><?php echo $bill['customer_name']; ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="items-section">
        <div style="border-bottom: 1px solid #000; margin-bottom: 10px; padding-bottom: 5px;">
            <strong>ITEMS TO PREPARE:</strong>
        </div>
        
        <?php while($item = $items->fetch_assoc()): ?>
        <div class="item-row">
            <div class="item-name"><?php echo $item['item_name']; ?></div>
            <div class="item-qty">x <?php echo $item['qty']; ?></div>
        </div>
        <?php endwhile; ?>
    </div>

    <div class="footer">
        <p><strong>Prepared by:</strong> <?php echo $_SESSION['username']; ?></p>
        <p><strong>Time:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>═══════════════════════</p>
        <p><strong>🍽️ KITCHEN COPY 🍽️</strong></p>
    </div>
</div>

<div class="no-print" style="text-align: center; margin-top: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #155d27; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">🖨️ Print KOT</button>
    <button onclick="window.close()" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">❌ Close</button>
</div>

<script>
// Auto print when page loads
window.onload = function() {
    // Uncomment the line below to auto-print
    // window.print();
}
</script>
</body>
</html>