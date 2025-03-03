<!DOCTYPE html>
<html lang="en">
<?php
require("auth.php");
checkLoggedIn($db);
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <title>Bread and Butter | Admin Dashboard</title>
    <!-- Bootstrap Core CSS -->
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="fix-header">
    <!-- Preloader - style you can find in spinners.css -->
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
    <!-- Main wrapper  -->
    <div id="main-wrapper">
        <!-- header header  -->
        <?php require('header.php'); ?>
        <!-- End header header -->
        <!-- Left Sidebar  -->
        <?php require('sidebar.php'); ?>
        <!-- End Left Sidebar  -->
        <!-- Page wrapper  -->
        <div class="page-wrapper" style="height:1200px;">
            <!-- Bread crumb -->
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h3 class="text-primary">Dashboard</h3>
                </div>
            </div>
            <!-- End Bread crumb -->
            <!-- Container fluid  -->
            <div class="container-fluid">
                <!-- Start Page Content -->
                <div class="row">


                    <!-- Users Card -->
                    <div class="col-md-3">
                        <a href="all_users.php">
                            <div class="card p-30">
                                <div class="media">
                                    <div class="media-left meida media-middle">
                                        <span><i class="fa fa-users f-s-40" aria-hidden="true"></i></span>
                                    </div>
                                    <div class="media-body media-text-right">
                                        <h2>
                                            <?php
                                            $stmt = $db->prepare("SELECT COUNT(*) as user_count FROM users");
                                            $stmt->execute();
                                            $stmt->bind_result($user_count);
                                            $stmt->fetch();
                                            $stmt->close();
                                            echo $user_count;
                                            ?>
                                        </h2>
                                        <p class="m-b-0">Users</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Items Card -->
                    <div class="col-md-3">
                        <a href="all_items.php">
                            <div class="card p-30">
                                <div class="media">
                                    <div class="media-left meida media-middle">
                                        <span><i class="fa fa-cutlery f-s-40" aria-hidden="true"></i></span>
                                    </div>
                                    <div class="media-body media-text-right">
                                        <h2>
                                            <?php
                                            $stmt = $db->prepare("SELECT COUNT(*) as item_count FROM items");
                                            $stmt->execute();
                                            $stmt->bind_result($item_count);
                                            $stmt->fetch();
                                            $stmt->close();
                                            echo $item_count;
                                            ?>
                                        </h2>
                                        <p class="m-b-0">Items</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>


                    <!-- Orders Card -->
                    <div class="col-md-3">
                        <a href="all_orders.php">
                            <div class="card p-30">
                                <div class="media">
                                    <div class="media-left meida media-middle">
                                        <span><i class="fa fa-list f-s-40" aria-hidden="true"></i></span>
                                    </div>
                                    <div class="media-body media-text-right">
                                        <h2>
                                            <?php
                                            $stmt = $db->prepare("SELECT COUNT(*) as order_count FROM orders");
                                            $stmt->execute();
                                            $stmt->bind_result($order_count);
                                            $stmt->fetch();
                                            $stmt->close();
                                            echo $order_count;
                                            ?>
                                        </h2>
                                        <p class="m-b-0">Orders</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>





                </div>
                <!-- End Page Content -->
            </div>
            <!-- End Container fluid  -->
        </div>
        <!-- End Page wrapper  -->
    </div>
    <!-- End Wrapper -->
    <!-- All Jquery -->
    <script src="js/lib/jquery/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="js/jquery.slimscroll.js"></script>
    <!-- Menu sidebar -->
    <script src="js/sidebarmenu.js"></script>
    <!-- stickey kit -->
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="js/custom.min.js"></script>

</body>

</html>