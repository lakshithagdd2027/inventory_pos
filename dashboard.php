<?php
session_start();

// SECURITY: Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
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
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="pos.php">Point of Sale</a></li>
            
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
            <div class="welcome-card">
                <h3>System Overview</h3>
                <p>Use the sidebar menu to navigate the system.</p>
            </div>
        </section>
    </main>
</div>

</body>
</html>