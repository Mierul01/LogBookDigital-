<?php
  session_start();


//Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$userLevel = $_SESSION['position'];

// Check if the user is "Normal Staff"
if ($userLevel === 'Student') {
    echo '<script>alert("You do not have access to this page."); window.location.href = "index.php";</script>';
    exit();
}

// Define access rights
$accessRights = [
    'Student' => [
        'create' => false,
        'read' => false,
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

// Check access rights
if (!isset($_SESSION['username']) || !hasAccessRights($userLevel, 'read') || !hasAccessRights($userLevel, 'update')) {
    header("Location: login.php");
    exit();
}

function hasAccessRights($userLevel, $action) {
    global $accessRights;

    if (isset($accessRights[$userLevel])) {
        return $accessRights[$userLevel][$action];
    }

    return false;
}

include_once 'SupervisorPage_crud.php';
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<!--===============================================================================================-->	
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">
<!--===============================================================================================-->	
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.css">
<!--===============================================================================================-->	
<link href="css/style1.css" rel="stylesheet">
<!--===============================================================================================-->
  <link href="css/style2.css" rel="stylesheet">

  <style>
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
      display: none;
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
    <div class="row">
      <div class="col-xs-12 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
        <div class="page-header">
          <h2>Add New Student</h2>
        </div>

        <form action="SupervisorPage_crud.php" method="post" class="form-horizontal">
          <div class="form-group">
              <label for="Student_Matrix" class="col-sm-3 control-label">Student Matrix:</label>
              <div class="col-sm-9">
                  <input name="Student_Matrix" type="text" class="form-control" id="Student_Matrix" placeholder="Student Matrix" value="<?php if(isset($_GET['edit'])) echo $editrow['Student_Matrix']; ?>" required>
              </div>
          </div>

          <div class="form-group">
              <label for="Student_Name" class="col-sm-3 control-label">Student Name:</label>
              <div class="col-sm-9">
                  <input name="Student_Name" type="text" class="form-control" id="Student_Name" placeholder="Student Name" value="<?php if(isset($_GET['edit'])) echo $editrow['Student_Name']; ?>" required>
              </div>
          </div>

          <div class="form-group">
              <label for="Student_Position" class="col-sm-3 control-label">Position:</label>
              <div class="col-sm-9">
                  <select name="Student_Position" class="form-control" id="Student_Position" required>
                      <option value="" disabled <?php if (!isset($_GET['edit'])) echo "selected"; ?>>Select Position</option>
                      <option value="Student" <?php if(isset($_GET['edit']) && $editrow['Student_Position']=="Student") echo "selected"; ?>>Student</option>
                  </select>
              </div>
          </div>

          <div class="form-group">
              <label for="Student_Password" class="col-sm-3 control-label">Password:</label>
              <div class="col-sm-9">
                  <input name="Student_Password" type="password" class="form-control" id="Student_Password" placeholder="Student Password" value="<?php if(isset($_GET['edit'])) echo $editrow['Student_Password']; ?>" required>
              </div>
          </div>

          <input type="hidden" name="supervisor_id" value="<?php echo $_SESSION['username']; ?>">

          <div class="form-group">
              <div class="col-sm-offset-3 col-sm-9">
                  <?php if (isset($_GET['edit'])) { ?>
                      <input type="hidden" name="old_Student_Matrix" value="<?php echo $editrow['Student_Matrix']; ?>">
                      <button type="submit" name="update" class="btn btn-success">
                          <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> Kemaskini
                      </button>
                  <?php } else { ?>
                      <button type="submit" name="create" class="btn btn-success">
                          <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Tambah
                      </button>
                  <?php } ?>
              </div>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
        <div class="page-header">
          <h2>Student List</h2>
        </div>
        <table id="productTable" class="table table-striped table-bordered table-blue">
          <thead>
            <tr>
              <th>Student Matrix</th>
              <th>Student Name</th>
              <th>Student Position</th>
              <th>Student Password</th>
              <th>Supervisor</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $conn->prepare("SELECT s.*, sup.name AS supervisor_name FROM tbl_student s LEFT JOIN tbl_supervisor sup ON s.Supervisor_ID = sup.username WHERE s.Supervisor_ID = :supervisor_id");
                $stmt->bindParam(':supervisor_id', $_SESSION['username'], PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll();
            } catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            foreach($result as $readrow) {
                ?>
                <tr>
                    <td><?php echo $readrow['Student_Matrix']; ?></td>
                    <td><?php echo $readrow['Student_Name']; ?></td>
                    <td><?php echo $readrow['Student_Position']; ?></td>
                    <td><?php echo $readrow['Student_Password']; ?></td>
                    <td><?php echo $readrow['supervisor_name']; ?></td>
                    <td class="action-buttons">
                      <?php if (hasAccessRights($userLevel, 'update')) { ?>
                          <a href="SupervisorPage.php?edit=<?php echo $readrow['Student_Matrix']; ?>" class="btn btn-warning btn-xs" role="button"><i class="fas fa-pencil-alt"></i></a>
                      <?php } ?>

                      <?php if (hasAccessRights($userLevel, 'delete')) { ?>
                          <a href="SupervisorPage.php?delete=<?php echo $readrow['Student_Matrix']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure to delete?');" role="button"><i class="fas fa-times"></i></a>
                      <?php } ?>
                  </td>
                </tr>
                <?php
            }
            $conn = null;
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <footer class="footer bg-light">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 text-center">
          <p class="text-muted2">© 2024 Your Company Name. All Rights Reserved.</p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Include your JS files here -->
  <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
  <script src="js/table.js"></script>
  <script src="js/clock.js"></script> 

</body>
</html>

