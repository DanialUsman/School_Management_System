<?php
session_start();
if ($_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db.php');

$student_id = $_SESSION['user_id'];

$page_title = "My Marks";
$active_page = "my_marks";
include('../includes/header.php');
?>

<div class="data-table-card">
    <div class="card-header">
        <h3 class="card-title">Academic Results</h3>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Subject ID</th>
                    <th>Subject Name</th>
                    <th>Teacher</th>
                    <th>Marks</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT S.SUBJECT_ID, S.SUBJECT_NAME, T.FULL_NAME AS TEACHER_NAME, M.MARKS 
                          FROM MARKS M 
                          JOIN SUBJECTS S ON M.SUBJECT_ID = S.SUBJECT_ID 
                          JOIN TEACHERS T ON S.TEACHER_ID = T.TEACHER_ID
                          WHERE M.STUDENT_ID = :sid";
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ":sid", $student_id);
                oci_execute($stid);
                while ($row = oci_fetch_array($stid, OCI_ASSOC)):
                    $grade = 'F';
                    $color = '#ef4444';
                    if ($row['MARKS'] >= 80) { $grade = 'A+'; $color = '#22c55e'; }
                    else if ($row['MARKS'] >= 70) { $grade = 'A'; $color = '#22c55e'; }
                    else if ($row['MARKS'] >= 60) { $grade = 'B'; $color = '#818cf8'; }
                    else if ($row['MARKS'] >= 50) { $grade = 'C'; $color = '#fbbf24'; }
                    else if ($row['MARKS'] >= 40) { $grade = 'D'; $color = '#f97316'; }
                ?>
                <tr>
                    <td><?php echo $row['SUBJECT_ID']; ?></td>
                    <td><?php echo $row['SUBJECT_NAME']; ?></td>
                    <td><?php echo $row['TEACHER_NAME']; ?></td>
                    <td style="font-weight: 700;"><?php echo $row['MARKS']; ?></td>
                    <td><span class="badge" style="background:<?php echo $color; ?>22; color:<?php echo $color; ?>;"><?php echo $grade; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>


