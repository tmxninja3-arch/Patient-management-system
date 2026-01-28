<?php
require_once '../config/db.php';
include '../includes/header.php';

// PAGINATION SETTINGS

$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;


// SEARCH FUNCTIONALITY

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = "";
if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $search_condition = " WHERE p.patient_name LIKE '%$search_safe%' OR p.diagnosis LIKE '%$search_safe%' ";
}

// SORTING FUNCTIONALITY

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Allowed sort columns
$allowed_sort = ['id', 'patient_name', 'age', 'created_at'];
$allowed_order = ['ASC', 'DESC'];

if (!in_array($sort, $allowed_sort)) $sort = 'id';
if (!in_array($order, $allowed_order)) $order = 'DESC';


// COUNT TOTAL RECORDS

$count_sql = "SELECT COUNT(*) as total FROM patients p $search_condition";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);


// FETCH PATIENTS WITH LEFT JOIN

$sql = "SELECT p.*, 
        COALESCE(d.doctor_name, 'Not Assigned') as doctor_name,
        COALESCE(d.specialization, 'N/A') as specialization
        FROM patients p 
        LEFT JOIN doctors d ON p.doctor_id = d.id
        $search_condition 
        ORDER BY p.$sort $order 
        LIMIT $offset, $records_per_page";

$result = mysqli_query($conn, $sql);

// Success/Error Messages from other pages
$message = isset($_GET['message']) ? $_GET['message'] : '';
$msg_type = isset($_GET['type']) ? $_GET['type'] : '';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Patient List</h2>
    <a href="create.php" class="btn btn-success">
        <i class="bi bi-person-plus"></i> Add New Patient
    </a>
</div>

<!-- Messages -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $msg_type == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <i class="bi bi-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Search and Sort Controls -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <!-- Search Form -->
            <div class="col-md-6">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name or diagnosis..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="list.php" class="btn btn-secondary">
                                <i class="bi bi-x"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Sort Options -->
            <div class="col-md-6">
                <div class="d-flex gap-2 justify-content-md-end">
                    <span class="align-self-center">Sort by:</span>
                    <a href="?sort=patient_name&order=<?php echo ($sort == 'patient_name' && $order == 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn btn-outline-primary btn-sm <?php echo $sort == 'patient_name' ? 'active' : ''; ?>">
                        Name <?php echo ($sort == 'patient_name') ? ($order == 'ASC' ? '↑' : '↓') : ''; ?>
                    </a>
                    <a href="?sort=age&order=<?php echo ($sort == 'age' && $order == 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn btn-outline-primary btn-sm <?php echo $sort == 'age' ? 'active' : ''; ?>">
                        Age <?php echo ($sort == 'age') ? ($order == 'ASC' ? '↑' : '↓') : ''; ?>
                    </a>
                    <a href="?sort=created_at&order=<?php echo ($sort == 'created_at' && $order == 'DESC') ? 'ASC' : 'DESC'; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn btn-outline-primary btn-sm <?php echo $sort == 'created_at' ? 'active' : ''; ?>">
                        Date <?php echo ($sort == 'created_at') ? ($order == 'ASC' ? '↑' : '↓') : ''; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Records Info -->
<p class="text-muted">
    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> 
    of <?php echo $total_records; ?> records
</p>

<!-- Patients Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Diagnosis</th>
                            <th>Doctor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $serial = $offset + 1;
                        while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td><?php echo $serial++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['patient_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo $row['age']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($row['gender']); ?>">
                                        <?php echo $row['gender']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['diagnosis']); ?></td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($row['doctor_name']); ?>
                                        <?php if ($row['specialization'] != 'N/A'): ?>
                                            <br><span class="text-muted">(<?php echo htmlspecialchars($row['specialization']); ?>)</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-warning btn-action" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger btn-action" 
                                       onclick="return confirm('Are you sure you want to delete this patient?');"
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-records">
                <i class="bi bi-inbox"></i>
                <h4>No Patients Found</h4>
                <p>
                    <?php if (!empty($search)): ?>
                        No results for "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        Start by adding a new patient
                    <?php endif; ?>
                </p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Add Patient
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <!-- Previous Button -->
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                    <i class="bi bi-chevron-left"></i> Previous
                </a>
            </li>
            
            <!-- Page Numbers -->
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <!-- Next Button -->
            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                    Next <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>