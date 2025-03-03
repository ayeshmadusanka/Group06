<?php
require("auth.php");
checkLoggedIn($db);

$error = '';
$success = '';

// Function to get image dimensions safely
function getImageDimensions(string $imagePath): array
{
    $dimensions = @getimagesize($imagePath);
    if (!$dimensions) {
        return [0, 0];
    }
    list($width, $height) = $dimensions;
    return [$width, $height];
}

// Check if the item ID is provided via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: all_items.php");
    exit;
}

$item_id = (int) $_GET['id'];

// Fetch existing item data
$stmt = $db->prepare("SELECT * FROM items WHERE item_id = ?");
if (!$stmt) {
    die('Error preparing the query.');
}

$stmt->bind_param("i", $item_id);
if (!$stmt->execute()) {
    die('Error executing the query.');
}
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("No item found with ID: $item_id");
    header("Location: all_items.php");
    exit;
}

$item = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    // Log POST data for debugging
    error_log("POST Data: " . print_r($_POST, true));

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $item_type = $_POST['item_type'];
    $cuisine_type = $_POST['cuisine_type'];
    $beverage_type = $_POST['beverage_type'];
    $item_id = (int) $_POST['item_id']; // Get item ID from hidden form element

    // Handle image upload
    $image_path = $item['image_path']; // Keep existing image by default
    if (isset($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/items/';
        $tmp_name = $_FILES['image']['tmp_name'];
        $image_name = uniqid() . '-' . basename($_FILES['image']['name']);
        $new_image_path = $upload_dir . $image_name;

        if (move_uploaded_file($tmp_name, $new_image_path)) {
            list($width, $height) = getImageDimensions($new_image_path);
            if ($width !== 400 || $height !== 400) {
                unlink($new_image_path);
                $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                            Image dimensions must be exactly 400x400 pixels.
                          </div>';
            } else {
                $image_path = $new_image_path;
            }
        } else {
            $error = '<div class="alert alert-danger alert-dismissible fade show">
                       <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                       <span aria-hidden="true">&times;</span>
                       </button>
                       Failed to upload image. Please try again.
                      </div>';
        }
    }

    // Validate required inputs
    if (empty($name) || empty($price) || empty($item_type)) {
        $error = '<div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>                    
                    Please fill in all required fields.
                  </div>';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = '<div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>                    
                    Please enter a valid price.
                  </div>';
    } elseif (!in_array($item_type, ['Food', 'Beverage'])) {
        $error = '<div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>                    
                    Invalid item type selected.
                  </div>';
    } elseif ($item_type === 'Food' && !in_array($cuisine_type, ['', 'Sri Lankan', 'Chinese', 'Italian', 'Other'])) {
        $error = '<div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>                    
                    Invalid cuisine type selected.
                  </div>';
    } elseif ($item_type === 'Beverage' && !in_array($beverage_type, ['', 'Soft Drink', 'Juice', 'Alcoholic', 'Other'])) {
        $error = '<div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>                    
                    Invalid beverage type selected.
                  </div>';
    }

    // Only update if there are no errors so far
    if (empty($error)) {
        // Ensure consistency: if item type is Beverage, clear cuisine_type and vice versa.
        if ($item_type === 'Beverage') {
            $cuisine_type = '';
        } else {
            $beverage_type = '';
        }

        // Update item in the database
        $stmt = $db->prepare("UPDATE items SET name = ?, description = ?, price = ?, item_type = ?, cuisine_type = ?, beverage_type = ?, image_path = ? WHERE item_id = ?");
        if (!$stmt) {
            die('Error preparing the update query.');
        }
        $stmt->bind_param("ssdssssi", $name, $description, $price, $item_type, $cuisine_type, $beverage_type, $image_path, $item_id);

        if ($stmt->execute()) {
            $success = '<div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>                            
                            Item updated successfully.
                        </div>';
        } else {
            $error = '<div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>                        
                        Failed to update item. Please try again.
                      </div>';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gallery Cafe | Edit Item</title>
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
                    <h3 class="text-primary">Edit Item</h3>
                </div>
            </div>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-outline-primary">
                            <div class="card-header mb-2">
                                <h4 class="m-b-0 text-white">Edit Item</h4>
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
                                                    }, 2000);
                                                  </script>';
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars(isset($_GET['id']) ? intval($_GET['id']) : 0); ?>">

                                    <div class="form-group">
                                        <label for="name">Item Name:</label>
                                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description:</label>
                                        <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="price">Price (LKR):</label>
                                        <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($item['price']); ?>" step="0.01" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label for="item_type">Item Type:</label>
                                        <select id="item_type" name="item_type" class="form-control">
                                            <option value="Food" <?php echo $item['item_type'] == 'Food' ? 'selected' : ''; ?>>Food</option>
                                            <option value="Beverage" <?php echo $item['item_type'] == 'Beverage' ? 'selected' : ''; ?>>Beverage</option>
                                        </select>
                                    </div>

                                    <div id="food_fields" class="optional-fields">
                                        <div class="form-group">
                                            <label for="cuisine_type">Cuisine Type:</label>
                                            <select id="cuisine_type" name="cuisine_type" class="form-control">
                                                <option value="">Select</option>
                                                <option value="Sri Lankan" <?php echo $item['cuisine_type'] == 'Sri Lankan' ? 'selected' : ''; ?>>Sri Lankan</option>
                                                <option value="Chinese" <?php echo $item['cuisine_type'] == 'Chinese' ? 'selected' : ''; ?>>Chinese</option>
                                                <option value="Italian" <?php echo $item['cuisine_type'] == 'Italian' ? 'selected' : ''; ?>>Italian</option>
                                                <option value="Other" <?php echo $item['cuisine_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="beverage_fields" class="optional-fields">
                                        <div class="form-group">
                                            <label for="beverage_type">Beverage Type:</label>
                                            <select id="beverage_type" name="beverage_type" class="form-control">
                                                <option value="">Select</option>
                                                <option value="Soft Drink" <?php echo $item['beverage_type'] == 'Soft Drink' ? 'selected' : ''; ?>>Soft Drink</option>
                                                <option value="Juice" <?php echo $item['beverage_type'] == 'Juice' ? 'selected' : ''; ?>>Juice</option>
                                                <option value="Alcoholic" <?php echo $item['beverage_type'] == 'Alcoholic' ? 'selected' : ''; ?>>Alcoholic</option>
                                                <option value="Other" <?php echo $item['beverage_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="image">Image:</label>
                                        <input type="file" id="image" name="image" class="form-control-file">
                                    </div>

                                    <button type="submit" name="update_item" class="btn btn-primary">Update Item</button>
                                    <a href="all_items.php" class="btn btn-secondary">Cancel</a>
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
                            alert('Image must be exactly 400x400 pixels.');
                            $('.custom-file-input').val(''); // Clear the file input
                            $('.custom-file-label').removeClass("selected").html('Choose file');
                        }
                    };

                    reader.readAsDataURL(file);
                }
            });

            function toggleOptionalFields() {
                var itemType = $('#item_type').val();
                if (itemType === 'Food') {
                    $('#food_fields').show();
                    $('#beverage_fields').hide();
                } else if (itemType === 'Beverage') {
                    $('#food_fields').hide();
                    $('#beverage_fields').show();
                } else {
                    $('#food_fields, #beverage_fields').hide();
                }
            }

            // Initial call to toggle fields based on current selection
            toggleOptionalFields();

            // Bind change event to item type select
            $('#item_type').change(function() {
                toggleOptionalFields();
            });
        });
    </script> 
</body>
</html>
