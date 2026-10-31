
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

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? "";

    $allowed_statuses = [
        'Pending',
        'Accepted',
        'In Progress',
        'Delivered',
        'Completed',
        'Cancelled'
    ];

    if (!$order_id || !in_array($new_status, $allowed_statuses, true)) {
        $error = "Please provide a valid order ID and status.";
    } else {
        try {
            // Check the current order status
            $check = $pdo->prepare(
                "SELECT status FROM `order` WHERE order_id = ?"
            );
            $check->execute([$order_id]);
            $order = $check->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $error = "Order not found.";
            } else {
                $current_status = $order['status'];

                // Define permitted status transitions
                $transitions = [
                    'Pending' => ['Accepted', 'Cancelled'],
                    'Accepted' => ['In Progress', 'Cancelled'],
                    'In Progress' => ['Delivered', 'Cancelled'],
                    'Delivered' => ['Completed', 'In Progress'],
                    'Completed' => [],
                    'Cancelled' => []
                ];

                if (!in_array(
                    $new_status,
                    $transitions[$current_status] ?? [],
                    true
                )) {
                    $error = "This status change is not permitted.";
                } else {
                    $update = $pdo->prepare(
                        "UPDATE `order`
                         SET status = ?,
                             completed_at = CASE
                                 WHEN ? = 'Completed' THEN NOW()
                                 ELSE completed_at
                             END
                         WHERE order_id = ?"
                    );

                    $update->execute([
                        $new_status,
                        $new_status,
                        $order_id
                    ]);

                    $message = "Order status updated successfully.";
                }
            }
        } catch (PDOException $e) {
            $error = "Unable to update the order status.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order Status | Freelance Hub</title>

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

    <h1>Update Order Status</h1>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label for="order_id">Order ID</label>
        <input type="number" name="order_id" id="order_id"
               min="1" required>

        <label for="status">New Status</label>
        <select name="status" id="status" required>
            <option value="">Select status</option>
            <option value="Accepted">Accepted</option>
            <option value="In Progress">In Progress</option>
            <option value="Delivered">Delivered</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>

        <button type="submit">Update Status</button>

    </form>

</div>
</body>
</html>