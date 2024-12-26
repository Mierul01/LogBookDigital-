<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$userLevel = $_SESSION['position'];
$result = [];

// Define access rights
$accessRights = [
    'Student' => [
        'create' => true,
        'read' => true,
        'update' => true,
        'delete' => true
    ],
    'Supervisor' => [
        'create' => true,
        'read' => true,
        'update' => true,
        'delete' => false // Supervisors cannot delete logbook entries
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

// Fetch Student_Name and Supervisor_Name
$studentName = '';
$supervisorName = '';
$editrow = null;

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($userLevel === 'Student') {
        // Fetch student name and supervisor ID
        $stmt = $conn->prepare("SELECT Student_Name, supervisor_id FROM tbl_student WHERE Student_Matrix = :studentMatrix");
        $stmt->bindParam(':studentMatrix', $_SESSION['username'], PDO::PARAM_STR);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $studentName = $student['Student_Name'];
            $supervisorId = $student['supervisor_id'];

            // Fetch supervisor name using supervisor ID
            $stmt = $conn->prepare("SELECT name FROM tbl_supervisor WHERE username = :username");
            $stmt->bindParam(':username', $supervisorId, PDO::PARAM_STR);
            $stmt->execute();
            $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
            $supervisorName = $supervisor ? $supervisor['name'] : '';
        } else {
            echo "No student found with Matrix: " . $_SESSION['username'];
        }
    } elseif ($userLevel === 'Supervisor') {
        // Fetch supervisor name
        $stmt = $conn->prepare("SELECT name FROM tbl_supervisor WHERE username = :username");
        $stmt->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
        $stmt->execute();
        $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
        $supervisorName = $supervisor ? $supervisor['name'] : '';

        // Fetch students under this supervisor
        $stmt = $conn->prepare("SELECT Student_Name, Student_Matrix FROM tbl_student WHERE supervisor_id = :supervisorId");
        $stmt->bindParam(':supervisorId', $_SESSION['username'], PDO::PARAM_STR);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch data to edit if 'edit' parameter is set
    if (isset($_GET['edit'])) {
        $pid = $_GET['edit'];
        $stmt = $conn->prepare("SELECT * FROM tbl_logbook WHERE fld_minggu = :pid AND student_matrix = :student_matrix");
        $stmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':student_matrix', $_GET['student_matrix'], PDO::PARAM_STR);
        $stmt->execute();
        $editrow = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;

include_once 'LogBook_crud.php';
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
  <!--===============================================================================================-->  
  <style type="text/css">
    .custom-height {
        min-height: 100px;
    }

    .container-fluid {
        width: 100%;
        padding: 0 15px;
    }

    .table-responsive {
        width: 100%;
        overflow-x: hidden;
    }

    .table-responsive::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    .table-responsive {
        -ms-overflow-style: none;  /* Internet Explorer 10+ */
        scrollbar-width: none;  /* Firefox */
    }

    .table-responsive {
        overflow-x: scroll;
    }

    /* Enable scrollbars only on mobile devices */
    @media (max-width: 767px) {
        .table-responsive {
            overflow-x: auto;
        }
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        display: none;
    }

    .dt-button {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 5px 10px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        margin: 2px 2px;
        cursor: pointer;
        border-radius: 4px;
    }

    .dt-button .fa {
        margin-right: 5px;
    }

    .form-group.buttons {
        display: flex;
        justify-content: flex-start; /* Align buttons to the left */
        margin-top: 10px; /* Adjust the margin as needed */
    }

    .form-group.buttons .btn {
        margin-right: 2px; /* Add spacing between the buttons */
    }

    .table thead th {
        text-align: center !important; /* Center the text horizontally */
        vertical-align: top !important; /* Align the text to the top */
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
      <div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1">
        <?php if (hasAccessRights($userLevel, 'create') || (isset($_GET['edit']) && $userLevel === 'Supervisor')) { ?>
        <div class="page-header">
          <h2>Log Book Mingguan</h2>
        </div>

        <form action="LogBook_crud.php" method="post" class="form-horizontal" onsubmit="submitSignature()">
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label for="StudentName" class="col-sm-3 control-label">Nama Pelajar:</label>
                <div class="col-sm-9">
                  <input name="StudentName" type="text" class="form-control" id="StudentName" value="<?php echo isset($editrow['Student_Name']) ? $editrow['Student_Name'] : $studentName; ?>" readonly>
                </div>
              </div>
              <input type="hidden" name="student_matrix" value="<?php echo isset($editrow['student_matrix']) ? $editrow['student_matrix'] : $_SESSION['username']; ?>">

              <div class="form-group">
                <label for="SupervisorName" class="col-sm-3 control-label">Nama Supervisor:</label>
                <div class="col-sm-9">
                  <input name="SupervisorName" type="text" class="form-control" id="SupervisorName" value="<?php echo $supervisorName; ?>" readonly>
                </div>
              </div>

              <div class="form-group">
                <label for="WeekNo" class="col-sm-3 control-label">Week No.:</label>
                <div class="col-sm-9">
                  <select name="pid" class="form-control" id="WeekNo" required <?php if ($userLevel === 'Supervisor') echo 'readonly'; ?>>
                    <option value="" disabled selected>Week No.</option>
                    <?php
                    for ($i = 1; $i <= 10; $i++) {
                      $selected = (isset($editrow['fld_minggu']) && $editrow['fld_minggu'] == $i) ? 'selected' : '';
                      echo "<option value=\"$i\" $selected>$i</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label for="Date" class="col-sm-3 control-label">Tarikh:</label>
                <div class="col-sm-9">
                  <input name="date" type="date" class="form-control" id="Date" value="<?php echo isset($editrow['fld_tarikh']) ? $editrow['fld_tarikh'] : ''; ?>" required <?php if ($userLevel === 'Supervisor') echo 'readonly'; ?>>
                </div>
              </div>

              <div class="form-group">
                <label for="progress" class="col-sm-3 control-label">Kemajuan/Perkara Dibincang:</label>
                <div class="col-sm-9">
                  <textarea name="prog" class="form-control custom-height" id="progress" placeholder="Kemajuan/Perkara Dibincang" required rows="4" <?php if ($userLevel === 'Supervisor') echo 'readonly'; ?>><?php echo isset($editrow['fld_progress']) ? $editrow['fld_progress'] : ''; ?></textarea>
                </div>
              </div>

              <?php if ($userLevel === 'Supervisor' && isset($_GET['edit'])) { ?>
              <div class="form-group">
                <label for="SupervisorComment" class="col-sm-3 control-label">Supervisor Comment:</label>
                <div class="col-sm-9">
                  <textarea name="comment" class="form-control custom-height" id="SupervisorComment" placeholder="Supervisor Comment" required rows="4"><?php echo isset($editrow['supervisor_comment']) ? $editrow['supervisor_comment'] : ''; ?></textarea>
                </div>
              </div>
              <?php } ?>

              <div class="form-group buttons">
                <div class="col-sm-9 col-sm-offset-3">
                  <?php if (isset($_GET['edit'])) { ?>
                    <input type="hidden" name="oldpid" value="<?php echo $editrow['fld_minggu']; ?>">
                    <button class="btn btn-default" type="submit" name="update">
                      <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> Kemaskini
                    </button>
                    <button class="btn btn-default" type="reset">
                      <span class="glyphicon glyphicon-erase" aria-hidden="true"></span> Padam
                    </button>
                  <?php } else if ($userLevel === 'Student') { ?>
                    <button class="btn btn-default" type="submit" name="create">
                      <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Tambah
                    </button>
                    <button class="btn btn-default" type="reset">
                      <span class="glyphicon glyphicon-erase" aria-hidden="true"></span> Padam
                    </button>
                  <?php } ?>
                </div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="form-group">
                <label for="CurrentStatus" class="col-sm-3 control-label">Status Semasa:</label>
                <div class="col-sm-9">
                  <textarea name="status" class="form-control custom-height" id="CurrentStatus" placeholder="Status Semasa" required rows="3" <?php if ($userLevel === 'Supervisor') echo 'readonly'; ?>><?php echo isset($editrow['fld_status']) ? $editrow['fld_status'] : ''; ?></textarea>
                </div>
              </div>
              <div class="form-group">
                <label for="Problem" class="col-sm-3 control-label">Masalah:</label>
                <div class="col-sm-9">
                  <textarea name="problem" class="form-control custom-height" id="Problem" placeholder="Masalah" required rows="4" <?php if ($userLevel === 'Supervisor') echo 'readonly'; ?>><?php echo isset($editrow['fld_problem']) ? $editrow['fld_problem'] : ''; ?></textarea>
                </div>
              </div>
              <div class="form-group">
                <label for="TaskNextWeek" class="col-sm-3 control-label">Tugasan Minggu Hadapan:</label>
                <div class="col-sm-9">
                  <textarea name="task" class="form-control custom-height" id="TaskNextWeek" placeholder="Tugasan Minggu Hadapan" required rows="4" <?php if ($userLevel === 'Supervisor') echo 'readonly'; ?>><?php echo isset($editrow['fld_task']) ? $editrow['fld_task'] : ''; ?></textarea>
                </div>
              </div>

              <?php if ($userLevel === 'Supervisor' && isset($_GET['edit'])) { ?>
              <div class="form-group">
                <label for="SupervisorSignature" class="col-sm-3 control-label">Supervisor Signature:</label>
                <div class="col-sm-9">
                  <div id="signature-pad" class="signature-pad">
                    <canvas class="signature-pad"></canvas>
                    <input type="hidden" name="signature" id="signature" value="">
                  </div>
                </div>
              </div>
              <?php } ?>
            </div>
          </div>
        </form>
        
        <?php } ?>
      </div>
    </div>
   
    <div class="row">
      <div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1">
        <div class="page-header">
          <h2>Maklumat Log Book Mingguan</h2>
        </div>
        <div class="table-responsive">
          <table id="productTable" class="table table-striped table-bordered table-blue">
            <thead>
              <tr>
                <th>Nama</th>
                <th>No. Minggu</th>
                <th>Tarikh</th>
                <th>Kemajuan</th>
                <th>Status Semasa</th>
                <th>Masalah</th>
                <th>Tugasan Minggu Hadapan</th>
                <th>Komen Supervisor</th>
                <th>Tandatangan Supervisor</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                if ($userLevel === 'Student') {
                  // Fetch logbook entries for the student
                  $stmt = $conn->prepare("SELECT tbl_logbook.*, tbl_student.Student_Name FROM tbl_logbook JOIN tbl_student ON tbl_logbook.student_matrix = tbl_student.Student_Matrix WHERE tbl_logbook.student_matrix = :student_matrix ORDER BY tbl_logbook.fld_minggu ASC");
                  $stmt->bindParam(':student_matrix', $_SESSION['username'], PDO::PARAM_STR);
                } elseif ($userLevel === 'Supervisor') {
                  // Fetch logbook entries for all students under the supervisor
                  $stmt = $conn->prepare("SELECT tbl_logbook.*, tbl_student.Student_Name FROM tbl_logbook JOIN tbl_student ON tbl_logbook.student_matrix = tbl_student.Student_Matrix WHERE tbl_student.supervisor_id = :supervisor_id ORDER BY tbl_logbook.fld_minggu ASC");
                  $stmt->bindParam(':supervisor_id', $_SESSION['username'], PDO::PARAM_STR);
                }

                $stmt->execute();
                $result = $stmt->fetchAll();
              } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
              }

              foreach ($result as $readrow) {
                // Determine the status of the logbook
                if (empty($readrow['supervisor_comment']) && empty($readrow['supervisor_signature'])) {
                  $status = 'Pending';
                } elseif (!empty($readrow['supervisor_comment']) && !empty($readrow['supervisor_signature'])) {
                  $status = 'Checked';
                } else {
                  $status = 'No fill in yet';
                }
              ?>
              <tr>
                <td><?php echo $readrow['Student_Name']; ?></td>
                <td><?php echo $readrow['fld_minggu']; ?></td>
                <td><?php echo $readrow['fld_tarikh']; ?></td>
                <td><?php echo nl2br($readrow['fld_progress']); ?></td>
                <td><?php echo nl2br($readrow['fld_status']); ?></td>
                <td><?php echo nl2br($readrow['fld_problem']); ?></td>
                <td><?php echo nl2br($readrow['fld_task']); ?></td>
                <td><?php echo nl2br($readrow['supervisor_comment']); ?></td>
                <td><?php echo $readrow['supervisor_signature'] ? '<img src="'.$readrow['supervisor_signature'].'" alt="Signature" style="width: 170px; height: auto;">' : ''; ?></td>
                <td>
                  <?php
                  if ($status === 'Checked') {
                    echo '<div class="checked-button">Checked</div>';
                  } elseif ($status === 'Pending') {
                    echo '<div class="pending-button">Pending</div>';
                  } else {
                    echo $status;
                  }
                  ?>
                </td>
                <td class="action-buttons">
                  <?php if ($status === 'Checked') { ?>
                    <a href="LogBookPage.php?delete=<?php echo $readrow['fld_minggu']; ?>&student_matrix=<?php echo $readrow['student_matrix']; ?>" onclick="return confirm('Are you sure to delete?');" class="btn btn-danger btn-xs" role="button"><i class="fas fa-times"></i></a>
                  <?php } elseif (hasAccessRights($userLevel, 'update') && $status !== 'Checked') { ?>
                    <a href="LogBookPage.php?edit=<?php echo $readrow['fld_minggu']; ?>&student_matrix=<?php echo $readrow['student_matrix']; ?>" class="btn btn-success btn-xs" role="button"><i class="fas fa-pencil-alt"></i></a>
                    <?php if (hasAccessRights($userLevel, 'delete')) { ?>
                      <a href="LogBookPage.php?delete=<?php echo $readrow['fld_minggu']; ?>&student_matrix=<?php echo $readrow['student_matrix']; ?>" onclick="return confirm('Are you sure to delete?');" class="btn btn-danger btn-xs" role="button"><i class="fas fa-times"></i></a>
                    <?php } ?>
                  <?php } ?>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer bg-light">
    <div class="container">
      <div class="row">
        <div class="col-xs-12 text-center">
          <p class="text-muted2">© 2024 LogBookDigital+. All Rights Reserved.</p>
        </div>
      </div>
    </div>
  </footer>

  <!-- External JavaScript -->
  <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
  <script src="js/table.js"></script>
  <script src="js/clock.js"></script> 

   <script>
      var canvas = document.querySelector('.signature-pad canvas');
      var signaturePad = new SignaturePad(canvas);

      document.getElementById('clear-signature').addEventListener('click', function () {
        signaturePad.clear();
      });

      function resizeCanvas() {
        var ratio =  Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
      }

      window.addEventListener("resize", resizeCanvas);
      resizeCanvas();

      function submitSignature() {
        var signatureData = signaturePad.toDataURL('image/png');
        document.getElementById('signature').value = signatureData;
      }
   </script>
  
</body>
</html>





