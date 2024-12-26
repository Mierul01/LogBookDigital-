<?php

include_once 'database.php';

$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create
if (isset($_POST['create'])) {
    try {
        // Check if the student matrix already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_Student WHERE Student_Matrix = :Student_Matrix");
        $checkStmt->bindParam(':Student_Matrix', $_POST['Student_Matrix'], PDO::PARAM_STR);
        $checkStmt->execute();
        $existingCount = $checkStmt->fetchColumn();

        if ($existingCount > 0) {
            // Display a pop-up message or handle the error in your preferred way
            echo "<script>alert('Student Matrix already exists. Please choose a different Matrix.');</script>";
        } else {
            // Proceed with the insertion if the Matrix doesn't exist
            $stmt = $conn->prepare("INSERT INTO tbl_Student(Student_Matrix, Student_Name, Student_Position, Student_Password, supervisor_id) 
                                    VALUES (:Student_Matrix, :Student_Name, :Student_Position, :Student_Password, :supervisor_id)");

            $stmt->bindParam(':Student_Matrix', $Student_Matrix, PDO::PARAM_STR);
            $stmt->bindParam(':Student_Name', $Student_Name, PDO::PARAM_STR);
            $stmt->bindParam(':Student_Position', $Student_Position, PDO::PARAM_STR);
            $stmt->bindParam(':Student_Password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':supervisor_id', $supervisor_id, PDO::PARAM_STR);

            $Student_Matrix = $_POST['Student_Matrix'];
            $Student_Name = $_POST['Student_Name'];
            $Student_Position = $_POST['Student_Position'];
            $Student_Password = $_POST['Student_Password'];
            $supervisor_id = $_POST['supervisor_id'];

            $hashedPassword = sha1($Student_Password); // Hash the password

            $stmt->execute();
            echo "<script>alert('Student added!');</script>";
            header("Location: SupervisorPage.php");
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Update
if (isset($_POST['update'])) {
    try {
        $stmt = $conn->prepare("UPDATE tbl_Student SET
            Student_Matrix = :Student_Matrix,
            Student_Name = :Student_Name,
            Student_Position = :Student_Position,
            Student_Password = :Student_Password,
            supervisor_id = :supervisor_id
            WHERE Student_Matrix = :old_Student_Matrix");

        $stmt->bindParam(':Student_Matrix', $Student_Matrix, PDO::PARAM_STR);
        $stmt->bindParam(':Student_Name', $Student_Name, PDO::PARAM_STR);
        $stmt->bindParam(':Student_Position', $Student_Position, PDO::PARAM_STR);
        $stmt->bindParam(':Student_Password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':supervisor_id', $supervisor_id, PDO::PARAM_STR);
        $stmt->bindParam(':old_Student_Matrix', $old_Student_Matrix, PDO::PARAM_STR);

        $Student_Matrix = $_POST['Student_Matrix'];
        $Student_Name = $_POST['Student_Name'];
        $Student_Position = $_POST['Student_Position'];
        $Student_Password = $_POST['Student_Password'];
        $supervisor_id = $_POST['supervisor_id'];
        $hashedPassword = sha1($Student_Password); // Hash the password
        $old_Student_Matrix = $_POST['old_Student_Matrix'];

        $stmt->execute();
        echo "<script>alert('Student updated!');</script>";
        header("Location: SupervisorPage.php");
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Delete
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM tbl_Student WHERE Student_Matrix = :Student_Matrix");

        $stmt->bindParam(':Student_Matrix', $Student_Matrix, PDO::PARAM_STR);
        $Student_Matrix = $_GET['delete'];

        $stmt->execute();
        header("Location: SupervisorPage.php");
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Edit
if (isset($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM tbl_Student WHERE Student_Matrix = :Student_Matrix");

        $stmt->bindParam(':Student_Matrix', $Student_Matrix, PDO::PARAM_STR);
        $Student_Matrix = $_GET['edit'];

        $stmt->execute();
        $editrow = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

$conn = null;

?>
