
<?php
// 1. Start the session to securely remember the user
session_start();

// 2. Connect to your database
// Note: This assumes db.php is in the exact same folder as index.php
require_once 'db.php'; 

$error_message = ''; // Variable to hold any login errors

// 3. Process the form when the user clicks "Login"
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if fields are empty
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            // Securely search the database for the user using PDO Prepared Statements
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM Users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify the user exists and the typed password matches the secure hash
            if ($user && password_verify($password, $user['password_hash'])) {
                
                // Security best practice: prevent session fixation attacks
                session_regenerate_id(true); 

                // Store user details in the active session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect to the main POS dashboard upon success
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch(PDOException $e) {
            // In a production app, log the actual error ($e->getMessage()) to a hidden file
            $error_message = "System error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Login</title>
    <!-- Connects to your separate CSS file -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-container">
    <h2>System Login</h2>
    
    <!-- Display error messages here if login fails -->
    <?php if(!empty($error_message)): ?>
        <div style="color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px;">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <!-- A professional touch: retain the typed username so the user doesn't have to retype it if they get the password wrong -->
            <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" name="login">Login</button>
    </form>
</div>

</body>
</html>