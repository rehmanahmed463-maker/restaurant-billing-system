if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bill_no'])) {
    $bill_no = intval($_POST['bill_no']);
    // Fetch bill details from DB
    $stmt = $conn->prepare("SELECT * FROM bills WHERE id=? AND status='pending'");
    $stmt->bind_param("i", $bill_no);
    $stmt->execute();
    $bill = $stmt->get_result()->fetch_assoc();

    if ($bill) {
        // Output HTML snippet for payment method selection
        ?>
        <div id="paymentSection">
            <h3>Settle Bill #<?= $bill_no ?></h3>
            <form id="paymentForm">
                <label>Payment Method:</label>
                <select name="payment_method" required>
                    <option value="">Select</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="credit">Credit</option>
                    <option value="swiggy">Swiggy</option>
                    <option value="zomato">Zomato</option>
                    <option value="upi">UPI</option>
                </select>
                <br>
                <label>Amount:</label>
                <input type="number" name="amount" value="<?= $bill['total'] ?>" readonly>
                <br>
                <button type="submit">Settle Bill</button>
            </form>
            <div id="paymentResponse"></div>
        </div>

        <script>
        $('#paymentForm').submit(function(e) {
            e.preventDefault();

            const data = $(this).serialize() + '&action=settle_bill&bill_id=<?= $bill_no ?>';

            $.post('settle.php', data, function(res) {
                if (res.status === 'success') {
                    $('#paymentResponse').html('<p>Bill settled successfully via ' + res.payment_method + '.</p>');
                    // Optionally refresh or hide settlement UI and reload billing page / running tables
                } else {
                    $('#paymentResponse').html('<p>Error: ' + res.message + '</p>');
                }
            }, 'json');
        });
        </script>
        <?php
    } else {
        echo "<p>No pending bill found or already settled.</p>";
    }

    exit; // Stop further output
}

// Handling the POST to actually settle the bill
if (isset($_POST['action']) && $_POST['action'] === 'settle_bill') {
    $bill_id = intval($_POST['bill_id']);
    $payment_method = $_POST['payment_method'] ?? '';

    if (!$payment_method) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a payment method.']);
        exit;
    }

    // Update bill as settled and save payment method
    $stmt = $conn->prepare("UPDATE bills SET status='completed', payment_method=? WHERE id=?");
    $stmt->bind_param("si", $payment_method, $bill_id);
    if ($stmt->execute()) {
        // Update table status to free
        $stmt2 = $conn->prepare("UPDATE pos_tables SET status='free' WHERE table_no=(SELECT table_no FROM bills WHERE id=?)");
        $stmt2->bind_param("i", $bill_id);
        $stmt2->execute();

        echo json_encode(['status' => 'success', 'payment_method' => $payment_method]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update bill.']);
    }
    exit;
}
