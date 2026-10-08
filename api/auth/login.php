<?php
    require_once __DIR__ . '/../_common.php';

    require_method('POST');
    session_start();

    $data = read_json_body();
    $username = trim((string) ($data['username'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($username === '' || $password === '') {
        json_error(400, 'VALIDATION_ERROR', 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu');
    }

    try {
        $user = (new UserDAO(db()))->findByUsername($username);
    } catch (Throwable $e) {
        error_log((string) $e);
        json_error(500, 'INTERNAL_ERROR', 'Không thể đăng nhập, vui lòng thử lại');
    }

    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_error(401, 'UNAUTHORIZED', 'Sai tên đăng nhập hoặc mật khẩu');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];

    json_response(200, ['status' => 'success', 'message' => 'Đăng nhập thành công']);
?>