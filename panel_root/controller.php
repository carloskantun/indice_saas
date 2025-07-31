<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['current_role']) || $_SESSION['current_role'] !== 'root') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}
// ...API endpoints for companies, plans, assign, limits, etc...
