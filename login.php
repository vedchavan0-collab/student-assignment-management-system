<?php

session_start();

require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] == "student") {
                header("Location: student/dashboard.php");
                exit();
            } else {
                header("Location: teacher/dashboard.php");
                exit();
            }

        } else {
            $message = "Incorrect password.";
        }

    } else {
        $message = "Account not found.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - Assignment System</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f2f6fc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            width: 400px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #2563eb;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 8px 0 15px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            text-align: center;
            margin-bottom: 15px;
            color: #dc2626;
        }

    </style>
</head>

<body>

<div class="container">

    <h2>Login</h2>

    <?php if (!empty($message)): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Email</label>
        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <button type="submit">Login</button>

    </form>

</div>

</body>

</html>