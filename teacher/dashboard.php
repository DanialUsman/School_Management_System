<?php
session_start();
if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

$teacher_id = $_SESSION['user_id'];

$stid = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM SUBJECTS WHERE TEACHER_ID = :tid");
oci_bind_by_name($stid, ":tid", $teacher_id);
oci_execute($stid);
$subj_count = oci_fetch_array($stid, OCI_ASSOC)['CNT'];

$page_title = "Teacher Dashboard";
$active_page = "dashboard";
include('../includes/header.php');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i data-lucide="book-open"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $subj_count; ?></span>
            <span class="label">My Subjects</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-info">
            <span class="value">95%</span>
            <span class="label">Avg Attendance</span>
        </div>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">My Subjects</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Subject Name</th>
                    <th>Enrolled Students</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT S.SUBJECT_ID, S.SUBJECT_NAME, (SELECT COUNT(*) FROM ENROLLMENTS E WHERE E.SUBJECT_ID = S.SUBJECT_ID) AS STU_COUNT 
                          FROM SUBJECTS S WHERE S.TEACHER_ID = :tid";
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ":tid", $teacher_id);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['SUBJECT_NAME']; ?></td>
                    <td><?php echo $row['STU_COUNT']; ?></td>
                    <td><a href="attendance.php?subject_id=<?php echo $row['SUBJECT_ID']; ?>" class="badge badge-present" style="text-decoration:none">View Attendance</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>


