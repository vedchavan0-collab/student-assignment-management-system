<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: manage-assignments.php");
    exit();
}

$assignment_id = (int) $_GET["id"];
$teacher_id = $_SESSION["user_id"];

$message = "";


/* =========================
   GET ASSIGNMENT
========================= */

$sql = "SELECT
            assignments.id,
            assignments.title,
            assignments.description,
            assignments.batch_id,
            assignments.subject_id,
            assignments.deadline,
            assignments.max_marks

        FROM assignments

        WHERE assignments.id = ?
        AND assignments.teacher_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $assignment_id,
    $teacher_id
);

$stmt->execute();

$result = $stmt->get_result();

$assignment = $result->fetch_assoc();

$stmt->close();


if (!$assignment) {
    die("Assignment not found.");
}


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

$stmt->bind_param(
    "i",
    $teacher_id
);

$stmt->execute();

$batches_result = $stmt->get_result();

$batches = [];

while ($row = $batches_result->fetch_assoc()) {
    $batches[] = $row;
}

$stmt->close();


/* =========================
   UPDATE ASSIGNMENT
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


        /* Update assignment */

        $sql = "UPDATE assignments

                SET
                    title = ?,
                    description = ?,
                    batch_id = ?,
                    subject_id = ?,
                    deadline = ?,
                    max_marks = ?

                WHERE id = ?
                AND teacher_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssiisiii",
            $title,
            $description,
            $batch_id,
            $subject_id,
            $deadline,
            $max_marks,
            $assignment_id,
            $teacher_id
        );


        if ($stmt->execute()) {

            $message = "Assignment updated successfully! 🎉";

            $assignment["title"] = $title;
            $assignment["description"] = $description;
            $assignment["batch_id"] = $batch_id;
            $assignment["subject_id"] = $subject_id;
            $assignment["deadline"] = $deadline;
            $assignment["max_marks"] = $max_marks;

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

    <title>Edit Assignment</title>

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
            max-width: 700px;
            margin: 40px auto;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px;
        }

        textarea {
            height: 120px;
        }

        button {
            margin-top: 20px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

    </style>

</head>


<body>


<div class="navbar">

    <h2>✏️ Edit Assignment</h2>

</div>


<div class="container">

    <div class="form-card">

        <h1>Edit Assignment</h1>


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
                value="<?php echo htmlspecialchars($assignment["title"]); ?>"
                required
            >


            <label>Description</label>

            <textarea name="description"><?php
                echo htmlspecialchars($assignment["description"]);
            ?></textarea>


            <label>Batch *</label>

            <select name="batch_id" required>

                <option value="">
                    Select Batch
                </option>


                <?php foreach ($batches as $batch): ?>

                    <option
                        value="<?php echo $batch["id"]; ?>"

                        <?php
                        if ($batch["id"] == $assignment["batch_id"]) {
                            echo "selected";
                        }
                        ?>
                    >

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

                value="<?php
                    echo date(
                        "Y-m-d\TH:i",
                        strtotime($assignment["deadline"])
                    );
                ?>"

                required
            >


            <label>Maximum Marks *</label>

            <input
                type="number"
                name="max_marks"
                min="1"
                value="<?php echo $assignment["max_marks"]; ?>"
                required
            >


            <button type="submit">
                💾 Update Assignment
            </button>


        </form>

    </div>

</div>


</body>

</html>