<?php
session_start();
require 'config/db.php';
require 'config/academic_integration.php';

// Security Check: Only logged-in users can borrow
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$book_id = $_GET['book_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Get book details
$book = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
} catch (PDOException $e) {}

if (!$book) {
    header("Location: books.php");
    exit;
}

// Check if user already borrowed this book
$already_borrowed = false;
try {
    $stmt = $pdo->prepare("
        SELECT * FROM borrowings 
        WHERE user_id = ? AND book_id = ? AND status = 'Issued'
    ");
    $stmt->execute([$user_id, $book_id]);
    $already_borrowed = $stmt->fetch() !== false;
} catch (PDOException $e) {}

// Check user's current borrowings limit
$current_borrowings = 0;
$max_borrowings = 5; // Maximum books a student can borrow at once
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM borrowings 
        WHERE user_id = ? AND status = 'Issued'
    ");
    $stmt->execute([$user_id]);
    $current_borrowings = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Handle borrowing request
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'confirm_borrow':
                // Check availability one more time
                if ($book['available_copies'] <= 0) {
                    $message = 'This book is no longer available. Please try again later.';
                    $message_type = 'error';
                    break;
                }
                
                if ($current_borrowings >= $max_borrowings) {
                    $message = "You have reached the maximum borrowing limit of {$max_borrowings} books. Please return some books first.";
                    $message_type = 'error';
                    break;
                }
                
                // Create borrowing record
                $due_date = date('Y-m-d', strtotime('+14 days')); // 2 weeks borrowing period
                $stmt = $pdo->prepare("
                    INSERT INTO borrowings (user_id, book_id, issue_date, due_date, status) 
                    VALUES (?, ?, ?, ?, 'Issued')
                ");
                $stmt->execute([$user_id, $book_id, date('Y-m-d'), $due_date]);
                
                // Update available copies
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_copies = available_copies - 1 
                    WHERE id = ?
                ");
                $stmt->execute([$book_id]);
                
                // Log the activity
                $integration = getAcademicIntegration($pdo);
                if (method_exists($integration, 'logIntegration')) {
                    $integration->logIntegration('create', 'borrow', $user_info['academic_id'] ?? null, 'Book borrowed successfully', [
                        'book_id' => $book_id,
                        'book_title' => $book['title'],
                        'due_date' => $due_date
                    ]);
                }
                
                $message = 'Book borrowed successfully! Due date: ' . date('M j, Y', strtotime($due_date));
                $message_type = 'success';
                
                // Redirect to dashboard after successful borrowing
                header("refresh:3;url=student/dashboard_enhanced.php");
                break;
        }
    } catch (Exception $e) {
        $message = 'Error processing your request: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get user's academic info for personalization
$user_info = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
} catch (PDOException $e) {}

$page_title = "Borrow Book | NGA Library";
$header_title = "Borrow Book";
require 'components/header.php'; 
?>

