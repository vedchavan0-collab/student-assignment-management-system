<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["user_id"];

$selected_batch_id = 0;

if (isset($_GET["batch_id"])) {
    $selected_batch_id = (int) $_GET["batch_id"];
}


/* =========================
   SAVE MARKS
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_marks"])) {

    $submission_id = (int) $_POST["submission_id"];
    $marks = (int) $_POST["marks"];

    $sql = "SELECT assignments.max_marks
            FROM submissions

            INNER JOIN assignments
                ON submissions.assignment_id = assignments.id

            WHERE submissions.id = ?
            AND assignments.teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $submission_id,
        $teacher_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stmt->close();


    if ($row && $marks >= 0 && $marks <= $row["max_marks"]) {

        $sql = "UPDATE submissions

                INNER JOIN assignments
                    ON submissions.assignment_id = assignments.id

                SET submissions.marks = ?

                WHERE submissions.id = ?
                AND assignments.teacher_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iii",
            $marks,
            $submission_id,
            $teacher_id
        );

        $stmt->execute();

        $stmt->close();
    }


    /* Keep teacher on the selected batch */

    if (isset($_POST["batch_id"])) {

        $selected_batch_id =
            (int) $_POST["batch_id"];

        header(
            "Location: view-submissions.php?batch_id="
            . $selected_batch_id
        );

        exit();
    }
}


/* =========================
   GET TEACHER BATCHES
========================= */

$sql = "SELECT
            batches.id,
            batches.batch_name,
            batches.batch_code,
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
   VERIFY SELECTED BATCH
========================= */

$selected_batch = null;

if ($selected_batch_id > 0) {

    $sql = "SELECT
                batches.id,
                batches.batch_name,
                batches.batch_code,
                subjects.subject_name

            FROM batches

            INNER JOIN subjects
                ON batches.subject_id = subjects.id

            WHERE batches.id = ?
            AND batches.teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $selected_batch_id,
        $teacher_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $selected_batch = $result->fetch_assoc();

    $stmt->close();


    if (!$selected_batch) {

        $selected_batch_id = 0;
    }
}


/* =========================
   GET ASSIGNMENTS
   AND SUBMISSIONS
========================= */

$assignments = [];

if ($selected_batch_id > 0) {

    $sql = "SELECT
                assignments.id,
                assignments.title,
                assignments.description,
                assignments.deadline,
                assignments.max_marks

            FROM assignments

            WHERE assignments.batch_id = ?
            AND assignments.teacher_id = ?

            ORDER BY assignments.deadline ASC";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $selected_batch_id,
        $teacher_id
    );

    $stmt->execute();

    $assignment_result = $stmt->get_result();

    while ($assignment = $assignment_result->fetch_assoc()) {

        $assignment_id = $assignment["id"];

        $assignment["submissions"] = [];

        /* Get submissions for this assignment */

        $sql2 = "SELECT
                    submissions.id,
                    submissions.submission_text,
                    submissions.submitted_at,
                    submissions.status,
                    submissions.pdf_file,
                    submissions.marks,
                    users.name AS student_name

                 FROM submissions

                 INNER JOIN users
                    ON submissions.student_id = users.id

                 WHERE submissions.assignment_id = ?

                 ORDER BY submissions.submitted_at DESC";

        $stmt2 = $conn->prepare($sql2);

        $stmt2->bind_param(
            "i",
            $assignment_id
        );

        $stmt2->execute();

        $submission_result = $stmt2->get_result();

        while ($submission = $submission_result->fetch_assoc()) {

            $assignment["submissions"][] =
                $submission;
        }

        $stmt2->close();

        $assignments[] = $assignment;
    }

    $stmt->close();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Student Submissions</title>


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
    max-width: 1100px;
    margin: auto;
    padding: 30px;
}


/* =========================
   BATCH SELECTION
========================= */

.batch-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.batch-section h1 {
    margin-top: 0;
    color: #1e3a8a;
}

.batch-section p {
    color: #64748b;
}

.batch-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 18px;
    margin-top: 20px;
}

.batch-card {
    display: block;
    background: #f8fafc;
    padding: 20px;
    border-radius: 10px;
    border-left: 5px solid #2563eb;
    text-decoration: none;
    color: inherit;
    transition: 0.2s;
}

.batch-card:hover {
    background: #eff6ff;
    transform: translateY(-2px);
}

.batch-card h3 {
    margin-top: 0;
    color: #2563eb;
}

