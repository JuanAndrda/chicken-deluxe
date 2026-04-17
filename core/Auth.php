<?php
/**
 * Auth class — handles sessions, login state, and role-based access
 */
class Auth
{
    /** Start the session (call once in index.php) */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Auto-logout after inactivity
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                self::logout();
                header('Location: ' . BASE_URL . '/login?timeout=1');
                exit;
            }
        }
        $_SESSION['last_activity'] = time();
    }

    /** Log in a user — store user data in session */
    public static function login(array $user): void
    {
        $_SESSION['user_id']    = $user['User_ID'];
        $_SESSION['username']   = $user['Username'];
        $_SESSION['role_id']    = $user['Role_ID'];
        $_SESSION['outlet_id']  = $user['Outlet_ID'];
        $_SESSION['full_name']  = $user['Full_name'];
        $_SESSION['logged_in']  = true;
        $_SESSION['last_activity'] = time();
    }

    /** Log out — destroy session */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /** Check if user is logged in */
    public static function check(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /** Get the current user's ID */
    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /** Get the current user's role ID */
    public static function roleId(): ?int
    {
        return $_SESSION['role_id'] ?? null;
    }

    /** Get the current user's assigned outlet ID */
    public static function outletId(): ?int
    {
        return $_SESSION['outlet_id'] ?? null;
    }

    /** Get the current user's full name */
    public static function fullName(): ?string
    {
        return $_SESSION['full_name'] ?? null;
    }

    /** Get the current user's username */
    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    /** Check if user is the Business Owner */
    public static function isOwner(): bool
    {
        return self::roleId() === ROLE_OWNER;
    }

    /** Check if user is Staff */
    public static function isStaff(): bool
    {
        return self::roleId() === ROLE_STAFF;
    }

    /** Check if user is Auditor */
    public static function isAuditor(): bool
    {
        return self::roleId() === ROLE_AUDITOR;
    }

    /** Require login — redirect to login page if not authenticated */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /** Require specific role(s) — returns 403 if user doesn't have the role */
    public static function requireRole(array $allowed_roles): void
    {
        self::requireLogin();
        if (!in_array(self::roleId(), $allowed_roles, true)) {
            http_response_code(403);
            echo '<h1>403 — Access Denied</h1><p>You do not have permission to access this page.</p>';
            exit;
        }
    }

    /** Generate a CSRF token */
    public static function generateCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Validate a CSRF token from a POST request */
    public static function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
