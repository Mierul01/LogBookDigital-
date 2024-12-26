<?php

// Include your database connection file
include_once 'database.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check user level and fetch the corresponding name
        $username = $_SESSION['username'];
        $userLevel = $_SESSION['position']; // 'position' session variable holds either 'Student' or 'Supervisor'

        if ($userLevel == 'Student') {
            $query = "SELECT Student_Name FROM tbl_student WHERE Student_Matrix = :username";
        } else if ($userLevel == 'Supervisor') {
            $query = "SELECT name FROM tbl_supervisor WHERE username = :username";
        } else {
            throw new Exception("Invalid user level.");
        }

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $name = $stmt->fetchColumn();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }

    $conn = null;

    

?>
