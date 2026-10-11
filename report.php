<?php
session_start();
require_once 'db.php';

// SECURITY: Only Admins can view this report
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Complex SQL query to combine Products and Sales data
$query = "
    SELECT 
        p.name as product_name, 
        p.price, 
        p.stock_quantity as remaining_stock, 
        COALESCE(SUM(si.qty), 0) as total_sold, 
        COALESCE(SUM(si.qty * si.price), 0) as total_revenue
    FROM Products p
    LEFT JOIN Sales_Items si ON p.name = si.product_name
    GROUP BY p.id, p.name, p.price, p.stock_quantity
    ORDER BY total_sold DESC
";
$report_data = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales & Inventory Report</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .report-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .report-table th, .report-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .report-table th { background-color: #2c3e50; color: white; }
        
        /* MAGIC CSS: This hides the sidebar and buttons when saving as PDF */
        @media print {
            .sidebar, .btn-print, .top-header p { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .dashboard-container { display: block; }
            body { background-color: white; }
        }
    </style>
</head>
<body class="dashboard-body">

<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <h2>SmartPOS</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="pos.php">Point of Sale</a></li>
            <li><a href="inventory.php">Manage Inventory</a></li>
            <li><a href="users.php">Manage Users</a></li>
            <li><a href="report.php" style="color: #3498db;">Sales Report</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <header class="top-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Official Sales & Inventory Report</h1>
                <p>Generated on: <?php echo date('Y-m-d h:i A'); ?></p>
            </div>
            <!-- This button triggers the browser's PDF engine -->
            <button onclick="window.print()" class="btn btn-print" style="background: #e74c3c; padding: 10px 20px;">Download PDF / Print</button>
        </header>
        
        <section class="content">
            <div class="welcome-card" style="background: white;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Unit Price</th>
                            <th>Total Sold (Qty)</th>
                            <th>Total Sales (Revenue)</th>
                            <th>Remaining Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_revenue = 0;
                        foreach($report_data as $row): 
                            $grand_revenue += $row['total_revenue'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td>Rs <?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo $row['total_sold']; ?> items</td>
                            <td>Rs <?php echo number_format($row['total_revenue'], 2); ?></td>
                            <td style="font-weight: bold; color: <?php echo $row['remaining_stock'] < 10 ? '#e74c3c' : '#2ecc71'; ?>;">
                                <?php echo $row['remaining_stock']; ?> units
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" style="text-align: right; background-color: #ecf0f1; color: #2c3e50;">Total Business Revenue:</th>
                            <th colspan="2" style="background-color: #ecf0f1; color: #27ae60; font-size: 18px;">Rs <?php echo number_format($grand_revenue, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>