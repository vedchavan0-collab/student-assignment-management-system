<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION["user_id"];


/* =========================
   GET STUDENT BATCHES
========================= */

$sql = "SELECT
            batches.id,
            batches.batch_name,
            batches.batch_code,
            subjects.subject_name

        FROM student_batches

        INNER JOIN batches
            ON student_batches.batch_id = batches.id

        INNER JOIN subjects
            ON batches.subject_id = subjects.id

        WHERE student_batches.student_id = ?

        ORDER BY batches.batch_name ASC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $student_id);

$stmt->execute();

$batches = $stmt->get_result();

$stmt->close();


/* =========================
   GET REMINDERS
========================= */

$sql = "SELECT DISTINCT
            assignments.id,
            assignments.title,
            assignments.deadline

        FROM assignments

        INNER JOIN student_batches
            ON student_batches.batch_id = assignments.batch_id
            AND student_batches.student_id = ?

        WHERE assignments.batch_id IS NOT NULL

        ORDER BY assignments.deadline ASC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $student_id);

$stmt->execute();

$result = $stmt->get_result();

$reminders = [];

while ($assignment = $result->fetch_assoc()) {

    $deadline_time = strtotime($assignment["deadline"]);
    $current_time = time();

    $remaining_time = $deadline_time - $current_time;

    if ($remaining_time < 0) {

        $reminders[] = [
            "title" => $assignment["title"],
            "message" => "Deadline has passed.",
            "type" => "danger"
        ];

    } elseif ($remaining_time <= 24 * 60 * 60) {

        $reminders[] = [
            "title" => $assignment["title"],
            "message" => "Deadline is within 24 hours!",
            "type" => "warning"
        ];

    } elseif ($remaining_time <= 3 * 24 * 60 * 60) {

        $reminders[] = [
            "title" => $assignment["title"],
            "message" => "Deadline is within 3 days.",
            "type" => "info"
        ];
    }
}

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Dashboard</title>

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
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar h2 {
    margin: 0;
}

.nav-buttons {
    display: flex;
    align-items: center;
}

.history-btn {
    display: inline-block;
    background: white;
    color: #2563eb;
    padding: 8px 14px;
    text-decoration: none;
    border-radius: 6px;
    margin-right: 10px;
    font-weight: bold;
}

.logout {
    background: white;
    color: #2563eb;
    padding: 8px 15px;
    text-decoration: none;
    border-radius: 6px;
}

.container {
    max-width: 1000px;
    margin: auto;
    padding: 30px;
}

