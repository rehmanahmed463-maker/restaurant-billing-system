<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';

$bill_no = $_GET['bill_no'] ?? $_GET['id'] ?? '';

if (!$bill_no) {
    die("Bill number required");
}

// Get bill details
$bill = $conn->query("SELECT * FROM bills WHERE id = $bill_no")->fetch_assoc();
if (!$bill) {
    die("Bill not found");
}

// Get bill items
$items = $conn->query("SELECT * FROM order_items WHERE bill_id = $bill_no");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Bill Print</title>
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
    padding: 15px;
    background: white;
    font-size: 12px;
    line-height: 1.3;
}

.bill-container {
    max-width: 350px;
    margin: 0 auto;
    border: 2px solid #000;
    padding: 15px;
    background: white;
}

.header {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.header h1 {
    margin: 0;
    font-size: 18px;
    font-weight: bold;
    color: #155d27;
}

.header h2 {
    margin: 5px 0;
    font-size: 14px;
    font-weight: normal;
}

.header p {
    margin: 2px 0;
    font-size: 10px;
}

.bill-info {
    margin-bottom: 15px;
    border-bottom: 1px solid #000;
    padding-bottom: 10px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

.items-table th {
    border-bottom: 1px solid #000;
    padding: 5px 2px;
    text-align: left;
    font-weight: bold;
    font-size: 11px;
}

.items-table td {
    padding: 3px 2px;
    border-bottom: 1px dotted #ccc;
    font-size: 11px;
}

.items-table .qty {
    text-align: center;
    width: 30px;
}

.items-table .rate, .items-table .amount {
    text-align: right;
    width: 60px;
}

.totals-section {
    border-top: 2px solid #000;
    padding-top: 10px;
    margin-top: 10px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
    font-size: 12px;
}

.total-row.final {
    font-weight: bold;
    font-size: 14px;
    border-top: 1px solid #000;
    padding-top: 5px;
    margin-top: 5px;
}

.footer {
    text-align: center;
    margin-top: 15px;
    border-top: 1px solid #000;
    padding-top: 10px;
    font-size: 10px;
}

@media print {
    body {
        padding: 5px;
    }
    
    .bill-container {
        border: 1px solid #000;
        max-width: none;
        box-shadow: none;
    }
    
    .no-print {
        display: none;
    }
}
</style>
</head>
<body>
<div class="bill-container">
    <div class="header">
        <h1>🏨 HOTEL NISARG</h1>
        <h2>CUSTOMER BILL</h2>
        <p>📍 Address: Your Hotel Address Here</p>
        <p>📞 Phone: +91 9999999999 | Email: info@hotel.com</p>
        <p>🌐 GST No: 12ABCDE3456F7GH</p>
    </div>

    <div class="bill-info">
        <div class="info-row">
            <strong>Bill No:</strong>
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
        <?php if ($bill['phone']): ?>
        <div class="info-row">
            <strong>Phone:</strong>
            <span><?php echo $bill['phone']; ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <strong>Cashier:</strong>
            <span><?php echo $_SESSION['username']; ?></span>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="qty">Qty</th>
                <th class="rate">Rate</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            while($item = $items->fetch_assoc()): 
                $amount = $item['qty'] * $item['rate'];
                $subtotal += $amount;
            ?>
            <tr>
                <td><?php echo $item['item_name']; ?></td>
                <td class="qty"><?php echo $item['qty']; ?></td>
                <td class="rate">₹<?php echo number_format($item['rate'], 2); ?></td>
                <td class="amount">₹<?php echo number_format($amount, 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="totals-section">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>₹<?php echo number_format($bill['subtotal'], 2); ?></span>
        </div>
        
        <?php if ($bill['tax'] > 0): ?>
        <div class="total-row">
            <span>Tax (5%):</span>
            <span>₹<?php echo number_format($bill['tax'], 2); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (isset($bill['discount']) && $bill['discount'] > 0): ?>
        <div class="total-row">
            <span>Discount:</span>
            <span>- ₹<?php echo number_format($bill['discount'], 2); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="total-row final">
            <span>Grand Total:</span>
            <span>₹<?php echo number_format($bill['total'], 2); ?></span>
        </div>
    </div>

    <div class="footer">
        <p><strong>💳 Payment Status:</strong> <?php echo ucfirst($bill['status']); ?></p>
        <p>═══════════════════════════</p>
        <p><strong>Thank you for dining with us! 🙏</strong></p>
        <p>Please visit again!</p>
        <p>Generated on: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</div>

<div class="no-print" style="text-align: center; margin-top: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #155d27; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">🖨️ Print Bill</button>
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