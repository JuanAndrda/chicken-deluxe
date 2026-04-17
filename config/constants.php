<?php
/**
 * Application-wide constants
 */

// -- Roles --
define('ROLE_OWNER',   1);
define('ROLE_STAFF',   2);
define('ROLE_AUDITOR', 3);

// -- Role names (for display) --
define('ROLE_NAMES', [
    ROLE_OWNER   => 'Owner',
    ROLE_STAFF   => 'Staff',
    ROLE_AUDITOR => 'Auditor',
]);

// -- Application --
define('APP_NAME', 'Chicken Deluxe');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/chicken-deluxe');

// -- Session --
define('SESSION_TIMEOUT', 1800); // 30 minutes of inactivity

// -- Audit Log Actions --
define('ACTION_LOGIN',           'LOGIN');
define('ACTION_LOGOUT',          'LOGOUT');
define('ACTION_CREATE',          'CREATE');
define('ACTION_UPDATE',          'UPDATE');
define('ACTION_DELETE',          'DELETE');
define('ACTION_LOCK',            'LOCK');
define('ACTION_UNLOCK',          'UNLOCK');
define('ACTION_RECORD_UNLOCKED', 'RECORD_UNLOCKED');

// -- Date/Time Format --
define('DATE_FORMAT',     'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
