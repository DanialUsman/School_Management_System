<?php
session_start();
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $tables = ['MARKS', 'ATTENDANCE', 'ENROLLMENTS'];
    foreach ($tables as $table) {
        $del_child = oci_parse($conn, "DELETE FROM $table WHERE STUDENT_ID = :id");
        oci_bind_by_name($del_child, ":id", $id);
        oci_execute($del_child);
    }

    $del_student = oci_parse($conn, "DELETE FROM STUDENTS WHERE STUDENT_ID = :id");
    oci_bind_by_name($del_student, ":id", $id);
    if (oci_execute($del_student)) {

        $del_user = oci_parse($conn, "DELETE FROM USERS WHERE USER_ID = :id");
        oci_bind_by_name($del_user, ":id", $id);
        oci_execute($del_user);
        header("Location: manage_students.php?deleted=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $id = $_POST['student_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $class_id = $_POST['class_id'];

    $update = oci_parse($conn, "UPDATE STUDENTS SET FULL_NAME = :fname, EMAIL = :email, PHONE = :phone, CLASS_ID = :cid WHERE STUDENT_ID = :id");
    oci_bind_by_name($update, ":fname", $full_name);
    oci_bind_by_name($update, ":email", $email);
    oci_bind_by_name($update, ":phone", $phone);
    oci_bind_by_name($update, ":cid", $class_id);
    oci_bind_by_name($update, ":id", $id);

    if (oci_execute($update)) {
        header("Location: manage_students.php?updated=1");
        exit();
    }
}

$page_title = "Manage Students";
$active_page = "students";
include('../includes/header.php');
?>

<div class="stats-grid" id="registrationForm" style="grid-template-columns: 1fr; margin-bottom: 2.5rem; display: <?php echo isset($_GET['show_form']) ? 'block' : 'none'; ?>;">
    <div class="stat-card" style="display: block;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 class="card-title">Register New Student</h3>
            <button onclick="document.getElementById('registrationForm').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message" style="display: block; margin-bottom: 1.5rem;">
                <?php echo $error; ?>
            </div>
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
                <input type="text" name="full_name" required placeholder="Full name of student" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="email@school.com" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" required placeholder="Phone number" style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group">
                <label>Assign Class</label>
                <select name="class_id" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                    <option value="">Select Class</option>
                    <?php
                    $c_stid = oci_parse($conn, "SELECT CLASS_ID, CLASS_NAME FROM CLASSES ORDER BY CLASS_NAME");
                    oci_execute($c_stid);
                    while ($crow = oci_fetch_array($c_stid, OCI_ASSOC)) {
                        echo "<option value='{$crow['CLASS_ID']}'>{$crow['CLASS_NAME']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div style="grid-column: 1 / -1; text-align: right;">
                <button type="submit" name="register_student" class="login-btn" style="width: auto; padding: 0.75rem 2.5rem; margin-top: 0;">Confirm Registration</button>
            </div>
        </form>
    </div>
</div>

<div class="data-table-card">
    <div class="card-header" style="justify-content: space-between;">
        <h3 class="card-title">Student Records</h3>
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <button onclick="document.getElementById('registrationForm').style.display='block'" class="badge badge-present" style="border:none; cursor:pointer; padding: 0.6rem 1.2rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                <i data-lucide="user-plus" style="width:16px;"></i> Register Student
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Class</th>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($_SESSION['role'] == 'admin') {
                    $query = "SELECT S.STUDENT_ID, S.FULL_NAME, S.EMAIL, S.PHONE, S.CLASS_ID, C.CLASS_NAME 
                              FROM STUDENTS S 
                              LEFT JOIN CLASSES C ON S.CLASS_ID = C.CLASS_ID 
                              ORDER BY S.STUDENT_ID";
                    $stid = oci_parse($conn, $query);
                } else {

                    $teacher_id = $_SESSION['user_id'];
                    $query = "SELECT DISTINCT S.STUDENT_ID, S.FULL_NAME, S.EMAIL, S.PHONE, S.CLASS_ID, C.CLASS_NAME 
                              FROM STUDENTS S 
                              LEFT JOIN CLASSES C ON S.CLASS_ID = C.CLASS_ID 
                              JOIN ENROLLMENTS E ON S.STUDENT_ID = E.STUDENT_ID
                              JOIN SUBJECTS SUB ON E.SUBJECT_ID = SUB.SUBJECT_ID
                              WHERE SUB.TEACHER_ID = :tid
                              ORDER BY S.STUDENT_ID";
                    $stid = oci_parse($conn, $query);
                    oci_bind_by_name($stid, ":tid", $teacher_id);
                }
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                ?>
                <tr>
                    <td><?php echo $row['STUDENT_ID']; ?></td>
                    <td><?php echo $row['FULL_NAME']; ?></td>
                    <td><?php echo $row['EMAIL']; ?></td>
                    <td><?php echo $row['PHONE']; ?></td>
                    <td><span class="badge" style="background:rgba(6, 182, 212, 0.1); color:#06b6d4;"><?php echo $row['CLASS_NAME'] ?: 'Unassigned'; ?></span></td>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <td style="display: flex; gap: 0.5rem;">
                            <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="background:none; border:none; color:#fbbf24; cursor:pointer;"><i data-lucide="edit-2" style="width:16px;"></i></button>
                            <a href="?delete_id=<?php echo $row['STUDENT_ID']; ?>" onclick="return confirm('Delete this student and their login account?')" style="color:#ef4444;"><i data-lucide="trash-2" style="width:16px;"></i></a>
                        </td>
                    <?php endif; ?>
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
            <h3 class="card-title">Edit Student</h3>
            <button onclick="document.getElementById('editModal').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="student_id" id="edit_student_id">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Full Name</label>
                <input type="text" name="full_name" id="edit_full_name" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Class</label>
                <select name="class_id" id="edit_class_id" required style="background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; padding:0.75rem; border-radius:0.75rem; width: 100%;">
                    <?php
                    $c_stid = oci_parse($conn, "SELECT CLASS_ID, CLASS_NAME FROM CLASSES ORDER BY CLASS_NAME");
                    oci_execute($c_stid);
                    while ($crow = oci_fetch_array($c_stid, OCI_ASSOC)) {
                        echo "<option value='{$crow['CLASS_ID']}'>{$crow['CLASS_NAME']}</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" name="edit_student" class="login-btn" style="width: 100%; margin-top: 0;">Update Student</button>
        </form>
    </div>
</div>

<script>
function editStudent(student) {
    document.getElementById('edit_student_id').value = student.STUDENT_ID;
    document.getElementById('edit_full_name').value = student.FULL_NAME;
    document.getElementById('edit_email').value = student.EMAIL;
    document.getElementById('edit_phone').value = student.PHONE;


    document.getElementById('edit_class_id').value = student.CLASS_ID;
    
    document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php include('../includes/footer.php'); ?>


