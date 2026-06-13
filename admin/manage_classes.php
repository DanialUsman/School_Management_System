<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM STUDENTS WHERE CLASS_ID = :id");
    oci_bind_by_name($check, ":id", $id);
    oci_execute($check);
    if (oci_fetch_array($check, OCI_ASSOC)['CNT'] > 0) {
        header("Location: manage_classes.php?error=has_students");
        exit();
    }

    $del = oci_parse($conn, "DELETE FROM CLASSES WHERE CLASS_ID = :id");
    oci_bind_by_name($del, ":id", $id);
    if (oci_execute($del)) {
        header("Location: manage_classes.php?deleted=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_class'])) {
    $id = $_POST['class_id'];
    $name = trim($_POST['class_name']);

    $update = oci_parse($conn, "UPDATE CLASSES SET CLASS_NAME = :name WHERE CLASS_ID = :id");
    oci_bind_by_name($update, ":name", $name);
    oci_bind_by_name($update, ":id", $id);

    if (oci_execute($update)) {
        header("Location: manage_classes.php?updated=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);

    $stid = oci_parse($conn, "SELECT MAX(CLASS_ID) AS MAX_ID FROM CLASSES");
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC);
    $next_id = ($row['MAX_ID'] ?: 0) + 1;

    $insert = oci_parse($conn, "INSERT INTO CLASSES (CLASS_ID, CLASS_NAME) VALUES (:id, :name)");
    oci_bind_by_name($insert, ":id", $next_id);
    oci_bind_by_name($insert, ":name", $class_name);
    
    if (oci_execute($insert)) {
        header("Location: manage_classes.php?success=1");
        exit();
    }
}

$page_title = "Manage Classes";
$active_page = "classes";
include('../includes/header.php');
?>

<div class="stats-grid" style="grid-template-columns: 1fr;">
    <div class="stat-card" style="display: block;">
        <h3 class="card-title" style="margin-bottom: 1.5rem;">Add New Class</h3>
        <form method="POST" style="display: flex; gap: 1rem;">
            <input type="text" name="class_name" required placeholder="e.g. Grade 10-A" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            <button type="submit" name="add_class" class="login-btn" style="width: auto; margin-top: 0; padding: 0.75rem 2rem;">Add Class</button>
        </form>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">Existing Classes</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Class ID</th>
                    <th>Class Name</th>
                    <th>Students Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT C.CLASS_ID, C.CLASS_NAME, (SELECT COUNT(*) FROM STUDENTS S WHERE S.CLASS_ID = C.CLASS_ID) AS STU_COUNT FROM CLASSES C ORDER BY C.CLASS_ID";
                $stid = oci_parse($conn, $query);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['CLASS_ID']; ?></td>
                    <td><?php echo $row['CLASS_NAME']; ?></td>
                    <td><?php echo $row['STU_COUNT']; ?></td>
                    <td style="display: flex; gap: 0.5rem;">
                        <button onclick="editClass(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="background:none; border:none; color:#fbbf24; cursor:pointer;"><i data-lucide="edit-2" style="width:16px;"></i></button>
                        <a href="?delete_id=<?php echo $row['CLASS_ID']; ?>" onclick="return confirm('Delete this class?')" style="color:#ef4444;"><i data-lucide="trash-2" style="width:16px;"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="stat-card" style="width:100%; max-width:400px; display:block;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 class="card-title">Edit Class Name</h3>
            <button onclick="document.getElementById('editModal').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="class_id" id="edit_class_id">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Class Name</label>
                <input type="text" name="class_name" id="edit_class_name" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <button type="submit" name="edit_class" class="login-btn" style="width: 100%; margin-top: 0;">Update Class</button>
        </form>
    </div>
</div>

<script>
function editClass(cls) {
    document.getElementById('edit_class_id').value = cls.CLASS_ID;
    document.getElementById('edit_class_name').value = cls.CLASS_NAME;
    document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php include('../includes/footer.php'); ?>


