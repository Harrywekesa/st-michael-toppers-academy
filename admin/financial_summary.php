<!-- admin/financial_summary.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'accountant') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Get filter parameters
$year = $_GET['year'] ?? date('Y');
$term = $_GET['term'] ?? '';

// Build query for financial summary
$query = "
    SELECT 
        p.term,
        p.year,
        SUM(p.amount_paid) as total_collected,
        COUNT(DISTINCT p.student_id) as unique_students,
        COUNT(p.id) as total_payments
    FROM payments p
    WHERE p.year = ?
";

$params = [$year];

if ($term) {
    $query .= " AND p.term = ?";
    $params[] = $term;
}

$query .= " GROUP BY p.term, p.year ORDER BY p.term";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$financial_data = $stmt->fetchAll();

// Get total students
$stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
$total_students = $stmt->fetch()['count'];

// Get fee structures
$fee_query = "SELECT * FROM fees WHERE year = ?";
if ($term) {
    $fee_query .= " AND term = ?";
    $stmt = $pdo->prepare($fee_query);
    $stmt->execute([$year, $term]);
} else {
    $stmt = $pdo->prepare($fee_query);
    $stmt->execute([$year]);
}
$fee_structures = $stmt->fetchAll();

// Calculate expected revenue
$total_expected = 0;
foreach($fee_structures as $fee) {
    $total_expected += $fee['amount'] * $total_students;
}

// Calculate total collected
$total_collected = 0;
foreach($financial_data as $data) {
    $total_collected += $data['total_collected'];
}

// Calculate collection rate
$collection_rate = $total_expected > 0 ? ($total_collected / $total_expected) * 100 : 0;
?>

<?php include 'includes/accountant_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/accountant_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Financial Summary</h1>
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo $year; ?>" required>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label">Term</label>
                            <select class="form-control" name="term">
                                <option value="">All Terms</option>
                                <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                                <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                                <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Generate</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h6>Total Students</h6>
                            <h3><?php echo $total_students; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6>Total Collected (KES)</h6>
                            <h3><?php echo number_format($total_collected, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h6>Expected Revenue (KES)</h6>
                            <h3><?php echo number_format($total_expected, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h6>Collection Rate</h6>
                            <h3><?php echo number_format($collection_rate, 1); ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Fee Structures for <?php echo $year; ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Term</th>
                                    <th>Fee per Student (KES)</th>
                                    <th>Expected from All Students (KES)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($fee_structures as $fee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fee['term']); ?></td>
                                    <td><?php echo number_format($fee['amount'], 2); ?></td>
                                    <td><?php echo number_format($fee['amount'] * $total_students, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Financial Performance</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($financial_data)): ?>
                        <div class="alert alert-info">No financial data available for the selected period.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Term</th>
                                        <th>Payments</th>
                                        <th>Students</th>
                                        <th>Collected (KES)</th>
                                        <th>Expected (KES)</th>
                                        <th>Variance (KES)</th>
                                        <th>Collection Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($financial_data as $data): 
                                        // Get expected amount for this term
                                        $expected_for_term = 0;
                                        foreach($fee_structures as $fee) {
                                            if ($fee['term'] == $data['term']) {
                                                $expected_for_term = $fee['amount'] * $total_students;
                                                break;
                                            }
                                        }
                                        $variance = $data['total_collected'] - $expected_for_term;
                                        $term_collection_rate = $expected_for_term > 0 ? 
                                            ($data['total_collected'] / $expected_for_term) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['term']); ?></td>
                                        <td><?php echo $data['total_payments']; ?></td>
                                        <td><?php echo $data['unique_students']; ?></td>
                                        <td><?php echo number_format($data['total_collected'], 2); ?></td>
                                        <td><?php echo number_format($expected_for_term, 2); ?></td>
                                        <td class="<?php echo $variance >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo number_format($variance, 2); ?>
                                        </td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-<?php echo $term_collection_rate >= 90 ? 'success' : ($term_collection_rate >= 70 ? 'warning' : 'danger'); ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo min(100, $term_collection_rate); ?>%" 
                                                     aria-valuenow="<?php echo $term_collection_rate; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php echo number_format($term_collection_rate, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
@media print {
    .sidebar, .btn, form, .alert {
        display: none !important;
    }
    body {
        padding: 0 !important;
    }
}
</style>

<?php include 'includes/accountant_footer.php'; ?>