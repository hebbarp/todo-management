<?php
// MySQL configuration - update these with your database credentials
$mysql_host = 'localhost';
$mysql_db = 'biotech';
$mysql_user = 'biotech_user';
$mysql_pass = 'biotech_password';

try {
    $dsn = "mysql:host=$mysql_host;dbname=$mysql_db;charset=utf8mb4";
    $db = new PDO($dsn, $mysql_user, $mysql_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Database connection failed. Please check your MySQL configuration and ensure the database is set up correctly.<br>Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biotech Portal - Import Data</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .db-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
            align-self: end;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid #667eea;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
            line-height: 1.3;
        }
        
        .card-category {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .card-details {
            display: grid;
            gap: 8px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            flex-shrink: 0;
        }
        
        .detail-value {
            text-align: right;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .value-highlight {
            color: #27ae60;
            font-weight: 700;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 1.1rem;
        }
        
        .results-count {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .filter-row {
                flex-direction: column;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .card {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            header {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Biotech Portal</h1>
            <p class="subtitle">Import Data Dashboard - Explore biotech commodities and trade statistics</p>
        </header>
        
        <div class="db-notice">
            <strong>MySQL Version:</strong> This version uses MySQL database. Make sure to run the biotech_mysql.sql file to set up the database.
        </div>
        
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search Commodity:</label>
                        <input type="text" id="search" name="search" placeholder="Enter commodity name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php
                            try {
                                // Build dynamic query based on other selected filters
                                $cat_sql = "SELECT DISTINCT `Broad Product Category` FROM biotech_companies WHERE `Broad Product Category` IS NOT NULL";
                                $cat_params = [];
                                
                                // If sector is selected, filter categories by that sector
                                if (!empty($_GET['sector'])) {
                                    $cat_sql .= " AND `Sector /Segment` = ?";
                                    $cat_params[] = $_GET['sector'];
                                }
                                
                                // If search is provided, filter categories by search term
                                if (!empty($_GET['search'])) {
                                    $cat_sql .= " AND Commodity LIKE ?";
                                    $cat_params[] = '%' . $_GET['search'] . '%';
                                }
                                
                                $cat_sql .= " ORDER BY `Broad Product Category`";
                                
                                $stmt = $db->prepare($cat_sql);
                                $stmt->execute($cat_params);
                                while ($row = $stmt->fetch()) {
                                    $selected = ($_GET['category'] ?? '') == $row['Broad Product Category'] ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['Broad Product Category']) . "' $selected>" . htmlspecialchars($row['Broad Product Category']) . "</option>";
                                }
                            } catch (Exception $e) {
                                echo "<!-- Error loading categories: " . $e->getMessage() . " -->";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sector">Sector:</label>
                        <select id="sector" name="sector">
                            <option value="">All Sectors</option>
                            <?php
                            try {
                                // Build dynamic query based on other selected filters
                                $sector_sql = "SELECT DISTINCT `Sector /Segment` FROM biotech_companies WHERE `Sector /Segment` IS NOT NULL";
                                $sector_params = [];
                                
                                // If category is selected, filter sectors by that category
                                if (!empty($_GET['category'])) {
                                    $sector_sql .= " AND `Broad Product Category` = ?";
                                    $sector_params[] = $_GET['category'];
                                }
                                
                                // If search is provided, filter sectors by search term
                                if (!empty($_GET['search'])) {
                                    $sector_sql .= " AND Commodity LIKE ?";
                                    $sector_params[] = '%' . $_GET['search'] . '%';
                                }
                                
                                $sector_sql .= " ORDER BY `Sector /Segment`";
                                
                                $stmt = $db->prepare($sector_sql);
                                $stmt->execute($sector_params);
                                while ($row = $stmt->fetch()) {
                                    $selected = ($_GET['sector'] ?? '') == $row['Sector /Segment'] ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['Sector /Segment']) . "' $selected>" . htmlspecialchars($row['Sector /Segment']) . "</option>";
                                }
                            } catch (Exception $e) {
                                echo "<!-- Error loading sectors: " . $e->getMessage() . " -->";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>
        </div>

        <?php
        try {
            $sql = "SELECT * FROM biotech_companies WHERE 1=1";
            $params = [];
            
            if (!empty($_GET['search'])) {
                $sql .= " AND Commodity LIKE ?";
                $params[] = '%' . $_GET['search'] . '%';
            }
            
            if (!empty($_GET['category'])) {
                $sql .= " AND `Broad Product Category` = ?";
                $params[] = $_GET['category'];
            }
            
            if (!empty($_GET['sector'])) {
                $sql .= " AND `Sector /Segment` = ?";
                $params[] = $_GET['sector'];
            }
            
            $sql .= " ORDER BY `2024  (Rs crore)` DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            $count = count($results);
            
            echo "<div class='results-count'>Showing $count results</div>";
            
            if ($count > 0) {
                echo "<div class='cards-grid'>";
                foreach ($results as $row) {
                    $value = number_format((float)$row['2024  (Rs crore)'], 2);
                    $quantity = number_format((float)$row['Import Quantity (2024)'], 2);
                    
                    echo "<div class='card'>";
                    echo "<div class='card-header'>";
                    echo "<div>";
                    echo "<h3 class='card-title'>" . htmlspecialchars($row['Commodity']) . "</h3>";
                    echo "<span class='card-category'>" . htmlspecialchars($row['Broad Product Category'] ?? 'N/A') . "</span>";
                    echo "</div>";
                    echo "</div>";
                    
                    echo "<div class='card-details'>";
                    echo "<div class='detail-row'>";
                    echo "<span class='detail-label'>Import Value (2024):</span>";
                    echo "<span class='detail-value value-highlight'>₹ $value Cr</span>";
                    echo "</div>";
                    
                    echo "<div class='detail-row'>";
                    echo "<span class='detail-label'>HS Code:</span>";
                    echo "<span class='detail-value'>" . htmlspecialchars($row['HSCode']) . "</span>";
                    echo "</div>";
                    
                    echo "<div class='detail-row'>";
                    echo "<span class='detail-label'>Import Quantity:</span>";
                    echo "<span class='detail-value'>$quantity " . htmlspecialchars($row['Unit of Quantity (Unit)'] ?? '') . "</span>";
                    echo "</div>";
                    
                    echo "<div class='detail-row'>";
                    echo "<span class='detail-label'>Chapter:</span>";
                    echo "<span class='detail-value'>" . htmlspecialchars($row['Chapter #'] ?? 'N/A') . "</span>";
                    echo "</div>";
                    
                    echo "<div class='detail-row'>";
                    echo "<span class='detail-label'>Sector:</span>";
                    echo "<span class='detail-value'>" . htmlspecialchars($row['Sector /Segment'] ?? 'N/A') . "</span>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }
                echo "</div>";
            } else {
                echo "<div class='no-results'>";
                echo "<h3>No results found</h3>";
                echo "<p>Try adjusting your search criteria or browse all data by clearing the filters.</p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='no-results'>";
            echo "<h3>Database Error</h3>";
            echo "<p>Unable to load data. Please make sure the MySQL database is set up correctly.</p>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
    </div>

    <script>
        // Progressive search functionality
        const searchInput = document.getElementById('search');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length > 2 || this.value.length === 0) {
                    document.querySelector('form').submit();
                }
            }, 500);
        });
        
        // Auto-submit on filter changes
        document.getElementById('category').addEventListener('change', function() {
            document.querySelector('form').submit();
        });
        
        document.getElementById('sector').addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    </script>
</body>
</html>