<!DOCTYPE html>
<html lang="en">
<?php
require("auth.php");
checkLoggedIn($db);

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id === 0) {
    die("Invalid Order ID");
}

// Fetch order details
$order_sql = "
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
        o.customer_id = u.user_id
    WHERE 
        o.order_id = $order_id
";
$order_query = mysqli_query($db, $order_sql);
$order_details = mysqli_fetch_assoc($order_query);

if (!$order_details) {
    die("Order not found");
}

// Fetch order items
$items_sql = "
    SELECT 
        oi.order_item_id, 
        oi.quantity, 
        i.name, 
        i.price, 
        i.image_path 
    FROM 
        order_items oi
    JOIN 
        items i 
    ON 
        oi.item_id = i.item_id
    WHERE 
        oi.order_id = $order_id
";
$items_query = mysqli_query($db, $items_sql);

// Initialize status update message
$status_update_message = '';

// Handle status update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];
    $allowed_statuses = [
        'Pending' => ['Confirmed', 'Canceled'],
        'Confirmed' => ['Processing'],
        'Processing' => ['Ready To Pickup'],
        'Ready To Pickup' => ['Completed']
    ];
    if (isset($allowed_statuses[$order_details['status']]) && in_array($new_status, $allowed_statuses[$order_details['status']])) {
        // Update the status
        $update_sql = "UPDATE orders SET status='$new_status' WHERE order_id=$order_id";
        if (mysqli_query($db, $update_sql)) {
            $status_update_message = '<p class="alert alert-success">Status updated successfully.</p>';
            echo '<script>setTimeout(function() { window.location.href = window.location.href; }, 2000);</script>';
        } else {
            $status_update_message = '<p class="alert alert-danger">Failed to update status. Please try again.</p>';
        }
    } else {
        $status_update_message = '<p class="alert alert-warning">Invalid status transition.</p>';
    }
}
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <title>Gallery Cafe | View Order</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
                    <h3 class="text-primary">View Order #<?php echo $order_details['order_id']; ?></h3>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Order Details</h4>
                                <div class="table-responsive m-t-40">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Order ID</th>
                                            <td><?php echo $order_details['order_id']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Customer Name</th>
                                            <td><?php echo $order_details['username']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Contact Number</th>
                                            <td><?php echo $order_details['phone_number']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Order Date</th>
                                            <td><?php echo $order_details['order_date']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Update Date</th>
                                            <td><?php echo $order_details['update_date']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <?php 
                                                    $status = ($order_details['status'] == 'Pending')
                                                        ? '<span class="badge badge-warning">Pending</span>'
                                                        : ($order_details['status'] == 'Confirmed'
                                                            ? '<span class="badge badge-info">Confirmed</span>'
                                                            : ($order_details['status'] == 'Processing'
                                                                ? '<span class="badge badge-primary">Processing</span>'
                                                                : ($order_details['status'] == 'Ready To Pickup'
                                                                    ? '<span class="badge badge-success">Ready To Pickup</span>'
                                                                    : ($order_details['status'] == 'Completed'
                                                                        ? '<span class="badge badge-secondary">Completed</span>'
                                                                        : '<span class="badge badge-danger">Canceled</span>'))));

                                                    echo $status;
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <h4 class="card-title m-t-40">Order Items</h4>
                                <div class="table-responsive m-t-20">
                                    <table class="table table-hover table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Item Image</th>
                                                <th>Item Name</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $total = 0;
                                            if (mysqli_num_rows($items_query) > 0) {
                                                while ($item = mysqli_fetch_array($items_query)) {
                                                    $subtotal = $item['price'] * $item['quantity'];
                                                    $total += $subtotal;

                                                    echo '<tr>
                                                            <td><img src="' . $item['image_path'] . '" alt="' . $item['name'] . '" width="50" class="img-fluid"></td>
                                                            <td>' . $item['name'] . '</td>
                                                            <td>' . $item['quantity'] . '</td>
                                                            <td>LKR ' . number_format($item['price'], 2) . '</td>
                                                            <td>LKR ' . number_format($subtotal, 2) . '</td>
                                                        </tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5"><center>No Items Found</center></td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-right">Total</th>
                                                <th>LKR <?php echo number_format($total, 2); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <!-- Display Status Update Message -->
                                <?php echo $status_update_message; ?>

                                <!-- Update Status Form -->
                                <?php if ($order_details['status'] !== 'Completed' && $order_details['status'] !== 'Canceled') : ?>
                                <h4 class="card-title m-t-40">Update Order Status</h4>
                                <form id="statusForm" method="POST">
                                    <div class="form-group">
                                        <label for="status">Select Status:</label>
                                        <select id="status" name="status" class="form-control">
                                            <?php
                                            $current_status = $order_details['status'];
                                            if ($current_status === 'Pending') {
                                                echo '<option value="Confirmed">Confirm Order</option>';
                                                echo '<option value="Canceled">Cancel Order</option>';
                                            } elseif ($current_status === 'Confirmed') {
                                                echo '<option value="Processing">Process Order</option>';
                                            } elseif ($current_status === 'Processing') {
                                                echo '<option value="Ready To Pickup">Ready To Pickup</option>';
                                            } elseif ($current_status === 'Ready To Pickup') {
                                                echo '<option value="Completed">Complete Order</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                </form>
                                <?php endif; ?>

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
</body>
</html>
