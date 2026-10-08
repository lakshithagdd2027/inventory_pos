<?php
session_start();
require_once 'db.php';

// SECURITY: Kick out anyone who is not logged in OR is not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Products WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        // Refresh the page to remove the ?delete=id from the URL
        header("Location: inventory.php");
        exit();
    } catch(PDOException $e) {
        $message = "Error: Cannot delete this product.";
    }
}


// Handle form submission to add a new product
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']);
    $price = trim($_POST['price']);
    $stock = trim($_POST['stock_quantity']);

    try {
        $stmt = $pdo->prepare("INSERT INTO Products (name, sku, price, stock_quantity) VALUES (:name, :sku, :price, :stock)");
        $stmt->execute(['name' => $name, 'sku' => $sku, 'price' => $price, 'stock' => $stock]);
        $message = "Product successfully added!";
    } catch(PDOException $e) {
        $message = "Error: SKU/Barcode might already exist.";
    }
}

// Fetch all existing products from the database to display in the table
$stmt = $pdo->query("SELECT * FROM Products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-container">
    <aside class="sidebar">
        <h2>SmartPOS</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="pos.php">Point of Sale</a></li>
            <li><a href="inventory.php" style="color: #3498db;">Manage Inventory</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <h1>Inventory Management</h1>
        </header>

        <?php if($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Product Form -->
        <section class="welcome-card">
            <h3>Add New Product</h3>
            <form method="POST" action="" class="form-inline">
                <input type="text" name="name" placeholder="Product Name" required>
                <input type="text" name="sku" placeholder="SKU / Barcode" required>
                <input type="number" step="0.01" name="price" placeholder="Price" required>
                <input type="number" name="stock_quantity" placeholder="Stock Qty" required>
                <button type="submit" name="add_product" class="btn">Add Product</button>
            </form>
        </section>

        <!-- Current Stock Table -->
        <section class="welcome-card" style="margin-top: 20px;">
            <h3>Current Stock</h3>
            <table class="inventory-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>SKU / Barcode</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Action</th>
                </tr>
                <?php foreach($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                    <td>Rs <?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['stock_quantity']; ?></td>
                    <td>
                    <!-- New Delete Button -->
                    <a href="inventory.php?delete=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($product['name']); ?>?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>
    </main>
</div>

</body>
</html>