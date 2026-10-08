<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION["user_id"];

$sql = "SELECT
            submissions.id,
            submissions.submission_text,
            submissions.submitted_at,
            submissions.status,
            submissions.marks,
            assignments.title,
            subjects.subject_name,
            assignments.deadline,
            assignments.max_marks

        FROM submissions
        INNER JOIN assignments
            ON submissions.assignment_id = assignments.id
        INNER JOIN subjects
            ON assignments.subject_id = subjects.id
        WHERE submissions.student_id = ?
        ORDER BY submissions.submitted_at DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $student_id);

$stmt->execute();

$result = $stmt->get_result();

$submissions = [];

while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Submission History</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background: #2563eb;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 30px auto;
        }

        .submission-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .submission-card h3 {
            margin-top: 0;
            color: #1e3a8a;
        }

        .subject {
            color: #555;
        }

        .submitted {
            color: #16a34a;
            font-weight: bold;
        }

        .late {
            color: #dc2626;
            font-weight: bold;
        }

        .submission-text {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .back-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .empty {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

    </style>

</head>

<body>

    <div class="navbar">

        <strong>Student Assignment System</strong>

        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>

    </div>

    <div class="container">

        <h1>📤 Submission History</h1>

        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>

        <?php if (count($submissions) > 0): ?>

            <?php foreach ($submissions as $submission): ?>

                <div class="submission-card">

                    <h3>
                        <?php echo htmlspecialchars($submission["title"]); ?>
                    </h3>

                    <p class="subject">
                        📚 Subject:
                        <?php echo htmlspecialchars($submission["subject_name"]); ?>
                    </p>

                    <p>
                        ⏰ Deadline:
                        <?php echo date("d M Y, h:i A", strtotime($submission["deadline"])); ?>
                    </p>

                    <p>
                        📅 Submitted:
                        <?php echo date("d M Y, h:i A", strtotime($submission["submitted_at"])); ?>
                    </p>

                    <?php if ($submission["status"] == "late"): ?>

    <p class="late">
        🔴 Submitted Late
    </p>

<?php else: ?>

    <p class="submitted">
        ✅ Submitted On Time
    </p>

<?php endif; ?>

<p>
    <strong>Marks:</strong>

    <?php if ($submission["marks"] !== null): ?>

        <?php echo $submission["marks"]; ?>
        /
        <?php echo $submission["max_marks"]; ?>

    <?php else: ?>

        Not Graded Yet

    <?php endif; ?>

</p>

                    <div class="submission-text">

                        <strong>Your Submission:</strong>

                        <p>
                            <?php echo nl2br(htmlspecialchars($submission["submission_text"])); ?>
                        </p>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">

                <h3>No submissions yet.</h3>

                <p>
                    You haven't submitted any assignments.
                </p>

            </div>

        <?php endif; ?>

    </div>

</body>

</html>