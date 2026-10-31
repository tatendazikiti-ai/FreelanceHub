
<?php
session_start();

$host = "localhost";
$dbname = "freelance_hub_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please refresh the page.";
    } else {
        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $method = $_POST['payment_method'] ?? "";
        $reference = trim($_POST['transaction_reference'] ?? "");

        $allowed_methods = [
            'Card',
            'Bank Transfer',
            'Cash'
        ];

        if (
            !$order_id ||
            !in_array($method, $allowed_methods, true) ||
            $reference === ""
        ) {
            $error = "Please complete all required fields.";
        } else {
            try {
                // Check whether the order exists and is completed
                $check = $pdo->prepare(
                    "SELECT total_amount, status
                     FROM `order`
                     WHERE order_id = ?"
                );
                $check->execute([$order_id]);
                $order = $check->fetch(PDO::FETCH_ASSOC);

                if (!$order) {
                    $error = "Order not found.";
                } elseif ($order['status'] !== 'Completed') {
                    $error = "Payment can only be recorded for completed orders.";
                } else {
                    // Check whether payment already exists
                    $existing = $pdo->prepare(
                        "SELECT payment_id FROM payment WHERE order_id = ?"
                    );
                    $existing->execute([$order_id]);

                    if ($existing->fetch()) {
                        $error = "A payment already exists for this order.";
                    } else {
                        // Record the demonstration payment
                        $insert = $pdo->prepare(
                            "INSERT INTO payment
                            (order_id, amount, payment_method,
                             payment_status, transaction_reference, paid_at)
                            VALUES (?, ?, ?, 'Paid', ?, NOW())"
                        );

                        $insert->execute([
                            $order_id,
                            $order['total_amount'],
                            $method,
                            $reference
                        ]);

                        $message = "Payment recorded successfully!";
                    }
                }
            } catch (PDOException $e) {
                $error = "Unable to record payment. Check the transaction reference.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | Freelance Hub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 30px;
        }

        .container {
            max-width: 500px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            color: #263b60;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 11px;
            margin-top: 7px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            background: #263b60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .message {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Record Payment</h1>
    <p>Enter the order and payment details below.</p>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <label for="order_id">Order ID</label>
        <input type="number" name="order_id" id="order_id"
               min="1" required>

        <label for="payment_method">Payment Method</label>
        <select name="payment_method" id="payment_method" required>
            <option value="">Select payment method</option>
            <option value="Card">Card</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash</option>
        </select>

        <label for="transaction_reference">Transaction Reference</label>
        <input type="text" name="transaction_reference"
               id="transaction_reference" required>

        <button type="submit">Record Payment</button>

    </form>

</div>
</body>
</html>