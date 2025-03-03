<!DOCTYPE html>
<html lang="en">
<?php
require("auth.php");
checkLoggedIn($db);
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <title>Gallery Cafe | Orders</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body class="fix-header fix-sidebar">
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>

    <div id="main-wrapper">
        <?php require('header.php'); ?>
        <?php require('sidebar.php'); ?>

        <div class="page-wrapper">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h3 class="text-primary">Orders</h3>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">All Orders</h4>
                                <h6 class="card-subtitle">List of all orders</h6>
                                <div class="table-responsive m-t-40">
                                    <table id="example234" class="display nowrap table table-hover table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer Name</th>
                                                <th>Contact Number</th>
                                                <th>Order Date</th>
                                                <th>Update Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>User Name</th>
                                                <th>Contact Number</th>
                                                <th>Order Date</th>
                                                <th>Update Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </tfoot>
                                        <tbody>
                                            <?php
                                            // Updated query to join orders with users table
                                            $sql = "
                                                SELECT 
                                                    o.order_id, 
                                                    o.order_date, 
                                                    o.update_date, 
                                                    o.status, 
                                                    u.username, 
                                                    u.phone_number 
                                                FROM 
                                                    orders o
                                                JOIN 
                                                    users u 
                                                ON 
                                                    o.user_id = u.user_id
                                            ";
                                            $query = mysqli_query($db, $sql);

                                            if (!mysqli_num_rows($query) > 0) {
                                                echo '<tr><td colspan="7"><center>No Orders Data Found</center></td></tr>';
                                            } else {
                                                while ($rows = mysqli_fetch_array($query)) {
                                                    $status = ($rows['status'] == 'Pending')
                                                        ? '<span class="badge badge-warning">Pending</span>'
                                                        : ($rows['status'] == 'Confirmed'
                                                            ? '<span class="badge badge-info">Confirmed</span>'
                                                            : ($rows['status'] == 'Processing'
                                                                ? '<span class="badge badge-primary">Processing</span>'
                                                                : ($rows['status'] == 'Ready To Pickup'
                                                                    ? '<span class="badge badge-success">Ready To Pickup</span>'
                                                                    : ($rows['status'] == 'Completed'
                                                                        ? '<span class="badge badge-secondary">Completed</span>'
                                                                        : '<span class="badge badge-danger">Canceled</span>'))));

                                                    echo '<tr>
                                                            <td>' . $rows['order_id'] . '</td>
                                                            <td>' . $rows['username'] . '</td>
                                                            <td>' . $rows['phone_number'] . '</td>
                                                            <td>' . $rows['order_date'] . '</td>
                                                            <td>' . $rows['update_date'] . '</td>
                                                            <td>' . $status . '</td>                                                    
                                                            <td>
                                                                <a href="view_order.php?id=' . $rows['order_id'] . '" class="btn btn-info btn-sm">
                                                                    <i class="fa fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/lib/jquery/jquery.min.js"></script>
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>

    <script src="js/lib/datatables/datatables.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
    <script src="js/lib/datatables/cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script src="js/lib/datatables/cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
    <script src="js/lib/datatables/cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
    <script src="js/lib/datatables/datatables-init.js"></script>
</body>
<script>
  $(document).ready(function() {
    $('#example234').DataTable({
        "order": [
            [4, 'desc']
        ],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

</script>

</html>
