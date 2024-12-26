<?php
include_once 'database.php';

$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Registration
if (isset($_POST['register'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO tbl_supervisor(username, name, email, position, passwords)
            VALUES(:username, :name, :email, :position, :passwords)");

        $username = $_POST['username'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $position = $_POST['position']; // Ensure this matches the name attribute in the form
        $passwords = $_POST['pass'];
        $hashedPassword = sha1($passwords); // Hash the password

        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':position', $position, PDO::PARAM_STR);
        $stmt->bindParam(':passwords', $hashedPassword, PDO::PARAM_STR);

        $stmt->execute();

        // Registration successful message
        echo "Successful registration.";

        // Redirect to login.php
        header("Location: login.php");
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

$conn = null;
?>
