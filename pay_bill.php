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

$acc_sql = "SELECT a.account_id, a.account_number, a.account_type, a.balance,
                   u.full_name
            FROM accounts a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.account_id = $account_id AND a.user_id = $user_id";
$acc_result = $conn->query($acc_sql);

if ($acc_result->num_rows === 0) {
    echo "Invalid account or you do not have access to this account.";
    exit();
}

$account = $acc_result->fetch_assoc();

$biller_sql = "SELECT biller_id, biller_name, category FROM billers ORDER BY biller_name";
$biller_result = $conn->query($biller_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $biller_id   = (int)($_POST['biller_id'] ?? 0);
    $amount      = (float)($_POST['amount'] ?? 0);
    $reference   = $conn->real_escape_string(trim($_POST['reference'] ?? ""));
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ""));

    if ($biller_id <= 0) {
        $error = "Please select a biller.";
    } elseif ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } elseif ($amount > $account['balance']) {
        $error = "Insufficient balance to pay this bill.";
    } else {
        $new_balance = $account['balance'] - $amount;

       
        $txn_sql = "
            INSERT INTO transactions
            (account_id, biller_id, transaction_type, direction,
             amount, reference, description, running_balance)
            VALUES
            ($account_id, $biller_id, 'PAY_BILL', 'DEBIT',
             $amount, '$reference', '$description', $new_balance)
        ";

        if ($conn->query($txn_sql)) {
            
            $upd_sql = "UPDATE accounts SET balance = $new_balance WHERE account_id = $account_id";
            $conn->query($upd_sql);

            $success = "Bill paid successfully.";
            $account['balance'] = $new_balance; 
        } else {
            $error = "Failed to save transaction. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pay Bills</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; padding: 0; }
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
        .account-summary, .form-box {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            margin-bottom: 15px;
        }
        .account-summary {
            border-left: 6px solid #737CA1;
        }
        h2 { color: #333; }
        label { display: block; margin-top: 10px; }
        select, input[type=text], input[type=number], textarea {
            width: 100%;
            padding: 7px;
            margin-top: 4px;
            box-sizing: border-box;
        }
        .btn {
            margin-top: 15px;
            padding: 8px 16px;
            background: #737CA1;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .message-success {
            color: #27ae60;
            margin-top: 10px;
        }
        .message-error {
            color: #c0392b;
            margin-top: 10px;
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
        <h2>Pay Bills</h2>
        <p><strong>Account Type:</strong> <?php echo htmlspecialchars($account['account_type']); ?></p>
        <p><strong>Account Number:</strong> <?php echo htmlspecialchars($account['account_number']); ?></p>
        <p><strong>Available Balance:</strong> RM <?php echo number_format($account['balance'], 2); ?></p>
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
            <label>Biller</label>
            <select name="biller_id" required>
                <option value="">-- Select Biller --</option>
                <?php while ($b = $biller_result->fetch_assoc()) { ?>
                    <option value="<?php echo $b['biller_id']; ?>">
                        <?php echo htmlspecialchars($b['biller_name'] . " (" . $b['category'] . ")"); ?>
                    </option>
                <?php } ?>
            </select>

            <label>Bill Reference Number</label>
            <input type="text" name="reference" placeholder="e.g. TNB account number">

            <label>Amount (RM)</label>
            <input type="number" name="amount" step="0.01" min="0" required>

            <label>Description</label>
            <textarea name="description" rows="3" placeholder="e.g. Electricity bill for March"></textarea>

            <button type="submit" class="btn">Pay Bill</button>
        </form>
    </div>

</div>

</body>
</html>
