<?php
    session_start();
    $_SESSION = [];
    session_destroy();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'message' => 'Đã đăng xuất'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ../../login.php');
    exit;
?>