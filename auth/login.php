<?php
session_start();
require_once('../config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $query = "SELECT USER_ID, USERNAME, PASSWORD, ROLE FROM USERS WHERE USERNAME = :username";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ":username", $username);
    oci_execute($stid);

    $user = oci_fetch_array($stid, OCI_ASSOC);

    if ($user) {

        if (password_verify($password, $user['PASSWORD'])) {
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['role'] = $user['ROLE'];

            $redirect = "../" . $user['ROLE'] . "/dashboard.php";
            header("Location: $redirect");
            exit();
        } else {

            if ($password === $user['PASSWORD']) {
                $_SESSION['user_id'] = $user['USER_ID'];
                $_SESSION['username'] = $user['USERNAME'];
                $_SESSION['role'] = $user['ROLE'];
                
                $redirect = "../" . $user['ROLE'] . "/dashboard.php";
                header("Location: $redirect");
                exit();
            }
            header("Location: ../index.php?error=1");
        }
    } else {
        header("Location: ../index.php?error=1");
    }
    oci_free_statement($stid);
    oci_close($conn);
}
?>


