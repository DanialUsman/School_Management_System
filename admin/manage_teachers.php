<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM SUBJECTS WHERE TEACHER_ID = :id");
    oci_bind_by_name($check, ":id", $id);
    oci_execute($check);
    if (oci_fetch_array($check, OCI_ASSOC)['CNT'] > 0) {
        header("Location: manage_teachers.php?error=has_subjects");
        exit();
    }

    $del_t = oci_parse($conn, "DELETE FROM TEACHERS WHERE TEACHER_ID = :id");
    oci_bind_by_name($del_t, ":id", $id);
    if (oci_execute($del_t)) {
        $del_u = oci_parse($conn, "DELETE FROM USERS WHERE USER_ID = :id");
        oci_bind_by_name($del_u, ":id", $id);
        oci_execute($del_u);
        header("Location: manage_teachers.php?deleted=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_teacher'])) {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stid = oci_parse($conn, "SELECT MAX(USER_ID) AS MAX_ID FROM USERS");
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC);
    $user_id = ($row['MAX_ID'] ?: 0) + 1;

    $check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM USERS WHERE USERNAME = :name");
    oci_bind_by_name($check, ":name", $username);
    oci_execute($check);
    if (oci_fetch_array($check, OCI_ASSOC)['CNT'] > 0) {
        $error = "Username '$username' is already taken.";
    } else {
        $ins_user = oci_parse($conn, "INSERT INTO USERS (USER_ID, USERNAME, PASSWORD, ROLE) VALUES (:id, :name, :pass, 'teacher')");
        oci_bind_by_name($ins_user, ":id", $user_id);
        oci_bind_by_name($ins_user, ":name", $username);
        oci_bind_by_name($ins_user, ":pass", $password);
        
        if (oci_execute($ins_user)) {
            $ins_teacher = oci_parse($conn, "INSERT INTO TEACHERS (TEACHER_ID, FULL_NAME, EMAIL, PHONE) VALUES (:id, :fname, :email, :phone)");
            oci_bind_by_name($ins_teacher, ":id", $user_id);
            oci_bind_by_name($ins_teacher, ":fname", $full_name);
            oci_bind_by_name($ins_teacher, ":email", $email);
            oci_bind_by_name($ins_teacher, ":phone", $phone);
            
            if (oci_execute($ins_teacher)) {
                header("Location: manage_teachers.php?success=1");
                exit();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_teacher'])) {
    $id = $_POST['teacher_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $update = oci_parse($conn, "UPDATE TEACHERS SET FULL_NAME = :fname, EMAIL = :email, PHONE = :phone WHERE TEACHER_ID = :id");
    oci_bind_by_name($update, ":fname", $full_name);
    oci_bind_by_name($update, ":email", $email);
    oci_bind_by_name($update, ":phone", $phone);
    oci_bind_by_name($update, ":id", $id);

    if (oci_execute($update)) {
        header("Location: manage_teachers.php?updated=1");
        exit();
    }
}

$page_title = "Manage Teachers";
$active_page = "teachers";
include('../includes/header.php');
?>

<div class="stats-grid" id="regForm" style="grid-template-columns: 1fr; margin-bottom: 2.5rem; display: <?php echo isset($error) ? 'block' : 'none'; ?>;">
    <div class="stat-card" style="display: block;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 class="card-title">Register New Teacher</h3>
            <button onclick="document.getElementById('regForm').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <?php if (isset($error)): ?>
            <div class="error-message" style="display: block; margin-bottom: 1rem;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label>Login Username</label>
                <input type="text" name="username" required placeholder="User handle" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group">
                <label>Login Password</label>
                <input type="password" name="password" required placeholder="••••••••" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="Full name" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="teacher@school.com" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" required placeholder="Phone number" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div style="grid-column: 1 / -1; text-align: right;">
                <button type="submit" name="register_teacher" class="login-btn" style="width: auto; padding: 0.75rem 2.5rem; margin-top: 0;">Confirm Registration</button>
            </div>
        </form>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header" style="justify-content: space-between;">
        <h3 class="card-title">Teacher Records</h3>
        <button onclick="document.getElementById('regForm').style.display='block'" class="badge badge-present" style="border:none; cursor:pointer; padding: 0.6rem 1.2rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
            <i data-lucide="user-plus" style="width:16px;"></i> Register Teacher
        </button>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error']) && $_GET['error'] == 'has_subjects'): ?>
            <div class="error-message" style="display: block; margin-bottom: 1rem;">Cannot delete teacher. They are currently assigned to subjects.</div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Subjects</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT T.TEACHER_ID, T.FULL_NAME, T.EMAIL, T.PHONE, 
                          (SELECT LISTAGG(SUBJECT_NAME, ', ') WITHIN GROUP (ORDER BY SUBJECT_NAME) FROM SUBJECTS WHERE TEACHER_ID = T.TEACHER_ID) AS SUB_LIST
                          FROM TEACHERS T 
                          ORDER BY T.TEACHER_ID";
                $stid = oci_parse($conn, $query);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['TEACHER_ID']; ?></td>
                    <td><?php echo $row['FULL_NAME']; ?></td>
                    <td><?php echo $row['EMAIL']; ?></td>
                    <td><?php echo $row['PHONE']; ?></td>
                    <td><span class="badge" style="background:rgba(99, 102, 241, 0.1); color:#6366f1; max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo $row['SUB_LIST'] ?: 'None'; ?></span></td>
                    <td style="display: flex; gap: 0.5rem;">
                        <button onclick="editTeacher(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="background:none; border:none; color:#fbbf24; cursor:pointer;"><i data-lucide="edit-2" style="width:16px;"></i></button>
                        <a href="?delete_id=<?php echo $row['TEACHER_ID']; ?>" onclick="return confirm('Delete this teacher and their account?')" style="color:#ef4444;"><i data-lucide="trash-2" style="width:16px;"></i></a>
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
            <h3 class="card-title">Edit Teacher</h3>
            <button onclick="document.getElementById('editModal').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="teacher_id" id="edit_teacher_id">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Full Name</label>
                <input type="text" name="full_name" id="edit_full_name" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <button type="submit" name="edit_teacher" class="login-btn" style="width: 100%; margin-top: 0;">Update Teacher</button>
        </form>
    </div>
</div>

<script>
function editTeacher(teacher) {
    document.getElementById('edit_teacher_id').value = teacher.TEACHER_ID;
    document.getElementById('edit_full_name').value = teacher.FULL_NAME;
    document.getElementById('edit_email').value = teacher.EMAIL;
    document.getElementById('edit_phone').value = teacher.PHONE;
    document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php include('../includes/footer.php'); ?>


