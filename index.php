<!-- Matric Number: A192910 Name: Muhamad Amirul Aiman Mohd Azhar -->

<?php
session_start();


// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
   exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
    <link rel="icon" type="image/png" href="images/icons/favicon.ico"/>
<!--===============================================================================================-->	
    <title>Digital LogBook+</title>
<!--===============================================================================================-->	
    <link href="css/bootstrap.min.css" rel="stylesheet">
<!--===============================================================================================-->	
    <style type="text/css">
       html, body {
          width: 100%;
          height: 100%;
          margin: 0;
          padding: 0;
          background: url("bglogo/UKMBG.jpg") no-repeat center center / cover fixed;
        }
    </style>
</head>
<body>
    
    <?php include_once 'nav_bar.php'; ?>
    
<!--===============================================================================================-->	
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<!--===============================================================================================-->	
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
