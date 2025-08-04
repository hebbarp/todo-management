<?php
/**
 * Simple PHP Development Server for Workshop Registration System
 * Serves static files and handles dynamic requests
 */

// Get the requested URI
$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'];

// Remove leading slash and query parameters
$file = ltrim($path, '/');

// Default to index.html if no file specified
if (empty($file) || $file === '/') {
    $file = 'index.html';
}

// Security check - prevent directory traversal
if (strpos($file, '..') !== false || strpos($file, '/') !== false) {
    http_response_code(403);
    echo "Forbidden";
    exit();
}

// Handle PHP files
if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    // Include the PHP file
    if (file_exists($file)) {
        include $file;
    } else {
        http_response_code(404);
        echo "PHP file not found: $file";
    }
    exit();
}

// Handle static files
if (file_exists($file)) {
    // Determine content type
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'svg' => 'image/svg+xml',
        'json' => 'application/json',
        'txt' => 'text/plain'
    ];
    
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $contentType = $mimeTypes[$extension] ?? 'text/plain';
    
    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . filesize($file));
    
    readfile($file);
} else {
    // File not found
    http_response_code(404);
    echo "File not found: $file";
}
?>