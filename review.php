
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
        $client_id = filter_input(INPUT_POST, 'client_profile_id', FILTER_VALIDATE_INT);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        $comment = trim($_POST['comment'] ?? "");

        if (
            !$order_id ||
            !$client_id ||
            !$rating ||
            $rating < 1 ||
            $rating > 5
        ) {
            $error = "Please enter valid order details and a rating from 1 to 5.";
        } else {
            try {
                // Check that the order belongs to the client and is completed
                $check = $pdo->prepare(
                    "SELECT freelancer_profile_id, status
                     FROM `order`
                     WHERE order_id = ?
                     AND client_profile_id = ?"
                );

                $check->execute([$order_id, $client_id]);
                $order = $check->fetch(PDO::FETCH_ASSOC);

                if (!$order) {
                    $error = "Order not found for this client.";
                } elseif ($order['status'] !== 'Completed') {
                    $error = "You can only review completed orders.";
                } else {
                    // Check whether a review already exists
                    $existing = $pdo->prepare(
                        "SELECT review_id FROM review WHERE order_id = ?"
                    );

                    $existing->execute([$order_id]);

                    if ($existing->fetch()) {
                        $error = "A review has already been submitted for this order.";
                    } else {
                        // Save the review
                        $insert = $pdo->prepare(
                            "INSERT INTO review
                            (order_id, client_profile_id,
                             freelancer_profile_id, rating, comment)
                            VALUES (?, ?, ?, ?, ?)"
                        );

                        $insert->execute([
                            $order_id,
                            $client_id,
                            $order['freelancer_profile_id'],
                            $rating,
                            $comment
                        ]);

                        $message = "Review submitted successfully!";
                    }
                }
            } catch (PDOException $e) {
                $error = "Unable to submit the review. Please try again.";
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
    <title>Submit Review | Freelance Hub</title>

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

        input, select, textarea {
            width: 100%;
            padding: 11px;
            margin-top: 7px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 100px;
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

    <h1>Submit a Review</h1>
    <p>Share your experience with the freelancer.</p>

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

        <label for="client_profile_id">Client Profile ID</label>
        <input type="number" name="client_profile_id"
               id="client_profile_id" min="1" required>

        <label for="rating">Rating</label>
        <select name="rating" id="rating" required>
            <option value="">Select a rating</option>
            <option value="1">1 - Poor</option>
            <option value="2">2 - Fair</option>
            <option value="3">3 - Good</option>
            <option value="4">4 - Very Good</option>
            <option value="5">5 - Excellent</option>
        </select>

        <label for="comment">Comment (Optional)</label>
        <textarea name="comment" id="comment"
                  placeholder="Write your feedback..."></textarea>

        <button type="submit">Submit Review</button>

    </form>

</div>
</body>
</html>