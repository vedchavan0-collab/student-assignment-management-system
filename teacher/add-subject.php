<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher") {
    header("Location: ../login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $subject_name = trim($_POST["subject_name"]);
    $teacher_id = $_SESSION["user_id"];

    if (empty($subject_name)) {

        $message = "Please enter a subject name.";

    } else {

        $sql = "INSERT INTO subjects (subject_name, teacher_id)
                VALUES (?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "si",
            $subject_name,
            $teacher_id
        );

        if ($stmt->execute()) {
            $message = "Subject added successfully! 🎉";
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

    <title>Add Subject</title>

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
            width: 450px;
            max-width: 90%;
            margin: 60px auto;
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
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 13px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .message {
            margin-bottom: 15px;
            color: #15803d;
        }

    </style>

</head>

<body>

<div class="navbar">
    <h2>Teacher - Add Subject</h2>
</div>

<div class="container">

    <h2>Add New Subject</h2>

    <?php if (!empty($message)): ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label>Subject Name</label>

        <input
            type="text"
            name="subject_name"
            placeholder="Example: Database Management Systems"
            required
        >

        <button type="submit">
            Add Subject
        </button>

    </form>

</div>

</body>

</html>