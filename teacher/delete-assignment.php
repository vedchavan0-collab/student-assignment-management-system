<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: manage-assignments.php");
    exit();
}

$assignment_id = $_GET["id"];
$teacher_id = $_SESSION["user_id"];

/* Delete the assignment only if it belongs to this teacher */
$sql = "DELETE FROM assignments
        WHERE id = ?
        AND teacher_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $assignment_id,
    $teacher_id
);

if ($stmt->execute()) {

    header("Location: manage-assignments.php");
    exit();

} else {

    echo "Error deleting assignment: " . $stmt->error;

}

$stmt->close();

?>