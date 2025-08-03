<?php
// Debug version to troubleshoot MySQL connection and data issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>MySQL Debug Information</h2>";

// MySQL configuration
$mysql_host = 'localhost';
$mysql_db = 'biotech';
$mysql_user = 'auto';
$mysql_pass = 'newstart';

echo "<p><strong>Configuration:</strong><br>";
echo "Host: $mysql_host<br>";
echo "Database: $mysql_db<br>";
echo "User: $mysql_user<br>";
echo "Password: " . (empty($mysql_pass) ? '[empty]' : '[set]') . "</p>";

try {
    echo "<h3>1. Testing PDO Connection</h3>";
    $dsn = "mysql:host=$mysql_host;dbname=$mysql_db;charset=utf8mb4";
    $db = new PDO($dsn, $mysql_user, $mysql_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ PDO Connection successful<br>";
    
    echo "<h3>2. Checking Tables</h3>";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(', ', $tables) . "<br>";
    
    if (!in_array('biotech_companies', $tables)) {
        echo "❌ biotech_companies table not found!<br>";
        exit;
    }
    echo "✅ biotech_companies table exists<br>";
    
    echo "<h3>3. Checking Table Structure</h3>";
    $stmt = $db->query("DESCRIBE biotech_companies");
    $columns = $stmt->fetchAll();
    echo "<table border='1'><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>4. Checking Data Count</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM biotech_companies");
    $count = $stmt->fetch();
    echo "Total records: " . $count['total'] . "<br>";
    
    if ($count['total'] == 0) {
        echo "❌ No data in table!<br>";
        exit;
    }
    
    echo "<h3>5. Sample Data (First 3 Records)</h3>";
    $stmt = $db->query("SELECT * FROM biotech_companies LIMIT 3");
    $samples = $stmt->fetchAll();
    echo "<table border='1'>";
    if (!empty($samples)) {
        // Header
        echo "<tr>";
        foreach (array_keys($samples[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($samples as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<h3>6. Testing Main Query</h3>";
    $sql = "SELECT * FROM biotech_companies WHERE 1=1 ORDER BY `2024  (Rs crore)` DESC LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    echo "Main query returned: " . count($results) . " records<br>";
    
    if (empty($results)) {
        echo "❌ Main query returned no results!<br>";
        
        // Try without ORDER BY
        echo "<h4>Testing without ORDER BY</h4>";
        $sql2 = "SELECT * FROM biotech_companies LIMIT 5";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute();
        $results2 = $stmt2->fetchAll();
        echo "Simple query returned: " . count($results2) . " records<br>";
    } else {
        echo "✅ Main query working<br>";
    }
    
    echo "<h3>7. Character Set Check</h3>";
    $stmt = $db->query("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$mysql_db'");
    $charset = $stmt->fetch();
    if ($charset) {
        echo "Database charset: " . $charset['DEFAULT_CHARACTER_SET_NAME'] . "<br>";
        echo "Database collation: " . $charset['DEFAULT_COLLATION_NAME'] . "<br>";
    }
    
    $stmt = $db->query("SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$mysql_db' AND TABLE_NAME = 'biotech_companies'");
    $table_charset = $stmt->fetch();
    if ($table_charset) {
        echo "Table collation: " . $table_charset['TABLE_COLLATION'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
}
?>