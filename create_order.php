<?php
session_start();

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
	die("Database connection failed. Please check XAMPP.");
}

// CSRF protection
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
		$client_id = filter_input(INPUT_POST, 'client_profile_id', FILTER_VALIDATE_INT);
		$service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
		$requirements = trim($_POST['requirements'] ?? "");
		$due_date = $_POST['due_date'] ?? "";

		if (!$client_id || !$service_id || $requirements === "") {
			$error = "Please complete all required fields.";
		} else {
			try {
				// Check that the client profile exists
				$clientQuery = $pdo->prepare(
					"SELECT client_profile_id FROM client_profile
					 WHERE client_profile_id = ?"
				);
				$clientQuery->execute([$client_id]);

				if (!$clientQuery->fetch()) {
					$error = "Client profile not found.";
				} else {
					// Get the selected service details
					$serviceQuery = $pdo->prepare(
						"SELECT freelancer_profile_id, title, price
						 FROM service WHERE service_id = ?"
					);
					$serviceQuery->execute([$service_id]);
					$service = $serviceQuery->fetch(PDO::FETCH_ASSOC);

					if (!$service) {
						$error = "Service not found.";
					} else {
						// Validate optional due date
						if ($due_date !== "") {
							$date = DateTime::createFromFormat('Y-m-d', $due_date);

							if (!$date || $date->format('Y-m-d') !== $due_date) {
								$error = "Please enter a valid due date.";
							}
						}

						if ($error === "") {
							// Create the order
							$insert = $pdo->prepare(
								"INSERT INTO `order`
								(client_profile_id, service_id,
								 freelancer_profile_id, title,
								 requirements, total_amount, due_date)
								VALUES (?, ?, ?, ?, ?, ?, ?)"
							);

							$insert->execute([
								$client_id,
								$service_id,
								$service['freelancer_profile_id'],
								$service['title'],
								$requirements,
								$service['price'],
								$due_date !== "" ? $due_date : null
							]);

							$message = "Order created successfully! Your order ID is "
								. $pdo->lastInsertId() . ".";
						}
					}
				}
			} catch (PDOException $e) {
				$error = "Unable to create the order. Please check the information and try again.";
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
	<title>Create New Order | Freelance Hub</title>

	<style>
		body {
			font-family: Arial, sans-serif;
			background: #f4f6f9;
			margin: 0;
			padding: 30px;
		}

		.container {
			max-width: 550px;
			margin: 30px auto;
			background: white;
			padding: 30px;
			border-radius: 10px;
			box-shadow: 0 3px 12px rgba(0,0,0,0.08);
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

		input, textarea {
			width: 100%;
			padding: 11px;
			margin-top: 7px;
			border: 1px solid #ccc;
			border-radius: 5px;
			box-sizing: border-box;
		}

		textarea {
			min-height: 110px;
			resize: vertical;
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
			font-size: 16px;
		}

		button:hover {
			background: #172943;
		}

		.message {
			background: #e1f5e8;
			color: #176b36;
			padding: 12px;
			border-radius: 5px;
		}

		.error {
			background: #ffe5e5;
			color: #a52222;
			padding: 12px;
			border-radius: 5px;
		}
	</style>
</head>

<body>

<div class="container">

	<h1>Create a New Order</h1>
	<p>Enter your details below to place a service order.</p>

	<?php if ($message): ?>
		<p class="message">
			<?= htmlspecialchars($message) ?>
		</p>
	<?php endif; ?>

	<?php if ($error): ?>
		<p class="error">
			<?= htmlspecialchars($error) ?>
		</p>
	<?php endif; ?>

	<form method="POST" action="">

		<input type="hidden" name="csrf_token"
			   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

		<label for="client_profile_id">Client Profile ID</label>
		<input type="number" id="client_profile_id"
			   name="client_profile_id" min="1" required>

		<label for="service_id">Service ID</label>
		<input type="number" id="service_id"
			   name="service_id" min="1" required>

		<label for="requirements">Order Requirements</label>
		<textarea id="requirements" name="requirements"
				  placeholder="Describe what you need from the freelancer..."
				  required></textarea>

		<label for="due_date">Expected Due Date</label>
		<input type="date" id="due_date" name="due_date">

		<button type="submit">Place Order</button>

	</form>

</div>

</body>
</html>
