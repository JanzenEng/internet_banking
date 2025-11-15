<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['account_id'])) {
    header("Location: account_overview.php");
    exit();
}

$account_id = (int) $_GET['account_id'];

$acc_sql = "SELECT a.account_id, a.account_number, a.account_type, a.balance, u.full_name
            FROM accounts a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.account_id = ? AND a.user_id = ?";
$stmt = $conn->prepare($acc_sql);
$stmt->bind_param("ii", $account_id, $user_id);
$stmt->execute();
$acc_result = $stmt->get_result();

if ($acc_result->num_rows === 0) {
    echo "Invalid account or you do not have access to this account.";
    exit();
}

$account = $acc_result->fetch_assoc();

$txn_sql = "SELECT t.transaction_id, t.transaction_type, t.direction, t.amount,
                   t.reference, t.description, t.running_balance, t.txn_datetime,
                   b.biller_name
            FROM transactions t
            LEFT JOIN billers b ON t.biller_id = b.biller_id
            WHERE t.account_id = ?
            ORDER BY t.txn_datetime DESC, t.transaction_id DESC";

$stmt2 = $conn->prepare($txn_sql);
$stmt2->bind_param("i", $account_id);
$stmt2->execute();
$txns = $stmt2->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transaction History</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
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
        .container {
            width: 90%;
            margin: 20px auto;
        }
        h2 {
            color: #333;
        }
        .account-summary {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            border-left: 6px solid #737CA1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        th {
            background: #737CA1;
            color: #fff;
            text-align: left;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        .amount-debit {
            color: #c0392b;
        }
        .amount-credit {
            color: #27ae60;
        }
        .no-data {
            padding: 15px;
            background: #fff;
            text-align: center;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .btn-back {
            display: inline-block;
            margin-top: 10px;
            padding: 7px 12px;
            background: #737CA1;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="nav-bar">
    Welcome, <?php echo htmlspecialchars($account['full_name']); ?> |
    <a href="account_overview.php">Overview</a>
    <a href="transaction_history.php?account_id=<?php echo $account['account_id']; ?>">Transactions</a>
    <a href="pay_bill.php?account_id=<?php echo $account['account_id']; ?>">Pay Bills</a>
    <a href="transfer_funds.php?account_id=<?php echo $account['account_id']; ?>">Transfer Funds</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">

    <div class="account-summary">
        <h2>Transaction History</h2>
        <p><strong>Account Type:</strong> <?php echo htmlspecialchars($account['account_type']); ?></p>
        <p><strong>Account Number:</strong> <?php echo htmlspecialchars($account['account_number']); ?></p>
        <p><strong>Current Balance:</strong> RM <?php echo number_format($account['balance'], 2); ?></p>
        <a class="btn-back" href="account_overview.php">&laquo; Back to Account Overview</a>
    </div>

    <?php if ($txns->num_rows === 0) { ?>

        <div class="no-data">
            No transactions found for this account.
        </div>

    <?php } else { ?>

        <table>
            <tr>
                <th>Date / Time</th>
                <th>Type</th>
                <th>Biller / Reference</th>
                <th>Description</th>
                <th>Debit (RM)</th>
                <th>Credit (RM)</th>
                <th>Running Balance (RM)</th>
            </tr>

            <?php while ($row = $txns->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['txn_datetime']); ?></td>

                    <td><?php echo htmlspecialchars($row['transaction_type']); ?></td>

                    <td>
                        <?php
                        if (!empty($row['biller_name'])) {
                            echo htmlspecialchars($row['biller_name']);
                            if (!empty($row['reference'])) {
                                echo " (" . htmlspecialchars($row['reference']) . ")";
                            }
                        } else {
                            echo htmlspecialchars($row['reference']);
                        }
                        ?>
                    </td>

                    <td><?php echo htmlspecialchars($row['description']); ?></td>

                    <td class="amount-debit">
                        <?php
                        if ($row['direction'] === 'DEBIT') {
                            echo number_format($row['amount'], 2);
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>

                    <td class="amount-credit">
                        <?php
                        if ($row['direction'] === 'CREDIT') {
                            echo number_format($row['amount'], 2);
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>

                    <td><?php echo number_format($row['running_balance'], 2); ?></td>
                </tr>
            <?php } ?>

        </table>
    <?php } ?>

</div>

</body>
</html>
