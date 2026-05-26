<?php
declare(strict_types=1);

class Router {
    private array $routes = [];

    public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }
    public function patch(string $path, callable $handler): void { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, callable $handler): void { $this->add('DELETE', $path, $handler); }

    private function add(string $method, string $path, callable $handler): void {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler
        ];
    }

    public function dispatch(Request $request): void {
        foreach ($this->routes as $route) {
            if ($route['method'] === $request->method) {
                if (preg_match($route['pattern'], $request->uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    try {
                        // Use Reflection to check if callable accepts Request
                        $reflection = new ReflectionFunction(Closure::fromCallable($route['handler']));
                        if ($reflection->getNumberOfParameters() > 0) {
                            $result = call_user_func_array($route['handler'], array_merge([$request], array_values($params)));
                        } else {
                            $result = call_user_func($route['handler']);
                        }
                        
                        if ($result !== null && !headers_sent()) {
                            Response::json($result);
                        }
                    } catch (RuntimeException $e) {
                        Response::json(['error' => $e->getMessage()], $e->getCode() ?: 500);
                    } catch (Exception $e) {
                        Response::json(['error' => $e->getMessage()], 500);
                    }
                    return;
                }
            }
        }
        Response::json(['error' => 'Not found'], 404);
    }
}
