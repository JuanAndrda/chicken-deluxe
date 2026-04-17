<?php
/**
 * Base Controller — all controllers extend this
 * Handles view rendering and redirects
 */
class Controller
{
    /** Render a view file inside the main layout */
    protected function render(string $view, array $data = []): void
    {
        // Make data available as variables in the view
        extract($data);

        // The view file path
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "View not found: {$view}";
            return;
        }

        // Capture view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render inside the main layout
        require __DIR__ . '/../views/layouts/main.php';
    }

    /** Render a view WITHOUT the main layout (for login page, etc.) */
    protected function renderPlain(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "View not found: {$view}";
            return;
        }

        require $viewFile;
    }

    /** Redirect to another URL */
    protected function redirect(string $url): void
    {
        header('Location: ' . BASE_URL . $url);
        exit;
    }

    /** Return a JSON response */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /** Get a POST value with optional default */
    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /** Get a GET value with optional default */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /** Validate that required POST fields are present */
    protected function requirePost(array $fields): bool
    {
        foreach ($fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                return false;
            }
        }
        return true;
    }
}
