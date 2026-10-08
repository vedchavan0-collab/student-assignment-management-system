<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION["user_id"];

$sql = "SELECT
            assignments.id,
            assignments.title,
            assignments.description,
            assignments.deadline,
            subjects.subject_name
        FROM assignments
        INNER JOIN subjects
            ON assignments.subject_id = subjects.id
        WHERE assignments.teacher_id = ?
        ORDER BY assignments.deadline ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Manage Assignments</title>

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
            padding: 30px;
        }

        .assignment-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .assignment-card h3 {
            color: #2563eb;
            margin-top: 0;
        }

        .edit-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 9px 15px;
            text-decoration: none;
            border-radius: 6px;
        }
        .delete-btn {
    display: inline-block;
    background: #dc2626;
    color: white;
    padding: 9px 15px;
    text-decoration: none;
    border-radius: 6px;
    margin-left: 8px;
}

        .empty {
            background: white;
            padding: 25px;
            border-radius: 10px;
        }
        .add-btn {
    display: inline-block;
    background: #2563eb;
    color: white;
    padding: 10px 16px;
    text-decoration: none;
    border-radius: 6px;
    margin-bottom: 20px;
}

    </style>

</head>

<body>

<div class="navbar">

    <h2>👨‍🏫 Manage Assignments</h2>
    <a href="add-assignment.php" class="add-btn">
    ➕ Add Assignment
</a>

</div>

<div class="container">

    <h1>Assignments</h1>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($assignment = $result->fetch_assoc()): ?>

            <div class="assignment-card">

                <h3>
                    <?php echo htmlspecialchars($assignment["title"]); ?>
                </h3>

                <p>
                    📚 <strong>Subject:</strong>
                    <?php echo htmlspecialchars($assignment["subject_name"]); ?>
                </p>

                <p>
                    <?php echo htmlspecialchars($assignment["description"]); ?>
                </p>

                <p>
                    ⏰ <strong>Deadline:</strong>
                    <?php echo date(
                        "d M Y, h:i A",
                        strtotime($assignment["deadline"])
                    ); ?>
                </p>

                <a
                    class="edit-btn"
                    href="edit-assignment.php?id=<?php echo $assignment["id"]; ?>"
                >
                    ✏️ Edit Assignment
                </a>
                <a
    class="delete-btn"
    href="delete-assignment.php?id=<?php echo $assignment["id"]; ?>"
    onclick="return confirm('Are you sure you want to delete this assignment?');"
>
    🗑️ Delete Assignment
</a>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <h3>No assignments found.</h3>
        </div>

    <?php endif; ?>

</div>

</body>

</html>