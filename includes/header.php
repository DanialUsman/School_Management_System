<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMS Portal</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i data-lucide="graduation-cap"></i>
                <span>Student Management System</span>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>">
                        <a href="dashboard.php"><i data-lucide="layout-dashboard"></i> Dashboard</a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="<?php echo ($active_page == 'users') ? 'active' : ''; ?>">
                            <a href="manage_users.php"><i data-lucide="users"></i> Manage Users</a>
                        </li>
                        <li class="<?php echo ($active_page == 'teachers') ? 'active' : ''; ?>">
                            <a href="manage_teachers.php"><i data-lucide="user-plus"></i> Manage Teachers</a>
                        </li>
                        <li class="<?php echo ($active_page == 'classes') ? 'active' : ''; ?>">
                            <a href="manage_classes.php"><i data-lucide="door-open"></i> Classes</a>
                        </li>
                        <li class="<?php echo ($active_page == 'subjects') ? 'active' : ''; ?>">
                            <a href="manage_subjects.php"><i data-lucide="book-open"></i> Manage Subjects</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin'): ?>
                        <li class="<?php echo ($active_page == 'students') ? 'active' : ''; ?>">
                            <a href="manage_students.php"><i data-lucide="user-check"></i> Students</a>
                        </li>
                        <li class="<?php echo ($active_page == 'attendance') ? 'active' : ''; ?>">
                            <a href="attendance.php"><i data-lucide="calendar"></i> Attendance</a>
                        </li>
                        <li class="<?php echo ($active_page == 'marks') ? 'active' : ''; ?>">
                            <a href="marks.php"><i data-lucide="file-text"></i> Marks</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($_SESSION['role'] == 'student'): ?>
                        <li class="<?php echo ($active_page == 'course_reg') ? 'active' : ''; ?>">
                            <a href="register_courses.php"><i data-lucide="book-plus"></i> Register Courses</a>
                        </li>
                        <li class="<?php echo ($active_page == 'my_attendance') ? 'active' : ''; ?>">
                            <a href="my_attendance.php"><i data-lucide="calendar"></i> My Attendance</a>
                        </li>
                        <li class="<?php echo ($active_page == 'my_marks') ? 'active' : ''; ?>">
                            <a href="my_marks.php"><i data-lucide="file-text"></i> My Marks</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout-btn">
                    <i data-lucide="log-out"></i> Logout
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="mobile-toggle" id="sidebarToggle">
                        <i data-lucide="menu"></i>
                    </button>
                    <h2><?php echo $page_title; ?></h2>
                </div>
                <div class="top-bar-right">
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?php echo $_SESSION['username']; ?>&background=4f46e5&color=fff" alt="User">
                        <div class="user-info">
                            <span class="username"><?php echo $_SESSION['username']; ?></span>
                            <span class="role"><?php echo ucfirst($_SESSION['role']); ?></span>
                        </div>
                        <a href="../auth/logout.php" style="margin-left: 1rem; color: #ef4444; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; font-size: 0.875rem; font-weight: 500; padding: 0.5rem 0.75rem; border-radius: 0.5rem; background: #fef2f2;" title="Logout">
                            <i data-lucide="log-out" style="width: 18px; height: 18px;"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </header>
            <div class="content">
