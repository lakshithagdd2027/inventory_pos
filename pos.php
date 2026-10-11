<?php
session_start();
require_once 'db.php';

// SECURITY: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';
$show_receipt = false;
$receipt_data = [];

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $qty = (int)$_POST['quantity'];
    $product_id = !empty($_POST['product_barcode']) ? $_POST['product_barcode'] : $_POST['product_id'];

    if ($product_id) {
        $stmt = $pdo->prepare("SELECT * FROM Products WHERE id = :id");
        $stmt->execute(['id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['stock_quantity'] >= $qty) {
            if (isset($_SESSION['cart'][$product['id']])) {
                $_SESSION['cart'][$product['id']]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$product['id']] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'qty' => $qty
                ];
            }
        } else {
            $message = "Error: Product not found or not enough stock!";
        }
    }
}

// Handle Clear Cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: pos.php");
    exit();
}

// Handle Checkout
if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    // 1. Calculate Grand Total First
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += ($item['price'] * $item['qty']);
    }

    // 2. Handle Payment Details
    $payment_method = $_POST['payment_method'];
    $tendered = ($payment_method === 'Cash') ? (float)$_POST['tendered_amount'] : $total_amount;
    
    if ($payment_method === 'Cash' && $tendered < $total_amount) {
        $message = "Error: Cash given is less than the total bill!";
    } else {
        $balance = $tendered - $total_amount;

        try {
            $pdo->beginTransaction();

            // 3. Create the Sale Record (With cashier_name as fixed earlier)
            $stmt = $pdo->prepare("INSERT INTO Sales (cashier_name, total_amount, payment_method, tendered_amount, balance) VALUES (:cashier_name, :total, :method, :tendered, :balance)");
            $stmt->execute([
                'cashier_name' => $_SESSION['username'],
                'total' => $total_amount,
                'method' => $payment_method,
                'tendered' => $tendered,
                'balance' => $balance
            ]);
            $sale_id = $pdo->lastInsertId();

            // 4. Process Items and Deduct Stock
            foreach ($_SESSION['cart'] as $id => $item) {
                $item_stmt = $pdo->prepare("INSERT INTO Sales_Items (sale_id, product_name, qty, price) VALUES (?, ?, ?, ?)");
                $item_stmt->execute([$sale_id, $item['name'], $item['qty'], $item['price']]);

                $stock_stmt = $pdo->prepare("UPDATE Products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stock_stmt->execute([$item['qty'], $id]);
            }

            $pdo->commit();

            // 5. Prepare Data for the Printed Slip
            $receipt_data = [
                'sale_id' => $sale_id,
                'total' => $total_amount,
                'method' => $payment_method,
                'tendered' => $tendered,
                'balance' => $balance,
                'items' => $_SESSION['cart'],
                'date' => date('Y-m-d h:i A'),
                'cashier' => $_SESSION['username']
            ];
            
            $_SESSION['cart'] = [];
            $show_receipt = true; // This triggers the popup
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $message = "Checkout failed: " . $e->getMessage();
        }
    }
}

