<!DOCTYPE html>
<html lang="en">
<?php
require_once '../connection/db.php';
error_reporting(0);
session_start();

if (isset($_POST['submit'])) {
    $a_name = $_POST['a_name'];
    $a_pw = $_POST['a_pw'];
    
    if (!empty($_POST['submit'])) {
        // Prepare the SQL statement
        $stmt = $db->prepare("SELECT * FROM bread_admin WHERE bread_admin_username = ?");
        $stmt->bind_param("s", $a_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // Verify the password
        if (is_array($row) && password_verify($a_pw, $row['bread_admin_password'])) {
            $_SESSION["bread_admin"] = $row['bread_admin_id'];
            header('Location: dashboard.php');
            exit();
        } else {
            $message = "Invalid Username or Password!";
        }
        
        // Close the statement
        $stmt->close();
    }
}
?>


<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
  <title>Bread and Butter  | Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
  <link rel='stylesheet prefetch' href='https://fonts.googleapis.com/css?family=Roboto:400,100,300,500,700,900'>
  <link rel='stylesheet prefetch' href='https://fonts.googleapis.com/css?family=Montserrat:400,700'>
  <link rel='stylesheet prefetch' href='https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css'>
  <link rel="stylesheet" href="css/login.css">
</head>

<body>
<div class="container">
  <div class="info">
    <h1>Bread and Butter </h1><h2>Admin Login</h2>
  </div>
</div>
<div class="form">
  <div class="thumbnail"><img src="images/manager.png"/></div>
  
  <span style="color:red;"><?php echo $message; ?></span>
  <span style="color:green;"><?php echo $success; ?></span>
  <br>
  <br>
  <form class="login-form" action="index.php" method="post">
    <input type="text" placeholder="Username" name="a_name" required />
    <input type="password" placeholder="Password" name="a_pw" required />
    <input type="submit" name="submit" value="Login" />
  </form>
</div>

<script src='http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
<script src='js/index.js'></script>
</body>
</html>
