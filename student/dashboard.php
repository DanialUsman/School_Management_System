<?php
session_start();
if ($_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

$student_id = $_SESSION['user_id'];

$stid = oci_parse($conn, "SELECT COUNT(*) AS TOTAL, SUM(CASE WHEN STATUS = 'Present' THEN 1 ELSE 0 END) AS PRESENT 
                          FROM ATTENDANCE WHERE STUDENT_ID = :sid");
oci_bind_by_name($stid, ":sid", $student_id);
oci_execute($stid);
$att = oci_fetch_array($stid, OCI_ASSOC);
$att_percent = ($att['TOTAL'] > 0) ? round(($att['PRESENT'] / $att['TOTAL']) * 100) : 0;

$page_title = "Student Dashboard";
$active_page = "dashboard";
include('../includes/header.php');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i data-lucide="percent"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $att_percent; ?>%</span>
            <span class="label">Attendance</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-purple">
            <i data-lucide="book-open"></i>
        </div>
        <div class="stat-info">
            <?php
            $stid = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM ENROLLMENTS WHERE STUDENT_ID = :sid");
            oci_bind_by_name($stid, ":sid", $student_id);
            oci_execute($stid);
            $sub_count = oci_fetch_array($stid, OCI_ASSOC)['CNT'];
            ?>
            <span class="value"><?php echo $sub_count; ?></span>
            <span class="label">My Courses</span>
        </div>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">Recent Marks</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Marks</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT S.SUBJECT_NAME, M.MARKS 
                          FROM MARKS M 
                          JOIN SUBJECTS S ON M.SUBJECT_ID = S.SUBJECT_ID 
                          WHERE M.STUDENT_ID = :sid";
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ":sid", $student_id);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['SUBJECT_NAME']; ?></td>
                    <td><?php echo $row['MARKS']; ?></td>
                    <td>
                        <?php if ($row['MARKS'] >= 40): ?>
                            <span class="badge badge-present">Pass</span>
                        <?php else: ?>
                            <span class="badge badge-absent">Fail</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>


