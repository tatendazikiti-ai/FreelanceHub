
<?php
// Database connection
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

// Retrieve all orders
$query = $pdo->prepare(
    "SELECT
        o.order_id,
        o.title,
        o.requirements,
        o.total_amount,
        o.status,
        o.order_date,
        o.due_date,
        u.first_name,
        u.last_name
     FROM `order` o
     JOIN client_profile cp
        ON o.client_profile_id = cp.client_profile_id
     JOIN `user` u
        ON cp.user_id = u.user_id
     ORDER BY o.order_date DESC"
);

$query->execute();
$orders = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | Freelance Hub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h1 {
            color: #263b60;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #263b60;
            color: white;
        }

        tr:nth-child(even) {
            background: #f4f6f9;
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Freelance Hub - Orders</h1>
    <p>View all orders placed by clients.</p>

    <?php if (count($orders) > 0): ?>

        <table>
            <tr>
                <th>Order ID</th>
                <th>Client</th>
                <th>Service</th>
                <th>Amount (MUR)</th>
                <th>Status</th>
                <th>Order Date</th>
                <th>Due Date</th>
            </tr>

            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                    <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                    <td><?= htmlspecialchars($order['title']) ?></td>
                    <td><?= htmlspecialchars($order['total_amount']) ?></td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td><?= htmlspecialchars($order['order_date']) ?></td>
                    <td><?= htmlspecialchars($order['due_date'] ?? 'Not specified') ?></td>
                </tr>
            <?php endforeach; ?>

        </table>

    <?php else: ?>

        <p>No orders have been placed yet.</p>

    <?php endif; ?>

</div>
</body>
</html>