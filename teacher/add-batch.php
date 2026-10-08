<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["user_id"];
$message = "";

/* Get subjects created by this teacher */

$sql = "SELECT id, subject_name
        FROM subjects
        WHERE teacher_id = ?
        ORDER BY subject_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();

$result = $stmt->get_result();

$subjects = [];

while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

$stmt->close();


/* Create batch */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $batch_name = trim($_POST["batch_name"]);
    $subject_id = $_POST["subject_id"];

    if (empty($batch_name) || empty($subject_id)) {

        $message = "Please fill in all fields.";

    } else {

        /* Generate a random batch code */

        $batch_code = strtoupper(
            substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6)
        );

        $sql = "INSERT INTO batches
                (batch_name, batch_code, subject_id, teacher_id)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssii",
            $batch_name,
            $batch_code,
            $subject_id,
            $teacher_id
        );

        if ($stmt->execute()) {

            $message = "Batch created successfully! Batch Code: " . $batch_code;

        } else {

            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Batch</title>

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
            max-width: 600px;
            margin: 40px auto;
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

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 11px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            margin-top: 22px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
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

    <h2>👨‍🏫 Create Batch</h2>

</div>

<div class="container">

    <div class="card">

        <h1>➕ Create New Batch</h1>

        <?php if ($message != ""): ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <label>Batch Name</label>

            <input
                type="text"
                name="batch_name"
                placeholder="Example: BCA 3rd Year - A"
                required
            >

            <label>Subject</label>

            <select name="subject_id" required>

                <option value="">
                    -- Select Subject --
                </option>

                <?php foreach ($subjects as $subject): ?>

                    <option value="<?php echo $subject["id"]; ?>">

                        <?php echo htmlspecialchars($subject["subject_name"]); ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">
                Create Batch
            </button>

        </form>

        <a href="dashboard.php" class="back">
            ← Back to Teacher Dashboard
        </a>

    </div>

</div>

</body>

</html>