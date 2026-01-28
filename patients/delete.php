<?php
require_once '../config/db.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: list.php?message=Invalid patient ID&type=error");
    exit();
}

$id = (int)$_GET['id'];

// Check if patient exists
$checkSql = "SELECT id FROM patients WHERE id = ?";
$stmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) == 0) {
    header("Location: list.php?message=Patient not found&type=error");
    exit();
}
mysqli_stmt_close($stmt);

// Delete the patient
$deleteSql = "DELETE FROM patients WHERE id = ?";
$stmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: list.php?message=Patient deleted successfully&type=success");
} else {
    header("Location: list.php?message=Error deleting patient&type=error");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit();
?>