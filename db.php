<?php
$conn = new mysqli("localhost", "root", "", "production report");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
