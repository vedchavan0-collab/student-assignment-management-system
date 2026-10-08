<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit();
}

$assignment_id = (int) $_GET["id"];
$student_id = $_SESSION["user_id"];

$message = "";
$assignment = null;
$already_submitted = false;


/* =========================
   GET ASSIGNMENT
   AND VERIFY STUDENT BATCH
========================= */

$sql = "SELECT
            assignments.id,
            assignments.title,
            assignments.description,
            assignments.deadline,
            assignments.max_marks,
            assignments.batch_id,
            subjects.subject_name

        FROM assignments

        INNER JOIN subjects
            ON assignments.subject_id = subjects.id

        INNER JOIN student_batches
            ON assignments.batch_id = student_batches.batch_id

        WHERE assignments.id = ?
        AND student_batches.student_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $assignment_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

$assignment = $result->fetch_assoc();

$stmt->close();


/* =========================
   SECURITY CHECK
========================= */

if (!$assignment) {
    die("You are not authorized to access this assignment.");
}


/* =========================
   CHECK EXISTING SUBMISSION
========================= */

$sql = "SELECT id
        FROM submissions
        WHERE assignment_id = ?
        AND student_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $assignment_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $already_submitted = true;
}

$stmt->close();


/* =========================
   SUBMIT ASSIGNMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* Double-check duplicate submission */

    if ($already_submitted) {

        $message = "You have already submitted this assignment.";

    } else {

        $submission_text = trim(
            $_POST["submission_text"] ?? ""
        );

        $pdf_file = $_FILES["pdf_file"] ?? null;


        /* =========================
           CHECK EMPTY SUBMISSION
        ========================= */

        if (
            empty($submission_text) &&
            (
                !$pdf_file ||
                $pdf_file["error"] == UPLOAD_ERR_NO_FILE
            )
        ) {

            $message =
                "Please enter your submission or upload a PDF.";

        } else {

            /* =========================
               CHECK DEADLINE
            ========================= */

            $deadline = strtotime(
                $assignment["deadline"]
            );

            $current_time = time();

            if ($current_time > $deadline) {

                $status = "late";

            } else {

                $status = "submitted";
            }


            /* =========================
               PDF UPLOAD
            ========================= */

            $file_name = null;

            if (
                $pdf_file &&
                $pdf_file["error"] != UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $pdf_file["error"] != UPLOAD_ERR_OK
                ) {

                    $message =
                        "There was an error uploading the PDF.";

                } elseif (
                    $pdf_file["size"] >
                    5 * 1024 * 1024
                ) {

                    $message =
                        "PDF file must be smaller than 5 MB.";

                } elseif (
                    strtolower(
                        pathinfo(
                            $pdf_file["name"],
                            PATHINFO_EXTENSION
                        )
                    ) != "pdf"
                ) {

                    $message =
                        "Only PDF files are allowed.";

                } else {

                    /*
                     * Create a safer unique filename.
                     */

                    $file_name =
                        time() .
                        "_" .
                        uniqid() .
                        "_" .
                        basename(
                            $pdf_file["name"]
                        );

                    $upload_path =
                        "uploads/" . $file_name;


                    if (
                        !move_uploaded_file(
                            $pdf_file["tmp_name"],
                            $upload_path
                        )
                    ) {

                        $message =
                            "Failed to save the PDF.";

                        $file_name = null;
                    }
                }
            }


            /* =========================
               INSERT SUBMISSION
            ========================= */

            if ($message == "") {

                $sql = "INSERT INTO submissions
                        (
                            assignment_id,
                            student_id,
                            submission_text,
                            status,
                            pdf_file
                        )

                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "iisss",
                    $assignment_id,
                    $student_id,
                    $submission_text,
                    $status,
                    $file_name
                );


                if ($stmt->execute()) {

                    $message =
                        "Assignment submitted successfully! 🎉";

                    $already_submitted = true;

                } else {

                    $message =
                        "Error: " . $stmt->error;
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

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Submit Assignment</title>


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
    width: 650px;
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

.info {
    background: #f1f5f9;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

textarea {
    width: 100%;
    height: 200px;
    padding: 12px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
    resize: vertical;
}

input[type="file"] {
    width: 100%;
    margin-top: 10px;
    padding: 10px;
    box-sizing: border-box;
}

button {
    width: 100%;
    margin-top: 15px;
    padding: 13px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
}

.message {
    background: #dcfce7;
    color: #15803d;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 6px;
}

.already-submitted {
    background: #eff6ff;
    color: #1e40af;
    padding: 18px;
    border-radius: 8px;
    margin-top: 20px;
    font-weight: bold;
}

.back-btn {
    display: inline-block;
    margin-top: 15px;
    background: #64748b;
    color: white;
    padding: 10px 16px;
    text-decoration: none;
    border-radius: 6px;
}

</style>

</head>


<body>


<div class="navbar">

    <h2>📤 Submit Assignment</h2>

</div>


<div class="container">


    <h2>

        <?php
        echo htmlspecialchars(
            $assignment["title"]
        );
        ?>

    </h2>


    <div class="info">

        <p>

            📚 <strong>Subject:</strong>

            <?php
            echo htmlspecialchars(
                $assignment["subject_name"]
            );
            ?>

        </p>


        <p>

            ⏰ <strong>Deadline:</strong>

            <?php
            echo date(
                "d M Y, h:i A",
                strtotime(
                    $assignment["deadline"]
                )
            );
            ?>

        </p>


        <p>

            🎯 <strong>Maximum Marks:</strong>

            <?php
            echo $assignment["max_marks"];
            ?>

        </p>


        <p>

            <?php
            echo nl2br(
                htmlspecialchars(
                    $assignment["description"]
                )
            );
            ?>

        </p>

    </div>


    <?php if (!empty($message)): ?>

        <div class="message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($already_submitted): ?>

        <div class="already-submitted">

            ✅ You have already submitted this assignment.

            <br><br>

            You cannot submit it again.

        </div>


        <a
            href="submission-history.php"
            class="back-btn"
        >
            📤 View Submission History
        </a>


    <?php else: ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <label>

                <strong>Your Submission</strong>

            </label>


            <br><br>


            <textarea
                name="submission_text"
                placeholder="Write your assignment submission here..."
            ></textarea>


            <br><br>


            <label>

                <strong>📄 Upload PDF</strong>

            </label>


            <input
                type="file"
                name="pdf_file"
                accept=".pdf,application/pdf"
            >


            <button type="submit">

                Submit Assignment

            </button>


        </form>


    <?php endif; ?>


</div>


</body>

</html>