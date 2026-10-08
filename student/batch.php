<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION["user_id"];

if (!isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit();
}

$batch_id = (int) $_GET["id"];


/* =========================
   GET BATCH INFORMATION
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

        INNER JOIN student_batches
            ON student_batches.batch_id = batches.id

        WHERE batches.id = ?
        AND student_batches.student_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $batch_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

$batch = $result->fetch_assoc();

$stmt->close();


if (!$batch) {
    die("You are not a member of this batch.");
}


/* =========================
   GET ASSIGNMENTS
========================= */

$sql = "SELECT
            assignments.id,
            assignments.title,
            assignments.description,
            assignments.deadline,
            assignments.max_marks,
            submissions.status AS submission_status

        FROM assignments

        LEFT JOIN submissions
            ON assignments.id = submissions.assignment_id
            AND submissions.student_id = ?

        WHERE assignments.batch_id = ?

        ORDER BY assignments.deadline ASC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $student_id,
    $batch_id
);

$stmt->execute();

$assignments = $stmt->get_result();

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?php echo htmlspecialchars($batch["batch_name"]); ?>
</title>


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

.navbar h2 {
    margin: 0;
}

.container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px;
}

.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    color: #2563eb;
    text-decoration: none;
    font-weight: bold;
}

.batch-header {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.batch-header h1 {
    margin-top: 0;
    color: #1e3a8a;
}

.assignment-card {
    background: white;
    padding: 22px;
    margin-bottom: 18px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
}

.assignment-card h3 {
    margin-top: 0;
    color: #2563eb;
}

.assignment-card p {
    color: #475569;
}

.deadline {
    color: #dc2626 !important;
    font-weight: bold;
}

.marks {
    color: #7c3aed !important;
    font-weight: bold;
}

.solve-btn {
    display: inline-block;
    background: #2563eb;
    color: white;
    padding: 10px 16px;
    border-radius: 7px;
    text-decoration: none;
    font-weight: bold;
    margin-top: 8px;
}

.solve-btn:hover {
    background: #1d4ed8;
}

.submitted-btn {
    display: inline-block;
    margin-top: 12px;
    background: #16a34a;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}

.submitted-btn:hover {
    background: #15803d;
}

.no-assignments {
    background: white;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    color: #64748b;
}

</style>

</head>


<body>


<div class="navbar">

    <h2>🎓 Student Assignment System</h2>

</div>


<div class="container">


    <a
        href="dashboard.php"
        class="back-btn"
    >
        ← Back to Dashboard
    </a>


    <!-- BATCH INFORMATION -->

    <div class="batch-header">

        <h1>
            📚
            <?php echo htmlspecialchars(
                $batch["batch_name"]
            ); ?>
        </h1>

        <p>

            <strong>Subject:</strong>

            <?php echo htmlspecialchars(
                $batch["subject_name"]
            ); ?>

        </p>

        <p>

            <strong>Batch Code:</strong>

            <?php echo htmlspecialchars(
                $batch["batch_code"]
            ); ?>

        </p>

    </div>


    <!-- ASSIGNMENTS -->

    <h2>📝 Assignments</h2>


    <?php if ($assignments->num_rows > 0): ?>


        <?php while (
            $assignment = $assignments->fetch_assoc()
        ): ?>

            <div class="assignment-card">


                <h3>

                    <?php echo htmlspecialchars(
                        $assignment["title"]
                    ); ?>

                </h3>


                <p>

                    <?php echo nl2br(
                        htmlspecialchars(
                            $assignment["description"]
                        )
                    ); ?>

                </p>


                <p class="deadline">

                    ⏰ Deadline:

                    <?php echo date(
                        "d M Y, h:i A",
                        strtotime(
                            $assignment["deadline"]
                        )
                    ); ?>

                </p>


                <p class="marks">

                    🎯 Maximum Marks:

                    <?php echo $assignment["max_marks"]; ?>

                </p>


                <?php if (
                    !empty(
                        $assignment["submission_status"]
                    )
                ): ?>


                    <a
                        href="submission-history.php"
                        class="submitted-btn"
                    >

                        ✅ Submitted — View Submission

                    </a>


                <?php else: ?>


                    <a
                        href="submit-assignment.php?id=<?php echo $assignment["id"]; ?>"
                        class="solve-btn"
                    >

                        📖 Open & Solve Assignment

                    </a>


                <?php endif; ?>


            </div>


        <?php endwhile; ?>


    <?php else: ?>


        <div class="no-assignments">

            <h3>📭 No Assignments Yet</h3>

            <p>
                Your teacher has not added any assignments yet.
            </p>

        </div>


    <?php endif; ?>


</div>


</body>

</html>