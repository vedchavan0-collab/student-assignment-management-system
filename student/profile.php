<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION["user_id"];

$message = "";
$error = "";


/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);

    if ($name == "" || $email == "") {

        $error = "Name and email cannot be empty.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* Check if email already belongs to another user */

        $sql = "SELECT id
                FROM users
                WHERE email = ?
                AND id != ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "si",
            $email,
            $student_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $existing_user = $result->fetch_assoc();

        $stmt->close();


        if ($existing_user) {

            $error = "This email address is already being used.";

        } else {

            $sql = "UPDATE users
                    SET name = ?, email = ?
                    WHERE id = ?
                    AND role = 'student'";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssi",
                $name,
                $email,
                $student_id
            );

            if ($stmt->execute()) {

                $_SESSION["name"] = $name;

                $message = "Profile updated successfully.";

            } else {

                $error = "Unable to update profile.";

            }

            $stmt->close();
        }
    }
}


/* =========================
   CHANGE PASSWORD
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {

    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];


    /* Get current password */

    $sql = "SELECT password
            FROM users
            WHERE id = ?
            AND role = 'student'
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $student_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    $stmt->close();


    if (!$user || !password_verify($current_password, $user["password"])) {

        $error = "Current password is incorrect.";

    } elseif (strlen($new_password) < 6) {

        $error = "New password must be at least 6 characters.";

    } elseif ($new_password != $confirm_password) {

        $error = "New passwords do not match.";

    } else {

        $hashed_password = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );

        $sql = "UPDATE users
                SET password = ?
                WHERE id = ?
                AND role = 'student'";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "si",
            $hashed_password,
            $student_id
        );

        if ($stmt->execute()) {

            $message = "Password changed successfully.";

        } else {

            $error = "Unable to change password.";

        }

        $stmt->close();
    }
}


/* =========================
   GET STUDENT DETAILS
========================= */

$sql = "SELECT
            id,
            name,
            email,
            role,
            created_at
        FROM users
        WHERE id = ?
        AND role = 'student'
        LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

$student = $result->fetch_assoc();

$stmt->close();


if (!$student) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}


/* =========================
   GET JOINED BATCHES
========================= */

$sql = "SELECT
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

$stmt->bind_param(
    "i",
    $student_id
);

$stmt->execute();

$batches = $stmt->get_result();

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

<title>My Profile</title>


<style>

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f7fb;
}


/* =========================
   NAVBAR
========================= */

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

.back-btn {
    background: white;
    color: #2563eb;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}


/* =========================
   CONTAINER
========================= */

.container {
    max-width: 900px;
    margin: auto;
    padding: 30px;
}


/* =========================
   MESSAGE
========================= */

.success-message {
    background: #dcfce7;
    color: #166534;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.error-message {
    background: #fee2e2;
    color: #991b1b;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
}


/* =========================
   PROFILE CARD
========================= */

.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,0.08);
}

.card h2 {
    margin-top: 0;
    color: #1e3a8a;
}

.card-description {
    color: #64748b;
}


/* =========================
   PROFILE INFORMATION
========================= */

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-top: 20px;
}

.info-box {
    background: #f8fafc;
    padding: 18px;
    border-radius: 9px;
    border-left: 4px solid #2563eb;
}

.info-label {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 7px;
}

.info-value {
    color: #1e293b;
    font-size: 16px;
    font-weight: bold;
}


/* =========================
   FORM
========================= */

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-weight: bold;
    color: #334155;
}

.form-group input {
    width: 100%;
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    box-sizing: border-box;
    font-size: 15px;
}

.form-group input:focus {
    outline: none;
    border-color: #2563eb;
}


.update-btn {
    background: #2563eb;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 7px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
}

.update-btn:hover {
    background: #1d4ed8;
}


/* =========================
   BATCHES
========================= */

.batch-card {
    background: #f8fafc;
    padding: 17px;
    border-radius: 9px;
    margin-top: 12px;
    border-left: 4px solid #2563eb;
}

