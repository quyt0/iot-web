<?php
    require_once __DIR__ . '/_common.php';

    require_method('GET');
    require_login();

    [$page, $size] = read_pagination();

    $status = strtoupper(trim((string) ($_GET['status'] ?? '')));
    if ($status !== '' && !in_array($status, ['LOADING', 'ON', 'OFF', 'FAILED', 'TIMEOUT'], true)) {
        json_error(400, 'VALIDATION_ERROR', 'Trạng thái không hợp lệ');
    }

    $action = strtoupper(trim((string) ($_GET['action'] ?? '')));
    if ($action !== '' && !in_array($action, ['TURN_ON', 'TURN_OFF'], true)) {
        json_error(400, 'VALIDATION_ERROR', 'Hành động không hợp lệ');
    }

    $filters = [
        'device'     => trim((string) ($_GET['device'] ?? '')),
        'status'     => $status,
        'action'     => $action,
        'time_range' => read_time_filter(),
    ];

    paginated_response((new ActionDAO(db()))->search($filters, $page, $size), $page, $size);
?>