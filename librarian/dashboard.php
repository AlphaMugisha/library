<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../login.php");
    exit;
}

// Live DB Stats
$stats = ['total_books' => 0, 'available' => 0, 'issued' => 0, 'overdue' => 0];
try {
    $bookStats = $pdo->query("SELECT SUM(total_copies) as total, SUM(available_copies) as available FROM books")->fetch();
    $stats['total_books'] = $bookStats['total'] ?? 0;
    $stats['available'] = $bookStats['available'] ?? 0;
    $stats['issued'] = $pdo->query("SELECT COUNT(*) as issued FROM borrowings WHERE status = 'Issued'")->fetchColumn();
    $stats['overdue'] = $pdo->query("SELECT COUNT(*) as overdue FROM borrowings WHERE status = 'Issued' AND due_date < CURDATE()")->fetchColumn();
} catch (PDOException $e) {}

// Set Headers and Include UI
$page_title = "Dashboard | NGA Library";
$header_title = "Overview";
require '../components/header.php'; 
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                <i class='bx bxs-book-bookmark text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1"><?php echo $stats['total_books']; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Total Books</p>
    </div>
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/25">
                <i class='bx bxs-check-shield text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1"><?php echo $stats['available']; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Available</p>
    </div>
    <a href="transactions.php" class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-[#FF6600] rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/25">
                <i class='bx bxs-hand text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1"><?php echo $stats['issued']; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Currently Issued</p>
    </a>
    <a href="transactions.php" class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg shadow-red-500/25">
                <i class='bx bxs-error-circle text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1"><?php echo $stats['overdue']; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Overdue Returns</p>
    </a>
</div>

<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8 mt-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Recent Transactions</h2>
        <a href="transactions.php" class="px-4 py-2 bg-[#FF6600]/10 text-[#FF6600] rounded-lg font-bold text-sm hover:bg-[#FF6600] hover:text-white transition-colors">
            Manage All
        </a>
    </div>
    <div class="text-center py-10 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
        <i class='bx bx-transfer-alt text-4xl text-slate-400 mb-3'></i>
        <p class="text-slate-500 font-medium">Issue a book to see activity here.</p>
    </div>
</div>

<?php require '../components/footer.php'; ?>