<!-- Borrowing Confirmation -->
<div class="max-w-4xl mx-auto">
    <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8">
        <!-- Book Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                    <i class='bx bx-book-plus text-blue-500'></i>
                    Borrow Book
                </h2>
                
                <div class="flex gap-6">
                    <!-- Book Cover -->
                    <div class="w-32 h-48 bg-gradient-to-br from-blue-400 to-purple-500 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <?php if ($book['cover_image']): ?>
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                 class="w-full h-full object-cover rounded-2xl">
                        <?php else: ?>
                            <i class='bx bx-book text-white text-4xl'></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Book Info -->
                    <div class="flex-1">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </h3>
                        <p class="text-slate-600 dark:text-slate-400 mb-4">
                            by <?php echo htmlspecialchars($book['author']); ?>
                        </p>
                        
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">ISBN:</span>
                                <span class="text-slate-900 dark:text-white font-mono"><?php echo htmlspecialchars($book['isbn']); ?></span>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Category:</span>
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 rounded-lg text-sm font-semibold">
                                    <?php echo htmlspecialchars($book['category']); ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Pages:</span>
                                <span class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($book['pages']); ?></span>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Language:</span>
                                <span class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($book['language']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Availability Status -->
                <div class="space-y-4">
                    <div class="p-4 <?php echo ($book['available_copies'] > 0) ? 'bg-green-50 dark:bg-green-500/10 border-green-200 dark:border-green-500/20' : 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20'; ?> rounded-xl">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 <?php echo ($book['available_copies'] > 0) ? 'bg-green-100 dark:bg-green-500/20 text-green-600 dark:text-green-400' : 'bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400'; ?> rounded-xl flex items-center justify-center">
                                <i class='bx <?php echo ($book['available_copies'] > 0) ? 'bx-check-circle' : 'bx-x-circle'; ?> text-xl'></i>
                            </div>
                            <div>
                                <p class="font-bold <?php echo ($book['available_copies'] > 0) ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'; ?>">
                                    <?php echo ($book['available_copies'] > 0) ? 'Available' : 'Unavailable'; ?>
                                </p>
                                <p class="text-sm <?php echo ($book['available_copies'] > 0) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <?php echo $book['available_copies']; ?> of <?php echo $book['total_copies']; ?> copies available
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Borrowing Stats -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-500/10 rounded-xl flex items-center justify-center">
                                <i class='bx bx-time text-blue-600 dark:text-blue-400 text-xl'></i>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white">Borrowing Period</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">14 days (2 weeks)</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-500/10 rounded-xl flex items-center justify-center">
                                <i class='bx bx-book-reader text-purple-600 dark:text-purple-400 text-xl'></i>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white">Your Borrowings</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo $current_borrowings; ?> / <?php echo $max_borrowings; ?> active</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Borrowing Conditions -->
        <div class="bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-4 flex items-center gap-2">
                <i class='bx bx-info-circle'></i>
                Borrowing Terms & Conditions
            </h3>
            <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                <li class="flex items-start gap-2">
                    <i class='bx bx-check-circle text-blue-600 dark:text-blue-400 mt-0.5'></i>
                    <span>Borrowing period is 14 days from the date of borrowing</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class='bx bx-check-circle text-blue-600 dark:text-blue-400 mt-0.5'></i>
                    <span>Late returns will incur a fine of 100 RWF per day</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class='bx bx-check-circle text-blue-600 dark:text-blue-400 mt-0.5'></i>
                    <span>Maximum <?php echo $max_borrowings; ?> books can be borrowed at a time</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class='bx bx-check-circle text-blue-600 dark:text-blue-400 mt-0.5'></i>
                    <span>Books must be returned in the same condition as borrowed</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class='bx bx-check-circle text-blue-600 dark:text-blue-400 mt-0.5'></i>
                    <span>Lost or damaged books will be charged at replacement cost</span>
                </li>
            </ul>
        </div>
        
        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl border <?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
                <div class="flex items-center gap-2">
                    <i class='bx <?php echo $message_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?> text-xl'></i>
                    <span class="font-semibold"><?php echo $message; ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="flex gap-4">
            <a href="book_details.php?id=<?php echo $book_id; ?>" class="flex-1 px-6 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors text-center">
                <i class='bx bx-arrow-back'></i>
                Back to Book Details
            </a>
            
            <?php if (!$already_borrowed && $book['available_copies'] > 0 && $current_borrowings < $max_borrowings): ?>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="action" value="confirm_borrow">
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all duration-300 flex items-center justify-center gap-2">
                        <i class='bx bx-book-plus text-xl'></i>
                        Confirm Borrowing
                    </button>
                </form>
            <?php elseif ($already_borrowed): ?>
                <button disabled class="flex-1 px-6 py-3 bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-xl font-semibold cursor-not-allowed text-center">
                    <i class='bx bx-book-reader text-xl'></i>
                    Already Borrowed
                </button>
            <?php elseif ($book['available_copies'] <= 0): ?>
                <button onclick="reserveBook(<?php echo $book_id; ?>)" class="flex-1 px-6 py-3 bg-orange-600 text-white rounded-xl font-semibold hover:bg-orange-700 transition-colors flex items-center justify-center gap-2">
                    <i class='bx bx-bookmark text-xl'></i>
                    Reserve This Book
                </button>
            <?php elseif ($current_borrowings >= $max_borrowings): ?>
                <button disabled class="flex-1 px-6 py-3 bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-xl font-semibold cursor-not-allowed text-center">
                    <i class='bx bx-block text-xl'></i>
                    Borrowing Limit Reached
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Academic Integration Notice -->
<?php if (!empty($user_info['academic_department']) && $book['category'] === $user_info['academic_department']): ?>
    <div class="max-w-4xl mx-auto mt-6">
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-500 rounded-xl flex items-center justify-center">
                    <i class='bx bx-graduation text-white text-xl'></i>
                </div>
                <div>
                    <p class="font-semibold text-purple-900">Perfect for Your Studies!</p>
                    <p class="text-sm text-purple-700">This book matches your <?php echo htmlspecialchars($user_info['academic_department']); ?> department curriculum</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function reserveBook(bookId) {
    window.location.href = `reserve.php?book_id=${bookId}`;
}
</script>

<?php require 'components/footer.php'; ?>
