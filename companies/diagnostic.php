<?php
/**
 * Diagnostic script for companies directory
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Companies Directory Diagnostic</h1>\n";

// Test 1: Current working directory
echo "<h2>📁 Current Directory</h2>\n";
echo "<p><strong>Current working directory:</strong> " . getcwd() . "</p>\n";
echo "<p><strong>Script location:</strong> " . __FILE__ . "</p>\n";

// Test 2: Check if config.php exists and is accessible
echo "<h2>⚙️ Config File Test</h2>\n";
$config_paths = [
    '../config.php',
    './config.php',
    '/config.php',
    '../../config.php'
];

foreach ($config_paths as $path) {
    if (file_exists($path)) {
        echo "<p>✅ Found config at: <code>$path</code></p>\n";
        echo "<p>   Real path: <code>" . realpath($path) . "</code></p>\n";
    } else {
        echo "<p>❌ Not found: <code>$path</code></p>\n";
    }
}

// Test 3: Try to include config
echo "<h2>🔗 Config Include Test</h2>\n";
try {
    require_once '../config.php';
    echo "<p>✅ Config loaded successfully</p>\n";
    
    // Test database connection
    if (function_exists('getDB')) {
        echo "<p>✅ getDB function is available</p>\n";
        try {
            $db = getDB();
            echo "<p>✅ Database connection successful</p>\n";
        } catch (Exception $e) {
            echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p>❌ getDB function not found</p>\n";
    }
    
    // Test session
    if (session_status() == PHP_SESSION_ACTIVE) {
        echo "<p>✅ Session is active</p>\n";
        if (isset($_SESSION['user_id'])) {
            echo "<p>✅ User ID in session: " . $_SESSION['user_id'] . "</p>\n";
            if (isset($_SESSION['user_name'])) {
                echo "<p>✅ User name in session: " . htmlspecialchars($_SESSION['user_name']) . "</p>\n";
            }
        } else {
            echo "<p>❌ No user_id in session</p>\n";
        }
    } else {
        echo "<p>❌ Session is not active</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Config loading failed: " . $e->getMessage() . "</p>\n";
    echo "<p>   File: " . $e->getFile() . "</p>\n";
    echo "<p>   Line: " . $e->getLine() . "</p>\n";
}

// Test 4: Check for common functions
echo "<h2>🛠️ Function Availability Test</h2>\n";
$functions = ['checkAuth', 'checkRole', 'getDB'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p>✅ Function <code>$func</code> is available</p>\n";
    } else {
        echo "<p>❌ Function <code>$func</code> is NOT available</p>\n";
    }
}

// Test 5: File structure check
echo "<h2>📂 File Structure Check</h2>\n";
$files_to_check = [
    '../config.php',
    '../auth/index.php',
    './index.php',
    '../components/navbar_notifications_safe.php',
    '../admin/index.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p>✅ <code>$file</code> exists</p>\n";
    } else {
        echo "<p>❌ <code>$file</code> NOT found</p>\n";
    }
}

echo "<hr>\n";
echo "<p><strong>Diagnostic completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
