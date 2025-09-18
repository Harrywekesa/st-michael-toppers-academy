<!-- admin/analytics.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Fetch classes for dropdown
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Initialize variables
$class_id = '';
$subject_id = '';
$term = 'Term 1';
$year = date('Y');

// Fetch subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();

// Get analytics data if parameters are provided
if (isset($_GET['class_id']) && isset($_GET['subject_id']) && isset($_GET['term']) && isset($_GET['year'])) {
    $class_id = $_GET['class_id'];
    $subject_id = $_GET['subject_id'];
    $term = $_GET['term'];
    $year = $_GET['year'];
    
    // Get performance data for the last 3 terms
    $performance_data = [];
    for ($i = 0; $i < 3; $i++) {
        $current_term = "Term " . ($i + 1);
        $stmt = $pdo->prepare("
            SELECT AVG(m.score) as avg_score
            FROM marks m
            JOIN students s ON m.student_id = s.id
            WHERE s.class_id = ? AND m.subject_id = ? AND m.term = ? AND m.year = ?
        ");
        $stmt->execute([$class_id, $subject_id, $current_term, $year]);
        $result = $stmt->fetch();
        $performance_data[$current_term] = $result['avg_score'] ? round($result['avg_score'], 2) : 0;
    }
    
    // Get top performing students
    $stmt = $pdo->prepare("
        SELECT s.name, s.admission_no, AVG(m.score) as avg_score
        FROM students s
        JOIN marks m ON s.id = m.student_id
        WHERE s.class_id = ? AND m.subject_id = ? AND m.term = ? AND m.year = ?
        GROUP BY s.id, s.name, s.admission_no
        ORDER BY avg_score DESC
        LIMIT 10
    ");
    $stmt->execute([$class_id, $subject_id, $term, $year]);
    $top_students = $stmt->fetchAll();
    
    // Get class average
    $stmt = $pdo->prepare("
        SELECT AVG(m.score) as class_avg
        FROM marks m
        JOIN students s ON m.student_id = s.id
        WHERE s.class_id = ? AND m.subject_id = ? AND m.term = ? AND m.year = ?
    ");
    $stmt->execute([$class_id, $subject_id, $term, $year]);
    $class_avg = $stmt->fetch()['class_avg'];
    $class_avg = $class_avg ? round($class_avg, 2) : 0;
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Performance Analytics</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Class</label>
                            <select class="form-control" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Subject</label>
                            <select class="form-control" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo ($subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Term</label>
                            <select class="form-control" name="term" required>
                                <option value="">Select Term</option>
                                <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                                <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                                <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo $year; ?>" required>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if(isset($performance_data)): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Performance Trends</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="performanceChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Class Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <h1><?php echo $class_avg; ?></h1>
                                <p>Class Average</p>
                            </div>
                            <hr>
                            <p><strong>Subject:</strong> 
                                <?php 
                                foreach($subjects as $subject) {
                                    if($subject['id'] == $subject_id) echo $subject['name'];
                                }
                                ?>
                            </p>
                            <p><strong>Term:</strong> <?php echo $term; ?> <?php echo $year; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Top Performing Students</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($top_students)): ?>
                        <p class="text-muted">No data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Average Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($top_students as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo number_format($student['avg_score'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif(isset($_GET['class_id'])): ?>
            <div class="alert alert-info">
                Please select a class, subject, term, and year to generate analytics.
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if(isset($performance_data)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Term 1', 'Term 2', 'Term 3'],
            datasets: [{
                label: 'Average Score',
                data: [
                    <?php echo $performance_data['Term 1']; ?>,
                    <?php echo $performance_data['Term 2']; ?>,
                    <?php echo $performance_data['Term 3']; ?>
                ],
                borderColor: '#001f4d',
                backgroundColor: 'rgba(0, 31, 77, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
});
<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>