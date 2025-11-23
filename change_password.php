<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old_pass      = trim($_POST['old_password'] ?? '');
    $new_pass      = trim($_POST['new_password'] ?? '');
    $confirm_pass  = trim($_POST['confirm_password'] ?? '');

    if ($old_pass === "" || $new_pass === "" || $confirm_pass === "") {
        $error = "All fields are required.";
    }
    else if ($new_pass !== $confirm_pass) {
        $error = "New password and confirmation do not match.";
    }
    else {
        $sql = "SELECT password_hash FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {

            if (!password_verify($old_pass, $row['password_hash'])) {
                $error = "Your current password is incorrect.";
            } else {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);

                $upd = $conn->prepare("UPDATE users 
                                       SET password_hash = ?, must_change_password = 0 
                                       WHERE user_id = ?");
                $upd->bind_param("si", $new_hash, $user_id);
                $upd->execute();

              
                header("Location: account_overview.php?changed=1");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; margin:0; padding:0; }
        .box {
            width: 360px; margin: 80px auto; background:#fff; padding:20px;
            border-radius:6px; box-shadow:0 2px 5px rgba(0,0,0,0.2);
        }
        h2 { text-align:center; margin-top:0; color:#333; }
        label { display:block; margin-top:12px; }
        input[type=password] { width:100%; padding:8px; margin-top:5px; }
        .btn {
            margin-top:18px; width:100%; padding:10px;
            background:#737CA1; color:#fff; border:none; border-radius:4px;
        }
        .error { color:#c0392b; margin-top:10px; text-align:center; }
    </style>
</head>
<body>

<div class="box">
    <h2>Change Password</h2>

    <?php if ($error !== "") { ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="post">
        <label>Current Password</label>
        <input type="password" name="old_password" required>

        <label>New Password</label>
        <input type="password" name="new_password" required>

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>

        <button type="submit" class="btn">Update Password</button>
    </form>
</div>

</body>
</html>
