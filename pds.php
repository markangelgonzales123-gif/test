<?php
// Set page title
$page_title = "Individual Development Plan - EPMS";

// Include header
include_once('includes/header.php');

// Check if user has the right role to access this page
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'regular_employee' && $_SESSION['user_role'] !== 'department_head' && $_SESSION['user_role'] !== 'president' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: access_denied.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

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
$department_name = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['name'] : 'Unknown Department';

?>

<style>
    /* Custom PDS Styles to match the darker green sidebar */
    .pds-container {
        max-width: 1100px;
        margin: 20px auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    .section-header {
        /* Matching the sidebar green: #2d5d2a */
        background-color: #2d5d2a; 
        color: white;
        padding: 10px 15px;
        margin-top: 15px;
        margin-bottom: 0;
        font-size: 1.1rem;
        font-weight: 600;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s;
    }
    .section-header:hover {
        background-color: #224221; /* Darker hover state */
    }
    /* Hide the default collapse icon provided by Bootstrap for cleaner integration */
    .section-header[aria-expanded="true"] .bi-chevron-down {
        transform: rotate(180deg);
    }
    .pds-table th, .pds-table td {
        border: 1px solid #ccc;
        padding: 5px;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .pds-table th {
        background-color: #e9ecef; /* Light grey for table headers */
        font-weight: 600;
        text-align: center;
    }
    .small-label {
        font-size: 0.75rem;
        color: #666;
        margin-top: -5px;
        display: block;
    }
    .form-control, .form-select {
        border: none !important;
        padding: 2px 5px;
        height: auto !important;
        font-size: 0.9rem;
        border-radius: 0;
        box-shadow: none !important;
    }
    .pds-content {
        border: 1px solid #ccc;
        border-top: none;
        padding: 15px;
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
    }
</style>

<div class="pds-container">
    <div class="text-center mb-4">
        <h1 class="h3 fw-bold mb-1">PERSONAL DATA SHEET</h1>
        <p class="mb-0 small text-muted">CSC Form No. 212 (Revised 2017)</p>
        <p class="alert alert-danger p-2 mt-2 small">
            <strong>WARNING:</strong> Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal/civil cases against the person concerned.
        </p>
    </div>

    <!-- PDS Form Start -->
    <form id="pdsForm">

        <!-- ============================================== -->
        <!-- SECTION I: PERSONAL INFORMATION (Page 1) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapsePersonal" aria-expanded="true" aria-controls="collapsePersonal">
                I. PERSONAL INFORMATION <span class="badge bg-secondary me-0">Page 1 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapsePersonal">
                
                <!-- ... Content for Section I (Personal Info) - same as previous ... -->
                <table class="table table-bordered pds-table">
                    <tr>
                        <th class="w-25">2. SURNAME</th>
                        <td colspan="3"><input type="text" class="form-control" name="surname"></td>
                        <th class="w-25">NAME EXTENSION (JR, SR, etc)</th>
                        <td><input type="text" class="form-control" name="name_extension"></td>
                    </tr>
                    <tr>
                        <th>FIRST NAME</th>
                        <td colspan="5"><input type="text" class="form-control" name="first_name"></td>
                    </tr>
                    <tr>
                        <th>MIDDLE NAME</th>
                        <td colspan="5"><input type="text" class="form-control" name="middle_name"></td>
                    </tr>
                    <tr>
                        <th>12. DATE OF BIRTH <span class="small-label">(mm/dd/yyyy)</span></th>
                        <td><input type="date" class="form-control" name="date_of_birth"></td>
                        <th class="text-start" colspan="2">16. CITIZENSHIP</th>
                        <td colspan="2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="citizenship" id="filipino" value="Filipino" checked>
                                <label class="form-check-label" for="filipino">Filipino</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="citizenship" id="dual" value="Dual Citizenship">
                                <label class="form-check-label" for="dual">Dual Citizenship</label>
                            </div>
                            <div class="ms-3 small text-muted">
                                If holder of dual citizenship, please indicate the details.
                                <input type="text" class="form-control form-control-sm mt-1" name="dual_citizenship_details" placeholder="Pls. indicate country/details">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>14. PLACE OF BIRTH</th>
                        <td colspan="5"><input type="text" class="form-control" name="place_of_birth"></td>
                    </tr>
                    <tr>
                        <th>15. SEX</th>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sex" id="male" value="Male">
                                <label class="form-check-label" for="male">Male</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sex" id="female" value="Female">
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                        </td>
                        <th class="text-start" colspan="2">16. CIVIL STATUS</th>
                        <td colspan="2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="civil_status" id="single" value="Single">
                                <label class="form-check-label" for="single">Single</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="civil_status" id="married" value="Married">
                                <label class="form-check-label" for="married">Married</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="civil_status" id="widowed" value="Widowed">
                                <label class="form-check-label" for="widowed">Widowed</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="civil_status" id="separated" value="Separated">
                                <label class="form-check-label" for="separated">Separated</label>
                            </div>
                            <div class="input-group input-group-sm mt-1">
                                <span class="input-group-text p-1 small">Other/s:</span>
                                <input type="text" class="form-control" name="civil_status_other">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>17. HEIGHT (m)</th>
                        <td><input type="text" class="form-control" name="height"></td>
                        <th colspan="2">18. MOBILE NO.</th>
                        <td colspan="2"><input type="text" class="form-control" name="mobile_no"></td>
                    </tr>
                    <tr>
                        <th>18. WEIGHT (kg)</th>
                        <td><input type="text" class="form-control" name="weight"></td>
                        <th colspan="2">19. E-MAIL ADDRESS (if any)</th>
                        <td colspan="2"><input type="email" class="form-control" name="email_address"></td>
                    </tr>
                    <tr>
                        <th>19. BLOOD TYPE</th>
                        <td><input type="text" class="form-control" name="blood_type"></td>
                        <th colspan="2">20. AGENCY EMPLOYEE NO.</th>
                        <td colspan="2"><input type="text" class="form-control" name="agency_employee_no"></td>
                    </tr>
                    <tr>
                        <th>20. GSIS ID NO.</th>
                        <td><input type="text" class="form-control" name="gsis_id_no"></td>
                        <th colspan="2">21. TIN NO.</th>
                        <td colspan="2"><input type="text" class="form-control" name="tin_no"></td>
                    </tr>
                    <tr>
                        <th>21. PAG-IBIG ID NO.</th>
                        <td><input type="text" class="form-control" name="pagibig_id_no"></td>
                        <th colspan="2">22. PHILHEALTH NO.</th>
                        <td colspan="2"><input type="text" class="form-control" name="philhealth_no"></td>
                    </tr>
                    <tr>
                        <th>22. SSS NO.</th>
                        <td><input type="text" class="form-control" name="sss_no"></td>
                        <th colspan="2">23. TELEPHONE NO.</th>
                        <td colspan="2"><input type="text" class="form-control" name="telephone_no"></td>
                    </tr>
                    <tr>
                        <th rowspan="4">17. RESIDENTIAL ADDRESS</th>
                        <td colspan="3"><input type="text" class="form-control" name="res_house_no" placeholder="House/Block/Lot No."></td>
                        <td colspan="2"><input type="text" class="form-control" name="res_street" placeholder="Street"></td>
                    </tr>
                    <tr>
                        <td colspan="3"><input type="text" class="form-control" name="res_subdivision" placeholder="Subdivision/Village"></td>
                        <td colspan="2"><input type="text" class="form-control" name="res_barangay" placeholder="Barangay"></td>
                    </tr>
                    <tr>
                        <td colspan="3"><input type="text" class="form-control" name="res_city" placeholder="City/Municipality"></td>
                        <td colspan="2"><input type="text" class="form-control" name="res_province" placeholder="Province"></td>
                    </tr>
                    <tr>
                        <td colspan="3">ZIP CODE: <input type="text" class="form-control d-inline w-50" name="res_zip"></td>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <th rowspan="4">18. PERMANENT ADDRESS</th>
                        <td colspan="3"><input type="text" class="form-control" name="perm_house_no" placeholder="House/Block/Lot No."></td>
                        <td colspan="2"><input type="text" class="form-control" name="perm_street" placeholder="Street"></td>
                    </tr>
                    <tr>
                        <td colspan="3"><input type="text" class="form-control" name="perm_subdivision" placeholder="Subdivision/Village"></td>
                        <td colspan="2"><input type="text" class="form-control" name="perm_barangay" placeholder="Barangay"></td>
                    </tr>
                    <tr>
                        <td colspan="3"><input type="text" class="form-control" name="perm_city" placeholder="City/Municipality"></td>
                        <td colspan="2"><input type="text" class="form-control" name="perm_province" placeholder="Province"></td>
                    </tr>
                    <tr>
                        <td colspan="3">ZIP CODE: <input type="text" class="form-control d-inline w-50" name="perm_zip"></td>
                        <td colspan="2"></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- SECTION II: FAMILY BACKGROUND (Page 2 partial) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseFamily" aria-expanded="true" aria-controls="collapseFamily">
                II. FAMILY BACKGROUND <span class="badge bg-secondary ms-2">Page 2 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseFamily">
                <table class="table table-bordered pds-table">
                    <tr>
                        <th rowspan="8">22. SPOUSE'S INFO</th>
                        <th>SURNAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="spouse_surname"></td>
                    </tr>
                    <tr>
                        <th>FIRST NAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="spouse_first_name"></td>
                    </tr>
                    <tr>
                        <th>MIDDLE NAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="spouse_middle_name"></td>
                    </tr>
                    <tr>
                        <th>OCCUPATION</th>
                        <td colspan="4"><input type="text" class="form-control" name="spouse_occupation"></td>
                    </tr>
                    <tr>
                        <th>EMPLOYER/BUSINESS NAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="spouse_employer"></td>
                    </tr>
                    <tr>
                        <th>BUSINESS ADDRESS</th>
                        <td colspan="4"><input type="text" class="form-control" name="spouse_business_address"></td>
                    </tr>
                    <tr>
                        <th>TELEPHONE NO.</th>
                        <td colspan="4"><input type="text" class="form-control" name="spouse_telephone"></td>
                    </tr>
                    <tr>
                        <th colspan="2">23. NAME OF CHILDREN <span class="small-label">(Write full name and list all)</span></th>
                        <th>DATE OF BIRTH <span class="small-label">(mm/dd/yyyy)</span></th>
                        <th><button type="button" class="btn btn-sm btn-success w-100" onclick="addRow('children_table')">Add Child</button></th>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <table class="table table-sm table-borderless m-0" id="children_table">
                                <tr>
                                    <td class="w-50"><input type="text" class="form-control" name="child_name[]"></td>
                                    <td class="w-25"><input type="date" class="form-control" name="child_dob[]"></td>
                                    <td class="w-25"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">Remove</button></td>
                                </tr>
                            </table>
                            <small class="text-muted d-block text-end mt-1">(Continue on separate sheet if necessary)</small>
                        </td>
                    </tr>
                    <tr>
                        <th rowspan="3">24. FATHER'S INFO</th>
                        <th>SURNAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="father_surname"></td>
                    </tr>
                    <tr>
                        <th>FIRST NAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="father_first_name"></td>
                    </tr>
                    <tr>
                        <th>MIDDLE NAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="father_middle_name"></td>
                    </tr>
                    <tr>
                        <th rowspan="3">25. MOTHER'S MAIDEN NAME</th>
                        <th>SURNAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="mother_surname"></td>
                    </tr>
                    <tr>
                        <th>FIRST NAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="mother_first_name"></td>
                    </tr>
                    <tr>
                        <th>MIDDLE NAME</th>
                        <td colspan="4"><input type="text" class="form-control" name="mother_middle_name"></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- SECTION III: EDUCATIONAL BACKGROUND (Page 2 cont.) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseEducation" aria-expanded="true" aria-controls="collapseEducation">
                III. EDUCATIONAL BACKGROUND <span class="badge bg-secondary ms-2">Page 2 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseEducation">
                <table class="table table-bordered pds-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" class="w-10">26. LEVEL</th>
                            <th rowspan="2" class="w-20">NAME OF SCHOOL <span class="small-label">(Write in full)</span></th>
                            <th rowspan="2" class="w-20">BASIC EDUCATION/DEGREE/COURSE <span class="small-label">(Write in full)</span></th>
                            <th colspan="2" class="w-15">PERIOD OF ATTENDANCE</th>
                            <th rowspan="2" class="w-10">HIGHEST LEVEL/UNITS EARNED <span class="small-label">(if not graduated)</span></th>
                            <th rowspan="2" class="w-10">YEAR GRADUATED</th>
                            <th rowspan="2" class="w-10">SCHOLARSHIP/ACADEMIC HONORS RECEIVED</th>
                        </tr>
                        <tr>
                            <th class="w-5">From</th>
                            <th class="w-5">To</th>
                        </tr>
                    </thead>
                    <tbody id="education_table">
                        <tr data-level="Elementary">
                            <td class="text-center fw-bold">ELEMENTARY</td>
                            <td><input type="text" class="form-control" name="educ_school_elem"></td>
                            <td><input type="text" class="form-control" name="educ_course_elem" value="N/A" readonly></td>
                            <td><input type="month" class="form-control" name="educ_from_elem"></td>
                            <td><input type="month" class="form-control" name="educ_to_elem"></td>
                            <td><input type="text" class="form-control" name="educ_level_elem"></td>
                            <td><input type="number" class="form-control" name="educ_year_elem"></td>
                            <td><input type="text" class="form-control" name="educ_honors_elem"></td>
                        </tr>
                        <tr data-level="Secondary">
                            <td class="text-center fw-bold">SECONDARY</td>
                            <td><input type="text" class="form-control" name="educ_school_sec"></td>
                            <td><input type="text" class="form-control" name="educ_course_sec" value="N/A" readonly></td>
                            <td><input type="month" class="form-control" name="educ_from_sec"></td>
                            <td><input type="month" class="form-control" name="educ_to_sec"></td>
                            <td><input type="text" class="form-control" name="educ_level_sec"></td>
                            <td><input type="number" class="form-control" name="educ_year_sec"></td>
                            <td><input type="text" class="form-control" name="educ_honors_sec"></td>
                        </tr>
                        <tr data-level="Vocational">
                            <td class="text-center fw-bold">VOCATIONAL / TRADE COURSE</td>
                            <td><input type="text" class="form-control" name="educ_school_voc"></td>
                            <td><input type="text" class="form-control" name="educ_course_voc"></td>
                            <td><input type="month" class="form-control" name="educ_from_voc"></td>
                            <td><input type="month" class="form-control" name="educ_to_voc"></td>
                            <td><input type="text" class="form-control" name="educ_level_voc"></td>
                            <td><input type="number" class="form-control" name="educ_year_voc"></td>
                            <td><input type="text" class="form-control" name="educ_honors_voc"></td>
                        </tr>
                        <tr data-level="College">
                            <td class="text-center fw-bold">COLLEGE</td>
                            <td><input type="text" class="form-control" name="educ_school_coll"></td>
                            <td><input type="text" class="form-control" name="educ_course_coll"></td>
                            <td><input type="month" class="form-control" name="educ_from_coll"></td>
                            <td><input type="month" class="form-control" name="educ_to_coll"></td>
                            <td><input type="text" class="form-control" name="educ_level_coll"></td>
                            <td><input type="number" class="form-control" name="educ_year_coll"></td>
                            <td><input type="text" class="form-control" name="educ_honors_coll"></td>
                        </tr>
                        <tr data-level="Graduate">
                            <td class="text-center fw-bold">GRADUATE STUDIES</td>
                            <td><input type="text" class="form-control" name="educ_school_grad"></td>
                            <td><input type="text" class="form-control" name="educ_course_grad"></td>
                            <td><input type="month" class="form-control" name="educ_from_grad"></td>
                            <td><input type="month" class="form-control" name="educ_to_grad"></td>
                            <td><input type="text" class="form-control" name="educ_level_grad"></td>
                            <td><input type="number" class="form-control" name="educ_year_grad"></td>
                            <td><input type="text" class="form-control" name="educ_honors_grad"></td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted d-block text-end mt-1">(Continue on separate sheet if necessary)</small>
            </div>
        </div>
        
        <!-- ============================================== -->
        <!-- SECTION IV: CIVIL SERVICE ELIGIBILITY (Page 2 cont.) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseEligibility" aria-expanded="true" aria-controls="collapseEligibility">
                IV. CIVIL SERVICE ELIGIBILITY <span class="badge bg-secondary ms-2">Page 2 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseEligibility">
                <table class="table table-bordered pds-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" class="w-25">27. CAREER SERVICE / RA 1080 (BOARD/BAR) / CES / CSEE / BARANGAY ELIGIBILITY / DRIVER'S LICENSE</th>
                            <th rowspan="2" class="w-10">RATING <span class="small-label">(If Applicable)</span></th>
                            <th rowspan="2" class="w-15">DATE OF EXAMINATION / CONFERMENT</th>
                            <th rowspan="2" class="w-20">PLACE OF EXAMINATION / CONFERMENT</th>
                            <th colspan="2" class="w-30">LICENSE <span class="small-label">(If applicable)</span></th>
                            <th rowspan="2" class="w-5">
                                <button type="button" class="btn btn-sm btn-success w-100" onclick="addRow('eligibility_table')">Add</button>
                            </th>
                        </tr>
                        <tr>
                            <th>NUMBER</th>
                            <th>Date of Validity</th>
                        </tr>
                    </thead>
                    <tbody id="eligibility_table">
                        <tr>
                            <td><input type="text" class="form-control" name="elig_career[]"></td>
                            <td><input type="text" class="form-control" name="elig_rating[]"></td>
                            <td><input type="date" class="form-control" name="elig_date[]"></td>
                            <td><input type="text" class="form-control" name="elig_place[]"></td>
                            <td><input type="text" class="form-control" name="elig_license_num[]"></td>
                            <td><input type="date" class="form-control" name="elig_validity_date[]"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">X</button></td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted d-block text-end mt-1">(Continue on separate sheet if necessary)</small>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- SECTION V: WORK EXPERIENCE (Pages 2-3) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseWork" aria-expanded="true" aria-controls="collapseWork">
                V. WORK EXPERIENCE <span class="badge bg-secondary ms-2">Pages 2 & 3 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseWork">
                <p class="small fst-italic text-muted">
                    (Include private employment. Start from your recent work) Description of duties should be indicated in the attached Work Experience sheet.
                </p>
                <table class="table table-bordered pds-table mb-0">
                    <thead>
                        <tr>
                            <th colspan="2" class="w-15">28. INCLUSIVE DATES <span class="small-label">(mm/dd/yyyy)</span></th>
                            <th rowspan="2" class="w-20">POSITION TITLE <span class="small-label">(Write in full/Do not abbreviate)</span></th>
                            <th rowspan="2" class="w-25">DEPARTMENT / AGENCY / OFFICE / COMPANY <span class="small-label">(Write in full/Do not abbreviate)</span></th>
                            <th rowspan="2" class="w-10">MONTHLY SALARY</th>
                            <th rowspan="2" class="w-15">SALARY/ JOB/ PAY GRADE <span class="small-label">(If applicable) & STEP (Format '00-00')</span></th>
                            <th rowspan="2" class="w-10">STATUS OF APPOINTMENT</th>
                            <th rowspan="2" class="w-5">GOV'T SERVICE (Y/N)</th>
                            <th rowspan="2" class="w-5">
                                <button type="button" class="btn btn-sm btn-success w-100" onclick="addRow('work_exp_table')">Add</button>
                            </th>
                        </tr>
                        <tr>
                            <th class="w-7">From</th>
                            <th class="w-7">To</th>
                        </tr>
                    </thead>
                    <tbody id="work_exp_table">
                        <tr>
                            <td><input type="date" class="form-control" name="work_from[]"></td>
                            <td><input type="date" class="form-control" name="work_to[]"></td>
                            <td><input type="text" class="form-control" name="work_position[]"></td>
                            <td><input type="text" class="form-control" name="work_company[]"></td>
                            <td><input type="number" step="1" class="form-control" name="work_salary[]"></td>
                            <td><input type="text" class="form-control" name="work_grade[]"></td>
                            <td><input type="text" class="form-control" name="work_status[]"></td>
                            <td><input type="text" class="form-control" name="work_govt[]"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">X</button></td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted d-block text-end mt-1">(Continue on separate sheet if necessary)</small>
            </div>
        </div>
        
        <!-- ============================================== -->
        <!-- SECTION VI: VOLUNTARY WORK (Page 3 cont.) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseVoluntary" aria-expanded="true" aria-controls="collapseVoluntary">
                VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S <span class="badge bg-secondary ms-2">Page 3 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseVoluntary">
                <table class="table table-bordered pds-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" class="w-30">29. NAME & ADDRESS OF ORGANIZATION <span class="small-label">(Write in full)</span></th>
                            <th colspan="2" class="w-20">INCLUSIVE DATES <span class="small-label">(mm/dd/yyyy)</span></th>
                            <th rowspan="2" class="w-10">NUMBER OF HOURS</th>
                            <th rowspan="2" class="w-35">POSITION / NATURE OF WORK</th>
                            <th rowspan="2" class="w-5">
                                <button type="button" class="btn btn-sm btn-success w-100" onclick="addRow('voluntary_work_table')">Add</button>
                            </th>
                        </tr>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                        </tr>
                    </thead>
                    <tbody id="voluntary_work_table">
                        <tr>
                            <td><input type="text" class="form-control" name="vol_org_name[]"></td>
                            <td><input type="date" class="form-control" name="vol_from[]"></td>
                            <td><input type="date" class="form-control" name="vol_to[]"></td>
                            <td><input type="number" class="form-control" name="vol_hours[]"></td>
                            <td><input type="text" class="form-control" name="vol_position[]"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">X</button></td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted d-block text-end mt-1">(Continue on separate sheet if necessary)</small>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- SECTION VII: L&D/TRAINING (Page 3 cont.) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseTraining" aria-expanded="true" aria-controls="collapseTraining">
                VII. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED <span class="badge bg-secondary ms-2">Page 3 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseTraining">
                <table class="table table-bordered pds-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" class="w-30">30. TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS ATTENDED <span class="small-label">(Write in full)</span></th>
                            <th colspan="2" class="w-20">INCLUSIVE DATES OF ATTENDANCE <span class="small-label">(mm/dd/yyyy)</span></th>
                            <th rowspan="2" class="w-10">NUMBER OF HOURS</th>
                            <th rowspan="2" class="w-20">TYPE OF L&D <span class="small-label">(Managerial/ Supervisory/ Technical/ etc)</span></th>
                            <th rowspan="2" class="w-20">CONDUCTED / SPONSORED BY <span class="small-label">(Write in full)</span></th>
                            <th rowspan="2" class="w-5">
                                <button type="button" class="btn btn-sm btn-success w-100" onclick="addRow('training_table')">Add</button>
                            </th>
                        </tr>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                        </tr>
                    </thead>
                    <tbody id="training_table">
                        <tr>
                            <td><input type="text" class="form-control" name="train_title[]"></td>
                            <td><input type="date" class="form-control" name="train_from[]"></td>
                            <td><input type="date" class="form-control" name="train_to[]"></td>
                            <td><input type="number" class="form-control" name="train_hours[]"></td>
                            <td><input type="text" class="form-control" name="train_type[]"></td>
                            <td><input type="text" class="form-control" name="train_sponsor[]"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">X</button></td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted d-block text-end mt-1">(Continue on separate sheet if necessary)</small>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- SECTION VIII: OTHER INFORMATION (Page 3 cont.) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseOtherInfo" aria-expanded="true" aria-controls="collapseOtherInfo">
                VIII. OTHER INFORMATION <span class="badge bg-secondary ms-2">Page 3 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseOtherInfo">
                <table class="table table-bordered pds-table mb-0">
                    <thead>
                        <tr>
                            <th class="w-33">31. SPECIAL SKILLS AND HOBBIES</th>
                            <th class="w-33">32. NON-ACADEMIC DISTINCTIONS / RECOGNITION <span class="small-label">(Write in full)</span></th>
                            <th class="w-33">33. MEMBERSHIP IN ASSOCIATION/ORGANIZATION <span class="small-label">(Write in full)</span></th>
                        </tr>
                    </thead>
                    <tbody id="other_info_table">
                        <tr>
                            <td><input type="text" class="form-control" name="skill[]"></td>
                            <td><input type="text" class="form-control" name="distinction[]"></td>
                            <td><input type="text" class="form-control" name="membership[]"></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-center">
                                <button type="button" class="btn btn-sm btn-success w-25" onclick="addRow('other_info_table', true)">Add Row</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted d-block text-end mt-1">(Continue on separate sheet if necessary)</small>
            </div>
        </div>
        
        <!-- ============================================== -->
        <!-- SECTION IX: DECLARATIONS AND QUESTIONS (Page 4 cont.) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseQuestions" aria-expanded="true" aria-controls="collapseQuestions">
                IX. CENSUS INFORMATION / DECLARATIONS (34-40) <span class="badge bg-secondary ms-2">Page 4 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseQuestions">
                
                <div class="row question-row">
                    <div class="col-md-9 question-label">34. Are you related by consanguinity or affinity to the appointing/recommending authority, or Chief of Office/Bureau/Department or person who has immediate supervision over you in the Office, within the third degree?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q34" id="q34_yes" value="Yes" onclick="toggleDetails('q34_details', true)" required>
                            <label class="form-check-label" for="q34_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q34" id="q34_no" value="No" onclick="toggleDetails('q34_details', false)" checked>
                            <label class="form-check-label" for="q34_no">No</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="q34_details" style="display:none;">
                        <input type="text" class="form-control form-control-sm" name="q34_details" placeholder="If YES, give details (name and relationship)">
                    </div>
                </div>

                <div class="row question-row">
                    <div class="col-md-9 question-label">35. a) Have you ever been found guilty of any administrative offense?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q35a" id="q35a_yes" value="Yes" onclick="toggleDetails('q35a_details', true)" required>
                            <label class="form-check-label" for="q35a_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q35a" id="q35a_no" value="No" onclick="toggleDetails('q35a_details', false)" checked>
                            <label class="form-check-label" for="q35a_no">No</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="q35a_details" style="display:none;">
                        <input type="text" class="form-control form-control-sm" name="q35a_details_offense" placeholder="If YES, give details of offense">
                    </div>
                </div>

                <div class="row question-row">
                    <div class="col-md-9 question-label">35. b) Have you been criminally charged before any court?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q35b" id="q35b_yes" value="Yes" onclick="toggleDetails('q35b_details', true)" required>
                            <label class="form-check-label" for="q35b_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q35b" id="q35b_no" value="No" onclick="toggleDetails('q35b_details', false)" checked>
                            <label class="form-check-label" for="q35b_no">No</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="q35b_details" style="display:none;">
                        <input type="text" class="form-control form-control-sm mb-2" name="q35b_details_when" placeholder="If YES, Date Filed">
                        <input type="text" class="form-control form-control-sm" name="q35b_details_case" placeholder="Status of Case">
                    </div>
                </div>
                
                <div class="row question-row">
                    <div class="col-md-9 question-label">36. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, or other separation from the service?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q36" id="q36_yes" value="Yes" onclick="toggleDetails('q36_details', true)" required>
                            <label class="form-check-label" for="q36_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q36" id="q36_no" value="No" onclick="toggleDetails('q36_details', false)" checked>
                            <label class="form-check-label" for="q36_no">No</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="q36_details" style="display:none;">
                        <input type="text" class="form-control form-control-sm" name="q36_details" placeholder="If YES, give details">
                    </div>
                </div>

                <div class="row question-row">
                    <div class="col-md-9 question-label">37. a) Have you ever been a candidate in a national or local election (except Barangay election)?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q37a" id="q37a_yes" value="Yes" onclick="toggleDetails('q37a_details', true)" required>
                            <label class="form-check-label" for="q37a_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q37a" id="q37a_no" value="No" onclick="toggleDetails('q37a_details', false)" checked>
                            <label class="form-check-label" for="q37a_no">No</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="q37a_details" style="display:none;">
                        <input type="text" class="form-control form-control-sm" name="q37a_details" placeholder="If YES, give details of election">
                    </div>
                </div>

                <div class="row question-row">
                    <div class="col-md-9 question-label">37. b) Have you resigned from the government service during the three (3) month period before the last election to promote/campaign for a candidate?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q37b" id="q37b_yes" value="Yes" required>
                            <label class="form-check-label" for="q37b_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q37b" id="q37b_no" value="No" checked>
                            <label class="form-check-label" for="q37b_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="row question-row">
                    <div class="col-md-9 question-label">38. Have you acquired the status of an immigrant or permanent resident of a foreign country?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q38" id="q38_yes" value="Yes" onclick="toggleDetails('q38_details', true)" required>
                            <label class="form-check-label" for="q38_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q38" id="q38_no" value="No" onclick="toggleDetails('q38_details', false)" checked>
                            <label class="form-check-label" for="q38_no">No</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="q38_details" style="display:none;">
                        <input type="text" class="form-control form-control-sm" name="q38_details" placeholder="If YES, acquire permanent residency in which country?">
                    </div>
                </div>

                <div class="row question-row">
                    <div class="col-md-9 question-label">39. Are you a member of any Indigenous group?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q39" id="q39_yes" value="Yes" onclick="toggleDetails('q39_details', true)" required>
                            <label class="form-check-label" for="q39_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q39" id="q39_no" value="No" onclick="toggleDetails('q39_details', false)" checked>
                            <label class="form-check-label" for="q39_no">No</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="q39_details" style="display:none;">
                        <input type="text" class="form-control form-control-sm" name="q39_details" placeholder="If YES, please specify">
                    </div>
                </div>
                
                <div class="row question-row">
                    <div class="col-md-9 question-label">40. a) Are you a solo parent?</div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q40a" id="q40a_yes" value="Yes" onclick="toggleDetails('q40a_details', true)" required>
                            <label class="form-check-label" for="q40a_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="q40a" id="q40a_no" value="No" onclick="toggleDetails('q40a_details', false)" checked>
                            <label class="form-check-label" for="q40a_no">No</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="q40a_details" style="display:none;">
                        <input type="text" class="form-control form-control-sm" name="q40a_details" placeholder="If YES, CIC No. (RA 8972)">
                    </div>
                </div>
                <small class="text-muted d-block text-end mt-3">...and other remaining questions for PDS (40.b - 40.c) would follow here.</small>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- SECTION X: REFERENCES, ID AND SIGNATURE (Page 4 cont.) -->
        <!-- ============================================== -->
        <div class="card mb-3">
            <div class="section-header" data-bs-toggle="collapse" data-bs-target="#collapseReferences" aria-expanded="true" aria-controls="collapseReferences">
                X. REFERENCES, ID, SIGNATURE, AND OATH <span class="badge bg-secondary ms-2">Page 4 of 4</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show pds-content" id="collapseReferences">
                
                <h5 class="text-primary mt-2">41. REFERENCES (List at least three)</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered pds-table mb-0" id="references_table">
                        <thead>
                            <tr>
                                <th class="w-40">NAME</th>
                                <th class="w-30">ADDRESS</th>
                                <th class="w-30">TELEPHONE / MOBILE NO.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Fixed three rows for references -->
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                            <tr>
                                <td><input type="text" class="form-control" name="ref_name_<?php echo $i; ?>" required></td>
                                <td><input type="text" class="form-control" name="ref_address_<?php echo $i; ?>" required></td>
                                <td><input type="text" class="form-control" name="ref_tel_<?php echo $i; ?>" required></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info text-center fw-bold mt-4" role="alert">
                    I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein. I  agree that any misrepresentation made in this document and its attachments shall cause the filing of administrative/criminal case/s against me.
                </div>
                
                <div class="row g-4 justify-content-around align-items-end mt-4">
                    <div class="col-md-3 text-center">
                        <div class="p-3 border rounded-lg bg-light">
                            <label class="form-label text-muted mb-0 small">Government Issued ID</label>
                            <input type="text" class="form-control form-control-sm mb-2" name="govt_id_type" placeholder="ID Card Type" required>
                            <input type="text" class="form-control form-control-sm mb-2" name="govt_id_no" placeholder="ID Card No." required>
                            <input type="date" class="form-control form-control-sm" name="govt_id_issue_date" title="Date of Issuance" required>
                            <small class="text-muted d-block mt-2">Date of Issuance</small>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <small class="text-muted">Subscribed and sworn to before me this <input type="date" class="form-control form-control-sm d-inline w-auto" name="sworn_date">, affiant exhibiting his/her aforesaid Government Issued ID.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg">Save PDS Draft</button>
        </div>

    </form>
