<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$user_sql = "SELECT full_name, email FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$acc_sql = "SELECT account_id, account_number, account_type, balance 
            FROM accounts WHERE user_id = ?";
$stmt2 = $conn->prepare($acc_sql);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result = $stmt2->get_result();

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}

$defaultAccountId = count($accounts) > 0 ? $accounts[0]['account_id'] : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Overview</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 85%;
            margin: 20px auto;
        }
        h2 {
            color: #333;
        }
        .account-box {
            background: #fff;
            padding: 18px;
            margin-bottom: 15px;
            border-radius: 6px;
            border-left: 6px solid #737CA1;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .btn {
            padding: 8px 14px;
            background: #737CA1;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 10px;
        }
        .nav-bar {
            background: #737CA1;
            padding: 12px;
            color: #fff;
        }
        .nav-bar a {
            color: #fff;
            margin-right: 18px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="nav-bar">
    Welcome, <?php echo htmlspecialchars($user['full_name']); ?> |
    <a href="account_overview.php">Overview</a>
    <?php if ($defaultAccountId !== null) { ?>
        <a href="transaction_history.php?account_id=<?php echo $defaultAccountId; ?>">Transactions</a>
        <a href="pay_bill.php?account_id=<?php echo $defaultAccountId; ?>">Pay Bills</a>
        <a href="transfer_funds.php?account_id=<?php echo $defaultAccountId; ?>">Transfer Funds</a>
    <?php } ?>
    <a href="logout.php">Logout</a>
</div>

<div class="container">

    <h2>Your Accounts</h2>

    <?php if (count($accounts) === 0) { ?>

        <p>You have no accounts yet.</p>

    <?php } else { ?>

        <?php foreach ($accounts as $acc) { ?>
            <div class="account-box">
                <h3><?php echo htmlspecialchars($acc['account_type']); ?> Account</h3>

                <p><strong>Account Number:</strong> <?php echo htmlspecialchars($acc['account_number']); ?></p>
                <p><strong>Available Balance:</strong> RM <?php echo number_format($acc['balance'], 2); ?></p>

                <div style="margin-top: 10px;">
                    <a class="btn" href="transaction_history.php?account_id=<?php echo $acc['account_id']; ?>">View Transactions</a>
                    <a class="btn" href="transfer_funds.php?account_id=<?php echo $acc['account_id']; ?>">Transfer Funds</a>
                    <a class="btn" href="pay_bill.php?account_id=<?php echo $acc['account_id']; ?>">Pay Bills</a>
                </div>
            </div>
        <?php } ?>

    <?php } ?>

</div>

</body>
</html>
