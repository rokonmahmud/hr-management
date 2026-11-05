
<?php 

    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "";

    $conn = mysqli_connect($host, $user, $password, $db)

    if(!$conn){
        die("Database connection faild" . mysqli_connect_error());
    };


?>