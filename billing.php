<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';

// Handle loading existing table data
$current_bill = null;
$current_table = null;
$existing_items = [];
$table_info = null;
$table_group = 'DEFAULT';

if (isset($_GET['table'])) {
    $table_no = intval($_GET['table']);
    
    // Get table information from pos_tables
    $table_stmt = $conn->prepare("SELECT * FROM pos_tables WHERE table_no = ?");
    $table_stmt->bind_param("i", $table_no);
    $table_stmt->execute();
    $table_result = $table_stmt->get_result();
    
    if ($table_result && $table_result->num_rows > 0) {
        $table_info = $table_result->fetch_assoc();
    }
    
    // Determine table group by checking which range the table number falls into
    $group_stmt = $conn->query("SELECT name FROM table_groups WHERE $table_no >= start_number AND $table_no <= end_number LIMIT 1");
    if ($group_stmt && $group_stmt->num_rows > 0) {
        $group_data = $group_stmt->fetch_assoc();
        $table_group = strtoupper($group_data['name']);
    }
    
    // Check if table has pending bill
    $stmt = $conn->prepare("SELECT * FROM bills WHERE table_no = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $table_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $current_bill = $result->fetch_assoc();
        $current_table = $table_no;
        
        // Load existing items
        $stmt2 = $conn->prepare("SELECT * FROM order_items WHERE bill_id = ?");
        $stmt2->bind_param("i", $current_bill['id']);
        $stmt2->execute();
        $items_result = $stmt2->get_result();
        
        if ($items_result) {
            while ($item = $items_result->fetch_assoc()) {
                $existing_items[] = $item;
            }
        }
    } else {
        $current_table = $table_no;
    }
}

// Handle AJAX table group lookup
if (isset($_POST['get_table_group'])) {
    $table_no = intval($_POST['table_no']);
    $group_stmt = $conn->query("SELECT name FROM table_groups WHERE $table_no >= start_number AND $table_no <= end_number LIMIT 1");
    
    if ($group_stmt && $group_stmt->num_rows > 0) {
        $group_data = $group_stmt->fetch_assoc();
        echo json_encode(['found' => true, 'group' => strtoupper($group_data['name'])]);
    } else {
        echo json_encode(['found' => false, 'group' => 'DEFAULT']);
    }
    exit;
}

