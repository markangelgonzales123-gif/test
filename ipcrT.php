<?php
// Set page title
$page_title = "Individual Performance Commitment and Review - EPMS";

// Include header
include_once('includes/header.php');

// Include form workflow functions
include_once('includes/form_workflow.php');

// Check if user has the right role to access this page
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'regular_employee' && $_SESSION['user_role'] !== 'department_head' && $_SESSION['user_role'] !== 'president' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: access_denied.php");
    exit();
}

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_department_id = $_SESSION['user_department_id'];

// Get department name
$dept_query = "SELECT name FROM departments WHERE id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $user_department_id);
$stmt->execute();
$dept_result = $stmt->get_result();
$dept_name = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['name'] : "Unknown Department";

// Get evaluation periods
$periods_query = "SELECT id, name, start_date, end_date FROM evaluation_periods WHERE status = 'active' ORDER BY start_date DESC";
$periods_result = $conn->query($periods_query);

// Handle form submission
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ipcr'])) {
    $period_id = $_POST['period_id'];
    $form_content = $_POST['form_content']; // This is the JSON string from the hidden input

    // Basic server-side check
    if (empty($period_id)) {
        $message = "Error: Evaluation period is required.";
        $message_type = "danger";
    } else {
        // Insert into database - keeping original query structure
        $insert_query = "INSERT INTO ipcr_forms (user_id, period_id, content, status, created_at) VALUES (?, ?, ?, 'pending_supervisor', NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iis", $user_id, $period_id, $form_content);
        
        if ($stmt->execute()) {
            $message = "IPCR submitted successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "danger";
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Individual Performance Commitment and Review (IPCR)</h6>
                    <span class="badge badge-info">Logged in as: <?php echo $_SESSION['user_name']; ?></span>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form id="ipcr-form" method="POST" action="">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="period">Evaluation Period <span class="text-danger">*</span></label>
                                    <select class="form-control" id="period" name="period_id" required>
                                        <option value="">-- Select Period --</option>
                                        <?php while($row = $periods_result->fetch_assoc()): ?>
                                            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?> (<?php echo date('M Y', strtotime($row['start_date'])); ?> - <?php echo date('M Y', strtotime($row['end_date'])); ?>)</option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="computation_type">Computation Type</label>
                                    <select class="form-control" id="computation_type" name="computation_type">
                                        <option value="Type1">Type 1 (Strategic 35%, Core 65%)</option>
                                        <option value="Type2">Type 2 (Strategic 20%, Core 70%, Support 10%)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Strategic Functions Table -->
                        <div class="card mb-4 border-left-primary">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">I. STRATEGIC FUNCTIONS</h6>
                                <button type="button" class="btn btn-sm btn-primary add-row" data-type="strategic">
                                    <i class="fas fa-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="strategic-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th rowspan="2" style="width: 25%;">MFO / PAP</th>
                                            <th rowspan="2" style="width: 25%;">Success Indicators</th>
                                            <th rowspan="2" style="width: 20%;">Actual Accomplishments</th>
                                            <th colspan="4" class="text-center">Rating</th>
                                            <th rowspan="2" style="width: 50px;"></th>
                                        </tr>
                                        <tr class="text-center">
                                            <th style="width: 7%;">Q</th>
                                            <th style="width: 7%;">E</th>
                                            <th style="width: 7%;">T</th>
                                            <th style="width: 7%;">A</th>
                                        </tr>
                                    </thead>
                                    <tbody id="strategic-body">
                                        <tr class="strategic-function-row">
                                            <td><textarea name="strategic_mfo[]" class="form-control" rows="2"></textarea></td>
                                            <td><textarea name="strategic_success_indicators[]" class="form-control" rows="2"></textarea></td>
                                            <td><textarea name="strategic_accomplishments[]" class="form-control" rows="2"></textarea></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="strategic_q[]" class="form-control rating-input" data-type="strategic"></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="strategic_e[]" class="form-control rating-input" data-type="strategic"></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="strategic_t[]" class="form-control rating-input" data-type="strategic"></td>
                                            <td><input type="text" name="strategic_a[]" class="form-control bg-light" readonly></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Core Functions Table -->
                        <div class="card mb-4 border-left-success">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-success">II. CORE FUNCTIONS</h6>
                                <button type="button" class="btn btn-sm btn-success add-row" data-type="core">
                                    <i class="fas fa-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="core-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th rowspan="2" style="width: 25%;">MFO / PAP</th>
                                            <th rowspan="2" style="width: 25%;">Success Indicators</th>
                                            <th rowspan="2" style="width: 20%;">Actual Accomplishments</th>
                                            <th colspan="4" class="text-center">Rating</th>
                                            <th rowspan="2" style="width: 50px;"></th>
                                        </tr>
                                        <tr class="text-center">
                                            <th style="width: 7%;">Q</th>
                                            <th style="width: 7%;">E</th>
                                            <th style="width: 7%;">T</th>
                                            <th style="width: 7%;">A</th>
                                        </tr>
                                    </thead>
                                    <tbody id="core-body">
                                        <tr class="core-function-row">
                                            <td><textarea name="core_mfo[]" class="form-control" rows="2"></textarea></td>
                                            <td><textarea name="core_success_indicators[]" class="form-control" rows="2"></textarea></td>
                                            <td><textarea name="core_accomplishments[]" class="form-control" rows="2"></textarea></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="core_q[]" class="form-control rating-input" data-type="core"></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="core_e[]" class="form-control rating-input" data-type="core"></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="core_t[]" class="form-control rating-input" data-type="core"></td>
                                            <td><input type="text" name="core_a[]" class="form-control bg-light" readonly></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Support Functions Table -->
                        <div id="support-section" class="card mb-4 border-left-info d-none">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-info">III. SUPPORT FUNCTIONS</h6>
                                <button type="button" class="btn btn-sm btn-info add-row" data-type="support">
                                    <i class="fas fa-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="support-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th rowspan="2" style="width: 25%;">MFO / PAP</th>
                                            <th rowspan="2" style="width: 25%;">Success Indicators</th>
                                            <th rowspan="2" style="width: 20%;">Actual Accomplishments</th>
                                            <th colspan="4" class="text-center">Rating</th>
                                            <th rowspan="2" style="width: 50px;"></th>
                                        </tr>
                                        <tr class="text-center">
                                            <th style="width: 7%;">Q</th>
                                            <th style="width: 7%;">E</th>
                                            <th style="width: 7%;">T</th>
                                            <th style="width: 7%;">A</th>
                                        </tr>
                                    </thead>
                                    <tbody id="support-body">
                                        <tr class="support-function-row">
                                            <td><textarea name="support_mfo[]" class="form-control" rows="2"></textarea></td>
                                            <td><textarea name="support_success_indicators[]" class="form-control" rows="2"></textarea></td>
                                            <td><textarea name="support_accomplishments[]" class="form-control" rows="2"></textarea></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="support_q[]" class="form-control rating-input" data-type="support"></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="support_e[]" class="form-control rating-input" data-type="support"></td>
                                            <td><input type="number" step="0.01" min="1" max="5" name="support_t[]" class="form-control rating-input" data-type="support"></td>
                                            <td><input type="text" name="support_a[]" class="form-control bg-light" readonly></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="card mb-4 bg-light">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0 font-weight-bold">FINAL AVERAGE RATING:</h5>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <h2 id="final-rating-display" class="mb-0 text-primary">0.00</h2>
                                        <div id="adjectival-rating" class="font-weight-bold text-uppercase">Poor</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="form-content" name="form_content">
                        
                        <div class="text-right pb-4">
                            <button type="reset" class="btn btn-secondary mr-2">Reset Form</button>
                            <button type="submit" name="submit_ipcr" class="btn btn-primary px-5 shadow-sm">
                                <i class="fas fa-paper-plane mr-2"></i> Submit IPCR
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Validation Modal -->
<div class="modal fade" id="validationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white"><i class="fas fa-exclamation-triangle mr-2"></i> Missing Fields</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="validationMessage"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="js/auto_scoring.js"></script>
<script>
    $(document).ready(function() {
        // Toggle Support Functions
        $('#computation_type').change(function() {
            if ($(this).val() === 'Type2') {
                $('#support-section').removeClass('d-none');
            } else {
                $('#support-section').addClass('d-none');
            }
            if(typeof updateFinalRating === 'function') updateFinalRating();
        });

        // Add Row
        $('.add-row').click(function() {
            const type = $(this).data('type');
            const newRow = `
                <tr class="${type}-function-row">
                    <td><textarea name="${type}_mfo[]" class="form-control" rows="2"></textarea></td>
                    <td><textarea name="${type}_success_indicators[]" class="form-control" rows="2"></textarea></td>
                    <td><textarea name="${type}_accomplishments[]" class="form-control" rows="2"></textarea></td>
                    <td><input type="number" step="0.01" min="1" max="5" name="${type}_q[]" class="form-control rating-input" data-type="${type}"></td>
                    <td><input type="number" step="0.01" min="1" max="5" name="${type}_e[]" class="form-control rating-input" data-type="${type}"></td>
                    <td><input type="number" step="0.01" min="1" max="5" name="${type}_t[]" class="form-control rating-input" data-type="${type}"></td>
                    <td><input type="text" name="${type}_a[]" class="form-control bg-light" readonly></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;
            $(`#${type}-body`).append(newRow);
        });

        // Remove Row
        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            if(typeof updateFinalRating === 'function') updateFinalRating();
        });

        // Form Submission and Strict Validation
        $('#ipcr-form').submit(function(e) {
            let isValid = true;
            let errorMessage = "";

            // 1. Check Evaluation Period
            if ($('#period').val() === "") {
                isValid = false;
                errorMessage = "Please select an <strong>Evaluation Period</strong>.";
            }

            // 2. Row Validation Helper
            function validateRows(rows, sectionName) {
                let sectionData = [];
                rows.each(function(index, row) {
                    const mfo = $(row).find(`textarea[name*='_mfo']`).val().trim();
                    const si = $(row).find(`textarea[name*='_success_indicators']`).val().trim();
                    const acc = $(row).find(`textarea[name*='_accomplishments']`).val().trim();
                    const q = $(row).find(`input[name*='_q']`).val();
                    const e = $(row).find(`input[name*='_e']`).val();
                    const t = $(row).find(`input[name*='_t']`).val();

                    // If any part of the row is filled, all must be filled
                    if (mfo || si || acc || q || e || t) {
                        if (!mfo || !si || !acc || !q || !e || !t) {
                            isValid = false;
                            errorMessage = `Missing fields in <strong>${sectionName}</strong>. Every row must have MFO, Success Indicators, Accomplishments, and all Ratings (Q,E,T).`;
                            return false; 
                        }
                        sectionData.push({
                            mfo, success_indicators: si, accomplishments: acc,
                            rating: { q, e, t, a: $(row).find(`input[name*='_a']`).val() }
                        });
                    }
                });
                return sectionData;
            }

            const stratData = validateRows($('.strategic-function-row'), 'Strategic Functions');
            if (!isValid) return showModalError(errorMessage, e);

            const coreData = validateRows($('.core-function-row'), 'Core Functions');
            if (!isValid) return showModalError(errorMessage, e);

            let supportData = [];
            if ($('#computation_type').val() === 'Type2') {
                supportData = validateRows($('.support-function-row'), 'Support Functions');
            }
            if (!isValid) return showModalError(errorMessage, e);

            // 3. Minimum requirement check
            if (stratData.length === 0 || coreData.length === 0) {
                isValid = false;
                errorMessage = "You must provide at least one complete row for both <strong>Strategic</strong> and <strong>Core Functions</strong>.";
            }

            if (!isValid) return showModalError(errorMessage, e);

            // Prepare JSON payload for 'form_content'
            const finalPayload = {
                metadata: {
                    period_id: $('#period').val(),
                    comp_type: $('#computation_type').val(),
                    final_rating: $('#final-rating-display').text()
                },
                sections: { strategic: stratData, core: coreData, support: supportData }
            };

            $('#form-content').val(JSON.stringify(finalPayload));
            return true;
        });

        function showModalError(msg, e) {
            e.preventDefault();
            $('#validationMessage').html(msg);
            $('#validationModal').modal('show');
            return false;
        }

        // Ratings Delegation
        $(document).on('input', '.rating-input', function() {
            const row = $(this).closest('tr');
            const type = $(this).data('type');
            const q = parseFloat(row.find(`input[name="${type}_q[]"]`).val());
            const e = parseFloat(row.find(`input[name="${type}_e[]"]`).val());
            const t = parseFloat(row.find(`input[name="${type}_t[]"]`).val());
            const aField = row.find(`input[name="${type}_a[]"]`);

            if (!isNaN(q) && !isNaN(e) && !isNaN(t)) {
                aField.val(((q + e + t) / 3).toFixed(2));
            } else {
                aField.val('');
            }
            
            if(typeof updateFinalRating === 'function') updateFinalRating();
        });

        // Basic Local Calculator (as fallback for auto_scoring.js)
        function updateFinalRating() {
            const type = $('#computation_type').val();
            const getAvg = (cls) => {
                let s = 0, c = 0;
                $(`input[name="${cls}_a[]"]`).each(function() {
                    const v = parseFloat($(this).val());
                    if(!isNaN(v)) { s += v; c++; }
                });
                return c > 0 ? s / c : 0;
            };

            const sA = getAvg('strategic'), cA = getAvg('core'), suA = getAvg('support');
            let final = (type === 'Type1') ? (sA * 0.35 + cA * 0.65) : (sA * 0.20 + cA * 0.70 + suA * 0.10);
            
            $('#final-rating-display').text(final.toFixed(2));
            let adj = "Poor";
            if (final >= 4.5) adj = "Outstanding";
            else if (final >= 3.5) adj = "Very Satisfactory";
            else if (final >= 2.5) adj = "Satisfactory";
            else if (final >= 1.5) adj = "Unsatisfactory";
            $('#adjectival-rating').text(adj);
        }
    });
</script>

<?php
$conn->close();
include_once('includes/footer.php');
?>