<?php
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../database.php';
    require_once __DIR__ . '/../dao/UserDAO.php';
    require_once __DIR__ . '/../dao/DeviceDAO.php';
    require_once __DIR__ . '/../dao/SensorDAO.php';
    require_once __DIR__ . '/../dao/DataSensorDAO.php';
    require_once __DIR__ . '/../dao/ActionDAO.php';

    function uuid_v4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    function time_filter_range(string $input): ?array
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?: (\d{2})(?::(\d{2})(?::(\d{2}))?)?)?$/', $input, $m)) {
            return null;
        }

        $hour   = ($m[4] ?? '') !== '' ? (int) $m[4] : null;
        $minute = ($m[5] ?? '') !== '' ? (int) $m[5] : null;
        $second = ($m[6] ?? '') !== '' ? (int) $m[6] : null;

        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || $hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        $start = new DateTime(sprintf('%s-%s-%s %02d:%02d:%02d', $m[1], $m[2], $m[3], $hour ?? 0, $minute ?? 0, $second ?? 0));
        $step = $second !== null ? '+1 second' : ($minute !== null ? '+1 minute' : ($hour !== null ? '+1 hour' : '+1 day'));
        $end = (clone $start)->modify($step);

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }

    function name_initials(string $fullName): string
    {
        $words = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return '?';
        }
        $first = mb_substr($words[0], 0, 1);
        $last = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';
        return mb_strtoupper($first . $last);
    }
?>