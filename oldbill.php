<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';

// Get today's completed bills only
$today = date('Y-m-d');
$bills = [];

$stmt = $conn->prepare("
    SELECT b.*, COUNT(oi.id) as item_count 
    FROM bills b 
    LEFT JOIN order_items oi ON b.id = oi.bill_id 
    WHERE DATE(b.date) = ? AND b.status = 'completed' 
    GROUP BY b.id 
    ORDER BY b.id DESC
");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }
}

// Get bill details if requested
$selected_bill = null;
$bill_items = [];
$selected_index = 0;

if (isset($_GET['bill_id'])) {
    $bill_id = intval($_GET['bill_id']);
    
    // Find the index of the selected bill
    foreach ($bills as $index => $bill) {
        if ($bill['id'] == $bill_id) {
            $selected_index = $index;
            break;
        }
    }
    
    // Get bill info
    $stmt = $conn->prepare("SELECT * FROM bills WHERE id = ?");
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    $selected_bill = $stmt->get_result()->fetch_assoc();
    
    // Get bill items
    if ($selected_bill) {
        $stmt = $conn->prepare("SELECT * FROM order_items WHERE bill_id = ?");
        $stmt->bind_param("i", $bill_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        if ($items_result) {
            while ($item = $items_result->fetch_assoc()) {
                $bill_items[] = $item;
            }
        }
    }
} elseif (!empty($bills)) {
    // Auto-select first bill
    $selected_bill = $bills[0];
    $bill_id = $selected_bill['id'];
    
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE bill_id = ?");
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    if ($items_result) {
        while ($item = $items_result->fetch_assoc()) {
            $bill_items[] = $item;
        }
    }
}

// Calculate totals by group
$group_totals = [];
$total_sales = 0;
$total_bills = count($bills);

foreach ($bills as $bill) {
    $group = $bill['table_group'] ?? 'DEFAULT';
    if (!isset($group_totals[$group])) {
        $group_totals[$group] = ['count' => 0, 'total' => 0];
    }
    $group_totals[$group]['count']++;
    $group_totals[$group]['total'] += $bill['total'];
    $total_sales += $bill['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hotel Nisarga - Today's Bills</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* Compact CSS */
body {
    margin: 0;
    font-family: "Tahoma", "Arial", sans-serif;
    font-size: 10px;
    background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
    overflow: hidden;
}

.main-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #F0F0F0;
    border: 2px solid #808080;
}

.title-bar {
    background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
    color: white;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    border-bottom: 2px solid #808080;
    box-shadow: inset 0 1px 0 #FFD700, inset 0 -1px 0 #CC6600;
}

.toolbar {
    background: #E0E0E0;
    padding: 3px;
    border-bottom: 1px solid #808080;
    display: flex;
    align-items: center;
    gap: 8px;
}

.toolbar button {
    background: linear-gradient(135deg, #F0F0F0 0%, #D0D0D0 100%);
    border: 1px solid #808080;
    padding: 3px 8px;
    font-size: 10px;
    cursor: pointer;
    border-radius: 2px;
    transition: all 0.2s;
}

.toolbar button:hover {
    background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
    color: white;
}

.toolbar-right {
    margin-left: auto;
    font-size: 9px;
    color: #333;
}

.main-content {
    flex: 1;
    display: flex;
    background: #F0F0F0;
    overflow: hidden;
}

.left-panel {
    width: 200px;
    background: #E8E8E8;
    border-right: 2px solid #808080;
    display: flex;
    flex-direction: column;
    min-width: 180px;
    max-width: 220px;
}

.bills-header {
    background: linear-gradient(135deg, #D0D0D0 0%, #B0B0B0 100%);
    padding: 4px;
    font-weight: bold;
    text-align: center;
    border-bottom: 1px solid #808080;
    font-size: 10px;
    box-shadow: inset 0 1px 0 #E0E0E0;
}

.bills-list {
    flex: 1;
    overflow-y: auto;
    background: white;
    border: 1px inset #808080;
    margin: 1px;
    max-height: calc(100vh - 120px);
}

.bill-item {
    padding: 4px 6px;
    border-bottom: 1px solid #E0E0E0;
    cursor: pointer;
    font-size: 9px;
    transition: background 0.2s;
    border-left: 2px solid transparent;
    position: relative;
}

.bill-item:hover {
    background: #FFF5E6;
    border-left-color: #FFA500;
}

.bill-item.selected {
    background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
    color: white;
    border-left-color: #FF4500;
}

.bill-item-header {
    font-weight: bold;
    margin-bottom: 2px;
    font-size: 9px;
}

.bill-item-details {
    color: #666;
    font-size: 8px;
    line-height: 1.2;
}

.bill-item.selected .bill-item-details {
    color: #FFE4B5;
}

.right-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.bill-details-header {
    background: linear-gradient(135deg, #D0D0D0 0%, #B0B0B0 100%);
    padding: 6px;
    border-bottom: 1px solid #808080;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    font-size: 9px;
    box-shadow: inset 0 1px 0 #E0E0E0;
}

.detail-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 4px;
}

.detail-label {
    font-weight: bold;
    min-width: 45px;
    color: #333;
    font-size: 8px;
}

.detail-value {
    background: white;
    border: 1px inset #808080;
    padding: 2px 4px;
    min-width: 60px;
    font-family: "Courier New", monospace;
    font-size: 8px;
    background: linear-gradient(135deg, #FFFFFF 0%, #F8F8F8 100%);
}

.reprint-section {
    background: #E8E8E8;
    padding: 4px;
    border-bottom: 1px solid #808080;
    text-align: center;
}

.reprint-btn {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: 1px solid #1e7e34;
    padding: 4px 12px;
    font-size: 9px;
    font-weight: bold;
    cursor: pointer;
    border-radius: 2px;
    transition: all 0.2s;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.2);
}

.reprint-btn:hover {
    background: linear-gradient(135deg, #218838 0%, #1cc88a 100%);
    transform: translateY(-1px);
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.items-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    margin: 2px;
    overflow: hidden;
}

.items-table {
    flex: 1;
    border: 2px inset #808080;
    background: white;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.table-header {
    background: linear-gradient(135deg, #D0D0D0 0%, #B0B0B0 100%);
    display: grid;
    grid-template-columns: 40px 80px 2fr 35px 50px 60px 35px;
    font-weight: bold;
    font-size: 8px;
    border-bottom: 1px solid #808080;
    box-shadow: inset 0 1px 0 #E0E0E0;
    min-height: 20px;
}

.table-header div {
    padding: 4px 2px;
    border-right: 1px solid #808080;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
}

.table-body {
    flex: 1;
    overflow-y: auto;
    max-height: 250px;
}

.table-body::-webkit-scrollbar {
    width: 12px;
}

.table-body::-webkit-scrollbar-track {
    background: #F0F0F0;
}

.table-body::-webkit-scrollbar-thumb {
    background: #C0C0C0;
    border: 1px solid #808080;
}

.table-row {
    display: grid;
    grid-template-columns: 40px 80px 2fr 35px 50px 60px 35px;
    font-size: 8px;
    border-bottom: 1px solid #E0E0E0;
    background: white;
    min-height: 24px;
}

.table-row:nth-child(even) {
    background: #F8F8F8;
}

.table-row:hover {
    background: #FFF5E6;
}

.table-row div {
    padding: 3px 2px;
    border-right: 1px solid #E0E0E0;
    display: flex;
    align-items: center;
    font-size: 8px;
}

.table-row .code {
    justify-content: center;
    font-weight: bold;
    color: #FF8C00;
}

.table-row .qty {
    justify-content: center;
}

.table-row .amount {
    justify-content: flex-end;
    font-family: "Courier New", monospace;
    font-weight: bold;
}

.summary-panel {
    background: #E8E8E8;
    border-left: 2px solid #808080;
    width: 140px;
    padding: 4px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    overflow-y: auto;
    max-height: calc(100vh - 120px);
}

.summary-group {
    background: white;
    border: 2px inset #808080;
    padding: 6px;
    box-shadow: inset 1px 1px 0 #F0F0F0;
}

.summary-title {
    font-weight: bold;
    color: #FF8C00;
    margin-bottom: 4px;
    font-size: 8px;
    text-align: center;
    border-bottom: 1px solid #E0E0E0;
    padding-bottom: 2px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2px;
    font-size: 8px;
}

.summary-row span:first-child {
    color: #333;
}

.summary-row span:last-child {
    font-family: "Courier New", monospace;
    font-weight: bold;
    color: #FF8C00;
}

.summary-total {
    border-top: 1px solid #808080;
    padding-top: 2px;
    margin-top: 2px;
    font-weight: bold;
}

.summary-total span {
    color: #FF4500 !important;
}

.status-bar {
    background: #E0E0E0;
    border-top: 1px solid #808080;
    padding: 2px 6px;
    font-size: 8px;
    display: flex;
    justify-content: space-between;
    color: #666;
    box-shadow: inset 0 1px 0 #F0F0F0;
}

.no-bills, .no-selection {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100px;
    color: #666;
    font-style: italic;
    background: white;
    border: 1px inset #808080;
    margin: 5px;
    text-align: center;
    font-size: 10px;
}

/* Custom Scrollbars */
.bills-list::-webkit-scrollbar,
.summary-panel::-webkit-scrollbar {
    width: 10px;
}

.bills-list::-webkit-scrollbar-track,
.summary-panel::-webkit-scrollbar-track {
    background: #F0F0F0;
    border: 1px inset #808080;
}

.bills-list::-webkit-scrollbar-thumb,
.summary-panel::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #D0D0D0 0%, #B0B0B0 100%);
    border: 1px solid #808080;
}

.bills-list::-webkit-scrollbar-thumb:hover,
.summary-panel::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .left-panel {
        width: 180px;
    }
    
    .summary-panel {
        width: 120px;
    }
    
    .detail-value {
        min-width: 50px;
    }
}

/* Print Styles */
@media print {
    .toolbar, .status-bar, .reprint-section {
        display: none !important;
    }

    .main-container {
        border: none !important;
        height: auto !important;
    }

    body {
        background: white !important;
        font-size: 8px;
    }

    .title-bar {
        background: #FFA500 !important;
        color: black !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .left-panel {
        width: 150px;
    }

    .summary-panel {
        width: 100px;
    }

    .table-header, .bill-details-header, .bills-header {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: #D0D0D0 !important;
        color: black !important;
    }
}
</style>
</head>
<body>

<div class="main-container">
    <div class="title-bar">
        HOTEL NISARGA (<?php echo date('Y'); ?>) - Today's Bills Report (<?php echo date('d/m/Y'); ?>)
    </div>
    
    <div class="toolbar">
        <button onclick="goBack()" title="Return to Billing System">← Back to Billing</button>
        <button onclick="refreshData()" title="Refresh Data">🔄 Refresh</button>
        <span class="toolbar-right">
            User: <?php echo htmlspecialchars($_SESSION['username']); ?> | <?php echo date('d/m/Y H:i'); ?>
        </span>
    </div>

    <div class="main-content">
        <!-- Left Panel - Bills List -->
        <div class="left-panel">
            <div class="bills-header">
                TODAY'S BILLS (<?php echo $total_bills; ?>)
            </div>
            <div class="bills-list" id="billsList">
                <?php if (empty($bills)): ?>
                <div class="no-bills">No bills found for today</div>
                <?php else: ?>
                <?php foreach ($bills as $index => $bill): ?>
                <div class="bill-item <?php echo ($selected_bill && $selected_bill['id'] == $bill['id']) ? 'selected' : ''; ?>" 
                     data-bill-id="<?php echo $bill['id']; ?>"
                     data-index="<?php echo $index; ?>"
                     onclick="selectBill(<?php echo $bill['id']; ?>)" 
                     title="Click to view bill details">
                    <div class="bill-item-header">
                        Bill #<?php echo $bill['id']; ?> - Table <?php echo $bill['table_no']; ?>
                    </div>
                    <div class="bill-item-details">
                        <?php echo strtoupper($bill['table_group'] ?? 'DEFAULT'); ?> | ₹<?php echo number_format($bill['total'], 2); ?>
                        <br><?php echo $bill['item_count']; ?> items | <?php echo date('h:i A', strtotime($bill['created_at'] ?? $bill['date'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Panel - Bill Details -->
        <div class="right-panel">
            <?php if ($selected_bill): ?>
            
            <!-- Bill Header Details -->
            <div class="bill-details-header">
                <div class="detail-group">
                    <div class="detail-row">
                        <span class="detail-label">Bill No:</span>
                        <span class="detail-value"><?php echo $selected_bill['id']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Table:</span>
                        <span class="detail-value"><?php echo $selected_bill['table_no']; ?></span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <div class="detail-row">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value"><?php echo date('d/m/Y', strtotime($selected_bill['date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Time:</span>
                        <span class="detail-value"><?php echo date('h:i A', strtotime($selected_bill['created_at'] ?? $selected_bill['date'])); ?></span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <div class="detail-row">
                        <span class="detail-label">Group:</span>
                        <span class="detail-value"><?php echo strtoupper($selected_bill['table_group'] ?? 'DEFAULT'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><?php echo strtoupper($selected_bill['status']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Reprint Bill Button Area -->
            <div class="reprint-section">
                <button onclick="reprintBill(<?php echo $selected_bill['id']; ?>)" class="reprint-btn" title="Reprint this bill">
                    🖨️ REPRINT BILL
                </button>
            </div>

            <!-- Items Table -->
            <div class="items-section">
                <div class="items-table">
                    <div class="table-header">
                        <div>Code</div>
                        <div>Item Name</div>
                        <div>Description</div>
                        <div>Qty</div>
                        <div>Rate</div>
                        <div>Amount</div>
                        <div>KOT</div>
                    </div>
                    <div class="table-body">
                        <?php if (!empty($bill_items)): ?>
                        <?php foreach ($bill_items as $item): ?>
                        <div class="table-row">
                            <div class="code"><?php echo htmlspecialchars($item['item_code']); ?></div>
                            <div><?php echo htmlspecialchars($item['item_name']); ?></div>
                            <div><?php echo htmlspecialchars($item['item_name']); ?></div>
                            <div class="qty"><?php echo $item['qty']; ?></div>
                            <div class="amount"><?php echo number_format($item['rate'], 2); ?></div>
                            <div class="amount"><?php echo number_format($item['amount'], 2); ?></div>
                            <div class="qty">+1</div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div style="padding: 15px; text-align: center; color: #666; font-size: 10px;">
                            No items found for this bill
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="no-selection">
                <?php if (empty($bills)): ?>
                No bills found for today
                <?php else: ?>
                Select a bill from the left panel to view details
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Summary Panel -->
        <div class="summary-panel">
            <div class="summary-group">
                <div class="summary-title">BILL SUMMARY</div>
                <?php if ($selected_bill): ?>
                <div class="summary-row">
                    <span>Sub Total:</span>
                    <span>₹<?php echo number_format($selected_bill['subtotal'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (5%):</span>
                    <span>₹<?php echo number_format($selected_bill['tax'], 2); ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>TOTAL:</span>
                    <span>₹<?php echo number_format($selected_bill['total'], 2); ?></span>
                </div>
                <?php else: ?>
                <div style="text-align: center; color: #666; font-style: italic; font-size: 8px;">
                    Select a bill to view summary
                </div>
                <?php endif; ?>
            </div>

            <div class="summary-group">
                <div class="summary-title">TODAY'S SUMMARY</div>
                <?php if (!empty($group_totals)): ?>
                <?php foreach ($group_totals as $group => $data): ?>
                <div class="summary-row">
                    <span><?php echo $group; ?>:</span>
                    <span>₹<?php echo number_format($data['total'], 2); ?></span>
                </div>
                <div class="summary-row" style="font-size: 7px; color: #666;">
                    <span></span>
                    <span>(<?php echo $data['count']; ?> bills)</span>
                </div>
                <?php endforeach; ?>
                <div class="summary-row summary-total">
                    <span>TOTAL SALES:</span>
                    <span>₹<?php echo number_format($total_sales, 2); ?></span>
                </div>
                <?php else: ?>
                <div style="text-align: center; color: #666; font-style: italic; font-size: 8px;">
                    No sales data for today
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="status-bar">
        <span>Ready | Total Bills: <?php echo $total_bills; ?> | Use ↑↓ arrows to navigate</span>
        <span>Total Sales: ₹<?php echo number_format($total_sales, 2); ?></span>
        <span><?php echo date('d/m/Y h:i A'); ?></span>
    </div>
</div>

<script>
let currentBillIndex = <?php echo $selected_index; ?>;
let totalBills = <?php echo $total_bills; ?>;
let billIds = [<?php echo implode(',', array_column($bills, 'id')); ?>];

$(document).ready(function() {
    // Arrow key navigation
    $(document).keydown(function(e) {
        if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            navigateBills(-1);
        } else if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            navigateBills(1);
        } else if (e.keyCode === 13) { // Enter key
            e.preventDefault();
            if (currentBillIndex >= 0 && currentBillIndex < totalBills) {
                selectBill(billIds[currentBillIndex]);
            }
        } else if (e.keyCode === 27) { // ESC key
            goBack();
        } else if (e.keyCode === 34) { // Page Down key
            goBack();
        } else if (e.keyCode === 116) { // F5 key
            e.preventDefault();
            refreshData();
        } else if (e.keyCode === 80 && e.ctrlKey) { // Ctrl+P
            e.preventDefault();
            if (currentBillIndex >= 0 && currentBillIndex < totalBills) {
                reprintBill(billIds[currentBillIndex]);
            }
        }
    });
    
    scrollToSelectedBill();
});

function navigateBills(direction) {
    if (totalBills === 0) return;
    
    let newIndex = currentBillIndex + direction;
    
    if (newIndex < 0) {
        newIndex = totalBills - 1;
    } else if (newIndex >= totalBills) {
        newIndex = 0;
    }
    
    currentBillIndex = newIndex;
    
    $('.bill-item').removeClass('selected');
    $(`.bill-item[data-index="${currentBillIndex}"]`).addClass('selected');
    
    if (billIds[currentBillIndex]) {
        selectBill(billIds[currentBillIndex]);
    }
    
    scrollToSelectedBill();
}

function scrollToSelectedBill() {
    const selectedBill = $(`.bill-item[data-index="${currentBillIndex}"]`);
    if (selectedBill.length) {
        const billsList = $('#billsList');
        const scrollTop = billsList.scrollTop();
        const billTop = selectedBill.position().top;
        const billHeight = selectedBill.outerHeight();
        const listHeight = billsList.height();
        
        if (billTop < 0) {
            billsList.scrollTop(scrollTop + billTop);
        } else if (billTop + billHeight > listHeight) {
            billsList.scrollTop(scrollTop + billTop + billHeight - listHeight);
        }
    }
}

function selectBill(billId) {
    window.location.href = 'oldbill.php?bill_id=' + billId;
}

function goBack() {
    window.location.href = 'billing.php';
}

function refreshData() {
    window.location.reload();
}

function reprintBill(billId) {
    // Navigate to print URL in the same tab
    window.location.href = 'print_bill.php?bill_no=' + billId;
}


setTimeout(function() {
    refreshData();
}, 300000);
</script>

</body>
</html>
