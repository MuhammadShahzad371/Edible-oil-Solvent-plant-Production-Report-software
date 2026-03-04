<?php
$conn = mysqli_connect("localhost", "root", "", "lab_project");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}else{
    // echo "Connected successfully";
}
?>