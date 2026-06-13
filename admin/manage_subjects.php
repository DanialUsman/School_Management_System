<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM ENROLLMENTS WHERE SUBJECT_ID = :id");
    oci_bind_by_name($check, ":id", $id);
    oci_execute($check);
    if (oci_fetch_array($check, OCI_ASSOC)['CNT'] > 0) {
        header("Location: manage_subjects.php?error=has_students");
        exit();
    }

    $del = oci_parse($conn, "DELETE FROM SUBJECTS WHERE SUBJECT_ID = :id");
    oci_bind_by_name($del, ":id", $id);
    if (oci_execute($del)) {
        header("Location: manage_subjects.php?deleted=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_subject'])) {
    $id = $_POST['subject_id'];
    $name = trim($_POST['subject_name']);
    $teacher_id = $_POST['teacher_id'];

    $update = oci_parse($conn, "UPDATE SUBJECTS SET SUBJECT_NAME = :name, TEACHER_ID = :tid WHERE SUBJECT_ID = :id");
    oci_bind_by_name($update, ":name", $name);
    oci_bind_by_name($update, ":tid", $teacher_id);
    oci_bind_by_name($update, ":id", $id);

    if (oci_execute($update)) {
        header("Location: manage_subjects.php?updated=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $teacher_id = $_POST['teacher_id'];

    $stid = oci_parse($conn, "SELECT MAX(SUBJECT_ID) AS MAX_ID FROM SUBJECTS");
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC);
    $next_id = ($row['MAX_ID'] ?: 0) + 1;

    $insert = oci_parse($conn, "INSERT INTO SUBJECTS (SUBJECT_ID, SUBJECT_NAME, TEACHER_ID) VALUES (:id, :name, :tid)");
    oci_bind_by_name($insert, ":id", $next_id);
    oci_bind_by_name($insert, ":name", $subject_name);
    oci_bind_by_name($insert, ":tid", $teacher_id);
    
    if (oci_execute($insert)) {
        header("Location: manage_subjects.php?success=1");
        exit();
    }
}

$page_title = "Manage Subjects";
$active_page = "subjects";
include('../includes/header.php');
?>

<div class="stats-grid" style="grid-template-columns: 1fr; margin-bottom: 2rem;">
    <div class="stat-card" style="display: block;">
        <h3 class="card-title" style="margin-bottom: 1.5rem;">Register New Subject</h3>
        <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Subject Name</label>
                <input type="text" name="subject_name" required placeholder="e.g. Mathematics" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Assigned Teacher</label>
                <select name="teacher_id" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                    <option value="">Select Teacher</option>
                    <?php
                    $tstid = oci_parse($conn, "SELECT TEACHER_ID, FULL_NAME FROM TEACHERS");
                    oci_execute($tstid);
                    while ($trow = oci_fetch_array($tstid, OCI_ASSOC)) {
                        echo "<option value='{$trow['TEACHER_ID']}'>{$trow['FULL_NAME']}</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" name="add_subject" class="login-btn" style="width: 100%; margin-top: 0; padding: 0.75rem;">Add Subject</button>
        </form>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">Subject List</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject Name</th>
                    <th>Teacher</th>
                    <th>Students Enrolled</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT S.SUBJECT_ID, S.SUBJECT_NAME, S.TEACHER_ID, T.FULL_NAME AS TEACHER_NAME, 
                          (SELECT COUNT(*) FROM ENROLLMENTS E WHERE E.SUBJECT_ID = S.SUBJECT_ID) AS STU_COUNT 
                          FROM SUBJECTS S 
                          LEFT JOIN TEACHERS T ON S.TEACHER_ID = T.TEACHER_ID 
                          ORDER BY S.SUBJECT_ID";
                $stid = oci_parse($conn, $query);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['SUBJECT_ID']; ?></td>
                    <td><?php echo $row['SUBJECT_NAME']; ?></td>
                    <td><?php echo $row['TEACHER_NAME'] ?: 'N/A'; ?></td>
                    <td><?php echo $row['STU_COUNT']; ?></td>
                    <td style="display: flex; gap: 0.5rem;">
                        <button onclick="editSubject(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="background:none; border:none; color:#fbbf24; cursor:pointer;"><i data-lucide="edit-2" style="width:16px;"></i></button>
                        <a href="?delete_id=<?php echo $row['SUBJECT_ID']; ?>" onclick="return confirm('Delete this subject?')" style="color:#ef4444;"><i data-lucide="trash-2" style="width:16px;"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="stat-card" style="width:100%; max-width:500px; display:block;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 class="card-title">Edit Subject</h3>
            <button onclick="document.getElementById('editModal').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="subject_id" id="edit_subject_id">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Subject Name</label>
                <input type="text" name="subject_name" id="edit_subject_name" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Assigned Teacher</label>
                <select name="teacher_id" id="edit_teacher_id" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                    <?php
                    $tstid = oci_parse($conn, "SELECT TEACHER_ID, FULL_NAME FROM TEACHERS");
                    oci_execute($tstid);
                    while ($trow = oci_fetch_array($tstid, OCI_ASSOC)) {
                        echo "<option value='{$trow['TEACHER_ID']}'>{$trow['FULL_NAME']}</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" name="edit_subject" class="login-btn" style="width: 100%; margin-top: 0;">Update Subject</button>
        </form>
    </div>
</div>

<script>
function editSubject(sub) {
    document.getElementById('edit_subject_id').value = sub.SUBJECT_ID;
    document.getElementById('edit_subject_name').value = sub.SUBJECT_NAME;
    document.getElementById('edit_teacher_id').value = sub.TEACHER_ID;
    document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php include('../includes/footer.php'); ?>


