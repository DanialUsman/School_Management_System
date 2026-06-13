<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

$stats = [
    'students' => 0,
    'teachers' => 0,
    'classes' => 0,
    'subjects' => 0
];

function getCount($conn, $table) {
    $stid = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM $table");
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC);
    return $row['CNT'];
}

$stats['students'] = getCount($conn, "STUDENTS");
$stats['teachers'] = getCount($conn, "TEACHERS");
$stats['classes'] = getCount($conn, "CLASSES");
$stats['subjects'] = getCount($conn, "SUBJECTS");

$page_title = "Admin Dashboard";
$active_page = "dashboard";
include('../includes/header.php');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $stats['students']; ?></span>
            <span class="label">Total Students</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i data-lucide="briefcase"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $stats['teachers']; ?></span>
            <span class="label">Total Teachers</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-purple">
            <i data-lucide="door-open"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $stats['classes']; ?></span>
            <span class="label">Active Classes</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i data-lucide="book"></i>
        </div>
        <div class="stat-info">
            <span class="value"><?php echo $stats['subjects']; ?></span>
            <span class="label">Subjects</span>
        </div>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Date Joined</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM (SELECT USERNAME, ROLE, TO_CHAR(CREATED_AT, 'DD-MON-YYYY') AS JOIN_DATE FROM USERS ORDER BY CREATED_AT DESC) WHERE ROWNUM <= 5";
                $stid = oci_parse($conn, $query);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['USERNAME']; ?></td>
                    <td><?php echo ucfirst($row['ROLE']); ?></td>
                    <td><?php echo $row['JOIN_DATE']; ?></td>
                    <td><span class="badge badge-present">Active</span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>


