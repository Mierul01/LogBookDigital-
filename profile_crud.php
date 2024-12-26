<?php
 
include_once 'database.php';
 
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
if (isset($_POST['create'])) {

  try {
    // Check if the customer ID already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_customers_a192910_pt2 WHERE fld_customer_num = :cid");
    $checkStmt->bindParam(':cid', $_POST['cid'], PDO::PARAM_STR);
    $checkStmt->execute();
    $existingCount = $checkStmt->fetchColumn();

    if ($existingCount > 0) {
      // Display a pop-up message or handle the error in your preferred way
      echo "<script>alert('Customer ID already exists. Please choose a different ID.');</script>";
    } else {
      // Proceed with the insertion if the ID doesn't exist
      $stmt = $conn->prepare("INSERT INTO tbl_customers_a192910_pt2(fld_customer_num, fld_customer_fname, fld_customer_lname, fld_customer_gender, fld_customer_phone, fld_customer_membership) VALUES(:cid, :fname, :lname, :gender, :phone, :membership)");

      $stmt->bindParam(':cid', $cid, PDO::PARAM_STR);
      $stmt->bindParam(':fname', $fname, PDO::PARAM_STR);
      $stmt->bindParam(':lname', $lname, PDO::PARAM_STR);
      $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
      $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
      $stmt->bindParam(':membership', $membership, PDO::PARAM_STR);

      $cid = $_POST['cid'];
      $fname = $_POST['fname'];
      $lname = $_POST['lname'];
      $gender =  $_POST['gender'];
      $phone = $_POST['phone'];
      $membership = $_POST['membership'];

      $stmt->execute();
      echo "<script>alert('Customer Data Added!');</script>";
    }
  } catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
  }
}
 
//Update
if (isset($_POST['update'])) {
   
  try {
 
    $stmt = $conn->prepare("UPDATE tbl_customers_a192910_pt2 SET fld_customer_num = :cid, fld_customer_fname = :fname, fld_customer_lname = :lname, fld_customer_gender = :gender, fld_customer_phone = :phone, fld_customer_membership = :membership
      WHERE fld_customer_num = :oldcid");
   
    $stmt->bindParam(':cid', $cid, PDO::PARAM_STR);
    $stmt->bindParam(':fname', $fname, PDO::PARAM_STR);
    $stmt->bindParam(':lname', $lname, PDO::PARAM_STR);
    $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':membership', $membership, PDO::PARAM_STR);
    $stmt->bindParam(':oldcid', $oldcid, PDO::PARAM_STR);
       
    $cid = $_POST['cid'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $gender =  $_POST['gender'];
    $phone = $_POST['phone'];
    $membership = $_POST['membership'];
    $oldcid = $_POST['oldcid'];
       
    $stmt->execute();
 
    header("Location: customers.php");
    }
 
  catch(PDOException $e)
  {
      echo "Error: " . $e->getMessage();
  }
}
 
//Delete
if (isset($_GET['delete'])) {
 
  try {
 
    $stmt = $conn->prepare("DELETE FROM tbl_customers_a192910_pt2 WHERE fld_customer_num = :cid");
   
    $stmt->bindParam(':cid', $cid, PDO::PARAM_STR);
       
    $cid = $_GET['delete'];
     
    $stmt->execute();
 
    header("Location: customers.php");
    }
 
  catch(PDOException $e)
  {
      echo "Error: " . $e->getMessage();
  }
}
 
//Edit
if (isset($_GET['edit'])) {
   
  try {
 
    $stmt = $conn->prepare("SELECT * FROM tbl_customers_a192910_pt2 WHERE fld_customer_num = :cid");
   
    $stmt->bindParam(':cid', $cid, PDO::PARAM_STR);
       
    $cid = $_GET['edit'];
     
    $stmt->execute();
 
    $editrow = $stmt->fetch(PDO::FETCH_ASSOC);
    }
 
  catch(PDOException $e)
  {
      echo "Error: " . $e->getMessage();
  }
}
 
  $conn = null;
 
?>