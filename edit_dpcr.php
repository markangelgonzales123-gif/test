<?php
include 'includes/header.php';
include 'includes/db_connection.php';
include 'includes/functions.php';
include 'includes/session.php';

// Check if user is logged in and has department head role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'department_head') {
    header("Location: login.php");
    exit();
}

// Get DPCR ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No DPCR specified.";
    header("Location: dpcr.php");
    exit();
}

$dpcr_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];

// Fetch DPCR details
$query = "SELECT d.*, p.period_name, p.start_date, p.end_date 
          FROM dpcr d 
          JOIN periods p ON d.period_id = p.id 
          WHERE d.id = ? AND d.department_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $dpcr_id, $department_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "DPCR not found or you don't have permission to edit it.";
    header("Location: dpcr.php");
    exit();
}

$dpcr = $result->fetch_assoc();
$computation_type = $dpcr['computation_type'];

// Fetch DPCR items
$query = "SELECT * FROM dpcr_items WHERE dpcr_id = ? ORDER BY id ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $dpcr_id);
$stmt->execute();
$dpcr_items = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_dpcr'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Update DPCR data
        $updated_title = $_POST['dpcr_title'];
        $updated_type = $_POST['computation_type'];
        
        $update_query = "UPDATE dpcr SET title = ?, computation_type = ?, last_updated = NOW() 
                         WHERE id = ? AND department_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssii", $updated_title, $updated_type, $dpcr_id, $department_id);
        $update_stmt->execute();
        
        // Update or delete existing items
        if (isset($_POST['item_id'])) {
            foreach ($_POST['item_id'] as $index => $item_id) {
                if (isset($_POST['delete'][$index]) && $_POST['delete'][$index] == 1) {
                    // Delete item
                    $delete_query = "DELETE FROM dpcr_items WHERE id = ? AND dpcr_id = ?";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bind_param("ii", $item_id, $dpcr_id);
                    $delete_stmt->execute();
                } else {
                    // Update item
                    $category = $_POST['category'][$index];
                    $description = $_POST['description'][$index];
                    $weight = $_POST['weight'][$index];
                    $target = $_POST['target'][$index];
                    
                    $item_update = "UPDATE dpcr_items SET 
                                   category = ?, 
                                   description = ?,
                                   weight = ?, 
                                   target = ?
                                   WHERE id = ? AND dpcr_id = ?";
                    $item_stmt = $conn->prepare($item_update);
                    $item_stmt->bind_param("ssdiii", $category, $description, $weight, $target, $item_id, $dpcr_id);
                    $item_stmt->execute();
                }
            }
        }
        
        // Add new items
        if (isset($_POST['new_category'])) {
            foreach ($_POST['new_category'] as $index => $category) {
                if (!empty($category) && !empty($_POST['new_description'][$index])) {
                    $description = $_POST['new_description'][$index];
                    $weight = $_POST['new_weight'][$index];
                    $target = $_POST['new_target'][$index];
                    
                    $new_item = "INSERT INTO dpcr_items (dpcr_id, category, description, weight, target) 
                                VALUES (?, ?, ?, ?, ?)";
                    $new_stmt = $conn->prepare($new_item);
                    $new_stmt->bind_param("issdi", $dpcr_id, $category, $description, $weight, $target);
                    $new_stmt->execute();
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "DPCR updated successfully.";
        header("Location: dpcr.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error updating DPCR: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit DPCR</h1>
        <a href="dpcr.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to DPCR List
        </a>
    </div>

    <?php
    // Display error and success messages
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">DPCR Details - <?php echo htmlspecialchars($dpcr['period_name']); ?> (<?php echo date('M d, Y', strtotime($dpcr['start_date'])); ?> - <?php echo date('M d, Y', strtotime($dpcr['end_date'])); ?>)</h6>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="form-group">
                    <label for="dpcr_title">DPCR Title</label>
                    <input type="text" class="form-control" id="dpcr_title" name="dpcr_title" value="<?php echo htmlspecialchars($dpcr['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="computation_type">Computation Type</label>
                    <select class="form-control" id="computation_type" name="computation_type" required>
                        <option value="average" <?php echo ($computation_type == 'average') ? 'selected' : ''; ?>>Average</option>
                        <option value="weighted" <?php echo ($computation_type == 'weighted') ? 'selected' : ''; ?>>Weighted</option>
                    </select>
                </div>
                
                <h4 class="mt-4 mb-3">DPCR Items</h4>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="dpcr_items_table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Weight (%)</th>
                                <th>Target</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_weight = 0;
                            while ($item = $dpcr_items->fetch_assoc()) {
                                $total_weight += $item['weight'];
                            ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                        <input type="text" class="form-control" name="category[]" value="<?php echo htmlspecialchars($item['category']); ?>" required>
                                    </td>
                                    <td>
                                        <textarea class="form-control" name="description[]" rows="2" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control weight-input" name="weight[]" value="<?php echo $item['weight']; ?>" step="1" min="0" max="100" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" name="target[]" value="<?php echo $item['target']; ?>" required>
                                    </td>
                                    <td>
                                        <input type="hidden" name="delete[]" value="0" class="delete-flag">
                                        <button type="button" class="btn btn-danger btn-sm delete-row">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php
                                // Reset the data pointer to the beginning
                                $dpcr_items->data_seek(0);
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right"><strong>Total Weight:</strong></td>
                                <td id="total_weight"><?php echo number_format($total_weight, 2); ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div id="new_items_container">
                    <!-- New items will be added here dynamically -->
                </div>
                
                <button type="button" id="add_item" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Item
                </button>
                
                <div class="form-group mt-4">
                    <button type="submit" name="save_dpcr" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Delete row
    $(document).on('click', '.delete-row', function() {
        var row = $(this).closest('tr');
        row.find('.delete-flag').val(1);
        row.hide();
        updateTotalWeight();
    });
    
    // Update total weight
    function updateTotalWeight() {
        var total = 0;
        $('.weight-input:visible').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#total_weight').text(total.toFixed(2));
        
        // Check if using weighted computation
        if ($('#computation_type').val() === 'weighted' && Math.abs(total - 100) > 0.01) {
            $('#total_weight').addClass('text-danger');
        } else {
            $('#total_weight').removeClass('text-danger');
        }
    }
    
    // Listen for weight changes
    $(document).on('input', '.weight-input', function() {
        updateTotalWeight();
    });
    
    // Add new item
    var newItemIndex = 0;
    $('#add_item').click(function() {
        var newRow = `
            <div class="card mb-3 new-item">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">New Item</h5>
                    <button type="button" class="btn btn-danger btn-sm remove-new-item">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Category</label>
                            <input type="text" class="form-control" name="new_category[${newItemIndex}]" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Weight (%)</label>
                            <input type="number" class="form-control weight-input" name="new_weight[${newItemIndex}]" step="1" min="0" max="100" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Target</label>
                            <input type="number" class="form-control" name="new_target[${newItemIndex}]" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="new_description[${newItemIndex}]" rows="2" required></textarea>
                    </div>
                </div>
            </div>
        `;
        $('#new_items_container').append(newRow);
        newItemIndex++;
    });
    
    // Remove new item
    $(document).on('click', '.remove-new-item', function() {
        $(this).closest('.new-item').remove();
        updateTotalWeight();
    });
    
    // Initialize
    updateTotalWeight();
    
    // Validate before submission
    $('form').on('submit', function(e) {
        if ($('#computation_type').val() === 'weighted') {
            var total = 0;
            $('.weight-input:visible').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            
            if (Math.abs(total - 100) > 0.01) {
                e.preventDefault();
                alert('For weighted computation, the total weight must equal 100%.');
                return false;
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 