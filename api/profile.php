<?php
    require_once __DIR__ . '/_common.php';

    require_method('GET');
    $userId = require_login();

    $profile = (new UserDAO(db()))->findProfile($userId);
    if (!$profile) {
        json_error(404, 'NOT_FOUND', 'Không tìm thấy người dùng');
    }

    json_response(200, ['status' => 'success', 'data' => $profile]);
?>