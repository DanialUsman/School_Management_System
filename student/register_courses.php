<?php
session_start();
if ($_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

$student_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll_subject'])) {
    $subject_id = $_POST['subject_id'];

    $check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM ENROLLMENTS WHERE STUDENT_ID = :sid AND SUBJECT_ID = :subid");
    oci_bind_by_name($check, ":sid", $student_id);
    oci_bind_by_name($check, ":subid", $subject_id);
    oci_execute($check);
    $exists = oci_fetch_array($check, OCI_ASSOC)['CNT'];

    if ($exists == 0) {
        $id_stid = oci_parse($conn, "SELECT MAX(ENROLLMENT_ID) AS MAX_ID FROM ENROLLMENTS");
        oci_execute($id_stid);
        $next_id = (oci_fetch_array($id_stid, OCI_ASSOC)['MAX_ID'] ?: 0) + 1;

        $enroll = oci_parse($conn, "INSERT INTO ENROLLMENTS (ENROLLMENT_ID, STUDENT_ID, SUBJECT_ID) VALUES (:eid, :sid, :subid)");
        oci_bind_by_name($enroll, ":eid", $next_id);
        oci_bind_by_name($enroll, ":sid", $student_id);
        oci_bind_by_name($enroll, ":subid", $subject_id);
        
        if (oci_execute($enroll)) {
            header("Location: register_courses.php?success=1");
            exit();
        }
    } else {
        $error = "You are already enrolled in this course.";
    }
}

if (isset($_GET['unenroll'])) {
    $eid = $_GET['unenroll'];
    $del = oci_parse($conn, "DELETE FROM ENROLLMENTS WHERE ENROLLMENT_ID = :eid AND STUDENT_ID = :sid");
    oci_bind_by_name($del, ":eid", $eid);
    oci_bind_by_name($del, ":sid", $student_id);
    if (oci_execute($del)) {
        header("Location: register_courses.php?removed=1");
        exit();
    }
}

$page_title = "Course Registration";
$active_page = "course_reg";
include('../includes/header.php');
?>

<div class="stats-grid" style="grid-template-columns: 1fr; margin-bottom: 2rem;">
    <div class="stat-card" style="display: block;">
        <h3 class="card-title" style="margin-bottom: 1.5rem;">Join a New Course</h3>
        
        <?php if (isset($error)): ?>
            <div class="error-message" style="display: block; margin-bottom: 1rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" style="display: flex; gap: 1rem;">
            <select name="subject_id" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                <option value="">Select a Subject to Register</option>
                <?php

                $sq = "SELECT SUBJECT_ID, SUBJECT_NAME 
                       FROM SUBJECTS 
                       WHERE SUBJECT_ID NOT IN (SELECT SUBJECT_ID FROM ENROLLMENTS WHERE STUDENT_ID = :sid)
                       ORDER BY SUBJECT_NAME";
                $st = oci_parse($conn, $sq);
                oci_bind_by_name($st, ":sid", $student_id);
                oci_execute($st);
                while ($srow = oci_fetch_array($st, OCI_ASSOC)) {
                    echo "<option value='{$srow['SUBJECT_ID']}'>{$srow['SUBJECT_NAME']}</option>";
                }
                ?>
            </select>
            <button type="submit" name="enroll_subject" class="login-btn" style="width: auto; margin-top: 0; padding: 0.75rem 2rem;">Enroll Now</button>
        </form>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">My Enrolled Courses</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Subject Name</th>
                    <th>Teacher</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT E.ENROLLMENT_ID, S.SUBJECT_NAME, T.FULL_NAME AS TEACHER_NAME 
                          FROM ENROLLMENTS E 
                          JOIN SUBJECTS S ON E.SUBJECT_ID = S.SUBJECT_ID 
                          LEFT JOIN TEACHERS T ON S.TEACHER_ID = T.TEACHER_ID 
                          WHERE E.STUDENT_ID = :sid 
                          ORDER BY S.SUBJECT_NAME";
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ":sid", $student_id);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['SUBJECT_NAME']; ?></td>
                    <td><?php echo $row['TEACHER_NAME'] ?: 'N/A'; ?></td>
                    <td>
                        <a href="?unenroll=<?php echo $row['ENROLLMENT_ID']; ?>" onclick="return confirm('Are you sure you want to drop this course?')" 
                           style="color:#ef4444; text-decoration:none; font-weight:500; font-size:0.875rem; display:flex; align-items:center; gap:0.25rem;">
                           <i data-lucide="trash-2" style="width:14px;"></i> Drop Course
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (oci_num_rows($stid) == 0): ?>
                    <tr>
                        <td colspan="3" style="text-align:center; padding: 2rem; color:
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>


