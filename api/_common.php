<?php
    require_once __DIR__ . '/../lib/app.php';

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    set_exception_handler(function (Throwable $e) {
        error_log((string) $e);
        json_error(500, 'INTERNAL_ERROR', 'Lỗi hệ thống, vui lòng thử lại');
    });

    function json_response(int $httpCode, array $body): never
    {
        http_response_code($httpCode);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    function json_error(int $httpCode, string $code, string $message): never
    {
        json_response($httpCode, ['status' => 'error', 'code' => $code, 'message' => $message]);
    }

    function require_method(string ...$methods): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
            header('Allow: ' . implode(', ', $methods));
            json_error(405, 'METHOD_NOT_ALLOWED', 'Phương thức không được hỗ trợ');
        }
    }

    function require_login(): int
    {
        session_start();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        session_write_close();

        if ($userId <= 0) {
            json_error(401, 'UNAUTHORIZED', 'Chưa đăng nhập hoặc phiên đã hết hạn');
        }

        return $userId;
    }

    function read_json_body(): array
    {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            json_error(400, 'VALIDATION_ERROR', 'Body phải là JSON hợp lệ');
        }
        return $body;
    }

    function read_pagination(int $defaultSize = 10): array
    {
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $size = filter_var($_GET['size'] ?? $defaultSize, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]) ?: $defaultSize;
        return [$page, $size];
    }

    function read_time_filter(): ?array
    {
        $time = trim((string) ($_GET['time'] ?? ''));
        if ($time === '') {
            return null;
        }

        $range = time_filter_range($time);
        if ($range === null) {
            json_error(400, 'VALIDATION_ERROR', 'Thời gian phải theo định dạng YYYY-MM-DD hh:mm:ss');
        }
        return $range;
    }

    function paginated_response(array $result, int $page, int $size): never
    {
        json_response(200, [
            'status' => 'success',
            'data'   => [
                'items'      => $result['items'],
                'page'       => $page,
                'size'       => $size,
                'totalItems' => $result['totalItems'],
                'totalPages' => (int) ceil($result['totalItems'] / $size),
            ],
        ]);
    }
?>