<?php
/**
 * Router — maps URL paths to controller methods
 */
class Router
{
    private array $routes = [];

    /** Register a GET route */
    public function get(string $path, string $controller, string $method): void
    {
        $this->routes['GET'][$path] = ['controller' => $controller, 'method' => $method];
    }

    /** Register a POST route */
    public function post(string $path, string $controller, string $method): void
    {
        $this->routes['POST'][$path] = ['controller' => $controller, 'method' => $method];
    }

    /** Dispatch the current request to the matching controller/method */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Get the path relative to BASE_URL
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace(BASE_URL, '', $uri);
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/';
        }

        // Look for exact match
        if (isset($this->routes[$requestMethod][$path])) {
            $route = $this->routes[$requestMethod][$path];
            $controllerName = $route['controller'];
            $methodName = $route['method'];

            $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
            if (!file_exists($controllerFile)) {
                http_response_code(500);
                echo "Controller file not found: {$controllerName}";
                return;
            }

            require_once $controllerFile;
            $controller = new $controllerName();
            $controller->$methodName();
            return;
        }

        // No route matched — 404
        http_response_code(404);
        echo '<h1>404 — Page Not Found</h1>';
    }
}
