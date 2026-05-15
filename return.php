<?php
session_start();
require 'config/db.php';
require 'config/academic_integration.php';

// Security Check: Only logged-in users can return books
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's current borrowings
$active_borrowings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.*, br.issue_date, br.due_date, br.id as borrowing_id
        FROM borrowings br 
        JOIN books b ON br.book_id = b.id 
        WHERE br.user_id = ? AND br.status = 'Issued'
        ORDER BY br.due_date ASC
    ");
    $stmt->execute([$user_id]);
    $active_borrowings = $stmt->fetchAll();
} catch (PDOException $e) {}

// Handle return requests
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'return_book':
                $borrowing_id = $_POST['borrowing_id'] ?? 0;
                
                // Validate borrowing belongs to user
                $stmt = $pdo->prepare("
                    SELECT * FROM borrowings 
                    WHERE id = ? AND user_id = ? AND status = 'Issued'
                ");
                $stmt->execute([$borrowing_id, $user_id]);
                $borrowing = $stmt->fetch();
                
                if (!$borrowing) {
                    $message = 'Invalid borrowing record';
                    $message_type = 'error';
                    break;
                }
                
                // Calculate fine if overdue
                $fine = 0;
                $today = date('Y-m-d');
                if ($borrowing['due_date'] < $today) {
                    $days_overdue = (strtotime($today) - strtotime($borrowing['due_date'])) / (60 * 60 * 24);
                    $fine = $days_overdue * 100; // 100 RWF per day
                }
                
                // Update borrowing record
                $stmt = $pdo->prepare("
                    UPDATE borrowings 
                    SET return_date = ?, status = 'Returned', fine_amount = ?
                    WHERE id = ?
                ");
                $stmt->execute([$today, $fine, $borrowing_id]);
                
                // Update available copies
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_copies = available_copies + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$borrowing['book_id']]);
                
                // Log the activity
                $integration = getAcademicIntegration($pdo);
                if (method_exists($integration, 'logIntegration')) {
                    $integration->logIntegration('update', 'book_return', $_SESSION['academic_id'] ?? null, 'Book returned successfully', [
                        'book_id' => $borrowing['book_id'],
                        'fine_amount' => $fine,
                        'days_overdue' => $days_overdue ?? 0
                    ]);
                }
                
                if ($fine > 0) {
                    $message = "Book returned successfully! Fine: {$fine} RWF ({$days_overdue} days overdue)";
                    $message_type = 'warning';
                } else {
                    $message = 'Book returned successfully! No fine applied.';
                    $message_type = 'success';
                }
                
                // Refresh the borrowings list
                header("refresh:2");
                break;
                
            case 'return_all':
                // Return all books at once
                $return_count = 0;
                $total_fine = 0;
                
                foreach ($active_borrowings as $borrowing) {
                    $fine = 0;
                    $today = date('Y-m-d');
                    if ($borrowing['due_date'] < $today) {
                        $days_overdue = (strtotime($today) - strtotime($borrowing['due_date'])) / (60 * 60 * 24);
                        $fine = $days_overdue * 100;
                        $total_fine += $fine;
                    }
                    
                    // Update borrowing record
                    $stmt = $pdo->prepare("
                        UPDATE borrowings 
                        SET return_date = ?, status = 'Returned', fine_amount = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$today, $fine, $borrowing['borrowing_id']]);
                    
                    // Update available copies
                    $stmt = $pdo->prepare("
                        UPDATE books 
                        SET available_copies = available_copies + 1 
                        WHERE id = ?
                    ");
                    $stmt->execute([$borrowing['book_id']]);
                    
                    $return_count++;
                }
                
                if ($total_fine > 0) {
                    $message = "All {$return_count} books returned! Total fine: {$total_fine} RWF";
                    $message_type = 'warning';
                } else {
                    $message = "All {$return_count} books returned successfully!";
                    $message_type = 'success';
                }
                
                // Refresh the borrowings list
                header("refresh:2");
                break;
        }
    } catch (Exception $e) {
        $message = 'Error processing return: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = "Return Books | NGA Library";
$header_title = "Return Books";
require 'components/header.php'; 
?>

<!-- Message Display -->
<?php if ($message): ?>
<div class="mb-6 p-4 rounded-xl border <?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : ($message_type === 'warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 'bg-red-50 border-red-200 text-red-800'); ?>">
    <div class="flex items-center gap-2">
        <i class='bx <?php echo $message_type === 'success' ? 'bx-check-circle' : ($message_type === 'warning' ? 'bx-warning' : 'bx-error-circle'); ?> text-xl'></i>
        <span class="font-semibold"><?php echo $message; ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Return Books Section -->
<div class="max-w-6xl mx-auto">
    <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <i class='bx bx-undo text-blue-500'></i>
                    Return Books
                </h2>
                <p class="text-slate-600 dark:text-slate-400">
                    You have <?php echo count($active_borrowings); ?> book(s) to return
                </p>
            </div>
            
            <?php if (count($active_borrowings) > 0): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="return_all">
                    <button type="submit" onclick="return confirm('Are you sure you want to return all books?')" class="px-6 py-3 bg-orange-600 text-white rounded-xl font-semibold hover:bg-orange-700 transition-colors flex items-center gap-2">
                        <i class='bx bx-undo'></i>
                        Return All Books
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if (count($active_borrowings) > 0): ?>
            <!-- Books Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($active_borrowings as $borrowing): ?>
                    <?php 
                    $is_overdue = $borrowing['due_date'] < date('Y-m-d');
                    $days_overdue = $is_overdue ? (strtotime(date('Y-m-d')) - strtotime($borrowing['due_date'])) / (60 * 60 * 24) : 0;
                    $fine = $days_overdue * 100;
                    ?>
                    
                    <div class="border rounded-2xl p-6 <?php echo $is_overdue ? 'border-red-200 bg-red-50 dark:border-red-500/20 dark:bg-red-500/10' : 'border-slate-200 bg-slate-50 dark:border-slate-700/50 dark:bg-slate-800/50'; ?>">
                        <!-- Book Header -->
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-16 h-20 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <?php if ($borrowing['cover_image']): ?>
                                        <img src="<?php echo htmlspecialchars($borrowing['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($borrowing['title']); ?>" 
                                             class="w-full h-full object-cover rounded-xl">
                                    <?php else: ?>
                                        <i class='bx bx-book text-white text-2xl'></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-white mb-1">
                                        <?php echo htmlspecialchars($borrowing['title']); ?>
                                    </h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        by <?php echo htmlspecialchars($borrowing['author']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="px-3 py-1 rounded-full text-xs font-bold <?php echo $is_overdue ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                                <?php echo $is_overdue ? 'OVERDUE' : 'ON TIME'; ?>
                            </div>
                        </div>
                        
                        <!-- Borrowing Details -->
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Borrowed:</span>
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    <?php echo date('M j, Y', strtotime($borrowing['issue_date'])); ?>
                                </span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Due Date:</span>
                                <span class="font-semibold <?php echo $is_overdue ? 'text-red-600' : 'text-slate-900 dark:text-white'; ?>">
                                    <?php echo date('M j, Y', strtotime($borrowing['due_date'])); ?>
                                </span>
                            </div>
                            
                            <?php if ($is_overdue): ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Days Overdue:</span>
                                    <span class="font-bold text-red-600">
                                        <?php echo $days_overdue; ?> days
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Fine:</span>
                                    <span class="font-bold text-red-600">
                                        <?php echo $fine; ?> RWF
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Return Action -->
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="action" value="return_book">
                            <input type="hidden" name="borrowing_id" value="<?php echo $borrowing['borrowing_id']; ?>">
                            <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                                <i class='bx bx-undo'></i>
                                Return This Book
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Books to Return -->
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class='bx bx-check-circle text-4xl text-green-500'></i>
                </div>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">All Caught Up!</h3>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    You don't have any books to return right now.
                </p>
                <a href="student/dashboard_enhanced.php" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                    <i class='bx bx-home'></i>
                    Go to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Fine Information -->
    <?php if (count($active_borrowings) > 0): ?>
        <div class="mt-8 glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <i class='bx bx-error text-orange-500'></i>
                Fine Information
            </h3>
            
            <div class="bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-xl p-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <i class='bx bx-info-circle text-orange-600 text-sm'></i>
                        <span class="text-sm text-orange-700 dark:text-orange-300 font-semibold">Fine Rate:</span>
                        <span class="text-orange-900 dark:text-orange-100 font-bold">100 RWF per day</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <i class='bx bx-time text-orange-600 text-sm'></i>
                        <span class="text-sm text-orange-700 dark:text-orange-300 font-semibold">Grace Period:</span>
                        <span class="text-orange-900 dark:text-orange-100 font-bold">None (fines start immediately after due date)</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <i class='bx bx-money text-orange-600 text-sm'></i>
                        <span class="text-sm text-orange-700 dark:text-orange-300 font-semibold">Payment:</span>
                        <span class="text-orange-900 dark:text-orange-100 font-bold">Pay fines at library circulation desk</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require 'components/footer.php'; ?>
