<?php
/**
 * Workshop Registration Handler
 * Processes form submissions and stores data in SQLite database
 */

// Include database setup functions
require_once 'setup_db.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit();
}

/**
 * Validate registration data
 */
function validateRegistrationData($data) {
    $errors = [];
    
    // Required fields
    $requiredFields = ['fullName', 'email', 'phone', 'company', 'position'];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $errors[] = "Field '$field' is required";
        }
    }
    
    // Validate inquiry type
    $validInquiryTypes = ['workshop', 'talktous'];
    if (!empty($data['inquiryType']) && !in_array($data['inquiryType'], $validInquiryTypes)) {
        $errors[] = "Invalid inquiry type";
    }
    
    // Email validation
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Phone validation (basic)
    if (!empty($data['phone']) && !preg_match('/^[\+\-\(\)\s\d]{10,}$/', $data['phone'])) {
        $errors[] = "Invalid phone number format";
    }
    
    // Position validation
    $validPositions = ['CEO', 'CTO', 'CIO', 'COO', 'CFO', 'VP', 'Director', 'Senior Manager', 'Other Executive'];
    if (!empty($data['position']) && !in_array($data['position'], $validPositions)) {
        $errors[] = "Invalid position selected";
    }
    
    // Experience validation
    $validExperience = ['Beginner', 'Intermediate', 'Advanced', 'Expert', 'Not specified'];
    if (!empty($data['experience']) && !in_array($data['experience'], $validExperience)) {
        $errors[] = "Invalid experience level";
    }
    
    return $errors;
}

/**
 * Insert registration into database
 */
function insertRegistration($pdo, $data) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO registrations (full_name, email, phone, company, position, experience, inquiry_type, registration_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['fullName'],
            $data['email'],
            $data['phone'],
            $data['company'],
            $data['position'],
            $data['experience'] ?? 'Beginner',
            $data['inquiryType'] ?? 'workshop',
            $data['registrationDate'] ?? date('c')
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'id' => $pdo->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to insert registration'
            ];
        }
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // UNIQUE constraint violation
            return [
                'success' => false,
                'message' => 'Email already registered. Please use a different email address.'
            ];
        } else {
            error_log("Registration insert error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
}

/**
 * Log registration attempt
 */
function logRegistration($data, $success) {
    $logEntry = date('Y-m-d H:i:s') . " - Registration attempt: " . 
                ($success ? 'SUCCESS' : 'FAILED') . 
                " - Email: " . ($data['email'] ?? 'unknown') . 
                " - Name: " . ($data['fullName'] ?? 'unknown') . "\n";
    
    file_put_contents(__DIR__ . '/registration.log', $logEntry, FILE_APPEND | LOCK_EX);
}

// Main processing
try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data === null) {
        error_log("Invalid JSON received: " . $input);
        throw new Exception('Invalid JSON data');
    }
    
    // Validate input data
    $errors = validateRegistrationData($data);
    
    if (!empty($errors)) {
        error_log("Validation errors: " . json_encode($errors) . " for data: " . json_encode($data));
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $errors
        ]);
        
        logRegistration($data, false);
        exit();
    }
    
    // Get database connection
    $pdo = getDbConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Insert registration
    $result = insertRegistration($pdo, $data);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! We will contact you soon with workshop details.',
            'id' => $result['id'],
            'timestamp' => date('c')
        ]);
        
        logRegistration($data, true);
        
        // Optional: Send confirmation email (implement if needed)
        // sendConfirmationEmail($data);
        
    } else {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
        
        logRegistration($data, false);
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error. Please try again later.'
    ]);
    
    logRegistration($_POST ?? [], false);
}

/**
 * Optional: Send confirmation email
 * Implement this function if email confirmation is needed
 */
function sendConfirmationEmail($data) {
    // Email implementation would go here
    // Example: mail($data['email'], $subject, $message, $headers);
}
?>