<?php
include 'db.php';

$username = "admin";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$sql = "INSERT INTO super_admin (username, password)
        VALUES ('$username', '$password')";

if ($conn->query($sql)) {
    echo "Super Admin Created Successfully";
} else {
    echo "Admin already exists";
}
?>
