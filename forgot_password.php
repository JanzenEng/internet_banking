<?php
session_start();
require 'db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-Master/src/Exception.php';
require 'PHPMailer-Master/src/PHPMailer.php';
require 'PHPMailer-Master/src/SMTP.php';

$message = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if ($email === "") {
        $error = "Please enter your registered email address.";
    } else {
        $sql = "SELECT user_id, full_name, email FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $temp_password = '';
            for ($i = 0; $i < 6; $i++) {
                $temp_password .= $chars[random_int(0, strlen($chars) - 1)];
            }

            $new_hash = password_hash($temp_password, PASSWORD_DEFAULT);

            $upd = $conn->prepare("UPDATE users SET password_hash = ?, must_change_password = 1 WHERE user_id = ?");
            $upd->bind_param("si", $new_hash, $row['user_id']);
            $upd->execute();


            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = '1955porsche356acarrera@gmail.com';
                $mail->Password = 'solz ihdh ngcy paos';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('1955porsche356acarrera@gmail.com', 'Internet Banking');
                $mail->addAddress($row['email'], $row['full_name']);

                $mail->Subject = 'Your Temporary Password';
                $mail->Body =
                    "Hello " . $row['full_name'] . ",\n\n" .
                    "Your temporary password is: $temp_password\n\n" .
                    "Please log in and change your password immediately.\n\n" .
                    "Regards,\nInternet Banking System";

                $mail->send();
            } catch (Exception $e) { }

            $message = "If this email is registered, a temporary password has been sent.";
        } else {
            $message = "If this email is registered, a temporary password has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body { font-family: Arial; background:#f5f5f5; margin:0; padding:0; }
        .box {
            width: 360px; margin: 80px auto; background:#fff; padding:20px;
            border-radius:6px; box-shadow:0 2px 5px rgba(0,0,0,0.15);
        }
        h2 { text-align:center; margin-top:0; color:#333; }
        label { display:block; margin-top:10px; }
        input[type=email] {
            width:100%; padding:8px; margin-top:4px; box-sizing:border-box;
        }
        .btn {
            margin-top:15px; width:100%; padding:8px;
            background:#737CA1; color:#fff; border:none; border-radius:4px; cursor:pointer;
        }
        .msg-success { color:#27ae60; margin-top:10px; font-size:13px; }
        .msg-error { color:#c0392b; margin-top:10px; font-size:13px; }
        .back-link { margin-top:10px; font-size:13px; text-align:center; }
    </style>
</head>
<body>

<div class="box">
    <h2>Forgot Password</h2>

    <?php if ($message !== "") { ?>
        <div class="msg-success"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <?php if ($error !== "") { ?>
        <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="post" action="">
        <label>Registered Email Address</label>
        <input type="email" name="email" required>

        <button type="submit" class="btn">Send Temporary Password</button>
    </form>

    <div class="back-link">
        <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>
