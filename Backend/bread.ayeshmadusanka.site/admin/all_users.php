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
    <title>Gallery Cafe | Customers</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <h3 class="text-primary">Customers</h3>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Customer List</h4>
                                <h6 class="card-subtitle">List of all customers</h6>
                                <div class="table-responsive m-t-40">
                                    <table id="example234" class="display nowrap table table-hover table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Phone Number</th>
                                                <th>Created Date</th>
                                                <th>Updated Date</th>
                                                <th>Status</th>
                                                <th>Recommendations</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Phone Number</th>
                                                <th>Created Date</th>
                                                <th>Updated Date</th>
                                                <th>Status</th>
                                                <th>Recommendations</th>
                                            </tr>
                                        </tfoot>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT * FROM users";
                                            $query = mysqli_query($db, $sql);

                                            if (mysqli_num_rows($query) > 0) {
                                                while ($rows = mysqli_fetch_array($query)) {
                                                    $statusClass = $rows['is_active'] ? 'badge-info' : 'badge-danger';
                                                    $statusText = $rows['is_active'] ? 'Active' : 'Inactive';

                                                    echo '<tr>
                                                        <td>' . htmlspecialchars($rows['user_id']) . '</td>
                                                        <td>' . htmlspecialchars($rows['username']) . '</td>
                                                        <td>' . htmlspecialchars($rows['phone_number']) . '</td>
                                                        <td>' . htmlspecialchars($rows['created_at']) . '</td>
                                                        <td>' . htmlspecialchars($rows['updated_at']) . '</td>
                                                        <td><span class="badge ' . $statusClass . '">' . $statusText . '</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary btn-recommend" data-user="' . htmlspecialchars($rows['user_id']) . '">
                                                                View Recommendations
                                                            </button>
                                                        </td>
                                                    </tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="7"><center>No Users Found</center></td></tr>';
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

    <!-- JS Libraries -->
    <script src="js/lib/jquery/jquery.min.js"></script>
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="js/lib/datatables/datatables.min.js"></script>
    <script src="js/lib/datatables/datatables-init.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#example234').DataTable({
            "order": [[3, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        // Delegated event binding for dynamically generated elements (DataTables pagination)
        $(document).on('click', '.btn-recommend', function() {
            var user_id = $(this).data('user');
            // Call Flask API to get recommendations for this user
            $.ajax({
                url: 'https://model.ayeshmadusanka.site/recommendations/' + user_id,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    var rec_ids = response.recommendations;
                    if (rec_ids.length === 0) {
                        Swal.fire('No Recommendations', 'No recommendations available for user ' + user_id, 'info');
                        return;
                    }
                    // Call PHP endpoint to get item details for these IDs
                    $.ajax({
                        url: 'get_item_details.php',
                        method: 'GET',
                        data: { ids: rec_ids.join(',') },
                        dataType: 'json',
                        success: function(itemResponse) {
                            var html = '<ul style="list-style: none; padding-left: 0;">';
                            $.each(itemResponse, function(index, item) {
                                html += '<li style="margin-bottom: 10px;">' +
                                    '<img src="' + item.image_path + '" width="50" height="50" style="vertical-align:middle; margin-right:10px;">' +
                                    '<span style="vertical-align:middle;">' + item.name + '</span></li>';
                            });
                            html += '</ul>';
                            Swal.fire({
                                title: 'Recommendations for User ' + user_id,
                                html: html,
                                icon: 'info'
                            });
                        },
                        error: function() {
                            Swal.fire('Error', 'Could not fetch item details.', 'error');
                        }
                    });
                },
                error: function() {
                    Swal.fire('Error', 'Could not fetch recommendations.', 'error');
                }
            });
        });
    });
    </script>
</body>
</html>