$products = $pdo->query("SELECT * FROM Products WHERE stock_quantity > 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- CSS specifically for the receipt printer -->
    <style>
        @media print {
            body * { visibility: hidden; }
            #printable-slip, #printable-slip * { visibility: visible; }
            #printable-slip { position: absolute; left: 0; top: 0; width: 100%; max-width: 300px; padding: 0; margin: 0; border: none; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="dashboard-body">

<div class="dashboard-container">
    <aside class="sidebar">
        <h2>SmartPOS</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="pos.php" style="color: #3498db;">Point of Sale</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                <li><a href="inventory.php">Manage Inventory</a></li>
                <li><a href="users.php">Manage Users</a></li>
                <li><a href="report.php">Sales Report</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <h1>Point of Sale (Cashier)</h1>
        </header>

        <?php if($message): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="pos-layout">
            <!-- Left Side: Products -->
            <section class="welcome-card pos-products">
                <h3 style="margin-bottom: 15px;">Add Product to Cart</h3>
                <form method="POST" action="" style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label>1. Scan Barcode (Product ID)</label>
                        <input type="text" name="product_barcode" autofocus placeholder="Scan item here..." style="width: 100%; padding: 10px; border: 1px solid #ccc;">
                    </div>
                    <div>
                        <label>2. Select Manually</label>
                        <select name="product_id" style="width: 100%; padding: 10px; border: 1px solid #ccc;">
                            <option value="">-- Choose Item --</option>
                            <?php foreach($products as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name']); ?> (Rs <?php echo $p['price']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Quantity</label>
                        <input type="number" name="quantity" value="1" min="1" style="width: 100%; padding: 10px; border: 1px solid #ccc;">
                    </div>
                    <button type="submit" name="add_to_cart" class="btn" style="padding: 12px; font-size: 16px;">Add to Cart</button>
                </form>
            </section>

            <!-- Right Side: Cart & Payment -->
            <section class="pos-cart">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Current Bill</h3>
                    <a href="pos.php?clear=true" class="btn-delete" style="font-size: 12px;">Clear Cart</a>
                </div>
                
                <table class="cart-table">
                    <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                    <?php 
                    $grand_total = 0;
                    foreach($_SESSION['cart'] as $item): 
                        $item_total = $item['price'] * $item['qty'];
                        $grand_total += $item_total;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo $item['qty']; ?></td>
                        <td>Rs <?php echo number_format($item['price'], 2); ?></td>
                        <td>Rs <?php echo number_format($item_total, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <h2 style="text-align: right; margin-bottom: 15px;">Total: Rs <?php echo number_format($grand_total, 2); ?></h2>
                
                <!-- Payment Area -->
                <?php if($grand_total > 0): ?>
                <form method="POST" action="" style="background: #ecf0f1; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Payment Method:</label>
                    <select name="payment_method" id="payment_method" onchange="togglePayment()" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #bdc3c7;">
                        <option value="Cash">Cash</option>
                        <option value="Card">Credit/Debit Card</option>
                    </select>

                    <div id="cash_input_div">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Cash Given by Customer:</label>
                        <input type="number" name="tendered_amount" id="tendered_amount" step="0.01" style="width: 100%; padding: 10px; border: 1px solid #bdc3c7;" placeholder="e.g. 1000" required>
                    </div>

                    <button type="submit" name="checkout" class="btn btn-checkout" style="margin-top: 15px;">Complete Checkout</button>
                </form>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<!-- RECEIPT POPUP (Only shows after successful checkout) -->
<?php if ($show_receipt): ?>
<div class="receipt-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; justify-content: center; align-items: center; z-index: 1000;">
    <div id="printable-slip" style="background: white; padding: 20px; width: 320px; font-family: monospace; color: black; box-shadow: 0px 4px 15px rgba(0,0,0,0.2);">
        <h2 style="text-align: center; margin: 0; font-size: 24px;">SmartPOS</h2>
        <p style="text-align: center; margin: 10px 0; border-bottom: 1px dashed black; padding-bottom: 10px;">
            Receipt #: <?php echo $receipt_data['sale_id']; ?><br>
            Date: <?php echo $receipt_data['date']; ?><br>
            Cashier: <?php echo htmlspecialchars($receipt_data['cashier']); ?>
        </p>
        
        <table style="width: 100%; border-bottom: 1px dashed black; margin-bottom: 10px; font-size: 14px;">
            <tr>
                <th style="text-align: left;">Item</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Amount</th>
            </tr>
            <?php foreach($receipt_data['items'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td style="text-align: center;"><?php echo $item['qty']; ?></td>
                <td style="text-align: right;"><?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div style="font-size: 16px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px; margin-bottom: 5px;">
                <span>Total:</span>
                <span>Rs <?php echo number_format($receipt_data['total'], 2); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Paid (<?php echo $receipt_data['method']; ?>):</span>
                <span>Rs <?php echo number_format($receipt_data['tendered'], 2); ?></span>
            </div>
            <?php if($receipt_data['method'] === 'Cash'): ?>
            <div style="display: flex; justify-content: space-between;">
                <span>Balance/Change:</span>
                <span style="font-weight: bold;">Rs <?php echo number_format($receipt_data['balance'], 2); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <p style="text-align: center; margin-top: 15px; border-top: 1px dashed black; padding-top: 10px; font-size: 12px;">Thank you for shopping with us!</p>
        
        <!-- Buttons hidden during print -->
        <div class="no-print" style="margin-top: 20px; text-align: center; display: flex; gap: 10px;">
            <button onclick="window.print()" style="flex: 1; padding: 10px; background: #2ecc71; color: white; border: none; cursor: pointer; border-radius: 4px;">Print Slip</button>
            <a href="pos.php" style="flex: 1; padding: 10px; background: #e74c3c; color: white; text-decoration: none; border-radius: 4px;">Close / Next</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// This JavaScript hides the "Cash Given" input if the customer pays by Card
function togglePayment() {
    var method = document.getElementById('payment_method').value;
    var cashDiv = document.getElementById('cash_input_div');
    var tenderedInput = document.getElementById('tendered_amount');
    
    if(method === 'Card') {
        cashDiv.style.display = 'none';
        tenderedInput.removeAttribute('required');
    } else {
        cashDiv.style.display = 'block';
        tenderedInput.setAttribute('required', 'true');
    }
}
</script>
</body>
</html>