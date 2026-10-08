<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION["user_id"];
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $batch_code = strtoupper(trim($_POST["batch_code"]));

    if (empty($batch_code)) {

        $message = "Please enter a batch code.";
        $message_type = "error";

    } else {

        /* Find the batch */

        $sql = "SELECT id, batch_name
                FROM batches
                WHERE batch_code = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "s",
            $batch_code
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $batch = $result->fetch_assoc();

        $stmt->close();


        if (!$batch) {

            $message = "Invalid batch code.";
            $message_type = "error";

        } else {

            /* Check if student already joined */

            $sql = "SELECT id
                    FROM student_batches
                    WHERE student_id = ?
                    AND batch_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ii",
                $student_id,
                $batch["id"]
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $already_joined = $result->num_rows > 0;

            $stmt->close();


            if ($already_joined) {

                $message = "You have already joined this batch.";
                $message_type = "error";

            } else {

                /* Join the batch */

                $sql = "INSERT INTO student_batches
                        (student_id, batch_id)
                        VALUES (?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "ii",
                    $student_id,
                    $batch["id"]
                );

                if ($stmt->execute()) {

                    $message = "Successfully joined: " . $batch["batch_name"];
                    $message_type = "success";

                } else {

                    $message = "Error joining batch.";
                    $message_type = "error";
                }

                $stmt->close();
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

    <title>Join Batch</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        .navbar {
            background: #2563eb;
            color: white;
            padding: 18px 30px;
        }

        .container {
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            color: #1e3a8a;
        }

        p {
            color: #555;
        }

        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            text-transform: uppercase;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #2563eb;
        }

    </style>

</head>

<body>

<div class="navbar">

    <h2>🎓 Join Batch</h2>

</div>

<div class="container">

    <div class="card">

        <h1>🔑 Join a Batch</h1>

        <p>
            Enter the batch code given to you by your teacher.
        </p>

        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>

        <form method="POST">

            <label>Batch Code</label>

            <input
                type="text"
                name="batch_code"
                placeholder="Example: 6G5EQU"
                maxlength="20"
                required
            >

            <button type="submit">
                Join Batch
            </button>

        </form>

        <a href="dashboard.php" class="back">
            ← Back to Dashboard
        </a>

    </div>

</div>

</body>

</html>