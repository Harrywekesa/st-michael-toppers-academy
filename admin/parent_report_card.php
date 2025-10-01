<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'parent') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Verify that the student belongs to this parent
$student_id = $_GET['student_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND parent_id = ?");
$stmt->execute([$student_id, $_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: parent_dashboard.php');
    exit();
}

// Handle form submission for term/year selection
$term = $_GET['term'] ?? 'Term 1';
$year = $_GET['year'] ?? date('Y');

// Fetch marks for all subjects
$stmt = $pdo->prepare("
    SELECT sub.name as subject_name, m.score
    FROM subjects sub
    JOIN class_subjects cs ON sub.id = cs.subject_id
    LEFT JOIN marks m ON sub.id = m.subject_id AND m.student_id = ? AND m.term = ? AND m.year = ?
    WHERE cs.class_id = ?
    ORDER BY sub.name
");
$stmt->execute([$student_id, $term, $year, $student['class_id']]);
$marks = $stmt->fetchAll();

// Calculate total and average
$total_marks = 0;
$subject_count = 0;
foreach($marks as $mark) {
    if ($mark['score'] !== null) {
        $total_marks += $mark['score'];
        $subject_count++;
    }
}
$average = $subject_count > 0 ? $total_marks / $subject_count : 0;

// Function to calculate grade
function calculateGrade($score) {
    if ($score === null) return 'N/A';
    if ($score >= 80) return 'A';
    if ($score >= 75) return 'A-';
    if ($score >= 70) return 'B+';
    if ($score >= 65) return 'B';
    if ($score >= 60) return 'B-';
    if ($score >= 55) return 'C+';
    if ($score >= 50) return 'C';
    if ($score >= 45) return 'C-';
    if ($score >= 40) return 'D+';
    if ($score >= 35) return 'D';
    return 'E';
}

// Get class name
$stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
$stmt->execute([$student['class_id']]);
$class = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - Parent Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <button class="sidebar-toggle d-md-none">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/parent_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Report Card</h1>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report Card
                    </button>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                            
                            <div class="col-md-5">
                                <label class="form-label">Term</label>
                                <select class="form-control" name="term" onchange="this.form.submit()">
                                    <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                                    <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                                    <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                                </select>
                            </div>
                            
                            <div class="col-md-5">
                                <label class="form-label">Year</label>
                                <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo $year; ?>" onchange="this.form.submit()">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a href="parent_report_cards.php" class="btn btn-secondary w-100">Back</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div id="report-card" class="border p-4" style="max-width: 800px; margin: 0 auto;">
                    <div class="text-center mb-4">
                        <img src="../assets/images/logo.png" alt="School Logo" height="80">
                        <h2 class="mt-2">St. Michael Toppers Academy</h2>
                        <p>Excellence in Education since 2011</p>
                        <p>123 Education Street, Nairobi, Kenya | Tel: +254 700 123 456</p>
                        <hr>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6">
                            <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                            <p><strong>Admission Number:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></p>
                        </div>
                        <div class="col-6">
                            <p><strong>Class:</strong> <?php echo htmlspecialchars($class['name'] ?? 'Unassigned'); ?></p>
                            <p><strong>Term:</strong> <?php echo htmlspecialchars($term); ?> <?php echo htmlspecialchars($year); ?></p>
                        </div>
                    </div>
                    
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Marks</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($marks as $mark): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                <td><?php echo $mark['score'] !== null ? number_format($mark['score'], 1) : 'N/A'; ?></td>
                                <td><?php echo calculateGrade($mark['score']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th><?php echo number_format($total_marks, 1); ?></th>
                                <th><?php echo calculateGrade($average); ?></th>
                            </tr>
                            <tr>
                                <th>Average</th>
                                <th colspan="2"><?php echo number_format($average, 1); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div class="row mt-5">
                        <div class="col-6">
                            <p><strong>Class Teacher:</strong> ________________________</p>
                            <p>Signature: ________________________</p>
                            <p>Date: ________________________</p>
                        </div>
                        <div class="col-6">
                            <p><strong>Parent/Guardian:</strong> ________________________</p>
                            <p>Signature: ________________________</p>
                            <p>Date: ________________________</p>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p><em>"Where every child's potential is unlocked"</em></p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <style>
    @media print {
        .sidebar, .btn, form, .alert, .sidebar-toggle {
            display: none !important;
        }
        body {
            padding: 0 !important;
            background: white !important;
        }
        #report-card {
            box-shadow: none !important;
            border: 1px solid #000 !important;
        }
        main {
            margin: 0 !important;
            padding: 0 !important;
        }
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>