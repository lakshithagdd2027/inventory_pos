<?php
session_start();
require_once 'db.php';

// SECURITY: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize shopping cart in session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $qty = (int)$_POST['quantity'];

    $stmt = $pdo->prepare("SELECT * FROM Products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product && $product['stock_quantity'] >= $qty) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'qty' => $qty
            ];
        }
    } else {
        $message = "Error: Not enough stock available!";
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
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += ($item['price'] * $item['qty']);
    }

    try {
        // 1. Record the sale
        $stmt = $pdo->prepare("INSERT INTO Sales (cashier_name, total_amount) VALUES (:name, :total)");
        $stmt->execute(['name' => $_SESSION['username'], 'total' => $total_amount]);

        // 2. Reduce stock in Products table
        foreach ($_SESSION['cart'] as $id => $item) {
            $stmt = $pdo->prepare("UPDATE Products SET stock_quantity = stock_quantity - :qty WHERE id = :id");
            $stmt->execute(['qty' => $item['qty'], 'id' => $id]);
        }

        // 3. Empty the cart
        $_SESSION['cart'] = [];
        $message = "Checkout Successful! Total Paid: Rs " . number_format($total_amount, 2);
    } catch(PDOException $e) {
        $message = "Error processing checkout.";
    }
}

// Fetch available products
$products = $pdo->query("SELECT * FROM Products WHERE stock_quantity > 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-container">
    <aside class="sidebar">
        <h2>SmartPOS</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="pos.php" style="color: #3498db;">Point of Sale</a></li>
            
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <li><a href="inventory.php">Manage Inventory</a></li>
                <li><a href="users.php">Manage Users</a></li>
            <?php endif; ?>

            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <h1>Point of Sale (Cashier)</h1>
        </header>

        <?php if($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="pos-layout">
            <!-- Left Side: Products -->
            <section class="welcome-card pos-products">
                <h3>Select Product</h3>
                <form method="POST" action="" class="form-inline">
                    <select name="product_id" required style="padding: 10px; border-radius: 4px; width: 50%;">
                        <option value="">-- Choose Item --</option>
                        <?php foreach($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> (Rs <?php echo $p['price']; ?>) - Stock: <?php echo $p['stock_quantity']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantity" value="1" min="1" required style="width: 80px;">
                    <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                </form>
            </section>

            <!-- Right Side: Cart -->
            <section class="pos-cart">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Current Bill</h3>
                    <a href="pos.php?clear=true" class="btn-delete" style="font-size: 12px;">Clear Cart</a>
                </div>
                
                <table class="cart-table">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
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
                
                <form method="POST" action="">
                    <button type="submit" name="checkout" class="btn btn-checkout">Complete Checkout</button>
                </form>
            </section>
        </div>
    </main>
</div>

</body>
</html>