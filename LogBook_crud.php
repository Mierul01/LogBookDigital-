<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'database.php';

$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Function to display SweetAlert
function showSweetAlert($title, $message, $icon) {
    echo "<script type='text/javascript'>
            Swal.fire({
              position: 'top-end',
              icon: '$icon',
              title: '$title',
              text: '$message',
              showConfirmButton: false,
              timer: 1500
            });
          </script>";
}

// CREATE operation
if (isset($_POST['create'])) {
    try {
        $pid = $_POST['pid'];
        $date = $_POST['date'];
        $prog = $_POST['prog'];
        $status = $_POST['status'];
        $problem = $_POST['problem'];
        $task = $_POST['task'];
        $studentMatrix = $_SESSION['username']; // Get student matrix from session

        // Check if the week number already exists for this student
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_logbook WHERE fld_minggu = :pid AND student_matrix = :student_matrix");
        $checkStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $checkStmt->bindParam(':student_matrix', $studentMatrix, PDO::PARAM_STR);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            showSweetAlert("Error", "Week number already exists for this student. Please choose a different number.", "error");
        } else {
            $stmt = $conn->prepare("INSERT INTO tbl_logbook(fld_minggu, fld_tarikh, fld_progress, fld_status, fld_problem, fld_task, student_matrix, supervisor_comment, supervisor_signature) VALUES(:pid, :date, :prog, :status, :problem, :task, :student_matrix, '', '')");

            $stmt->bindParam(':pid', $pid, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            $stmt->bindParam(':prog', $prog, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':problem', $problem, PDO::PARAM_STR);
            $stmt->bindParam(':task', $task, PDO::PARAM_STR);
            $stmt->bindParam(':student_matrix', $studentMatrix, PDO::PARAM_STR);

            $stmt->execute();
            showSweetAlert("Success", "Log book entry added successfully!", "success");
            header("refresh:1;url=LogBookPage.php");
            exit();
        }
    } catch (PDOException $e) {
        showSweetAlert("Error", $e->getMessage(), "error");
    }
}

// UPDATE operation
// UPDATE operation
if (isset($_POST['update'])) {
    try {
        $pid = $_POST['pid'];
        $date = $_POST['date'];
        $prog = $_POST['prog'];
        $status = $_POST['status'];
        $problem = $_POST['problem'];
        $task = $_POST['task'];
        $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
        $signature = isset($_POST['signature']) ? $_POST['signature'] : '';
        $oldpid = $_POST['oldpid'];
        $studentMatrix = $_POST['student_matrix']; // Get student matrix from hidden input

        $stmt = $conn->prepare("UPDATE tbl_logbook SET fld_minggu = :pid, fld_tarikh = :date, fld_progress = :prog, fld_status = :status, fld_problem = :problem, fld_task = :task, supervisor_comment = :comment, supervisor_signature = :signature WHERE fld_minggu = :oldpid AND student_matrix = :student_matrix");

        $stmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':prog', $prog, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':problem', $problem, PDO::PARAM_STR);
        $stmt->bindParam(':task', $task, PDO::PARAM_STR);
        $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
        $stmt->bindParam(':signature', $signature, PDO::PARAM_STR);
        $stmt->bindParam(':oldpid', $oldpid, PDO::PARAM_INT);
        $stmt->bindParam(':student_matrix', $studentMatrix, PDO::PARAM_STR);

        $stmt->execute();
        showSweetAlert("Success", "Log book entry updated successfully!", "success");
        header("refresh:1;url=LogBookPage.php");
        exit();
    } catch (PDOException $e) {
        showSweetAlert("Error", $e->getMessage(), "error");
    }
}

// DELETE operation
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM tbl_logbook WHERE fld_minggu = :pid AND student_matrix = :student_matrix");

        $stmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':student_matrix', $_GET['student_matrix'], PDO::PARAM_STR);

        $pid = $_GET['delete'];

        $stmt->execute();
        showSweetAlert("Success", "Log book entry deleted successfully!", "success");
        header("refresh:1;url=LogBookPage.php");
        exit();
    } catch (PDOException $e) {
        showSweetAlert("Error", $e->getMessage(), "error");
    }
}

// EDIT operation
if (isset($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT tbl_logbook.*, tbl_student.Student_Name FROM tbl_logbook JOIN tbl_student ON tbl_logbook.student_matrix = tbl_student.Student_Matrix WHERE fld_minggu = :pid AND tbl_logbook.student_matrix = :student_matrix");

        $stmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':student_matrix', $_GET['student_matrix'], PDO::PARAM_STR);

        $pid = $_GET['edit'];

        $stmt->execute();

        $editrow = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        showSweetAlert("Error", $e->getMessage(), "error");
    }
}

$conn = null;
?>
