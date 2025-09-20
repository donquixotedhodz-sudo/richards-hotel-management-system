<?php
// Basic database connection for Richards Hotel Management System

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'rhms_db';

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>