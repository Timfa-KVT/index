<?php
// auth.php
/*
// Define the expected token (e.g. from env/config file)
$expectedToken = "Bearer 8>W[9DHxHS/srS7gs/9CQB&dFM@\"A@hC";

// Fetch the Authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : null;

// Reject if missing or incorrect
if (!$authHeader || $authHeader !== $expectedToken) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}*/