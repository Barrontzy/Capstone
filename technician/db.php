<?php
// Database connection settings
$host = 'localhost';
$user = 'u529803437_Ict';
$password = 'Ict_1234';
$dbname = 'u529803437_bsuict';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
} 