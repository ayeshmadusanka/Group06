<?php
require("auth.php");
checkLoggedIn($db);

$error = '';
$success = '';

// Function to get image dimensions
$getImageDimensions = fn($imagePath) => getimagesize($imagePath) ?: [0, 0];

try {
    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $price = $_POST['price'] ?? null;
        $item_type = $_POST['item_type'] ?? '';
        $cuisine_type = $_POST['cuisine_type'] ?? '';
        $beverage_type = $_POST['beverage_type'] ?? '';
        $is_active = 1;

        // Handle image upload
        $image_path = '';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/items/';
            $image_name = uniqid() . '-' . basename($_FILES['image']['name']);
            $tmp_name = $_FILES['image']['tmp_name'];
            $image_path = $upload_dir . $image_name;

            if (move_uploaded_file($tmp_name, $image_path)) {
                [$width, $height] = $getImageDimensions($image_path);
                if ($width !== 400 || $height !== 400) {
                    unlink($image_path);
                    throw new RuntimeException("Image dimensions must be exactly 400x400 pixels.");
                }
            } else {
                throw new RuntimeException("Failed to upload image. Please try again.");
            }
        }

        // Input validation
        if (empty($name) || empty($price) || empty($item_type)) {
            throw new InvalidArgumentException("Please fill in all required fields.");
        }

        if (!is_numeric($price) || $price <= 0) {
            throw new InvalidArgumentException("Please enter a valid price.");
        }

        if (!in_array($item_type, ['Food', 'Beverage'], true)) {
            throw new InvalidArgumentException("Invalid item type selected.");
        }

        // Handle cuisine_type and beverage_type based on item_type
        if ($item_type === 'Food') {
            $beverage_type = NULL; // Not applicable for Food
            if (!in_array($cuisine_type, ['', 'Sri Lankan', 'Chinese', 'Italian', 'Other'], true)) {
                throw new InvalidArgumentException("Invalid cuisine type selected.");
            }
        } elseif ($item_type === 'Beverage') {
            $cuisine_type = NULL; // Not applicable for Beverage
            if (!in_array($beverage_type, ['', 'Soft Drink', 'Juice', 'Alcoholic', 'Other'], true)) {
                throw new InvalidArgumentException("Invalid beverage type selected.");
            }
        } else {
            $cuisine_type = $beverage_type = NULL; // Default for invalid item type
        }

        // Check for duplicate item name
        $stmt = $db->prepare("SELECT item_id FROM items WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            throw new RuntimeException("An item with this name already exists. Please choose a different name.");
        }

        // Insert new item into the database
        $stmt = $db->prepare("INSERT INTO items (name, description, price, item_type, cuisine_type, beverage_type, image_path, is_active) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssssi", $name, $description, $price, $item_type, $cuisine_type, $beverage_type, $image_path, $is_active);

        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to add item. Please try again.");
        }

        $success = "Item added successfully.";
        $name = $description = $price = $item_type = $cuisine_type = $beverage_type = $image_path = '';
        $stmt->close();
    }
} catch (Exception $e) {
    $error = '<div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                ' . htmlspecialchars($e->getMessage()) . '
              </div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <title>Gallery Cafe | Add Item</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .optional-fields {
            display: none;
        }
    </style>
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
                    <h3 class="text-primary">Add Item</h3>
                </div>
            </div>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-outline-primary">
                            <div class="card-header mb-2">
                                <h4 class="m-b-0 text-white">Add New Item</h4>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($error)) : ?>
                                    <div class="alert alert-danger">
                                        <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($success)) : ?>
                                    <div class="alert alert-success">
                                        <?php
                                        echo $success;
                                        echo '<script>
                                            setTimeout(function(){
                                            window.location.href = "all_items.php";
                                            }, 2000); // 2000 milliseconds = 2 seconds
                                            </script>';
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="name">Item Name:</label>
                                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description:</label>
                                        <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="price">Price (LKR):</label>
                                        <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" class="form-control" step="0.01">
                                    </div>

                                    <div class="form-group">
                                        <label for="item_type">Item Type:</label>
                                        <select id="item_type" name="item_type" class="form-control" onchange="toggleOptionalFields()">
                                            <option value="">Select Item Type</option>
                                            <option value="Food" <?php echo ($item_type == 'Food') ? 'selected' : ''; ?>>Food</option>
                                            <option value="Beverage" <?php echo ($item_type == 'Beverage') ? 'selected' : ''; ?>>Beverage</option>
                                        </select>
                                    </div>

                                    <div id="optional-fields-food" class="optional-fields">
                                        <div class="form-group">
                                            <label for="cuisine_type">Cuisine Type (Optional):</label>
                                            <select id="cuisine_type" name="cuisine_type" class="form-control">
                                                <option value="">Select</option>
                                                <option value="Sri Lankan" <?php echo ($cuisine_type == 'Sri Lankan') ? 'selected' : ''; ?>>Sri Lankan</option>
                                                <option value="Chinese" <?php echo ($cuisine_type == 'Chinese') ? 'selected' : ''; ?>>Chinese</option>
                                                <option value="Italian" <?php echo ($cuisine_type == 'Italian') ? 'selected' : ''; ?>>Italian</option>
                                                <option value="Other" <?php echo ($cuisine_type == 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="optional-fields-beverage" class="optional-fields">
                                        <div class="form-group">
                                            <label for="beverage_type">Beverage Type (Optional):</label>
                                            <select id="beverage_type" name="beverage_type" class="form-control">
                                                <option value="">Select</option>
                                                <option value="Soft Drink" <?php echo ($beverage_type == 'Soft Drink') ? 'selected' : ''; ?>>Soft Drink</option>
                                                <option value="Juice" <?php echo ($beverage_type == 'Juice') ? 'selected' : ''; ?>>Juice</option>
                                                <option value="Alcoholic" <?php echo ($beverage_type == 'Alcoholic') ? 'selected' : ''; ?>>Alcoholic</option>
                                                <option value="Other" <?php echo ($beverage_type == 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="image">Image:</label>
                                        <div class="custom-file">
                                            <input type="file" id="image" name="image" class="custom-file-input" accept="image/*">
                                            <label class="custom-file-label" for="image">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">Only images of size 400x400 pixels are allowed.</small>
                                    </div>
                                    <div id="alert-container"></div>

                                    <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>

                                </form>
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
    <script>
        $(document).ready(function() {
            // Update the file input label with the selected file name
            $('.custom-file-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);

                // Check image dimensions
                var file = this.files[0];
                if (file) {
                    var img = new Image();
                    var reader = new FileReader();

                    reader.onload = function(e) {
                        img.src = e.target.result;
                    };

                    img.onload = function() {
                        if (img.width !== 400 || img.height !== 400) {
                            showAlert('Image must be exactly 400x400 pixels.', 'danger');
                            $('.custom-file-input').val(''); // Clear the file input
                            $('.custom-file-label').removeClass("selected").html('Choose file');
                        }
                    };

                    reader.readAsDataURL(file);
                }
            });

            function showAlert(message, type) {
                var alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
                $('#alert-container').html(alertHtml);
            }
        });
        function toggleOptionalFields() {
                var itemType = document.getElementById('item_type').value;
                var foodFields = document.getElementById('optional-fields-food');
                var beverageFields = document.getElementById('optional-fields-beverage');

                if (itemType === 'Food') {
                    foodFields.style.display = 'block';
                    beverageFields.style.display = 'none';
                } else if (itemType === 'Beverage') {
                    foodFields.style.display = 'none';
                    beverageFields.style.display = 'block';
                } else {
                    foodFields.style.display = 'none';
                    beverageFields.style.display = 'none';
                }
            }
    </script>


</body>

</html>