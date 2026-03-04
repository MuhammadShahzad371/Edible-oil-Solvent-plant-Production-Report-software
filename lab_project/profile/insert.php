<?php
include '../config/db.php';
if(isset($_POST['submit'])){
    $name = $_POST['name'];
    $role = $_POST['role'];
    $image = $_FILES['image']['name'];
    $tmp_name = $_FILES['image']['tmp_name'];
    $extension = pathinfo($image, PATHINFO_EXTENSION);
    $namee = pathinfo($image, PATHINFO_FILENAME);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff', 'ico', 'heic', 'avif','jif', 'jfif', 'pjpeg', 'pjp', 'jiff'];
    date_default_timezone_set('Asia/Karachi');
    $newname = $namee . '_' . date('Y-m-d H-i-s') ."_" . rand(99,10000) . '.' . $extension;
     if (in_array($extension, $allowed_extensions)) {
        move_uploaded_file($tmp_name, "..//upload/pic/" . $newname);
        $query = "INSERT INTO profile (name, role, image) VALUES ('$name', '$role', '$newname')";
    mysqli_query($conn, $query);
  } else {
    echo "<script>alert('Invalid file type. Please upload an image.');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="insert.php" method="POST" enctype="multipart/form-data">
        <input type="text" name="name" id="name" placeholder="Enter your name"><br>
        <input type="text" name="role" id="role" placeholder="Enter your role"><br>
        <input type="file" name="image" id="image" placeholder="Upload your profile image"><br>
        <input type="submit" name="submit" value="Submit">
    </form>
</body>
</html>