</div>

<!-- Font Awesome for Collapse Icon (Optional, but useful) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js"></script>

<!-- Custom JS for dynamic rows (Must be loaded *after* the HTML elements) -->
<script>
    /**
     * Handles adding new rows for dynamic sections (Children, Eligibility, Work, etc.).
     * @param {string} tableId - The ID of the <tbody> element to append the row to.
     * @param {boolean} isOtherInfo - Special handling for the Other Information table.
     */
    function addRow(tableId, isOtherInfo = false) {
        const tableBody = document.getElementById(tableId);
        let newRow = tableBody.insertRow(-1); 

        // Define row content templates
        const templates = {
            children_table: `
                <td class="w-50"><input type="text" class="form-control" name="child_name[]"></td>
                <td class="w-25"><input type="date" class="form-control" name="child_dob[]"></td>
                <td class="w-25"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">Remove</button></td>
            `,
            eligibility_table: `
                <td><input type="text" class="form-control" name="elig_career[]"></td>
                <td><input type="text" class="form-control" name="elig_rating[]"></td>
                <td><input type="date" class="form-control" name="elig_date[]"></td>
                <td><input type="text" class="form-control" name="elig_place[]"></td>
                <td><input type="text" class="form-control" name="elig_license_num[]"></td>
                <td><input type="date" class="form-control" name="elig_validity_date[]"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">X</button></td>
            `,
            work_exp_table: `
                <td><input type="date" class="form-control" name="work_from[]"></td>
                <td><input type="date" class="form-control" name="work_to[]"></td>
                <td><input type="text" class="form-control" name="work_position[]"></td>
                <td><input type="text" class="form-control" name="work_company[]"></td>
                <td><input type="number" step="1" class="form-control" name="work_salary[]"></td>
                <td><input type="text" class="form-control" name="work_grade[]"></td>
                <td><input type="text" class="form-control" name="work_status[]"></td>
                <td><input type="text" class="form-control" name="work_govt[]"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">X</button></td>
            `,
            voluntary_work_table: `
                <td><input type="text" class="form-control" name="vol_org_name[]"></td>
                <td><input type="date" class="form-control" name="vol_from[]"></td>
                <td><input type="date" class="form-control" name="vol_to[]"></td>
                <td><input type="number" class="form-control" name="vol_hours[]"></td>
                <td><input type="text" class="form-control" name="vol_position[]"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">X</button></td>
            `,
            training_table: `
                <td><input type="text" class="form-control" name="train_title[]"></td>
                <td><input type="date" class="form-control" name="train_from[]"></td>
                <td><input type="date" class="form-control" name="train_to[]"></td>
                <td><input type="number" class="form-control" name="train_hours[]"></td>
                <td><input type="text" class="form-control" name="train_type[]"></td>
                <td><input type="text" class="form-control" name="train_sponsor[]"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(this)">X</button></td>
            `,
            other_info_row: `
                <td><input type="text" class="form-control" name="skill[]"></td>
                <td><input type="text" class="form-control" name="distinction[]"></td>
                <td><input type="text" class="form-control" name="membership[]"></td>
            `
        };

        if (isOtherInfo) {
            // Special handling for Other Info: insert before the last row (which is the button row)
            const buttonRow = tableBody.lastElementChild;
            if (buttonRow && buttonRow.querySelectorAll('td').length === 3) {
                 newRow = tableBody.insertRow(tableBody.rows.length - 1);
            }
            newRow.innerHTML = templates.other_info_row;

            // Remove the previous button row and re-insert it at the end to keep the structure clean
            if (buttonRow.parentNode) {
                buttonRow.parentNode.removeChild(buttonRow);
            }
            const newButtonRow = tableBody.insertRow(-1);
            newButtonRow.innerHTML = `
                <td colspan="3" class="text-center">
                    <button type="button" class="btn btn-sm btn-success w-25" onclick="addRow('other_info_table', true)">Add Row</button>
                </td>
            `;
        } else if (templates[tableId]) {
            newRow.innerHTML = templates[tableId];
        }
    }

    /**
     * Removes the nearest <tr> ancestor of the button clicked.
     * @param {HTMLElement} btn - The button that was clicked.
     */
    function removeRow(btn) {
        const row = btn.closest('tr');
        if (row) {
            row.remove();
        }
    }

    /**
     * Toggles the visibility of a detail input field based on the radio button selection.
     * Also manages the 'required' attribute for validation.
     * @param {string} detailsId - The ID of the details container (e.g., 'q34_details').
     * @param {boolean} show - True to show the field, false to hide it.
     */
    function toggleDetails(detailsId, show) {
        const details = document.getElementById(detailsId);
        if (details) {
            details.style.display = show ? 'block' : 'none';
            // Manage the 'required' attribute for all input fields inside the details container
            const inputs = details.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (show) {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                    input.value = ''; // Clear the value when hidden
                }
            });
        }
    }

    // Initialize conditional fields on load
    document.addEventListener('DOMContentLoaded', function() {
        // Find all radio buttons that are checked to 'No' and hide their associated detail fields
        document.querySelectorAll('input[type="radio"][value="No"]:checked').forEach(radio => {
            const questionName = radio.name;
            const detailsId = questionName + '_details';
            toggleDetails(detailsId, false);
        });
    });
</script>

<?php
// Include footer
include_once('includes/footer.php');
?> 