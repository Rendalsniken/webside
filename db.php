<?php
/**
 * XRP Specter - Database Abstraction Layer
 * Secure PDO-based database operations with connection pooling and error handling
 */

// Prevent direct access
if (!defined('XRP_SPECTER')) {
    define('XRP_SPECTER', true);
}

require_once 'config.php';

class Database {
    private static $instance = null;
    private $pdo;
    private $inTransaction = false;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw new Exception("Database operation failed");
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params)->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }
    
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->pdo->beginTransaction();
            $this->inTransaction = true;
        }
    }
    
    public function commit() {
        if ($this->inTransaction) {
            $this->pdo->commit();
            $this->inTransaction = false;
        }
    }
    
    public function rollback() {
        if ($this->inTransaction) {
            $this->pdo->rollback();
            $this->inTransaction = false;
        }
    }
    
    public function paginate($sql, $params = [], $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM ({$sql}) as count_table";
        $total = $this->fetchColumn($countSql, $params);
        
        // Get paginated results
        $paginatedSql = $sql . " LIMIT {$perPage} OFFSET {$offset}";
        $results = $this->fetchAll($paginatedSql, $params);
        
        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
            'has_next' => $page < ceil($total / $perPage),
            'has_prev' => $page > 1
        ];
    }
    
    public function exists($table, $where, $params = []) {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        return $this->fetchColumn($sql, $params) !== false;
    }
    
    public function count($table, $where = '1', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return $this->fetchColumn($sql, $params);
    }
    
    public function escape($value) {
        return substr($this->pdo->quote($value), 1, -1);
    }
}

// Helper functions for common database operations
function db() {
    return Database::getInstance();
}

function get_user_by_id($id) {
    return db()->fetch("SELECT * FROM users WHERE id = ?", [$id]);
}

function get_user_by_email($email) {
    return db()->fetch("SELECT * FROM users WHERE email = ?", [$email]);
}

function get_user_by_username($username) {
    return db()->fetch("SELECT * FROM users WHERE username = ?", [$username]);
}

function create_user($data) {
    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    return db()->insert('users', $data);
}

function update_user($id, $data) {
    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    }
    return db()->update('users', $data, 'id = ?', [$id]);
}

function delete_user($id) {
    return db()->delete('users', 'id = ?', [$id]);
}

function get_users($page = 1, $perPage = 20, $search = '') {
    $where = '1';
    $params = [];
    
    if ($search) {
        $where = "username LIKE ? OR email LIKE ?";
        $params = ["%{$search}%", "%{$search}%"];
    }
    
    $sql = "SELECT * FROM users WHERE {$where} ORDER BY created_at DESC";
    return db()->paginate($sql, $params, $page, $perPage);
}

function get_comments($page = 1, $perPage = 20, $approved = null) {
    $where = '1';
    $params = [];
    
    if ($approved !== null) {
        $where = "approved = ?";
        $params = [$approved];
    }
    
    $sql = "SELECT c.*, u.username, u.avatar FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE {$where} ORDER BY c.created_at DESC";
    return db()->paginate($sql, $params, $page, $perPage);
}

function create_comment($data) {
    return db()->insert('comments', $data);
}

function approve_comment($id, $moderator_id) {
    return db()->update('comments', 
        ['approved' => 1, 'moderated_by' => $moderator_id, 'moderated_at' => date('Y-m-d H:i:s')], 
        'id = ?', [$id]);
}

function get_polls($active = true) {
    $sql = "SELECT p.*, u.username as created_by_name, 
            (SELECT COUNT(*) FROM poll_votes WHERE poll_id = p.id) as total_votes
            FROM polls p 
            JOIN users u ON p.created_by = u.id 
            WHERE p.active = ? 
            ORDER BY p.created_at DESC";
    return db()->fetchAll($sql, [$active]);
}

function get_poll_results($poll_id) {
    $sql = "SELECT option, COUNT(*) as votes 
            FROM poll_votes 
            WHERE poll_id = ? 
            GROUP BY option 
            ORDER BY votes DESC";
    return db()->fetchAll($sql, [$poll_id]);
}

function user_has_voted($user_id, $poll_id) {
    return db()->exists('poll_votes', 'user_id = ? AND poll_id = ?', [$user_id, $poll_id]);
}

