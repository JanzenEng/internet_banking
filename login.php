<?php
session_start();
require 'db_connect.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT user_id, username, password_hash, full_name 
            FROM users 
            WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id']   = $row['user_id'];
            $_SESSION['full_name'] = $row['full_name'];
    
            header("Location: account_overview.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Internet Banking Login</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; }
        .login-box {
            width: 320px;
            margin: 80px auto;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-top: 10px; }
        input[type=text], input[type=password] {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            box-sizing: border-box;
        }
        .btn {
            margin-top: 15px;
            width: 100%;
            padding: 8px;
            background: #737CA1;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .error { color: red; margin-top: 10px; text-align:center; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Internet Banking Login</h2>

    <?php if ($error !== "") { ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="post" action="">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" class="btn">Login</button>
    </form>
</div>
</body>
</html>
