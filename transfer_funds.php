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

$success = "";
$error   = "";

$acc_sql = "SELECT a.account_id, a.account_number, a.account_type, a.balance, u.full_name
            FROM accounts a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.account_id = $account_id AND a.user_id = $user_id";
$acc_result = $conn->query($acc_sql);

if ($acc_result->num_rows === 0) {
    echo "Invalid account or you do not have access to this account.";
    exit();
}

$source_acc = $acc_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_bank = trim($_POST['selected_bank'] ?? "");
    $target_account_number = trim($_POST['target_account_number'] ?? "");
    $amount = (float)($_POST['amount'] ?? 0);
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ""));

    if ($selected_bank === "") {
        $error = "Please select a bank.";
    } elseif ($target_account_number === "") {
        $error = "Please enter a target account number.";
    } elseif ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } elseif ($amount > $source_acc['balance']) {
        $error = "Insufficient balance to perform this transfer.";
    } else {
        $target_sql = "SELECT account_id, account_number, balance
                       FROM accounts
                       WHERE account_number = '" . $conn->real_escape_string($target_account_number) . "'";
        $target_result = $conn->query($target_sql);

        if ($target_result->num_rows === 0) {
            $error = "Target account not found.";
        } else {
            $target_acc = $target_result->fetch_assoc();

            if ($target_acc['account_id'] == $source_acc['account_id']) {
                $error = "You cannot transfer to the same account.";
            } else {
                $new_source_balance = $source_acc['balance'] - $amount;
                $new_target_balance = $target_acc['balance'] + $amount;

                $conn->begin_transaction();

                $ref_source = "To " . $target_acc['account_number'];
                $ref_target = "From " . $source_acc['account_number'];

                $txn1_sql = "
                    INSERT INTO transactions
                    (account_id, biller_id, transaction_type, direction,
                     amount, reference, description, running_balance)
                    VALUES
                    (" . $source_acc['account_id'] . ", NULL, 'TRANSFER', 'DEBIT',
                     $amount, '$ref_source', '$description', $new_source_balance)
                ";

                $txn2_sql = "
                    INSERT INTO transactions
                    (account_id, biller_id, transaction_type, direction,
                     amount, reference, description, running_balance)
                    VALUES
                    (" . $target_acc['account_id'] . ", NULL, 'TRANSFER', 'CREDIT',
                     $amount, '$ref_target', '$description', $new_target_balance)
                ";

                $upd1_sql = "UPDATE accounts SET balance = $new_source_balance WHERE account_id = " . $source_acc['account_id'];
                $upd2_sql = "UPDATE accounts SET balance = $new_target_balance WHERE account_id = " . $target_acc['account_id'];

                $ok = true;
                if (!$conn->query($txn1_sql)) $ok = false;
                if (!$conn->query($txn2_sql)) $ok = false;
                if (!$conn->query($upd1_sql)) $ok = false;
                if (!$conn->query($upd2_sql)) $ok = false;

                if ($ok) {
                    $conn->commit();
                    $success = "Funds transferred successfully.";
                    $source_acc['balance'] = $new_source_balance;
                } else {
                    $conn->rollback();
                    $error = "Failed to complete transfer. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transfer Funds</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; padding: 0; }
        .nav-bar { background: #737CA1; padding: 12px; color: #fff; }
        .nav-bar a { color: #fff; margin-right: 18px; text-decoration: none; }
        .container { width: 90%; margin: 20px auto; }
        .account-summary, .form-box {
            background: #fff; padding: 15px; border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08); margin-bottom: 15px;
        }
        .account-summary { border-left: 6px solid #737CA1; }
        h2 { color: #333; }
        label { display: block; margin-top: 10px; }
        select, input[type=text], input[type=number], textarea {
            width: 100%; padding: 7px; margin-top: 4px; box-sizing: border-box;
        }
        .btn {
            margin-top: 15px; padding: 8px 16px;
            background: #737CA1; color: #fff; border: none; border-radius: 4px;
            cursor: pointer;
        }
        .message-success { color: #27ae60; margin-top: 10px; }
        .message-error { color: #c0392b; margin-top: 10px; }
        .btn-back {
            display: inline-block; margin-top: 10px; padding: 7px 12px;
            background: #737CA1; color: #fff; text-decoration: none; border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="nav-bar">
    Welcome, <?php echo htmlspecialchars($source_acc['full_name']); ?> |
    <a href="account_overview.php">Overview</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">

    <div class="account-summary">
        <h2>Transfer Funds</h2>
        <p><strong>From Account Type:</strong> <?php echo htmlspecialchars($source_acc['account_type']); ?></p>
        <p><strong>From Account Number:</strong> <?php echo htmlspecialchars($source_acc['account_number']); ?></p>
        <p><strong>Available Balance:</strong> RM <?php echo number_format($source_acc['balance'], 2); ?></p>
        <a class="btn-back" href="account_overview.php">&laquo; Back to Account Overview</a>
    </div>

    <div class="form-box">
        <?php if ($success !== "") { ?>
            <div class="message-success"><?php echo htmlspecialchars($success); ?></div>
        <?php } ?>

        <?php if ($error !== "") { ?>
            <div class="message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form method="post" action="">
            <label>Select Bank</label>
            <select name="selected_bank" required>
                <option value="">-- Select Bank --</option>
                <option value="Maybank">Maybank</option>
                <option value="CIMB Bank">CIMB Bank</option>
                <option value="Public Bank">Public Bank</option>
                <option value="RHB Bank">RHB Bank</option>
                <option value="Hong Leong Bank">Hong Leong Bank</option>
                <option value="Ambank">Ambank</option>
                <option value="Bank Islam">Bank Islam</option>
                <option value="BSN">BSN</option>
            </select>

            <label>Target Account Number</label>
            <input type="text" name="target_account_number" placeholder="Enter target account number" required>

            <label>Amount (RM)</label>
            <input type="number" name="amount" step="0.01" min="0" required>

            <label>Description</label>
            <textarea name="description" rows="3" placeholder="e.g. Transfer for lunch"></textarea>

            <button type="submit" class="btn">Transfer</button>
        </form>
    </div>

</div>

</body>
</html>