.batch-card p {
    margin: 8px 0;
}

.view-btn {
    display: inline-block;
    margin-top: 10px;
    background: #2563eb;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: bold;
}


/* =========================
   SELECTED BATCH
========================= */

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

.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    color: #2563eb;
    text-decoration: none;
    font-weight: bold;
}


/* =========================
   ASSIGNMENT
========================= */

.assignment-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.assignment-title {
    color: #2563eb;
    margin-top: 0;
}

.assignment-info {
    color: #64748b;
}

.deadline {
    color: #dc2626;
    font-weight: bold;
}

.marks-info {
    color: #7c3aed;
    font-weight: bold;
}


/* =========================
   SUBMISSION CARD
========================= */

.submission-card {
    background: #f8fafc;
    padding: 20px;
    margin-top: 18px;
    border-radius: 10px;
    border-left: 4px solid #2563eb;
}

.submission-card h3 {
    margin-top: 0;
    color: #1e3a8a;
}

.student {
    font-weight: bold;
}

.submission-text {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-top: 12px;
    border: 1px solid #e5e7eb;
}

.status {
    font-weight: bold;
}

.submitted {
    color: #15803d;
}

.late {
    color: #dc2626;
}


/* =========================
   PDF
========================= */

.pdf-link {
    display: inline-block;
    margin-top: 12px;
    background: #7c3aed;
    color: white;
    padding: 9px 13px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}

.pdf-link:hover {
    background: #6d28d9;
}


/* =========================
   GRADING
========================= */

.grading-box {
    background: #eff6ff;
    padding: 18px;
    margin-top: 15px;
    border-radius: 8px;
}

.grading-box label {
    display: block;
    margin-bottom: 8px;
}

.grading-box input {
    padding: 10px;
    width: 180px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    box-sizing: border-box;
}

.grading-box button {
    padding: 10px 15px;
    margin-left: 8px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
}

.grading-box button:hover {
    background: #1d4ed8;
}


/* =========================
   EMPTY
========================= */

.empty {
    background: white;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    color: #64748b;
}

.no-submissions {
    background: #f8fafc;
    padding: 18px;
    border-radius: 8px;
    color: #64748b;
}


/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {

    .container {
        padding: 20px 15px;
    }

    .grading-box input {
        width: 100%;
        margin-bottom: 10px;
    }

    .grading-box button {
        margin-left: 0;
    }

}

</style>

</head>


<body>


<div class="navbar">

    <h2>👨‍🏫 Student Submissions</h2>

</div>


<div class="container">


<?php if ($selected_batch_id == 0): ?>


    <!-- =========================
         SELECT BATCH
    ========================= -->

    <div class="batch-section">

        <h1>📚 Select Batch</h1>

        <p>
            Select a batch to view its assignments
            and student submissions.
        </p>


        <?php if (count($batches) > 0): ?>

            <div class="batch-grid">

                <?php foreach ($batches as $batch): ?>

                    <a
                        href="view-submissions.php?batch_id=<?php echo $batch["id"]; ?>"
                        class="batch-card"
                    >

                        <h3>
                            🎓
                            <?php
                            echo htmlspecialchars(
                                $batch["batch_name"]
                            );
                            ?>
                        </h3>

                        <p>
                            <strong>Subject:</strong>
                            <?php
                            echo htmlspecialchars(
                                $batch["subject_name"]
                            );
                            ?>
                        </p>

                        <p>
                            <strong>Batch Code:</strong>
                            <?php
                            echo htmlspecialchars(
                                $batch["batch_code"]
                            );
                            ?>
                        </p>

                        <span class="view-btn">
                            👀 View Assignments →
                        </span>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty">

                <h3>📭 No Batches Found</h3>

                <p>
                    Create a batch first to view student submissions.
                </p>

            </div>

        <?php endif; ?>

    </div>


