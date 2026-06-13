<?php
session_start();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

$teacher_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin');
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : null;
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$page_title = "Attendance Management";
$active_page = "attendance";
include('../includes/header.php');
?>

<?php if (isset($_GET['success'])): ?>
    <div class="stat-card" style="background:#dcfce7; border-color:#22c55e; margin-bottom:1.5rem; justify-content:center; padding:1rem;">
        <span style="color:#166534; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
            <i data-lucide="check-circle" style="width:20px;"></i> Attendance records saved successfully!
        </span>
    </div>
<?php endif; ?>

<div class="data-table-card">
    <div class="card-header" style="flex-direction: column; align-items: flex-start; gap: 1rem;">
        <h3 class="card-title"><?php echo $is_admin ? "Manage Attendance (Admin View)" : "Mark Attendance"; ?></h3>
        <form method="GET" style="display: flex; gap: 1rem; width: 100%;">
            <select name="subject_id" onchange="this.form.submit()" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                <option value="">Select Subject</option>
                <?php
                if ($is_admin) {
                        $sq = "SELECT SUBJECT_ID, SUBJECT_NAME FROM SUBJECTS ORDER BY SUBJECT_NAME";
                        $st = oci_parse($conn, $sq);
                } else {
                        $sq = "SELECT SUBJECT_ID, SUBJECT_NAME FROM SUBJECTS WHERE TEACHER_ID = :tid ORDER BY SUBJECT_NAME";
                        $st = oci_parse($conn, $sq);
                        oci_bind_by_name($st, ":tid", $teacher_id);
                }
                oci_execute($st);
                while ($srow = oci_fetch_array($st, OCI_ASSOC)) {
                    $row_s = array_change_key_case($srow, CASE_UPPER);
                    $selected = ($subject_id == $row_s['SUBJECT_ID']) ? 'selected' : '';
                    echo "<option value='{$row_s['SUBJECT_ID']}' $selected>{$row_s['SUBJECT_NAME']}</option>";
                }
                ?>
            </select>

            <select name="class_id" onchange="this.form.submit()" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                <option value="">All Classes</option>
                <?php
                $cq = "SELECT CLASS_ID, CLASS_NAME FROM CLASSES ORDER BY CLASS_NAME";
                $cst = oci_parse($conn, $cq);
                oci_execute($cst);
                while ($crow = oci_fetch_array($cst, OCI_ASSOC)) {
                    $row_c = array_change_key_case($crow, CASE_UPPER);
                    $selected = ($class_id == $row_c['CLASS_ID']) ? 'selected' : '';
                    echo "<option value='{$row_c['CLASS_ID']}' $selected>{$row_c['CLASS_NAME']}</option>";
                }
                ?>
            </select>
            <input type="date" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
        </form>
    </div>
    
    <?php if ($subject_id): ?>
    <div class="card-body">
        <form action="../teacher/save_attendance.php" method="POST">
            <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT S.STUDENT_ID, S.FULL_NAME 
                              FROM STUDENTS S 
                              JOIN ENROLLMENTS E ON S.STUDENT_ID = E.STUDENT_ID 
                              WHERE E.SUBJECT_ID = :sid";
                    if ($class_id) $query .= " AND S.CLASS_ID = :cid";
                    $query .= " ORDER BY S.FULL_NAME";

                    $stid = oci_parse($conn, $query);
                    oci_bind_by_name($stid, ":sid", $subject_id);
                    if ($class_id) oci_bind_by_name($stid, ":cid", $class_id);
                    oci_execute($stid);
                    while ($raw_row = oci_fetch_array($stid, OCI_ASSOC)):
                        $row = array_change_key_case($raw_row, CASE_UPPER);
                    ?>
                    <tr>
                        <td><?php echo $row['FULL_NAME']; ?></td>
                        <td>
                            <label style="margin-right: 1rem; cursor: pointer;">
                                <input type="radio" name="status[<?php echo $row['STUDENT_ID']; ?>]" value="Present" checked> <span style="color:#22c55e;">Present</span>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="status[<?php echo $row['STUDENT_ID']; ?>]" value="Absent"> <span style="color:#ef4444;">Absent</span>
                            </label>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="padding: 1.5rem; text-align: right;">
                <button type="submit" class="login-btn" style="width: auto; padding: 0.75rem 2rem;">Save Attendance</button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="card-body" style="padding: 3rem; text-align: center; color:
        <i data-lucide="info" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
        <p>Please select a subject to mark attendance.</p>
    </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>


