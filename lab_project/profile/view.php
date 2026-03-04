<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <table>

    <?php
    include '../config/db.php';
    $query = "SELECT * FROM profile";   
    $result = mysqli_query($conn, $query);
    while($row = mysqli_fetch_assoc($result)){
        echo "<tr>";
        echo "<td>".$row['name']."</td>";
        echo "<td>".$row['role']."</td>";
        echo "<td><img src='../upload/pic/".$row['image']."' width='100' height='100'></td>";
        echo "</tr>";
    }
    ?>
    </table>
</body>
</html>