<?php else: ?>


    <!-- =========================
         SELECTED BATCH
    ========================= -->

    <?php if ($selected_batch): ?>

        <a
            href="view-submissions.php"
            class="back-btn"
        >
            ← Back to Batches
        </a>


        <div class="batch-header">

            <h1>
                🎓
                <?php
                echo htmlspecialchars(
                    $selected_batch["batch_name"]
                );
                ?>
            </h1>

            <p>
                📚 <strong>Subject:</strong>
                <?php
                echo htmlspecialchars(
                    $selected_batch["subject_name"]
                );
                ?>
            </p>

            <p>
                🔑 <strong>Batch Code:</strong>
                <?php
                echo htmlspecialchars(
                    $selected_batch["batch_code"]
                );
                ?>
            </p>

        </div>


        <h2>📝 Assignments</h2>


        <?php if (count($assignments) > 0): ?>


            <?php foreach ($assignments as $assignment): ?>


                <div class="assignment-section">

                    <h2 class="assignment-title">

                        📝
                        <?php
                        echo htmlspecialchars(
                            $assignment["title"]
                        );
                        ?>

                    </h2>


                    <?php if (!empty($assignment["description"])): ?>

                        <p class="assignment-info">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $assignment["description"]
                                )
                            );
                            ?>

                        </p>

                    <?php endif; ?>


                    <p class="deadline">

                        ⏰ Deadline:
                        <?php
                        echo date(
                            "d M Y, h:i A",
                            strtotime(
                                $assignment["deadline"]
                            )
                        );
                        ?>

                    </p>


                    <p class="marks-info">

                        🎯 Maximum Marks:
                        <?php
                        echo $assignment["max_marks"];
                        ?>

                    </p>


                    <hr>


                    <h3>
                        👥 Student Submissions
                    </h3>


                    <?php if (count($assignment["submissions"]) > 0): ?>


                        <?php foreach (
                            $assignment["submissions"]
                            as $submission
                        ): ?>


                            <div class="submission-card">


                                <h3>

                                    👤
                                    <?php
                                    echo htmlspecialchars(
                                        $submission["student_name"]
                                    );
                                    ?>

                                </h3>


                                <p>

                                    📅 Submitted:
                                    <?php
                                    echo date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $submission["submitted_at"]
                                        )
                                    );
                                    ?>

                                </p>


                                <p
                                    class="status
                                    <?php
                                    echo $submission["status"] == "late"
                                        ? "late"
                                        : "submitted";
                                    ?>"
                                >

                                    <?php if (
                                        $submission["status"] == "late"
                                    ): ?>

                                        🔴 Submitted Late

                                    <?php else: ?>

                                        ✅ Submitted On Time

                                    <?php endif; ?>

                                </p>


                                <div class="submission-text">

                                    <strong>
                                        Student Submission:
                                    </strong>

                                    <p>

                                        <?php

                                        if (
                                            !empty(
                                                $submission[
                                                    "submission_text"
                                                ]
                                            )
                                        ) {

                                            echo nl2br(
                                                htmlspecialchars(
                                                    $submission[
                                                        "submission_text"
                                                    ]
                                                )
                                            );

                                        } else {

                                            echo "<em>No text submission.</em>";

                                        }

                                        ?>

                                    </p>


                                    <?php if (
                                        !empty(
                                            $submission["pdf_file"]
                                        )
                                    ): ?>

                                        <a
                                            class="pdf-link"
                                            href="../student/uploads/<?php echo rawurlencode($submission["pdf_file"]); ?>"
                                            target="_blank"
                                        >
                                            📄 View / Download PDF
                                        </a>

                                    <?php endif; ?>


                                    <!-- =========================
                                         GRADING
                                    ========================= -->

                                    <div class="grading-box">

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="submission_id"
                                                value="<?php echo $submission["id"]; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="batch_id"
                                                value="<?php echo $selected_batch_id; ?>"
                                            >


                                            <label>

                                                <strong>

                                                    Marks
                                                    (out of
                                                    <?php
                                                    echo $assignment[
                                                        "max_marks"
                                                    ];
                                                    ?>
                                                    )

                                                </strong>

                                            </label>


                                            <input
                                                type="number"
                                                name="marks"
                                                min="0"
                                                max="<?php echo $assignment["max_marks"]; ?>"
                                                value="<?php echo $submission["marks"] ?? ""; ?>"
                                                placeholder="Enter marks"
                                                required
                                            >


                                            <button
                                                type="submit"
                                                name="save_marks"
                                            >

                                                💾 Save Marks

                                            </button>

                                        </form>

                                    </div>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="no-submissions">

                            📭 No students have submitted
                            this assignment yet.

                        </div>


                    <?php endif; ?>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="empty">

                <h3>
                    📭 No Assignments
                </h3>

                <p>
                    No assignments have been created
                    for this batch yet.
                </p>

            </div>


        <?php endif; ?>


    <?php endif; ?>


<?php endif; ?>


</div>


</body>

</html>