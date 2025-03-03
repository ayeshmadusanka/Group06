<?php
require_once 'auth.php';
error_reporting(0);
session_start();

// Query to get daily order counts
$result = $db->query("SELECT DATE(order_date) AS order_day, COUNT(*) AS order_count FROM orders GROUP BY DATE(order_date) ORDER BY order_day");

$dates = [];
$orderCounts = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['order_day'];
        $orderCounts[] = $row['order_count'];
    }
    $result->free();
}

// Encode data as JSON
$jsonDates = json_encode($dates);
$jsonCounts = json_encode($orderCounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Time Series Visualization</title>
  <!-- Include Chart.js from CDN -->
<!-- Include Chart.js and date adapter -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
</head>
<body>
  <h2>Daily Orders (Time Series)</h2>
  <canvas id="ordersChart" width="800" height="400"></canvas>
  
  <script>
    // Get data passed from PHP
    const labels = <?php echo $jsonDates; ?>;
    const dataCounts = <?php echo $jsonCounts; ?>;
    
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const ordersChart = new Chart(ctx, {
      type: 'line', // change to 'bar' if you prefer a bar chart
      data: {
        labels: labels,
        datasets: [{
          label: 'Number of Orders',
          data: dataCounts,
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
              displayFormats: {
                day: 'yyyy-MM-dd'
              }
            },
            title: {
              display: true,
              text: 'Date'
            }
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Orders Count'
            }
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
  </script>
</body>
</html>
