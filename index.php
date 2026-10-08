<?php
    session_start();
    define('IN_APP', true);
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    session_write_close();

    require_once __DIR__ . '/lib/app.php';

    $current_user = (new UserDAO(db()))->findProfile((int) $_SESSION['user_id']);
    if (!$current_user) {
        header('Location: api/auth/logout.php');
        exit;
    }
    $user_name_words = preg_split('/\s+/u', trim($current_user['full_name']));
    $user_avatar_letter = mb_strtoupper(mb_substr(end($user_name_words), 0, 1));

    $page = $_GET['page'] ?? 'dashboard';
    
    $routes = [
        'dashboard'      => 'dashboard.php',
        'data_sensors'   => 'data_sensors.php',
        'action_history' => 'action_history.php',
        'profile'        => 'profile.php'
    ];
    $file_to_load = $routes[$page] ?? '404.php';

    $page_titles = [
        'dashboard'      => 'Dashboard',
        'data_sensors'   => 'Data Sensors',
        'action_history' => 'Action History',
        'profile'        => 'Profile'
    ];
    $current_title = $page_titles[$page] ?? 'Lỗi 404';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ | PTIT IoT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="main-content-row row">
            <aside class="col-md-3 col-12 main-sidebar">
                <h1 class="main-sidebar-app-name">PTIT IoT</h1>
                <button type="button" class="main-sidebar-toggle" aria-label="Mở menu" aria-expanded="false" aria-controls="main-sidebar-menu">
                    <span></span><span></span><span></span>
                </button>
                <nav class="main-sidebar-nav" id="main-sidebar-menu">
                    <ul class="main-sidebar-list">
                        
                        <a href="index.php?page=dashboard" style="text-decoration: none; color: inherit;">
                            <li class="mainsidebar-item <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                                <span class="main-sidebar-item-avt">D</span> Dashboard
                            </li>
                        </a>

                        <a href="index.php?page=data_sensors" style="text-decoration: none; color: inherit;">
                            <li class="mainsidebar-item <?php echo ($page == 'data_sensors') ? 'active' : ''; ?>">
                                <span class="main-sidebar-item-avt">S</span> Data Sensors
                            </li>
                        </a>

                        <a href="index.php?page=action_history" style="text-decoration: none; color: inherit;">
                            <li class="mainsidebar-item <?php echo ($page == 'action_history') ? 'active' : ''; ?>">
                                <span class="main-sidebar-item-avt">A</span> Action History
                            </li>
                        </a>

                        <a href="index.php?page=profile" style="text-decoration: none; color: inherit;">
                            <li class="mainsidebar-item <?php echo ($page == 'profile') ? 'active' : ''; ?>">
                                <span class="main-sidebar-item-avt">P</span> Profile
                            </li>
                        </a>

                    </ul>
                </nav>
                <div class="main-sidebar-user">
                    <div class="main-sidebar-user-left">
                        <span class="main-sidebar-user-avt"><?= htmlspecialchars($user_avatar_letter) ?></span>
                        <div class="main-sidebar-user-info">
                            <span class="main-sidebar-user-info-name"><?= htmlspecialchars($current_user['full_name']) ?></span>
                            <span class="main-sidebar-user-info-msv"><?= htmlspecialchars($current_user['student_id']) ?></span>
                        </div>
                    </div>
                    <a href="api/auth/logout.php" class="main-sidebar-user-logout">Đăng xuất</a>
                </div>
            </aside>
            <div class="col-md-9 col-12 main-content">
                <div class="main-content-header">
                    <h2 class="main-content-header-title"><?php echo $current_title; ?></h2>
                    <span class="main-content-header-group">Nhóm 13</span>
                </div>
                <div class="main-content-data">
                    <?php include $file_to_load; ?>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="responsive.css">
    <script>
        (function () {
            const sidebar = document.querySelector('.main-sidebar');
            const toggle = document.querySelector('.main-sidebar-toggle');

            function setOpen(open) {
                sidebar.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
            }

            toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('is-open')));
            sidebar.querySelectorAll('.main-sidebar-nav a').forEach(a => a.addEventListener('click', () => setOpen(false)));
            document.addEventListener('click', (e) => {
                if (sidebar.classList.contains('is-open') && !sidebar.contains(e.target)) setOpen(false);
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') setOpen(false);
            });
        })();
    </script>
</body>
</html>