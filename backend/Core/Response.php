<?php
declare(strict_types=1);

class Response {
    public static function json($data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }
    public static function noContent(): void {
        http_response_code(204);
    }
}