// Handle AJAX search by code
if (isset($_POST['search_code'])) {
    $code = $_POST['search_code'];
    $stmt = $conn->prepare("SELECT item_code, item_name, price FROM menu_cards WHERE item_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
        echo json_encode(['found' => true, 'item' => $item]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit;
}

// Handle AJAX search by name
if (isset($_POST['search_term'])) {
    $search_term = '%' . $_POST['search_term'] . '%';
    $stmt = $conn->prepare("SELECT item_code, item_name, price FROM menu_cards WHERE item_name LIKE ? OR item_code LIKE ? ORDER BY item_name ASC LIMIT 12");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    echo json_encode($items);
    exit;
}

// Handle KOT saving and printing
if (isset($_POST['action']) && $_POST['action'] == 'save_kot') {
    $table_no = intval($_POST['table_no']);
    $table_group = $_POST['table_group']; // Get table group from POST
    $items = $_POST['items'];
    $subtotal = floatval($_POST['subtotal']);
    $tax = floatval($_POST['tax']);
    $total = floatval($_POST['total']);
    $bill_id = isset($_POST['bill_id']) ? intval($_POST['bill_id']) : null;

    if ($bill_id) {
        // Update existing KOT (also update table_group in case it changed)
        $stmt = $conn->prepare("UPDATE bills SET table_group=?, subtotal=?, tax=?, total=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->bind_param("sdddi", $table_group, $subtotal, $tax, $total, $bill_id);
        
        if ($stmt->execute()) {
            // Delete old items and insert new ones
            $stmt2 = $conn->prepare("DELETE FROM order_items WHERE bill_id=?");
            $stmt2->bind_param("i", $bill_id);
            $stmt2->execute();
            
            $stmt3 = $conn->prepare("INSERT INTO order_items (bill_id, item_code, item_name, qty, rate, amount) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($items as $item) {
                $item_code = $item['code'];
                $item_name = $item['name'];
                $qty = intval($item['qty']);
                $rate = floatval($item['rate']);
                $amount = $qty * $rate;
                
                $stmt3->bind_param("issidd", $bill_id, $item_code, $item_name, $qty, $rate, $amount);
                $stmt3->execute();
            }
            echo json_encode(['status' => 'success', 'bill_id' => $bill_id, 'action' => 'updated', 'type' => 'kot']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else {
        // Create new KOT with table_group
        $stmt = $conn->prepare("INSERT INTO bills (table_no, table_group, date, subtotal, tax, total, status) VALUES (?, ?, CURDATE(), ?, ?, ?, 'pending')");
        $stmt->bind_param("isddd", $table_no, $table_group, $subtotal, $tax, $total);
        
        if ($stmt->execute()) {
            $bill_id = $conn->insert_id;
            
            // Insert order items
            $stmt2 = $conn->prepare("INSERT INTO order_items (bill_id, item_code, item_name, qty, rate, amount) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($items as $item) {
                $item_code = $item['code'];
                $item_name = $item['name'];
                $qty = intval($item['qty']);
                $rate = floatval($item['rate']);
                $amount = $qty * $rate;
                
                $stmt2->bind_param("issidd", $bill_id, $item_code, $item_name, $qty, $rate, $amount);
                $stmt2->execute();
            }
            
            // Update table status to running (if pos_tables table exists)
            $table_exists_check = $conn->query("SHOW TABLES LIKE 'pos_tables'");
            if ($table_exists_check && $table_exists_check->num_rows > 0) {
                // Check if record exists, then update or insert
                $check_table = $conn->prepare("SELECT table_no FROM pos_tables WHERE table_no = ?");
                $check_table->bind_param("i", $table_no);
                $check_table->execute();
                $table_result = $check_table->get_result();
                
                if ($table_result && $table_result->num_rows > 0) {
                    // Update existing record
                    $stmt3 = $conn->prepare("UPDATE pos_tables SET status='running' WHERE table_no=?");
                    $stmt3->bind_param("i", $table_no);
                    $stmt3->execute();
                } else {
                    // Insert new record
                    $stmt3 = $conn->prepare("INSERT INTO pos_tables (table_no, status) VALUES (?, 'running')");
                    $stmt3->bind_param("i", $table_no);
                    $stmt3->execute();
                }
            }
            
            echo json_encode(['status' => 'success', 'bill_id' => $bill_id, 'action' => 'created', 'type' => 'kot']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    }
    exit;
}

// Handle bill printing and settlement
if (isset($_POST['action']) && $_POST['action'] == 'print_bill') {
    $bill_id = intval($_POST['bill_id']);
    $table_no = intval($_POST['table_no']);
    
    // Mark bill as completed and free the table
    $stmt = $conn->prepare("UPDATE bills SET status='completed' WHERE id=?");
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    
    // Update table status to free
    $table_exists_check = $conn->query("SHOW TABLES LIKE 'pos_tables'");
    if ($table_exists_check && $table_exists_check->num_rows > 0) {
        $stmt2 = $conn->prepare("UPDATE pos_tables SET status='free' WHERE table_no=?");
        $stmt2->bind_param("i", $table_no);
        $stmt2->execute();
    }
    
    echo json_encode(['status' => 'success']);
    exit;
}

// Get running tables with table groups
$running_tables = [];
$stmt = $conn->prepare("SELECT b.*, COUNT(oi.id) as item_count FROM bills b LEFT JOIN order_items oi ON b.id = oi.bill_id WHERE b.status = 'pending' GROUP BY b.id ORDER BY b.table_no");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $running_tables[] = $row;
    }
}

// Get next KOT number
$next_kot = 1;
if ($current_bill) {
    $next_kot = $current_bill['id'];
} else {
    $result = $conn->query("SELECT MAX(id) as max_id FROM bills");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $next_kot = ($row['max_id'] ?? 0) + 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KOT Billing System</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="billing.css">
</head>
<body>
<div class="billing-container">
    <div class="header">
        <h1>🍽️ HOTEL NISARGA - KOT BILLING</h1>
        <a href="logout.php" class="logout-btn">👤 Logout</a>
    </div>

    <div class="main-content">
        <div class="left-panel">
            <?php
            $tables_exist = true;
            $missing_tables = [];
            
            // Check for required tables
            $required_tables = ['bills', 'order_items', 'menu_cards'];
            foreach($required_tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if (!$result || $result->num_rows == 0) {
                    $tables_exist = false;
                    $missing_tables[] = $table;
                }
            }
            
            if (!$tables_exist):
            ?>
            
            <div style="background: #f8d7da; border: 2px solid #dc3545; color: #721c24; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3>⚠️ Database Setup Required</h3>
                <p><strong>Missing tables:</strong> <?php echo implode(', ', $missing_tables); ?></p>
                <p>Please run the SQL script to create the required tables.</p>
            </div>
            
            <?php else: ?>

            <!-- Show current table info if editing -->
            <?php if ($current_table): ?>
            <div class="current-table-info">
                🏃‍♂️ TABLE: <?php echo $current_table; ?> 
                | Group: <?php echo $table_group; ?>
                <?php if ($current_bill): ?>
                | KOT: <?php echo $current_bill['id']; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Table Entry Section -->
            <div class="info-section">
                <h3 style="color: #FF6B35; margin-bottom: 20px;">📋 Table Entry</h3>
                <div class="form-group">
                    <label>Table No:</label>
                    <input type="number" id="tableNo" placeholder="Enter table number" 
                           value="<?php echo $current_table ?? ''; ?>" min="1" max="200" required>
                </div>
                <div class="table-group-info" id="tableGroupDisplay" style="display: <?php echo $current_table ? 'block' : 'none'; ?>;">
                    Table Group: <strong id="currentTableGroup"><?php echo $table_group; ?></strong>
                </div>
            </div>

            <!-- Item Entry Section (Hidden initially) -->
            <div class="search-section" id="itemSection" style="display: <?php echo $current_table ? 'block' : 'none'; ?>;">
                <h3 style="color: #FF6B35; margin-bottom: 15px;">🔍 Item Entry (Press + for KOT, * for Bill, PageUp for Old Bills, F8 for Settle)</h3>
                <div style="position: relative;">
                    <input type="text" class="search-box" id="itemSearch" 
                           placeholder="Enter item code or search by name..." autocomplete="off">
                    <ul class="search-results" id="searchResults"></ul>
                </div>
            </div>

            <!-- Order Items Table (Hidden initially) -->
            <div class="items-table" id="itemsTable" style="display: <?php echo $current_table ? 'block' : 'none'; ?>;">
                <table class="table" id="orderTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Rate (₹)</th>
                            <th>Amount (₹)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="orderItems">
                        <?php if (empty($existing_items)): ?>
                        <tr class="empty-state">
                            <td colspan="6">No items added yet. Enter item code or search by name.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; ?>
        </div>

        <div class="right-panel">
            <!-- Order Status -->
            <div class="status-info">
                📅 <strong>Date:</strong> <?php echo date('d/m/Y'); ?><br>
                ⏰ <strong>Time:</strong> <span id="currentTime"></span><br>
                👤 <strong>User:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?><br>
                🎯 <strong>KOT No:</strong> <span id="kotNumber"><?php echo $next_kot; ?></span>
            </div>

            <?php if ($tables_exist): ?>
            
            <!-- Running Tables Section -->
            <div class="running-tables-section">
                <div class="running-tables-header">
                    🍽️ RUNNING TABLES
                </div>
                <div id="runningTablesList">
                    <?php if (empty($running_tables)): ?>
                    <div class="no-running-tables">
                        No running tables at the moment
                    </div>
                    <?php else: ?>
                    <?php foreach ($running_tables as $table): ?>
                    <div class="running-table-item <?php echo ($current_table == $table['table_no']) ? 'active' : ''; ?>" 
                         onclick="loadTable(<?php echo $table['table_no']; ?>)">
                        <div class="table-number">Table <?php echo $table['table_no']; ?></div>
                        <div class="table-details">
                            <?php if (isset($table['table_group']) && $table['table_group']): ?>
                            <span class="group-badge"><?php echo strtoupper($table['table_group']); ?></span>
                            <?php endif; ?>
                            KOT: <?php echo $table['id']; ?> | Items: <?php echo $table['item_count']; ?> | ₹<?php echo number_format($table['total'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Summary (Hidden initially) -->
            <div class="summary-section" id="summarySection" style="display: <?php echo $current_table ? 'block' : 'none'; ?>;">
                <h3 style="color: #FF6B35; margin-bottom: 15px;">💰 Bill Summary</h3>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₹ <span id="subtotal">0.00</span></span>
                </div>
                <div class="summary-row">
                    <span>Tax (5%):</span>
                    <span>₹ <span id="taxAmount">0.00</span></span>
                </div>
                <div class="summary-row">
                    <span>Total Amount:</span>
                    <span>₹ <span id="totalAmount">0.00</span></span>
                </div>
            </div>

            <!-- Shortcuts Info -->
            <div class="shortcuts-info">
                <h4>⌨️ Keyboard Shortcuts</h4>
                <div><strong>+</strong> - Print KOT</div>
                <div><strong>*</strong> - Print Bill</div>
                <div><strong>F8</strong> - Settle Bill</div>
                <div><strong>PageUp</strong> - Old Bills</div>
                <div><strong>Tab</strong> - Next Field</div>
                <div><strong>Enter</strong> - Add Item/Confirm</div>
                
                            </div>
                            <!-- Inside .right-panel near keyboard shortcuts -->
<div class="goto1st-btn-container" style="margin-top: 10px; text-align: center;">
  <button id="goto1stBtn" style="padding: 8px 15px; background: #FF6B35; color: white; font-weight: bold; border: none; border-radius: 4px; cursor: pointer;">
    Go to 1st.php
  </button>
</div>


            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quantity Input Modal -->
<div id="quantityModal" class="quantity-modal" style="display: none;">
    <div class="quantity-modal-content">
        <h3>Enter Quantity</h3>
        <div id="itemInfo"></div>
        <input type="number" id="quantityInput" min="1" value="1" placeholder="Enter quantity">
        <div class="modal-buttons">
            <button class="btn btn-primary" onclick="addItemWithQuantity()">Add Item</button>
            <button class="btn btn-secondary" onclick="cancelQuantity()">Cancel</button>
        </div>
    </div>
</div>

<!-- Bill Modal -->
<div id="billModal" class="bill-modal" style="display: none;">
    <div class="bill-modal-content">
        <span class="close-modal">&times;</span>
        <div class="bill-header">
            <h2>🍽️ HOTEL NISARGA</h2>
            <p>Final Bill</p>
        </div>
        <div id="billContent"></div>
        <div style="text-align: center; margin-top: 20px;">
            <button class="btn btn-primary" onclick="printAndCloseBill()">Print & Complete (Enter)</button>
            <button class="btn btn-secondary" onclick="closeBillModal()">Cancel</button>
        </div>
    </div>
</div>

<?php if ($tables_exist): ?>
<script>
let currentItemForQuantity = null;
let kotPrinted = false;
let currentTableGroup = '<?php echo $table_group; ?>'; // Store current table group

$(document).ready(function() {
    // Load existing items if any
    <?php if (!empty($existing_items)): ?>
    <?php foreach ($existing_items as $item): ?>
    addItemToTable('<?php echo addslashes($item['item_code']); ?>', '<?php echo addslashes($item['item_name']); ?>', 
                   <?php echo $item['rate']; ?>, <?php echo $item['qty']; ?>);
    <?php endforeach; ?>
    calculateTotals();
    $('#itemSearch').focus();
    <?php else: ?>
    <?php if ($current_table): ?>
    $('#itemSearch').focus();
    <?php else: ?>
    $('#tableNo').focus();
    <?php endif; ?>
    <?php endif; ?>

    // Update time
    function updateTime() {
        const now = new Date();
        $('#currentTime').text(now.toLocaleTimeString());
    }
    updateTime();
    setInterval(updateTime, 1000);

    // Table number entry and validation
    $('#tableNo').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            const tableNo = parseInt($(this).val());
            if (tableNo && tableNo > 0) {
                fetchTableGroup(tableNo);
            }
        }
    });

    // Also check table group when table number changes
    $('#tableNo').on('blur', function() {
        const tableNo = parseInt($(this).val());
        if (tableNo && tableNo > 0) {
            fetchTableGroup(tableNo);
        }
    });

    // Item search with code detection
    $('#itemSearch').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            const query = $(this).val().trim();
            if (!isNaN(query) && query !== '') {
                // It's a code
                searchByCode(query);
            } else if (query.length > 0) {
                // Search by name if results are showing
                const firstResult = $('#searchResults li:first');
                if (firstResult.length > 0) {
                    selectSearchResult(firstResult);
                }
            }
        } else if (e.which === 43) { // + key
            e.preventDefault();
            printKOT();
        } else if (e.which === 42) { // * key
            e.preventDefault();
            showBillModal();
        }
    });

    // Global keyboard shortcuts
    $(document).keypress(function(e) {
        if (e.which === 43 && !$(e.target).is('input')) { // + key
            e.preventDefault();
            printKOT();
        } else if (e.which === 42 && !$(e.target).is('input')) { // * key
            e.preventDefault();
            showBillModal();
        }
    });

    // Quantity modal Enter key
    $('#quantityInput').on('keypress', function(e) {
        if (e.which === 13) {
            addItemWithQuantity();
        }
    });

    // Bill modal Enter key
    $(document).on('keypress', function(e) {
        if ($('#billModal').is(':visible') && e.which === 13) {
            printAndCloseBill();
        }
    });

    // Search functionality with debounce for name search
    let searchTimeout;
    $('#itemSearch').on('input', function() {
        const query = $(this).val().trim();
        clearTimeout(searchTimeout);
        
        // Don't search if it's just a number (code)
        if (!isNaN(query) || query.length < 2) {
            $('#searchResults').hide();
            return;
        }

        searchTimeout = setTimeout(function() {
            searchByName(query);
        }, 300);
    });

    // Add item from search results
    $('#searchResults').on('click', 'li', function() {
        selectSearchResult($(this));
    });

    // Hide search results when clicking elsewhere
    $(document).click(function(e) {
        if (!$(e.target).closest('#itemSearch, #searchResults').length) {
            $('#searchResults').hide();
        }
    });
});

function fetchTableGroup(tableNo) {
    $.post('', {get_table_group: true, table_no: tableNo}, function(data) {
        try {
            const result = JSON.parse(data);
            currentTableGroup = result.group;
            $('#currentTableGroup').text(result.group);
            $('#tableGroupDisplay').show();
            loadTableInfo(tableNo);
        } catch(e) {
            console.error('Error fetching table group:', e);
            currentTableGroup = 'DEFAULT';
            $('#currentTableGroup').text('DEFAULT');
            $('#tableGroupDisplay').show();
            loadTableInfo(tableNo);
        }
    });
}

function loadTableInfo(tableNo) {
    showItemSection();
    $('#itemSearch').focus();
}

function showItemSection() {
    $('#itemSection').slideDown();
    $('#itemsTable').slideDown();
    $('#summarySection').slideDown();
}

function searchByCode(code) {
    $.post('', {search_code: code}, function(data) {
        try {
            const result = JSON.parse(data);
            if (result.found) {
                showQuantityModal(result.item);
            } else {
                alert('Item code not found: ' + code);
                $('#itemSearch').val('').focus();
            }
        } catch(e) {
            console.error('Error parsing code search result:', e);
        }
    });
}

function searchByName(query) {
    $.post('', {search_term: query}, function(data) {
        try {
            const results = JSON.parse(data);
            let html = '';
            results.forEach(function(item) {
                html += `<li data-code="${item.item_code}" data-name="${item.item_name}" data-price="${item.price}">
                    <strong>${item.item_code}</strong> - ${item.item_name} 
                    <span style="float: right; color: #28a745; font-weight: bold;">₹${parseFloat(item.price).toFixed(2)}</span>
                </li>`;
            });
            $('#searchResults').html(html).show();
        } catch(e) {
            console.error('Error parsing search results:', e);
        }
    });
}

function selectSearchResult(element) {
    const code = element.data('code');
    const name = element.data('name');
    const price = parseFloat(element.data('price'));
    
    showQuantityModal({item_code: code, item_name: name, price: price});
    $('#searchResults').hide();
}

function showQuantityModal(item) {
    currentItemForQuantity = item;
    $('#itemInfo').html(`<strong>${item.item_code}</strong> - ${item.item_name} (₹${parseFloat(item.price).toFixed(2)})`);
    $('#quantityInput').val(1);
    $('#quantityModal').show();
    $('#quantityInput').focus().select();
}

function addItemWithQuantity() {
    const qty = parseInt($('#quantityInput').val());
    if (qty > 0 && currentItemForQuantity) {
        addItemToTable(
            currentItemForQuantity.item_code,
            currentItemForQuantity.item_name,
            currentItemForQuantity.price,
            qty
        );
        calculateTotals();
        cancelQuantity();
        $('#itemSearch').val('').focus();
    }
}

function cancelQuantity() {
    $('#quantityModal').hide();
    currentItemForQuantity = null;
    $('#itemSearch').focus();
}

function printKOT() {
    const tableNo = parseInt($('#tableNo').val());

    if (!tableNo || tableNo < 1) {
        alert('Please enter a valid table number.');
        $('#tableNo').focus();
        return;
    }

    const items = [];
    $('#orderItems tr[data-code]').each(function() {
        const code = $(this).data('code');
        const name = $(this).find('td:eq(1)').text();
        const qty = $(this).find('.qty-input').val();
        const rate = $(this).data('price');
        items.push({code: code, name: name, qty: qty, rate: rate});
    });

    if (items.length === 0) {
        alert('Please add some items to the order.');
        $('#itemSearch').focus();
        return;
    }

    const subtotal = parseFloat($('#subtotal').text());
    const tax = parseFloat($('#taxAmount').text());
    const total = parseFloat($('#totalAmount').text());

    const data = {
        action: 'save_kot',
        table_no: tableNo,
        table_group: currentTableGroup,
        items: items,
        subtotal: subtotal,
        tax: tax,
        total: total
    };

    <?php if ($current_bill): ?>
    data.bill_id = <?php echo $current_bill['id']; ?>;
    <?php endif; ?>

    $.post('', data, function(response) {
        try {
            const result = JSON.parse(response);
            if (result.status === 'success') {
                window.open('kot.php?bill_id=' + result.bill_id, '_blank', 'width=400,height=600');
                
                kotPrinted = true;
                alert('KOT printed successfully!\\nTable: ' + tableNo + ' (' + currentTableGroup + ')\\nKOT ID: ' + result.bill_id);
                
                resetForNewTable();
            } else {
                alert('❌ Error: ' + result.message);
            }
        } catch(e) {
            alert('❌ Error processing KOT');
        }
    });
}

function showBillModal() {
    <?php if ($current_bill): ?>
    const billData = {
        id: <?php echo $current_bill['id']; ?>,
        table_no: <?php echo $current_table; ?>,
        table_group: '<?php echo isset($current_bill['table_group']) ? $current_bill['table_group'] : $table_group; ?>',
        date: '<?php echo $current_bill['date']; ?>'
    };
    
    const items = [];
    $('#orderItems tr[data-code]').each(function() {
        const code = $(this).data('code');
        const name = $(this).find('td:eq(1)').text();
        const qty = $(this).find('.qty-input').val();
        const rate = $(this).data('price');
        items.push({code: code, name: name, qty: qty, rate: rate});
    });
    
    const subtotal = parseFloat($('#subtotal').text());
    const tax = parseFloat($('#taxAmount').text());
    const total = parseFloat($('#totalAmount').text());
    
    displayBillModal(billData, items, subtotal, tax, total);
    <?php else: ?>
    alert('Please save KOT first before printing bill.');
    <?php endif; ?>
}

function displayBillModal(billData, items, subtotal, tax, total) {
    let billContent = `
        <div class="bill-details">
            <p><strong>Bill ID:</strong> ${billData.id}</p>
            <p><strong>Table:</strong> ${billData.table_no} (${billData.table_group})</p>
            <p><strong>Date:</strong> ${billData.date}</p>
        </div>
        
        <table class="bill-items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    items.forEach(function(item) {
        billContent += `
            <tr>
                <td>${item.name}</td>
                <td>${item.qty}</td>
                <td>₹${parseFloat(item.rate).toFixed(2)}</td>
                <td>₹${(parseFloat(item.rate) * parseInt(item.qty)).toFixed(2)}</td>
            </tr>
        `;
    });
    
    billContent += `
            </tbody>
        </table>
        
        <div style="text-align: right;">
            <p><strong>Subtotal: ₹${subtotal.toFixed(2)}</strong></p>
            <p><strong>Tax (5%): ₹${tax.toFixed(2)}</strong></p>
            <div class="bill-total">Total: ₹${total.toFixed(2)}</div>
        </div>
    `;
    
    $('#billContent').html(billContent);
    $('#billModal').show();
}

function printAndCloseBill() {
    <?php if ($current_bill): ?>
    window.open('print_bill.php?bill_no=<?php echo $current_bill['id']; ?>', '_blank', 'width=400,height=600');
    
    $.post('', {
        action: 'print_bill',
        bill_id: <?php echo $current_bill['id']; ?>,
        table_no: <?php echo $current_table; ?>
    }, function(response) {
        try {
            const result = JSON.parse(response);
            if (result.status === 'success') {
                alert('✅ Bill printed and table freed!');
                closeBillModal();
                resetForNewTable();
                location.href = 'billing.php';
            } else {
                alert('❌ Error settling bill.');
            }
        } catch(e) {
            alert('❌ Error processing bill settlement');
        }
    });
    <?php endif; ?>
}

function closeBillModal() {
    $('#billModal').hide();
}

function resetForNewTable() {
    $('#orderItems').html('<tr class="empty-state"><td colspan="6">No items added yet. Enter item code or search by name.</td></tr>');
    $('#tableNo, #itemSearch').val('');
    $('#itemSection, #itemsTable, #summarySection, #tableGroupDisplay').hide();
    currentTableGroup = 'DEFAULT';
    calculateTotals();
    
    $('#tableNo').focus();
    $('.running-table-item').removeClass('active');
}

function loadTable(tableNo) {
    location.href = 'billing.php?table=' + tableNo;
}

function addItemToTable(code, name, price, qty) {
    $('.empty-state').remove();

    const existingRow = $(`#orderItems tr[data-code='${code}']`);
    if (existingRow.length) {
        const qtyInput = existingRow.find('.qty-input');
        const newQty = parseInt(qtyInput.val()) + parseInt(qty);
        qtyInput.val(newQty);
        updateRowAmount(existingRow, price);
    } else {
        const newRow = `
            <tr data-code="${code}" data-price="${price}">
                <td><strong>${code}</strong></td>
                <td>${name}</td>
                <td>
                    <div class="qty-controls">
                        <button class="qty-btn" onclick="changeQty('${code}', -1)">-</button>
                        <input type="number" class="qty-input" value="${qty}" min="1" 
                               onchange="updateAmount('${code}')">
                        <button class="qty-btn" onclick="changeQty('${code}', 1)">+</button>
                    </div>
                </td>
                <td class="amount">${parseFloat(price).toFixed(2)}</td>
                <td class="amount item-amount">${(parseFloat(price) * parseInt(qty)).toFixed(2)}</td>
                <td>
                    <button class="remove-btn" onclick="removeItem('${code}')">×</button>
                </td>
            </tr>
        `;
        $('#orderItems').append(newRow);
    }
}

function changeQty(code, change) {
    const row = $(`#orderItems tr[data-code='${code}']`);
    const qtyInput = row.find('.qty-input');
    const currentQty = parseInt(qtyInput.val());
    const newQty = Math.max(1, currentQty + change);
    
    qtyInput.val(newQty);
    updateAmount(code);
}

function updateAmount(code) {
    const row = $(`#orderItems tr[data-code='${code}']`);
    const qty = parseInt(row.find('.qty-input').val());
    const price = parseFloat(row.data('price'));
    const amount = qty * price;
    
    row.find('.item-amount').text(amount.toFixed(2));
    calculateTotals();
}

function updateRowAmount(row, price) {
    const qty = parseInt(row.find('.qty-input').val());
    const amount = qty * price;
    row.find('.item-amount').text(amount.toFixed(2));
    calculateTotals();
}

function removeItem(code) {
    if (confirm('Remove this item from order?')) {
        $(`#orderItems tr[data-code='${code}']`).remove();
        
        if ($('#orderItems tr[data-code]').length === 0) {
            $('#orderItems').html('<tr class="empty-state"><td colspan="6">No items added yet. Enter item code or search by name.</td></tr>');
        }
        
        calculateTotals();
    }
}

function calculateTotals() {
    let subtotal = 0;
    $('#orderItems tr[data-code]').each(function() {
        const amount = parseFloat($(this).find('.item-amount').text()) || 0;
        subtotal += amount;
    });

    const tax = subtotal * 0.05; // 5% tax
    const total = subtotal + tax;

    $('#subtotal').text(subtotal.toFixed(2));
    $('#taxAmount').text(tax.toFixed(2));
    $('#totalAmount').text(total.toFixed(2));
}
// NEW FUNCTION: Reset to enter new table state
function resetToNewTable() {
    // Clear items table
    $('#orderItems').html('<tr class="empty-state"><td colspan="6">No items added yet. Enter item code or search by name.</td></tr>');
    // Clear inputs
    $('#tableNo, #itemSearch').val('');
    // Hide item & summary sections
    $('#itemSection, #itemsTable, #summarySection, #tableGroupDisplay').hide();
    // Hide current table info header
    $('.current-table-info').hide();
    // Reset variables
    currentTableGroup = 'DEFAULT'; kotPrinted = false;
    calculateTotals();
    // Focus table input
    $('#tableNo').focus();
    // Remove active highlight
    $('.running-table-item').removeClass('active');
    // Reload page without ?table
    window.location.href = 'billing.php';
}

// Global keyboard handler for ESC and PageUp
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        e.preventDefault();
        // If quantity modal open → close it
        if ($('#quantityModal').is(':visible')) {
            cancelQuantity();
        }
        // Else if bill modal open → close it
        else if ($('#billModal').is(':visible')) {
            closeBillModal();
        }
        // Else → reset to new table
        else {
            resetToNewTable();
        }
    }
    else if (e.key === 'PageUp') {
        e.preventDefault();
        window.location.href = 'oldbill.php';
    }
});
$('#goto1stBtn').click(function() {
  window.location.href = '1st.php';
});



</script>

<?php endif; ?>

</body>
</html>