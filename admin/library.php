<!-- admin/library.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_book'])) {
        $title = $_POST['title'];
        $author = $_POST['author'];
        $isbn = $_POST['isbn'];
        $category = $_POST['category'];
        $total_copies = $_POST['total_copies'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, category, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $author, $isbn, $category, $total_copies, $total_copies]);
            $success = "Book added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding book: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['issue_book'])) {
        $book_id = $_POST['book_id'];
        $student_id = $_POST['student_id'];
        $issue_date = $_POST['issue_date'];
        $due_date = $_POST['due_date'];
        
        try {
            // Check if student already has this book
            $stmt = $pdo->prepare("SELECT id FROM book_issues WHERE book_id=? AND student_id=? AND return_date IS NULL");
            $stmt->execute([$book_id, $student_id]);
            if ($stmt->fetch()) {
                $error = "This student already has this book issued.";
            } else {
                // Issue the book
                $stmt = $pdo->prepare("INSERT INTO book_issues (book_id, student_id, issue_date, due_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$book_id, $student_id, $issue_date, $due_date]);
                
                // Update available copies
                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id=?");
                $stmt->execute([$book_id]);
                
                $success = "Book issued successfully!";
            }
        } catch(PDOException $e) {
            $error = "Error issuing book: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['return_book'])) {
        $issue_id = $_POST['issue_id'];
        $return_date = $_POST['return_date'];
        
        try {
            // Get book_id before updating
            $stmt = $pdo->prepare("SELECT book_id FROM book_issues WHERE id=?");
            $stmt->execute([$issue_id]);
            $book = $stmt->fetch();
            
            // Update return date
            $stmt = $pdo->prepare("UPDATE book_issues SET return_date=? WHERE id=?");
            $stmt->execute([$return_date, $issue_id]);
            
            // Update available copies
            $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id=?");
            $stmt->execute([$book['book_id']]);
            
            $success = "Book returned successfully!";
        } catch(PDOException $e) {
            $error = "Error returning book: " . $e->getMessage();
        }
    }
}

// Create books and book_issues tables if they don't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(255) NOT NULL,
        isbn VARCHAR(20),
        category VARCHAR(100),
        total_copies INT DEFAULT 1,
        available_copies INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS book_issues (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT,
        student_id INT,
        issue_date DATE NOT NULL,
        due_date DATE NOT NULL,
        return_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (book_id) REFERENCES books(id),
        FOREIGN KEY (student_id) REFERENCES students(id)
    )");
} catch(PDOException $e) {
    // Table creation failed, but we'll continue
}

// Fetch all books
$books = $pdo->query("SELECT * FROM books ORDER BY title")->fetchAll();

// Fetch students for issue form
$students = $pdo->query("SELECT id, name, admission_no FROM students WHERE status='active' ORDER BY name")->fetchAll();

// Fetch issued books with student info
$issued_books = $pdo->query("
    SELECT bi.*, b.title, b.author, s.name as student_name, s.admission_no
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.id
    JOIN students s ON bi.student_id = s.id
    WHERE bi.return_date IS NULL
    ORDER BY bi.issue_date
")->fetchAll();

// Fetch overdue books
$overdue_books = $pdo->query("
    SELECT bi.*, b.title, b.author, s.name as student_name, s.admission_no,
           DATEDIFF(CURDATE(), bi.due_date) as days_overdue
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.id
    JOIN students s ON bi.student_id = s.id
    WHERE bi.return_date IS NULL AND bi.due_date < CURDATE()
    ORDER BY bi.due_date
")->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Library Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                    <i class="fas fa-plus"></i> Add New Book
                </button>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Book Catalog</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($books)): ?>
                                <p class="text-muted">No books in the library. Add your first book.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Available</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($books as $book): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><?php echo $book['available_copies']; ?>/<?php echo $book['total_copies']; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="issueBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-book"></i> Issue
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Currently Issued Books</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($issued_books)): ?>
                                <p class="text-muted">No books currently issued.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Book</th>
                                                <th>Student</th>
                                                <th>Issue Date</th>
                                                <th>Due Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($issued_books as $issue): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($issue['title']); ?></td>
                                                <td><?php echo htmlspecialchars($issue['student_name'] . " (" . $issue['admission_no'] . ")"); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($issue['issue_date'])); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($issue['due_date'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success" 
                                                            onclick="returnBook(<?php echo $issue['id']; ?>)">
                                                        <i class="fas fa-undo"></i> Return
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5>Overdue Books</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($overdue_books)): ?>
                                <p class="text-muted">No overdue books.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Book</th>
                                                <th>Student</th>
                                                <th>Days Overdue</th>
                                                <th>Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($overdue_books as $overdue): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($overdue['title']); ?></td>
                                                <td><?php echo htmlspecialchars($overdue['student_name'] . " (" . $overdue['admission_no'] . ")"); ?></td>
                                                <td><?php echo $overdue['days_overdue']; ?> days</td>
                                                <td><?php echo date('M j, Y', strtotime($overdue['due_date'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_book" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <input type="text" class="form-control" name="author" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ISBN (Optional)</label>
                        <input type="text" class="form-control" name="isbn">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" name="category">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Number of Copies</label>
                        <input type="number" class="form-control" name="total_copies" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Issue Book Modal -->
<div class="modal fade" id="issueBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Issue Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="issue_book" value="1">
                    <input type="hidden" name="book_id" id="issue_book_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Book</label>
                        <input type="text" class="form-control" id="issue_book_title" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select class="form-control" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['name'] . " (" . $student['admission_no'] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Issue Date</label>
                        <input type="date" class="form-control" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Issue Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Book Modal -->
<div class="modal fade" id="returnBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Return Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="return_book" value="1">
                    <input type="hidden" name="issue_id" id="return_issue_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Return Date</label>
                        <input type="date" class="form-control" name="return_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Return Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function issueBook(id, title) {
    document.getElementById('issue_book_id').value = id;
    document.getElementById('issue_book_title').value = title;
    new bootstrap.Modal(document.getElementById('issueBookModal')).show();
}

function returnBook(id) {
    document.getElementById('return_issue_id').value = id;
    new bootstrap.Modal(document.getElementById('returnBookModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>