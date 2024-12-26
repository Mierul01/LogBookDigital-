<?php
session_start();

include_once 'database.php';

$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $loginPassword = sha1($_POST['passwords']);

    try {
        // Check in tbl_supervisor first
        $stmt = $conn->prepare("SELECT passwords, position FROM tbl_supervisor WHERE username = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Supervisor found
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $hashedPassword = $result['passwords'];
            $userLevel = $result['position'];

            // Verify the login password against the hashed password
            if ($loginPassword === $hashedPassword) {
                // Login successful for supervisor
                $_SESSION['username'] = $username;
                $_SESSION['position'] = $userLevel; // Store user level in session
                header("Location: index.php");
                exit();
            } else {
                // Invalid password
                echo "Invalid password.";
            }
        } else {
            // Check in tbl_student if not found in tbl_supervisor
            $stmt = $conn->prepare("SELECT Student_Password, Student_Position FROM tbl_student WHERE Student_Matrix = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Student found
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashedPassword = $result['Student_Password'];
                $userLevel = $result['Student_Position'];

                // Verify the login password against the hashed password
                if ($loginPassword === $hashedPassword) {
                    // Login successful for student
                    $_SESSION['username'] = $username;
                    $_SESSION['position'] = $userLevel; // Store user level in session
                    header("Location: index.php");
                    exit();
                } else {
                    // Invalid password
                    echo "Invalid password.";
                }
            } else {
                // User not found in either table
                echo "User not found.";
            }
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    $conn = null;
}
?>