.batch-card h3 {
    margin-top: 0;
    color: #2563eb;
}

.batch-card p {
    margin: 7px 0;
}


/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {

    .navbar {
        padding: 15px;
    }

    .navbar h2 {
        font-size: 19px;
    }

    .container {
        padding: 20px 15px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .back-btn {
        padding: 7px 10px;
        font-size: 14px;
    }

}

</style>

</head>


<body>


<div class="navbar">

    <h2>👤 My Profile</h2>

    <a
        href="dashboard.php"
        class="back-btn"
    >
        ← Dashboard
    </a>

</div>


<div class="container">


<?php if ($message != ""): ?>

    <div class="success-message">
        ✅ <?php echo htmlspecialchars($message); ?>
    </div>

<?php endif; ?>


<?php if ($error != ""): ?>

    <div class="error-message">
        ❌ <?php echo htmlspecialchars($error); ?>
    </div>

<?php endif; ?>


<!-- =========================
     ACCOUNT INFORMATION
========================= -->

<div class="card">

    <h2>👤 Account Information</h2>

    <p class="card-description">
        Your basic student account details.
    </p>


    <div class="info-grid">

        <div class="info-box">

            <div class="info-label">
                Student ID
            </div>

            <div class="info-value">
                <?php echo $student["id"]; ?>
            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Account Role
            </div>

            <div class="info-value">
                🎓 Student
            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Account Created
            </div>

            <div class="info-value">

                <?php
                echo date(
                    "d M Y",
                    strtotime($student["created_at"])
                );
                ?>

            </div>

        </div>

    </div>

</div>


<!-- =========================
     EDIT PROFILE
========================= -->

<div class="card">

    <h2>✏️ Edit Profile</h2>

    <p class="card-description">
        Update your name and email address.
    </p>


    <form method="POST">


        <div class="form-group">

            <label>
                Full Name
            </label>

            <input
                type="text"
                name="name"
                value="<?php echo htmlspecialchars($student["name"]); ?>"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Email Address
            </label>

            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($student["email"]); ?>"
                required
            >

        </div>


        <button
            type="submit"
            name="update_profile"
            class="update-btn"
        >
            💾 Save Profile
        </button>


    </form>

</div>


<!-- =========================
     CHANGE PASSWORD
========================= -->

<div class="card">

    <h2>🔐 Change Password</h2>

    <p class="card-description">
        Keep your account secure by changing your password.
    </p>


    <form method="POST">


        <div class="form-group">

            <label>
                Current Password
            </label>

            <input
                type="password"
                name="current_password"
                required
            >

        </div>


        <div class="form-group">

            <label>
                New Password
            </label>

            <input
                type="password"
                name="new_password"
                minlength="6"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Confirm New Password
            </label>

            <input
                type="password"
                name="confirm_password"
                minlength="6"
                required
            >

        </div>


        <button
            type="submit"
            name="change_password"
            class="update-btn"
        >
            🔐 Change Password
        </button>


    </form>

</div>


<!-- =========================
     JOINED BATCHES
========================= -->

<div class="card">

    <h2>🎓 My Batches</h2>

    <p class="card-description">
        Batches you have currently joined.
    </p>


    <?php if ($batches->num_rows > 0): ?>


        <?php while ($batch = $batches->fetch_assoc()): ?>

            <div class="batch-card">

                <h3>

                    📚
                    <?php
                    echo htmlspecialchars(
                        $batch["subject_name"]
                    );
                    ?>

                </h3>

                <p>

                    <strong>Batch:</strong>
                    <?php
                    echo htmlspecialchars(
                        $batch["batch_name"]
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

            </div>

        <?php endwhile; ?>


    <?php else: ?>

        <p class="card-description">
            You have not joined any batches yet.
        </p>

    <?php endif; ?>


</div>


</div>


</body>

</html>