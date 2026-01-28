<?php
require_once '../config/db.php';

$errors = [];
$success = "";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: list.php?message=Invalid patient ID&type=error");
    exit();
}

$id = (int)$_GET['id'];   // Convert to integer for security

// Fetch doctors for dropdown
$doctorQuery = "SELECT id, doctor_name, specialization FROM doctors ORDER BY doctor_name";
$doctorResult = mysqli_query($conn, $doctorQuery);

// Fetch patient data
$sql = "SELECT * FROM patients WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: list.php?message=Patient not found&type=error");
    exit();
}

$patient = mysqli_fetch_assoc($result);

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $patient_name = trim($_POST['patient_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $age = trim($_POST['age']);
    $gender = $_POST['gender'];
    $diagnosis = trim($_POST['diagnosis']);
    $doctor_id = !empty($_POST['doctor_id']) ? $_POST['doctor_id'] : NULL;
    
   
    // VALIDATION
   
    
    // Validate Patient Name
    if (empty($patient_name)) {
        $errors[] = "Patient name is required";
    }
    
    // Validate Email (check if exists for other patients)
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        $checkEmail = "SELECT id FROM patients WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $checkEmail);
        mysqli_stmt_bind_param($stmt, "si", $email, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already exists for another patient";
        }
        mysqli_stmt_close($stmt);
    }
    
    // Validate Phone
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must be 10 digits";
    }
    
    // Validate Age
    if (empty($age) || !is_numeric($age) || $age < 0 || $age > 150) {
        $errors[] = "Age must be between 0 and 150";
    }
    
    // Validate Gender
    if (empty($gender)) {
        $errors[] = "Gender is required";
    }
    
    // Validate Diagnosis
    if (empty($diagnosis)) {
        $errors[] = "Diagnosis is required";
    }
    
    // UPDATE IF NO ERRORS
    
    
    if (empty($errors)) {
        $sql = "UPDATE patients SET 
                patient_name = ?, 
                email = ?, 
                phone = ?, 
                age = ?, 
                gender = ?, 
                diagnosis = ?,
                doctor_id = ?
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssissii", $patient_name, $email, $phone, $age, $gender, $diagnosis, $doctor_id, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: list.php?message=Patient updated successfully&type=success");
            exit();
        } else {
            $errors[] = "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
    
    // Update patient array with new values for form display
    $patient['patient_name'] = $patient_name;
    $patient['email'] = $email;
    $patient['phone'] = $phone;
    $patient['age'] = $age;
    $patient['gender'] = $gender;
    $patient['diagnosis'] = $diagnosis;
    $patient['doctor_id'] = $doctor_id;
}

include '../includes/header.php';
?>

<!-- Page Content -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="bi bi-pencil-square"></i> Edit Patient
                </h4>
            </div>
            <div class="card-body">
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Patient Form -->
                <form action="" method="POST">
                    <div class="row">
                        <!-- Patient Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Patient Name <span class="text-danger">*</span></label>
                            <input type="text" name="patient_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['patient_name']); ?>" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                        </div>
                        
                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['phone']); ?>" 
                                   maxlength="10" required>
                        </div>
                        
                        <!-- Age -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" name="age" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['age']); ?>" 
                                   min="0" max="150" required>
                        </div>
                        
                        <!-- Gender -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <!-- Doctor -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Assign Doctor</label>
                            <select name="doctor_id" class="form-select">
                                <option value="">Select Doctor (Optional)</option>
                                <?php 
                                mysqli_data_seek($doctorResult, 0);
                                while ($doctor = mysqli_fetch_assoc($doctorResult)): 
                                ?>
                                    <option value="<?php echo $doctor['id']; ?>" 
                                            <?php echo ($patient['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doctor['doctor_name'] . ' - ' . $doctor['specialization']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Diagnosis -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Diagnosis <span class="text-danger">*</span></label>
                            <textarea name="diagnosis" class="form-control" rows="3" required><?php echo htmlspecialchars($patient['diagnosis']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Update Patient
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>