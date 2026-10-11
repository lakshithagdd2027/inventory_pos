<?php
session_start();

// SECURITY: Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Connect to the database to fetch analytics
require_once 'db.php';

// Fetch Today's Total Sales
$today_sales = $pdo->query("SELECT SUM(total_amount) as total FROM Sales WHERE DATE(sale_date) = CURDATE()")->fetch();
$total_sales_amount = $today_sales['total'] ? $today_sales['total'] : "0.00";

// Fetch Low Stock Products (Stock less than 10)
$low_stock = $pdo->query("SELECT name, stock_quantity as stock FROM Products WHERE stock_quantity < 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <h2>SmartPOS</h2>
        <ul>
            <li><a href="dashboard.php" style="color: #3498db;">Dashboard</a></li>
            <li><a href="pos.php">Point of Sale</a></li>
            <li><a href="report.php">Sales Report</a></li>
            <!-- These links only appear for Admins -->
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <li><a href="inventory.php">Manage Inventory</a></li>
                <li><a href="users.php">Manage Users</a></li>
            <?php endif; ?>

            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <header class="top-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>Access Level: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></p>
        </header>
        
        <section class="content">
            <!-- Analytics Cards -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                
                <!-- Today's Sales Card -->
                <div class="welcome-card" style="flex: 1; min-width: 250px; border-left: 5px solid #2ecc71;">
                    <h3>Today's Sales</h3>
                    <h2 style="font-size: 32px; color: #2ecc71; margin-top: 10px;">Rs <?php echo number_format((float)$total_sales_amount, 2); ?></h2>
                </div>
                
                <!-- Low Stock Alerts Card -->
                <div class="welcome-card" style="flex: 1; min-width: 250px; border-left: 5px solid #e74c3c;">
                    <h3>Low Stock Alerts</h3>
                    <?php if(count($low_stock) > 0): ?>
                        <ul style="color: #c0392b; font-weight: bold; padding-left: 20px; margin-top: 10px;">
                            <?php foreach($low_stock as $item): ?>
                                <li><?php echo htmlspecialchars($item['name']); ?> (Only <?php echo htmlspecialchars($item['stock']); ?> left!)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color: #27ae60; font-weight: bold; margin-top: 10px;">Inventory is fully stocked.</p>
                    <?php endif; ?>
                </div>

            </div>

            <!-- System Overview Card -->
            <div class="welcome-card">
                <h3>System Overview</h3>
                <p>Use the sidebar menu to navigate the system.</p>
            </div>
        </section>
    </main>
</div>

</body>
</html>