<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher") {
    header("Location: ../login.php");
    exit();
}

$message = "";
$teacher_id = $_SESSION["user_id"];


/* =========================
   GET TEACHER BATCHES
========================= */

$sql = "SELECT
            batches.id,
            batches.batch_name,
            batches.batch_code,
            batches.subject_id,
            subjects.subject_name

        FROM batches

        INNER JOIN subjects
            ON batches.subject_id = subjects.id

        WHERE batches.teacher_id = ?

        ORDER BY batches.batch_name ASC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $teacher_id);

$stmt->execute();

$result = $stmt->get_result();

$batches = [];

while ($row = $result->fetch_assoc()) {
    $batches[] = $row;
}

$stmt->close();


/* =========================
   CREATE ASSIGNMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $batch_id = (int) $_POST["batch_id"];
    $deadline = $_POST["deadline"];
    $max_marks = (int) $_POST["max_marks"];


    /* Get subject from selected batch */

    $sql = "SELECT subject_id
            FROM batches
            WHERE id = ?
            AND teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $batch_id,
        $teacher_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $batch = $result->fetch_assoc();

    $stmt->close();


    if (
        empty($title) ||
        empty($batch_id) ||
        empty($deadline) ||
        empty($max_marks)
    ) {

        $message = "Please fill in all required fields.";

    } elseif (!$batch) {

        $message = "Invalid batch selected.";

    } else {

        $subject_id = $batch["subject_id"];


        /* Insert assignment */

        $sql = "INSERT INTO assignments
                (
                    title,
                    description,
                    batch_id,
                    subject_id,
                    teacher_id,
                    deadline,
                    max_marks
                )

                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssiiisi",
            $title,
            $description,
            $batch_id,
            $subject_id,
            $teacher_id,
            $deadline,
            $max_marks
        );


        if ($stmt->execute()) {

            $message = "Assignment created successfully! 🎉";

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

    <title>Create Assignment</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
        }

        .navbar {
            background: #2563eb;
            color: white;
            padding: 18px 30px;
        }

        .container {
            width: 600px;
            max-width: 90%;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        h2 {
            color: #2563eb;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 13px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            color: #15803d;
        }

    </style>

</head>


<body>


<div class="navbar">

    <h2>Teacher - Create Assignment</h2>

</div>


<div class="container">

    <h2>Create New Assignment</h2>


    <?php if (!empty($message)): ?>

        <div class="message">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <label>Assignment Title *</label>

        <input
            type="text"
            name="title"
            placeholder="Enter assignment title"
            required
        >


        <label>Description</label>

        <textarea
            name="description"
            placeholder="Enter assignment details"
        ></textarea>


        <label>Batch *</label>

        <select name="batch_id" required>

            <option value="">
                Select Batch
            </option>


            <?php foreach ($batches as $batch): ?>

                <option value="<?php echo $batch["id"]; ?>">

                    <?php echo htmlspecialchars($batch["batch_name"]); ?>

                    -

                    <?php echo htmlspecialchars($batch["subject_name"]); ?>

                </option>

            <?php endforeach; ?>

        </select>


        <label>Deadline *</label>

        <input
            type="datetime-local"
            name="deadline"
            required
        >


        <label>Maximum Marks *</label>

        <input
            type="number"
            name="max_marks"
            min="1"
            placeholder="e.g. 50"
            required
        >


        <button type="submit">
            Create Assignment
        </button>


    </form>

</div>


</body>

</html>