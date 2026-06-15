<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dashboard');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('BASEURL', 'http://localhost/classdashboard');

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For testing purposes - set default session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 101;
    $_SESSION['user_type'] = 'A';
    $_SESSION['school_id'] = 1;
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// User authentication variables
$tutorix_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 101;
$tutorix_user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'A';
$school_id = isset($_SESSION['school_id']) ? $_SESSION['school_id'] : 1;

// Helper function
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function checkAuthorization($user_id) {
    return !empty($user_id);
}

// Database class for queries
class Database {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getFullQuery($sql) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return [];
        }
    }
}

$userObj = new Database($pdo);

// Global arrays for dropdowns
$class_array = [
    6 => 'Class 6',
    7 => 'Class 7', 
    8 => 'Class 8',
    9 => 'Class 9',
    10 => 'Class 10',
    11 => 'Class 11',
    12 => 'Class 12'
];

$board_Array = [
    'C' => 'CBSE',
    'I' => 'ICSE',
    'W' => 'WBBSE',
    'K' => 'Cambridge'
];
?>