function add_vote($user_id, $poll_id, $option) {
    return db()->insert('poll_votes', [
        'user_id' => $user_id,
        'poll_id' => $poll_id,
        'option' => $option
    ]);
}

function add_xp($user_id, $amount, $reason, $description = null) {
    $xp_id = db()->insert('xp_logs', [
        'user_id' => $user_id,
        'amount' => $amount,
        'reason' => $reason,
        'description' => $description
    ]);
    
    // Update user's total XP
    db()->execute("UPDATE users SET xp_points = xp_points + ? WHERE id = ?", [$amount, $user_id]);
    
    // Check for level up
    $user = get_user_by_id($user_id);
    $new_level = floor($user['xp_points'] / 100) + 1;
    if ($new_level > $user['level']) {
        db()->execute("UPDATE users SET level = ? WHERE id = ?", [$new_level, $user_id]);
    }
    
    return $xp_id;
}

function get_leaderboard($limit = 10) {
    $sql = "SELECT username, xp_points, level, avatar 
            FROM users 
            WHERE is_active = 1 
            ORDER BY xp_points DESC, level DESC 
            LIMIT ?";
    return db()->fetchAll($sql, [$limit]);
}

function create_notification($user_id, $type, $title, $message) {
    return db()->insert('notifications', [
        'user_id' => $user_id,
        'type' => $type,
        'title' => $title,
        'message' => $message
    ]);
}

function get_user_notifications($user_id, $unseen_only = false) {
    $where = "user_id = ?";
    $params = [$user_id];
    
    if ($unseen_only) {
        $where .= " AND seen = 0";
    }
    
    $sql = "SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC";
    return db()->fetchAll($sql, $params);
}

function mark_notification_seen($notification_id) {
    return db()->update('notifications', ['seen' => 1], 'id = ?', [$notification_id]);
}

function get_cache($key) {
    $sql = "SELECT cache_data FROM api_cache WHERE cache_key = ? AND expires_at > NOW()";
    $result = db()->fetchColumn($sql, [$key]);
    return $result ? json_decode($result, true) : null;
}

function set_cache($key, $data, $duration = 300) {
    $expires_at = date('Y-m-d H:i:s', time() + $duration);
    $cache_data = json_encode($data);
    
    $sql = "INSERT INTO api_cache (cache_key, cache_data, expires_at) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE cache_data = ?, expires_at = ?";
    
    return db()->execute($sql, [$key, $cache_data, $expires_at, $cache_data, $expires_at]);
}

function clear_expired_cache() {
    return db()->delete('api_cache', 'expires_at <= NOW()');
}

function check_rate_limit($ip_address, $endpoint) {
    $window_start = date('Y-m-d H:i:s', time() - RATE_LIMIT_WINDOW);
    
    // Clean old entries
    db()->delete('rate_limits', 'window_start < ?', [$window_start]);
    
    // Check current rate
    $current_count = db()->fetchColumn(
        "SELECT requests_count FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND window_start > ?",
        [$ip_address, $endpoint, $window_start]
    );
    
    if ($current_count === false) {
        // First request in this window
        db()->insert('rate_limits', [
            'ip_address' => $ip_address,
            'endpoint' => $endpoint,
            'requests_count' => 1,
            'window_start' => date('Y-m-d H:i:s')
        ]);
        return true;
    }
    
    if ($current_count >= RATE_LIMIT_REQUESTS) {
        return false; // Rate limit exceeded
    }
    
    // Increment count
    db()->execute(
        "UPDATE rate_limits SET requests_count = requests_count + 1 WHERE ip_address = ? AND endpoint = ? AND window_start > ?",
        [$ip_address, $endpoint, $window_start]
    );
    
    return true;
}

function get_login_attempts($ip_address) {
    $window_start = date('Y-m-d H:i:s', time() - LOGIN_ATTEMPTS_WINDOW);
    
    return db()->fetchColumn(
        "SELECT COUNT(*) FROM rate_limits WHERE ip_address = ? AND endpoint = 'login' AND window_start > ?",
        [$ip_address, $window_start]
    );
}

function record_login_attempt($ip_address) {
    db()->insert('rate_limits', [
        'ip_address' => $ip_address,
        'endpoint' => 'login',
        'requests_count' => 1,
        'window_start' => date('Y-m-d H:i:s')
    ]);
}

// Initialize database connection
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Database connection failed. Please check your configuration.");
}
?> 