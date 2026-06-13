<?php
session_start();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status'])) {
    $subject_id = $_POST['subject_id'];
    $attendance_date = $_POST['attendance_date'];
    $status_array = $_POST['status']; 

    foreach ($status_array as $student_id => $status) {

        $check_query = "SELECT COUNT(*) AS CNT FROM ATTENDANCE 
                        WHERE STUDENT_ID = :stud_id 
                        AND SUBJECT_ID = :sub_id 
                        AND ATTENDANCE_DATE = TO_DATE(:att_date, 'YYYY-MM-DD')";
        
        $check_stid = oci_parse($conn, $check_query);
        oci_bind_by_name($check_stid, ":stud_id", $student_id);
        oci_bind_by_name($check_stid, ":sub_id", $subject_id);
        oci_bind_by_name($check_stid, ":att_date", $attendance_date);
        oci_execute($check_stid);
        $row = oci_fetch_array($check_stid, OCI_ASSOC);

        if ($row['CNT'] > 0) {

            $update_query = "UPDATE ATTENDANCE SET STATUS = :status 
                             WHERE STUDENT_ID = :stud_id 
                             AND SUBJECT_ID = :sub_id 
                             AND ATTENDANCE_DATE = TO_DATE(:att_date, 'YYYY-MM-DD')";
            $stmt = oci_parse($conn, $update_query);
        } else {


            $id_query = "SELECT MAX(ATTENDANCE_ID) AS MAX_ID FROM ATTENDANCE";
            $id_stid = oci_parse($conn, $id_query);
            oci_execute($id_stid);
            $id_row = oci_fetch_array($id_stid, OCI_ASSOC);
            $next_id = ($id_row['MAX_ID'] ?: 0) + 1;

            $insert_query = "INSERT INTO ATTENDANCE (ATTENDANCE_ID, STUDENT_ID, SUBJECT_ID, ATTENDANCE_DATE, STATUS) 
                             VALUES (:id, :stud_id, :sub_id, TO_DATE(:att_date, 'YYYY-MM-DD'), :status)";
            $stmt = oci_parse($conn, $insert_query);
            oci_bind_by_name($stmt, ":id", $next_id);
        }

        oci_bind_by_name($stmt, ":stud_id", $student_id);
        oci_bind_by_name($stmt, ":sub_id", $subject_id);
        oci_bind_by_name($stmt, ":att_date", $attendance_date);
        oci_bind_by_name($stmt, ":status", $status);
        oci_execute($stmt);
    }

    $redirect_params = "?subject_id=$subject_id&date=$attendance_date" . ($_POST['class_id'] ? "&class_id=" . $_POST['class_id'] : "") . "&success=1";
    
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/attendance.php" . $redirect_params);
    } else {
        header("Location: attendance.php" . $redirect_params);
    }
    exit();
}
?>


