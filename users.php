<?php
session_start();
require_once 'db.php';

// SECURITY: Only Admins can access the User Management page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';

// Handle Add User
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("INSERT INTO Users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->execute(['username' => $username, 'password' => $password, 'role' => $role]);
        $message = "User added successfully!";
    } catch(PDOException $e) {
        $message = "Error: Username might already exist.";
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($delete_id != $_SESSION['user_id']) { // Prevent Admin from deleting themselves
        $stmt = $pdo->prepare("DELETE FROM Users WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
    }
    header("Location: users.php");
    exit();
}

$users = $pdo->query("SELECT * FROM Users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-container">
    <aside class="sidebar">
        <h2>SmartPOS</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="pos.php">Point of Sale</a></li>
            
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <li><a href="inventory.php">Manage Inventory</a></li>
                <li><a href="users.php" style="color: #3498db;">Manage Users</a></li>
            <?php endif; ?>

            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <h1>Manage System Users</h1>
        </header>

        <?php if($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="welcome-card" style="margin-bottom: 20px;">
            <h3>Add New User</h3>
            <form method="POST" action="" class="form-inline">
                <input type="text" name="username" placeholder="Username" required style="padding: 8px;">
                <input type="password" name="password" placeholder="Password" required style="padding: 8px;">
                <select name="role" required style="padding: 8px;">
                    <option value="Cashier">Cashier</option>
                    <option value="Admin">Admin</option>
                </select>
                <button type="submit" name="add_user" class="btn">Create User</button>
            </form>
        </div>

        <div class="welcome-card">
            <h3>Current Users</h3>
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <tr style="border-bottom: 2px solid #ecf0f1;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Username</th>
                    <th style="padding: 10px;">Role</th>
                    <th style="padding: 10px;">Action</th>
                </tr>
                <?php foreach($users as $user): ?>
                <tr style="border-bottom: 1px solid #ecf0f1;">
                    <td style="padding: 10px;"><?php echo $user['id']; ?></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td style="padding: 10px;"><?php echo $user['role']; ?></td>
                    <td style="padding: 10px;">
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Delete this user?');">Delete</a>
                        <?php else: ?>
                            <span style="color: gray;">Current User</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </main>
</div>
</body>
</html>