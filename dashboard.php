<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$dashboard = $_SESSION['role'] . "/dashboard.php";

if (file_exists($dashboard)) {
    header("Location: $dashboard");
    exit();
} else {

    echo "Error: Dashboard for role '{$_SESSION['role']}' not found at $dashboard";
}
?>


