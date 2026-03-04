<?php
session_start();
include '../config/db.php';

// Step 1: Check ID
if(!isset($_GET['id']) || empty($_GET['id'])){
    die("Error: ID missing in URL!");
}
$id = intval($_GET['id']);

// Step 2: Fetch profile
$query = "SELECT * FROM profile WHERE id=$id";
$result = mysqli_query($conn, $query);
if(!$result){
    die("Database Error: ".mysqli_error($conn));
}
$row = mysqli_fetch_assoc($result);
if(!$row){
    die("Profile not found!");
}

// Step 3: Handle form submit
if(isset($_POST['submit'])){
    $name = $_POST['name'];
    $role = $_POST['role'];

    if(!empty($_FILES['image']['name'])){
        $image = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];
        $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        $base = pathinfo($image, PATHINFO_FILENAME);
        $allowed = ['jpg','jpeg','png','gif','webp','bmp','svg','tiff','ico','heic','avif','jif','jfif','pjpeg','pjp','jiff'];

        if(!in_array($ext,$allowed)){
            echo "<script>alert('Invalid file type');</script>";
            exit();
        }

        if(file_exists("../upload/pic/".$row['image'])){
            unlink("../upload/pic/".$row['image']);
        }

        $new_image = $base."_".time('y-m-d_H-i-s').".".$ext;
        move_uploaded_file($tmp_name,"../upload/pic/".$new_image);

        $query = "UPDATE profile SET name='$name', role='$role', image='$new_image' WHERE id=$id";
    } else {
        $query = "UPDATE profile SET name='$name', role='$role' WHERE id=$id";
    }

    mysqli_query($conn,$query);
    $_SESSION['msg'] = "Profile updated successfully!";
    header("Location: ../dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile</title>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f8f9fa;
}
.card {
    margin-top: 50px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-radius: 12px;
}
img {
    border-radius: 8px;
    margin-top: 10px;
    border: 2px solid #ddd;
}
.btn-primary:hover {
    background-color: #0069d9;
}
</style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 bg-white">
                <h3 class="text-center mb-4">Edit Profile</h3>
                <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo $row['name']; ?>" placeholder="Enter Name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" name="role" value="<?php echo $row['role']; ?>" placeholder="Enter Role">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Image</label>
                        <input type="file" class="form-control" name="image">
                        <?php if(!empty($row['image'])): ?>
                            <img src="../upload/pic/<?php echo $row['image']; ?>" width="100" height="100">
                        <?php endif; ?>
                    </div>
                    <div class="text-center">
                        <input type="submit" name="submit" class="btn btn-primary" value="Update Profile">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>