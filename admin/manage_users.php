<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    if ($id == $_SESSION['user_id']) {
        header("Location: manage_users.php?error=self_delete");
        exit();
    }


    $check_subj = oci_parse($conn, "SELECT SUBJECT_NAME FROM SUBJECTS WHERE TEACHER_ID = :id");
    oci_bind_by_name($check_subj, ":id", $id);
    oci_execute($check_subj);
    $assigned_subjects = [];
    while ($row_sub = oci_fetch_array($check_subj, OCI_ASSOC)) {
        $assigned_subjects[] = $row_sub['SUBJECT_NAME'];
    }
    
    if (count($assigned_subjects) > 0) {
        $sub_list = implode(", ", $assigned_subjects);
        header("Location: manage_users.php?error=active_teacher&subjects=" . urlencode($sub_list));
        exit();
    }




    $child_tables = ['MARKS', 'ATTENDANCE', 'ENROLLMENTS'];
    foreach ($child_tables as $table) {
        $dct = oci_parse($conn, "DELETE FROM $table WHERE STUDENT_ID = :id");
        oci_bind_by_name($dct, ":id", $id);
        oci_execute($dct);
    }

    $del_s = oci_parse($conn, "DELETE FROM STUDENTS WHERE STUDENT_ID = :id");
    oci_bind_by_name($del_s, ":id", $id);
    oci_execute($del_s);

    $del_t = oci_parse($conn, "DELETE FROM TEACHERS WHERE TEACHER_ID = :id");
    oci_bind_by_name($del_t, ":id", $id);
    oci_execute($del_t);

    $del = oci_parse($conn, "DELETE FROM USERS WHERE USER_ID = :id");
    oci_bind_by_name($del, ":id", $id);
    if (oci_execute($del)) {
        header("Location: manage_users.php?deleted=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $new_pass = trim($_POST['password']);

    if (!empty($new_pass)) {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = oci_parse($conn, "UPDATE USERS SET USERNAME = :name, ROLE = :role, PASSWORD = :pass WHERE USER_ID = :id");
        oci_bind_by_name($update, ":pass", $hashed_pass);
    } else {
        $update = oci_parse($conn, "UPDATE USERS SET USERNAME = :name, ROLE = :role WHERE USER_ID = :id");
    }
    
    oci_bind_by_name($update, ":name", $username);
    oci_bind_by_name($update, ":role", $role);
    oci_bind_by_name($update, ":id", $id);

    if (oci_execute($update)) {
        header("Location: manage_users.php?updated=1");
        exit();
    }
}

$page_title = "Manage Users";
$active_page = "users";
include('../includes/header.php');
?>

<?php if (isset($_GET['error'])): ?>
<div class="stats-grid" style="grid-template-columns: 1fr; margin-bottom: 2rem;">
    <div class="stat-card" style="display: block; border-left: 4px solid
        <div class="error-message" style="display: block; margin-bottom: 0; background: transparent; border: none; padding: 0;">
            <?php 
            if ($_GET['error'] == 'teacher_has_subjects' || $_GET['error'] == 'active_teacher') {
                echo "<strong>Deletion Blocked:</strong> They are currently assigned as a teacher for: " . ($_GET['subjects'] ?? 'unknown subjects') . ". <br>Please reassign these subjects in 'Manage Subjects' first.";
            } elseif ($_GET['error'] == 'self_delete') {
                echo "<strong>Security Alert:</strong> You cannot delete your own admin account while logged in.";
            }
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">System Users</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT USER_ID, USERNAME, ROLE, TO_CHAR(CREATED_AT, 'DD-MON-YYYY HH24:MI') AS JOIN_DATE FROM USERS ORDER BY USER_ID";
                $stid = oci_parse($conn, $query);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['USER_ID']; ?></td>
                    <td><?php echo $row['USERNAME']; ?></td>
                    <td><span class="badge" style="background:rgba(129, 140, 248, 0.1); color:#818cf8;"><?php echo ucfirst($row['ROLE']); ?></span></td>
                    <td><?php echo $row['JOIN_DATE']; ?></td>
                    <td style="display: flex; gap: 0.5rem;">
                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="background:none; border:none; color:#fbbf24; cursor:pointer;"><i data-lucide="edit-2" style="width:16px;"></i></button>
                        <a href="?delete_id=<?php echo $row['USER_ID']; ?>" onclick="return confirm('Permanently delete this user?')" style="color:#ef4444;"><i data-lucide="trash-2" style="width:16px;"></i></a>
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
            <h3 class="card-title">Edit User Account</h3>
            <button onclick="document.getElementById('editModal').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="password" placeholder="••••••••" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Role</label>
                <select name="role" id="edit_role" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                    <option value="admin">Admin</option>
                    <option value="teacher">Teacher</option>
                    <option value="student">Student</option>
                </select>
            </div>
            <button type="submit" name="edit_user" class="login-btn" style="width: 100%; margin-top: 0;">Update Account</button>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.USER_ID;
    document.getElementById('edit_username').value = user.USERNAME;
    document.getElementById('edit_role').value = user.ROLE.toLowerCase();
    document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php include('../includes/footer.php'); ?>


