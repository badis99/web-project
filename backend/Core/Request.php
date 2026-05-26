<?php
declare(strict_types=1);

class Request {
    public string $method;
    public string $uri;
    public array $query;
    public mixed $body = [];

    public static function fromGlobals(): self {
        $req = new self();
        $req->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $req->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $req->query = $_GET;
        $bodyStr = file_get_contents('php://input');
        if ($bodyStr) {
            $decoded = json_decode($bodyStr, true);
            $req->body = $decoded ?? $_POST;
        } else {
            $req->body = $_POST;
        }
        return $req;
    }
}