.welcome {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.welcome h1 {
    margin-top: 0;
    color: #1e3a8a;
}

.batch-section {
    background: white;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.batch-section h2 {
    margin-top: 0;
    color: #1e3a8a;
}

.batch-card {
    display: block;
    background: #f8fafc;
    padding: 18px;
    border-radius: 10px;
    margin-top: 15px;
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

.view-batch {
    color: #2563eb;
    font-weight: bold;
}

.join-batch-btn {
    display: inline-block;
    margin-top: 18px;
    background: #2563eb;
    color: white;
    padding: 10px 16px;
    border-radius: 7px;
    text-decoration: none;
    font-weight: bold;
}

.join-batch-btn:hover {
    background: #1d4ed8;
}

.reminders {
    margin-top: 25px;
}

.reminder {
    padding: 15px 20px;
    margin-bottom: 12px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.reminder p {
    margin-bottom: 0;
}

.warning {
    border-left: 5px solid #f59e0b;
}

.danger {
    border-left: 5px solid #dc2626;
}

.info {
    border-left: 5px solid #2563eb;
}

.success {
    border-left: 5px solid #16a34a;
}

.no-batches {
    background: #f8fafc;
    padding: 20px;
    border-radius: 10px;
    color: #64748b;
}


/* =========================
   POPUP NOTIFICATION
========================= */

.notification-popup {
    position: fixed;
    top: 85px;
    right: 25px;
    width: 340px;
    max-width: calc(100% - 50px);
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.20);
    z-index: 9999;
    overflow: hidden;
    animation: notificationSlide 0.4s ease;
    border-left: 5px solid #2563eb;
}

.notification-title {
    background: #2563eb;
    color: white;
    padding: 15px 45px 15px 18px;
    font-size: 17px;
    font-weight: bold;
}

.notification-close {
    position: absolute;
    top: 8px;
    right: 10px;
    background: transparent;
    color: white;
    border: none;
    font-size: 25px;
    cursor: pointer;
    line-height: 1;
}

.notification-item {
    padding: 15px 18px;
    border-bottom: 1px solid #e5e7eb;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item strong {
    color: #1e3a8a;
    font-size: 15px;
}

.notification-item p {
    margin: 7px 0 0;
    color: #64748b;
    font-size: 14px;
}

@keyframes notificationSlide {

    from {
        opacity: 0;
        transform: translateX(30px);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }

}


/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {

    .navbar {
        padding: 15px;
    }

    .navbar h2 {
        font-size: 18px;
    }

    .container {
        padding: 20px 15px;
    }

    .notification-popup {
        top: 75px;
        right: 15px;
        width: 320px;
    }

}

</style>

</head>

<body>


<div class="navbar">

    <h2>Student Dashboard</h2>

    <div class="nav-buttons">

    <a class="history-btn" href="profile.php">
        👤 My Profile
    </a>

    <a class="history-btn" href="submission-history.php">
        📤 Submission History
    </a>

    <a class="logout" href="../logout.php">
        Logout
    </a>

</div>

</div>


<!-- =========================
     DEADLINE POPUP
========================= -->

<?php if (count($reminders) > 0): ?>

<div id="notificationPopup" class="notification-popup">

    <div class="notification-title">

        🔔 Deadline Reminder

        <button
            class="notification-close"
            onclick="closeNotification()"
            title="Close"
        >
            ×
        </button>

    </div>


    <?php foreach ($reminders as $reminder): ?>

        <div class="notification-item">

            <strong>
                <?php echo htmlspecialchars($reminder["title"]); ?>
            </strong>

            <p>
                <?php echo htmlspecialchars($reminder["message"]); ?>
            </p>

        </div>

    <?php endforeach; ?>

</div>

<?php endif; ?>


<div class="container">


    <div class="welcome">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($_SESSION["name"]); ?>! 👋
        </h1>

        <p>
            Select one of your batches to view its assignments.
        </p>

    </div>


    <div class="batch-section">

        <h2>🎓 My Batches</h2>


        <?php if ($batches->num_rows > 0): ?>

            <?php while ($batch = $batches->fetch_assoc()): ?>

                <a
                    href="batch.php?id=<?php echo $batch["id"]; ?>"
                    class="batch-card"
                >

                    <h3>
                        📚
                        <?php echo htmlspecialchars($batch["subject_name"]); ?>
                    </h3>

                    <p>
                        <strong>Batch:</strong>
                        <?php echo htmlspecialchars($batch["batch_name"]); ?>
                    </p>

                    <p>
                        <strong>Batch Code:</strong>
                        <?php echo htmlspecialchars($batch["batch_code"]); ?>
                    </p>

                    <p class="view-batch">
                        👀 View Assignments →
                    </p>

                </a>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="no-batches">

                <h3>📭 No Batches Joined</h3>

                <p>
                    Join a batch using the code provided by your teacher.
                </p>

            </div>

        <?php endif; ?>


        <a
            href="join-batch.php"
            class="join-batch-btn"
        >
            ➕ Join Another Batch
        </a>

    </div>


    <div class="reminders">

        <h2>🔔 Reminders</h2>


        <?php if (count($reminders) > 0): ?>

            <?php foreach ($reminders as $reminder): ?>

                <div class="reminder <?php echo $reminder["type"]; ?>">

                    <strong>
                        <?php echo htmlspecialchars($reminder["title"]); ?>
                    </strong>

                    <p>
                        <?php echo $reminder["message"]; ?>
                    </p>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="reminder success">

                🎉 No urgent deadlines right now.

            </div>

        <?php endif; ?>

    </div>


</div>


<script>

function closeNotification() {

    const popup =
        document.getElementById("notificationPopup");

    if (popup) {
        popup.style.display = "none";
    }

}

</script>

</body>

</html>