<?php
// Test script for user_stats API
require_once 'config.php';

// Simulate admin login for testing
$_SESSION['user_cedula'] = '9999999999'; // admin2 cedula
$_SESSION['user_role'] = 1; // admin role id

// Test the API
echo "Testing user_stats API...\n";
echo "Session user_cedula: " . $_SESSION['user_cedula'] . "\n";
echo "Session user_role: " . $_SESSION['user_role'] . "\n";

// Include the API file directly for testing
include 'api/user_stats.php';
?>