<?php
/**
 * AuthController — handles login and logout
 */
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../models/TimeInModel.php';

class AuthController extends Controller
{
    private UserModel $userModel;
    private AuditLogModel $auditLog;
    private TimeInModel $timeInModel;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->auditLog    = new AuditLogModel();
        $this->timeInModel = new TimeInModel();
    }

    /** Show the login page */
    public function showLogin(): void
    {
        // If already logged in, go to dashboard
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $data = [
            'error'   => null,
            'timeout' => isset($_GET['timeout']),
        ];

        $this->renderPlain('auth/login', $data);
    }

    /** Handle login form submission */
    public function handleLogin(): void
    {
        // Validate CSRF
        if (!Auth::validateCsrf()) {
            $this->renderPlain('auth/login', ['error' => 'Invalid request. Please try again.', 'timeout' => false]);
            return;
        }

        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');

        // Validate input
        if ($username === '' || $password === '') {
            $this->renderPlain('auth/login', ['error' => 'Please enter both username and password.', 'timeout' => false]);
            return;
        }

        // Find user
        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['Password'])) {
            $this->renderPlain('auth/login', ['error' => 'Invalid username or password.', 'timeout' => false]);
            return;
        }

        // Check if account is active
        if (!$user['Active_status']) {
            $this->renderPlain('auth/login', ['error' => 'Your account has been deactivated. Contact the owner.', 'timeout' => false]);
            return;
        }

        // Login successful — set session
        Auth::login($user);

        // Log to audit
        $this->auditLog->log($user['User_ID'], ACTION_LOGIN, 'User logged in');

        // Auto time-in for staff
        if ((int) $user['Role_ID'] === ROLE_STAFF && $user['Kiosk_ID']) {
            if (!$this->timeInModel->hasTimedInToday($user['User_ID'])) {
                $this->timeInModel->recordTimeIn($user['User_ID'], $user['Kiosk_ID']);
            }
        }

        $this->redirect('/dashboard');
    }

    /** Handle logout — also auto-closes any open Time_in row for staff */
    public function logout(): void
    {
        if (Auth::check()) {
            $user_id = Auth::userId();

            // Auto time-out for staff: close any open session for today
            if (Auth::isStaff() && $user_id) {
                $this->timeInModel->recordTimeOutForUser($user_id);
            }

            $this->auditLog->log($user_id, ACTION_LOGOUT, 'User logged out');
        }
        Auth::logout();
        $this->redirect('/login');
    }
}
