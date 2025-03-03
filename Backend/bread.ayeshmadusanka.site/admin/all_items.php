<?php  
require("auth.php");
checkLoggedIn($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gallery Cafe | Items</title>
  <!-- Use original Bootstrap CSS -->
  <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="css/helper.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <!-- Chart.js and date adapter -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- SweetAlert2 CSS & JS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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
          <h3 class="text-primary">Items</h3>
        </div>
      </div>

      <div class="container-fluid">
        <!-- Global prediction button -->
        <div class="row mb-3">
          <div class="col-12 text-end">
            <button onclick="showPrediction()" class="btn btn-warning">
              <i class="fa fa-chart-line"></i> Show Global Daily Prediction
            </button>
          </div>
        </div>
        
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <h4 class="card-title">All Items</h4>
                <h6 class="card-subtitle">List of all items</h6>
                <div class="table-responsive mt-4">
                  <table id="example23" class="table table-hover table-striped table-bordered" width="100%">
                    <thead>
                      <tr>
                        <th>Item ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price (LKR)</th>
                        <th>Item Type</th>
                        <th>Cuisine Type</th>
                        <th>Beverage Type</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sql = "SELECT * FROM items";
                      $query = mysqli_query($db, $sql);
                      if (!mysqli_num_rows($query) > 0) {
                        echo '<tr><td colspan="10"><center>No Items Data Found</center></td></tr>';
                      } else {
                        while ($rows = mysqli_fetch_array($query)) {
                          $status = ($rows['is_active'] == 1)
                            ? '<span class="badge bg-info">Active</span>'
                            : '<span class="badge bg-danger">Inactive</span>';
                          echo '<tr>
                                  <td>' . $rows['item_id'] . '</td>
                                  <td>' . $rows['name'] . '</td>
                                  <td>' . $rows['description'] . '</td>
                                  <td>' . number_format($rows['price'], 2) . '</td>
                                  <td>' . $rows['item_type'] . '</td>
                                  <td>' . (!empty($rows['cuisine_type']) ? $rows['cuisine_type'] : 'N/A') . '</td>
                                  <td>' . (!empty($rows['beverage_type']) ? $rows['beverage_type'] : 'N/A') . '</td>
                                  <td><img src="' . htmlspecialchars($rows['image_path']) . '" alt="Image" width="100" class="img-fluid"></td>
                                  <td>' . $status . '</td>
                                  <td>
                                    <a href="edit_item.php?id=' . $rows['item_id'] . '" class="btn btn-info btn-sm">
                                      <i class="fa fa-pencil"></i>
                                    </a>
                                    <button onclick="toggleStatus(' . $rows['item_id'] . ', \'' . $rows['is_active'] . '\')" class="btn btn-danger btn-sm">
                                      <i class="fa fa-exchange"></i>
                                    </button>
                                    <button onclick="showItemChart(' . $rows['item_id'] . ')" class="btn btn-success btn-sm">
                                      <i class="fa fa-line-chart"></i> Chart
                                    </button>
                                    <button onclick="showItemPrediction(' . $rows['item_id'] . ')" class="btn btn-primary btn-sm">
                                      <i class="fa fa-chart-bar"></i> Prediction
                                    </button>
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
      </div> <!-- container-fluid -->
    </div> <!-- page-wrapper -->
  </div> <!-- main-wrapper -->

  <!-- JavaScript files -->
  <script src="js/lib/bootstrap/js/popper.min.js"></script>
  <script src="js/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="js/sidebarmenu.js"></script>
  <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
  <script src="js/custom.min.js"></script>
  <script src="js/lib/datatables/datatables.min.js"></script>
  <script src="js/lib/datatables/datatables-init.js"></script>

  <script>
    // Function to toggle item status
    function toggleStatus(itemId, currentStatus) {
      $.ajax({
        url: 'change_item_status.php',
        type: 'GET',
        data: { id: itemId, status: currentStatus },
        success: function(response) {
          var result = JSON.parse(response);
          if (result.success) {
            location.reload();
          }
        },
        error: function() {
          location.reload();
        }
      });
    }

    // Function to fetch and show chart data for a single item using SweetAlert2
    function showItemChart(itemId) {
      $.ajax({
        url: 'get_item_chart.php',
        type: 'GET',
        data: { item_id: itemId },
        dataType: 'json',
        success: function(response) {
          var swalHtml = '<canvas id="swalChart" width="800" height="400"></canvas>';
          Swal.fire({
            title: 'Orders Chart for Item ID: ' + itemId,
            html: swalHtml,
            width: 850,
            showCloseButton: true,
            focusConfirm: false,
            didOpen: () => {
              var ctx = document.getElementById('swalChart').getContext('2d');
              if (window.itemChart && typeof window.itemChart.destroy === 'function') {
                window.itemChart.destroy();
              }
              window.itemChart = new Chart(ctx, {
                type: 'line',
                data: {
                  labels: response.labels,
                  datasets: [{
                    label: 'Number of Orders',
                    data: response.counts,
                    fill: false,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.1,
                    pointRadius: 5,
                    pointHoverRadius: 7
                  }]
                },
                options: {
                  scales: {
                    x: {
                      type: 'time',
                      time: {
                        unit: 'day',
                        tooltipFormat: 'yyyy-MM-dd',
                        displayFormats: { day: 'yyyy-MM-dd' }
                      },
                      title: { display: true, text: 'Date' }
                    },
                    y: {
                      beginAtZero: true,
                      title: { display: true, text: 'Orders Count' }
                    }
                  },
                  plugins: {
                    tooltip: {
                      callbacks: {
                        label: function(context) {
                          return 'Orders: ' + context.parsed.y;
                        }
                      }
                    }
                  }
                }
              });
            }
          });
        },
        error: function(xhr, status, error) {
          Swal.fire('Error', 'Error fetching chart data.', 'error');
        }
      });
    }

    // Function to call the prediction endpoint for a specific item and show daily forecast via SweetAlert2
    function showItemPrediction(itemId) {
      var url = 'https://model.ayeshmadusanka.site/predict_orders/' + itemId;
      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          var html = '<h5>Historical Daily Orders (Last 7 Days)</h5>';
          if(response.historical && response.historical.length > 0){
            html += '<ul>';
            response.historical.forEach(function(item) {
              html += '<li><strong>Date:</strong> ' + item.date + ' - <strong>Orders:</strong> ' + item.order_count + '</li>';
            });
            html += '</ul>';
          } else {
            html += '<p>No historical data available.</p>';
          }
          
          html += '<h5>Forecast for Next 7 Days</h5>';
          if(response.forecast && response.forecast.length > 0){
            html += '<ul>';
            response.forecast.forEach(function(item) {
              html += '<li><strong>Date:</strong> ' + item.date + ' - <strong>Predicted Orders:</strong> ' + item.predicted_order_count + '</li>';
            });
            html += '</ul>';
          } else {
            html += '<p>No forecast data available.</p>';
          }
          
          Swal.fire({
            title: 'Daily Order Prediction for Item ID: ' + itemId,
            html: html,
            width: 600,
            showCloseButton: true
          });
        },
        error: function(xhr, status, error) {
          Swal.fire('Error', 'Error fetching prediction data.', 'error');
        }
      });
    }

    // Global prediction function for all orders, showing daily forecast via SweetAlert2
    function showPrediction() {
      $.ajax({
        url: 'https://model.ayeshmadusanka.site/predict_orders_global',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          var html = '<h5>Historical Daily Orders (Last 7 Days)</h5>';
          if(response.historical && response.historical.length > 0){
            html += '<ul>';
            response.historical.forEach(function(item) {
              html += '<li><strong>Date:</strong> ' + item.date + ' - <strong>Orders:</strong> ' + item.order_count + '</li>';
            });
            html += '</ul>';
          } else {
            html += '<p>No historical data available.</p>';
          }
          
          html += '<h5>Forecast for Next 7 Days</h5>';
          if(response.forecast && response.forecast.length > 0){
            html += '<ul>';
            response.forecast.forEach(function(item) {
              html += '<li><strong>Date:</strong> ' + item.date + ' - <strong>Predicted Orders:</strong> ' + item.predicted_order_count + '</li>';
            });
            html += '</ul>';
          } else {
            html += '<p>No forecast data available.</p>';
          }
          
          Swal.fire({
            title: 'Global Daily Order Prediction',
            html: html,
            width: 600,
            showCloseButton: true
          });
        },
        error: function(xhr, status, error) {
          Swal.fire('Error', 'Error fetching global prediction data.', 'error');
        }
      });
    }
  </script>
</body>
</html>
