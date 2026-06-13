<?php
session_start();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

$teacher_id = $_SESSION['user_id'];
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_marks'])) {
    $sub_id = $_POST['subject_id'];
    $marks_data = $_POST['marks'];

    foreach ($marks_data as $stu_id => $score) {
        if ($score === '') continue;

        $check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM MARKS WHERE STUDENT_ID = :sid AND SUBJECT_ID = :subid");
        oci_bind_by_name($check, ":sid", $stu_id);
        oci_bind_by_name($check, ":subid", $sub_id);
        oci_execute($check);
        $exists = oci_fetch_array($check, OCI_ASSOC)['CNT'];

        if ($exists > 0) {
            $sql = "UPDATE MARKS SET MARKS = :marks WHERE STUDENT_ID = :sid AND SUBJECT_ID = :subid";
        } else {

            $id_stid = oci_parse($conn, "SELECT MAX(MARK_ID) AS MAX_ID FROM MARKS");
            oci_execute($id_stid);
            $next_id = (oci_fetch_array($id_stid, OCI_ASSOC)['MAX_ID'] ?: 0) + 1;
            
            $sql = "INSERT INTO MARKS (MARK_ID, STUDENT_ID, SUBJECT_ID, MARKS) VALUES (:mid, :sid, :subid, :marks)";
        }

        $stid = oci_parse($conn, $sql);
        if (!$exists) oci_bind_by_name($stid, ":mid", $next_id);
        oci_bind_by_name($stid, ":marks", $score);
        oci_bind_by_name($stid, ":sid", $stu_id);
        oci_bind_by_name($stid, ":subid", $sub_id);
        oci_execute($stid);
    }
    header("Location: marks.php?subject_id=$sub_id&class_id=" . ($_GET['class_id'] ?? '') . "&success=1");
    exit();
}

$page_title = "Student Marks";
$active_page = "marks";
include('../includes/header.php');
?>

<?php if (isset($_GET['success'])): ?>
    <div class="stat-card" style="background:#dcfce7; border-color:#22c55e; margin-bottom:1.5rem; justify-content:center; padding:1rem;">
        <span style="color:#166534; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
            <i data-lucide="check-circle" style="width:20px;"></i> Marks updated successfully!
        </span>
    </div>
<?php endif; ?>

<div class="data-table-card">
    <div class="card-header" style="flex-direction: column; align-items: flex-start; gap: 1rem;">
        <h3 class="card-title">Manage Student Marks</h3>
        <form method="GET" style="display: flex; gap: 1rem; width: 100%;">
            <select name="subject_id" onchange="this.form.submit()" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                <option value="">Select Subject</option>
                <?php
                if ($_SESSION['role'] == 'admin') {
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
        </form>
    </div>
    
    <?php if ($subject_id): ?>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th style="width: 200px;">Marks (out of 100)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT S.STUDENT_ID, S.FULL_NAME, M.MARKS 
                              FROM STUDENTS S 
                              JOIN ENROLLMENTS E ON S.STUDENT_ID = E.STUDENT_ID 
                              LEFT JOIN MARKS M ON S.STUDENT_ID = M.STUDENT_ID AND M.SUBJECT_ID = :sid
                              WHERE E.SUBJECT_ID = :sid";
                    if ($class_id) $query .= " AND S.CLASS_ID = :cid";
                    $query .= " ORDER BY S.FULL_NAME";

                    $stid = oci_parse($conn, $query);
                    oci_bind_by_name($stid, ":sid", $subject_id);
                    if ($class_id) oci_bind_by_name($stid, ":cid", $class_id);
                    oci_execute($stid);
                    while ($raw_row = oci_fetch_array($stid, OCI_ASSOC)):
                        $row = array_change_key_case($raw_row, CASE_UPPER);
                        $current_marks = $row['MARKS'] ?? null;
                    ?>
                    <tr>
                        <td style="vertical-align: middle;"><?php echo $row['FULL_NAME']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <input type="number" name="marks[<?php echo $row['STUDENT_ID']; ?>]" 
                                       value="<?php echo htmlspecialchars($current_marks); ?>" min="0" max="100" step="0.01"
                                       class="mark-input"
                                       oninput="updateStatus(this)"
                                       style="background:#f1f5f9; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                                <span class="pass-fail-badge" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: <?php echo ($current_marks !== null && $current_marks >= 40) ? '#22c55e' : ($current_marks !== null ? '#ef4444' : 'transparent'); ?>;">
                                    <?php echo ($current_marks !== null) ? ($current_marks >= 40 ? 'Pass' : 'Fail') : ''; ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="padding: 1.5rem; text-align: right;">
                <button type="submit" name="save_marks" class="login-btn" style="width: auto; padding: 0.75rem 2rem; margin-top: 0;">Update Marks</button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="card-body" style="padding: 3rem; text-align: center; color:
        <i data-lucide="info" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
        <p>Please select a subject to manage student marks.</p>
    </div>
    <?php endif; ?>
</div>

<script>
function updateStatus(input) {
    const val = parseFloat(input.value);
    const badge = input.nextElementSibling;
    
    if (isNaN(val)) {
        badge.textContent = '';
        input.style.color = '#1e293b';
        return;
    }
    
    if (val >= 40) {
        badge.textContent = 'Pass';
        badge.style.color = '#22c55e';
        input.style.color = '#166534';
    } else {
        badge.textContent = 'Fail';
        badge.style.color = '#ef4444';
        input.style.color = '#991b1b';
    }
}
</script>

<?php include('../includes/footer.php'); ?>


