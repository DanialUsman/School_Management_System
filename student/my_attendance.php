<?php
session_start();
if ($_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

$student_id = $_SESSION['user_id'];

$stid = oci_parse($conn, "SELECT COUNT(*) AS TOTAL, 
                          SUM(CASE WHEN STATUS = 'Present' THEN 1 ELSE 0 END) AS PRESENT,
                          SUM(CASE WHEN STATUS = 'Absent' THEN 1 ELSE 0 END) AS ABSENT
                          FROM ATTENDANCE WHERE STUDENT_ID = :sid");
oci_bind_by_name($stid, ":sid", $student_id);
oci_execute($stid);
$summary = oci_fetch_array($stid, OCI_ASSOC);
if (!$summary) {
    $summary = ['TOTAL' => 0, 'PRESENT' => 0, 'ABSENT' => 0];
}
$summary = array_change_key_case($summary, CASE_UPPER);

$total = $summary['TOTAL'] ?? 0;
$present = $summary['PRESENT'] ?? 0;
$absent = $summary['ABSENT'] ?? 0;
$percent = ($total > 0) ? round(($present / $total) * 100) : 0;

$page_title = "My Attendance";
$active_page = "my_attendance";
include('../includes/header.php');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i data-lucide="percent"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $percent; ?>%</span>
            <span class="label">Attendance Rate</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $present; ?></span>
            <span class="label">Days Present</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $absent; ?></span>
            <span class="label">Days Absent</span>
        </div>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">Daily Records</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT TO_CHAR(A.ATTENDANCE_DATE, 'DD-Mon-YYYY') AS ATT_DATE, 
                          S.SUBJECT_NAME, A.STATUS 
                          FROM ATTENDANCE A 
                          JOIN SUBJECTS S ON A.SUBJECT_ID = S.SUBJECT_ID 
                          WHERE A.STUDENT_ID = :sid 
                          ORDER BY A.ATTENDANCE_DATE DESC";
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ":sid", $student_id);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['ATT_DATE']; ?></td>
                    <td><?php echo $row['SUBJECT_NAME']; ?></td>
                    <td>
                        <span class="badge <?php echo ($row['STATUS'] == 'Present') ? 'badge-present' : 'badge-absent'; ?>">
                            <?php echo $row['STATUS']; ?>
                        </span>
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


