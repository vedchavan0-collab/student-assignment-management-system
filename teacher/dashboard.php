<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher") {
    header("Location: ../login.php");
    exit();
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

    <title>Teacher Dashboard</title>


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


        .logout {
            background: white;
            color: #2563eb;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 6px;
        }


        .container {
            padding: 30px;
            max-width: 1200px;
            margin: auto;
        }


        .welcome {
            background: white;
            padding: 25px;
            border-radius: 12px;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.08);
        }


        .welcome h1 {
            color: #1e3a8a;
        }


        .cards {
            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(240px, 1fr));

            gap: 20px;

            margin-top: 25px;
        }


        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.08);
        }


        .card h3 {
            color: #2563eb;
            margin-top: 0;
        }


        .card p {
            color: #64748b;
            min-height: 40px;
        }


        .card a {
            display: inline-block;

            margin-top: 10px;

            background: #2563eb;
            color: white;

            padding: 10px 14px;

            text-decoration: none;

            border-radius: 6px;

            font-weight: bold;
        }


        .card a:hover {
            background: #1d4ed8;
        }


        @media (max-width: 600px) {

            .container {
                padding: 20px 15px;
            }

            .navbar {
                padding: 15px;
            }

            .navbar h2 {
                font-size: 19px;
            }

        }

    </style>

</head>


<body>


<div class="navbar">

    <h2>Teacher Dashboard</h2>

    <a
        class="logout"
        href="../logout.php"
    >
        Logout
    </a>

</div>


<div class="container">


    <div class="welcome">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($_SESSION["name"]); ?>! 👋
        </h1>

        <p>
            Manage subjects, batches, assignments
            and student submissions.
        </p>

    </div>


    <div class="cards">


        <!-- SUBJECTS -->

        <div class="card">

            <h3>
                📚 Subjects
            </h3>

            <p>
                Manage your subjects.
            </p>

            <a href="add-subject.php">
                Manage Subjects
            </a>

        </div>


        <!-- BATCHES -->

        <div class="card">

            <h3>
                🎓 Batches
            </h3>

            <p>
                Create batches and generate
                joining codes for students.
            </p>

            <a href="add-batch.php">
                Manage Batches
            </a>

        </div>


        <!-- ASSIGNMENTS -->

        <div class="card">

            <h3>
                📝 Assignments
            </h3>

            <p>
                Create and manage assignment
                deadlines.
            </p>

            <a href="manage-assignments.php">
                Manage Assignments
            </a>

        </div>


        <!-- SUBMISSIONS -->

        <div class="card">

            <h3>
                📤 Submissions
            </h3>

            <p>
                View student submissions
                and give marks.
            </p>

            <a href="view-submissions.php">
                View Submissions
            </a>

        </div>


    </div>


</div>


</body>

</html>