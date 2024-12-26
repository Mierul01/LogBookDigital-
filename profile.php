<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$userLevel = $_SESSION['position'];

// Define access rights
$accessRights = [
    'Normal Staff' => [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false
    ],
    'Supervisor' => [
        'create' => true,
        'read' => true,
        'update' => true,
        'delete' => true
    ]
];

function hasAccessRights($userLevel, $action) {
    global $accessRights;

    if (isset($accessRights[$userLevel])) {
        return $accessRights[$userLevel][$action];
    }

    return false;
}

include_once 'database.php';

// Fetch information from the database
$fullName = '';
$supervisorName = '';
$email = '';
$position = '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($userLevel == 'Student') {
        // Fetch student information
        $stmt = $conn->prepare("SELECT Student_Name, Student_Matrix, supervisor_id, Student_Position FROM tbl_student WHERE Student_Matrix = :studentMatrix");
        $stmt->bindParam(':studentMatrix', $_SESSION['username'], PDO::PARAM_STR);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $fullName = $student['Student_Name'];
            $email = $student['Student_Matrix'];
            $supervisorId = $student['supervisor_id'];
            $position = $student['Student_Position'];

            // Fetch supervisor name
            $stmt = $conn->prepare("SELECT name FROM tbl_supervisor WHERE username = :username");
            $stmt->bindParam(':username', $supervisorId, PDO::PARAM_STR);
            $stmt->execute();
            $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
            $supervisorName = $supervisor ? $supervisor['name'] : '';
        } else {
            echo "No student found with Matrix: " . $_SESSION['username'];
        }
    } elseif ($userLevel == 'Supervisor') {
        // Fetch supervisor information
        $stmt = $conn->prepare("SELECT name, email, position FROM tbl_supervisor WHERE username = :username");
        $stmt->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
        $stmt->execute();
        $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($supervisor) {
            $fullName = $supervisor['name'];
            $email = $supervisor['email'];
            $position = $supervisor['position'];
            $supervisorName = ''; // Empty row for supervisor
        } else {
            echo "No supervisor found with Username: " . $_SESSION['username'];
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;

include_once 'profile_crud.php';
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Digital LogBook+</title>
	
<!--===============================================================================================-->	
  <link rel="icon" type="image/png" href="images/icons/favicon.ico"/>
<!--===============================================================================================-->	    
  <link href="css/bootstrap.min.css" rel="stylesheet">
<!--===============================================================================================-->	
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">
<!--===============================================================================================-->	
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.css">
<!--===============================================================================================-->	
  <link href="style1.css" rel="stylesheet">
<!--===============================================================================================-->	
<link href="css/style1.css" rel="stylesheet">
<!--===============================================================================================-->
  <link href="css/style2.css" rel="stylesheet">
<!--===============================================================================================-->	
<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Cooper+Black&display=swap');

    body {
      margin: 0;
      padding: 0;
      background-color: #fff;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .footer {
      padding: 10px 0;
      background-color: #f9f9f9;
      border-top: 1px solid #e5e5e5;
      text-align: center;
    }
  </style>
</head>
<body>
  <header>
    <div class="clock-container">
      <div id="clock" class="dark">
        <div class="display">
          <div class="weekdays"></div>
          <div class="ampm"></div>
          <div class="alarm"></div>
          <div class="digits"></div>
        </div>             
      </div>
    </div>
  </header>

  <?php include_once 'nav_bar.php'; ?>
  
  <div class="container-fluid">
    <div class="container">
      <div class="main-body">
        <div class="row gutters-sm">
          <div class="col-md-4 mb-3">
            <div class="card">
              <div class="card-body">
                <div class="d-flex flex-column align-items-center text-center">
                  <img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="Admin" class="rounded-circle" width="150">
                  <div class="mt-3">
                    <h4><?php echo $fullName; ?></h4>
                    <p class="text-secondary mb-1"><?php echo $position; ?></p>
                    <p class="text-muted font-size-sm">UNIVERSITI KEBANGSAAN MALAYSIA, UKM</p>
                    <!-- <button class="btn btn-primary">Log Out</button> -->
                    <!-- <button class="btn btn-outline-primary">Message</button> -->
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-8">
            <div class="card mb-3">
              <div class="card-body">
                <div class="row">
                  <div class="col-sm-3">
                    <h6 class="mb-0">FULL NAME:</h6>
                  </div>
                  <div class="col-sm-9 text-secondary">
                    <?php echo $fullName; ?>
                  </div>
                </div>
                <hr>
                <?php if ($userLevel === 'Student') { ?>
                <div class="row">
                  <div class="col-sm-3">
                    <h6 class="mb-0">SUPERVISOR NAME:</h6>
                  </div>
                  <div class="col-sm-9 text-secondary">
                    <?php echo $supervisorName; ?>
                  </div>
                </div>
                <hr>
                <?php } ?>
                <div class="row">
                  <div class="col-sm-3">
                    <h6 class="mb-0">EMAIL:</h6>
                  </div>
                  <div class="col-sm-9 text-secondary">
                    <?php echo $email; ?>
                  </div>
                </div>
                <hr>
                <div class="row">
                  <div class="col-sm-3">
                    <h6 class="mb-0">POSITION:</h6>
                  </div>
                  <div class="col-sm-9 text-secondary">
                    <?php echo $position; ?>
                  </div>
                </div>
                <hr>
                <div class="row">
                  <div class="col-sm-12">
                    <a class="btn btn-info" target="__blank" href="https://www.bootdey.com/snippets/view/profile-edit-data-and-skills">Edit</a>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer bg-light">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 text-center">
          <p class="text-muted1">© 2024 Your Company Name. All Rights Reserved.</p>
        </div>
      </div>
    </div>
  </footer>

<!--===============================================================================================-->	
  <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<!--===============================================================================================-->	
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<!--===============================================================================================-->	
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<!--===============================================================================================-->	
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.js"></script>
<!--===============================================================================================-->	
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<!--===============================================================================================-->	
  <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<!--===============================================================================================-->	
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<!--===============================================================================================-->	
  <script src="js/table.js"></script>
<!--===============================================================================================-->	
  <script src="js/clock.js"></script>
<!--===============================================================================================-->	

</body>
</html>
