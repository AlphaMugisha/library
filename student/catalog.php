<?php
session_start();
require '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// --- Check if the student is suspended ---
$stmt = $pdo->prepare("SELECT library_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_status = $stmt->fetchColumn();

// --- Handle Book Request ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_book'])) {
    if ($user_status === 'suspended') {
        $error_msg = "Your library account is currently suspended. Please see the librarian to clear your fines.";
    } else {
        $book_id = (int)$_POST['book_id'];
        
        // 1. Check if the book is actually available
        $check_book = $pdo->prepare("SELECT available_copies FROM books WHERE id = ?");
        $check_book->execute([$book_id]);
        $book = $check_book->fetch();

        // 2. Check if user already requested or currently has this book
        $check_existing = $pdo->prepare("SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status IN ('Pending', 'Issued')");
        $check_existing->execute([$user_id, $book_id]);
        
        if ($check_existing->rowCount() > 0) {
            $error_msg = "You already have a pending request or an active loan for this book.";
        } elseif ($book && $book['available_copies'] > 0) {
            // Create a pending request (Librarian will set the due_date upon approval)
            $stmt = $pdo->prepare("INSERT INTO borrowings (user_id, book_id, issue_date, due_date, status) VALUES (?, ?, CURDATE(), CURDATE(), 'Pending')");
            $stmt->execute([$user_id, $book_id]);
            $success_msg = "Book requested! Please pick it up at the librarian's desk.";
        } else {
            $error_msg = "Sorry, this book just went out of stock.";
        }
    }
}

// Fetch all books for the catalog
$books = [];
try {
    $stmt = $pdo->query("SELECT * FROM books ORDER BY available_copies DESC, title ASC");
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Could not load the catalog.";
}

$page_title = "Browse Catalog | NGA Student";
$header_title = "Library Catalog";
require '../components/header.php'; 
?>

<?php if ($success_msg): ?>
    <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl relative flex items-center gap-2 font-bold shadow-sm mb-6">
        <i class='bx bxs-check-circle text-xl'></i> <span><?php echo $success_msg; ?></span>
    </div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl relative flex items-center gap-2 font-bold shadow-sm mb-6">
        <i class='bx bxs-error-circle text-xl'></i> <span><?php echo $error_msg; ?></span>
    </div>
<?php endif; ?>
<?php if ($user_status === 'suspended'): ?>
    <div class="bg-red-500 text-white px-4 py-3 rounded-xl relative flex items-center justify-center gap-2 font-black shadow-lg mb-6 animate-pulse">
        <i class='bx bxs-lock text-2xl'></i> <span>ACCOUNT SUSPENDED: You have outstanding fines or overdue books.</span>
    </div>
<?php endif; ?>

<div class="glass p-4 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 mb-8 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
    <div class="relative w-full md:max-w-md">
        <i class='bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl'></i>
        <input type="text" id="catalogSearch" onkeyup="filterCatalog()" placeholder="Search by title, author, or category..." class="w-full bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl py-3 pl-12 pr-4 text-sm font-medium focus:outline-none focus:border-[#FF6600] text-slate-800 dark:text-white transition-colors">
    </div>
    
    <div class="flex items-center gap-2 text-sm font-bold text-slate-500 dark:text-slate-400">
        <i class='bx bx-filter-alt'></i>
        <span><?php echo count($books); ?> Books Available</span>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="bookGrid">
    <?php if (count($books) > 0): ?>
        <?php foreach ($books as $book): 
            $is_available = $book['available_copies'] > 0;
        ?>
            <div class="book-card card-hover relative glass p-6 rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 flex flex-col h-full shadow-sm">
                
                <div class="absolute top-4 right-4">
                    <?php if ($is_available): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 dark:bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg text-[10px] font-black uppercase tracking-wider">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div> <?php echo $book['available_copies']; ?> In Stock
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400 rounded-lg text-[10px] font-black uppercase tracking-wider">
                            <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div> Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <div class="w-16 h-20 bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 rounded-xl flex items-center justify-center shadow-inner mb-5 border border-slate-300/50 dark:border-slate-600/50">
                    <i class='bx bxs-book text-slate-400 dark:text-slate-500 text-3xl'></i>
                </div>

                <div class="flex-1">
                    <span class="text-[10px] font-bold text-[#FF6600] uppercase tracking-widest mb-1 block book-category">
                        <?php echo htmlspecialchars($book['category']); ?>
                    </span>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white leading-tight mb-1 book-title">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h3>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 book-author">
                        <?php echo htmlspecialchars($book['author']); ?>
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-700/50 flex flex-col gap-3">
                    <div class="text-xs font-bold text-slate-400 font-mono">
                        <i class='bx bx-barcode'></i> <?php echo htmlspecialchars($book['isbn']); ?>
                    </div>
                    
                    <?php if ($is_available && $user_status !== 'suspended'): ?>
                        <form method="POST">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <button type="submit" name="request_book" class="w-full py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold rounded-xl text-xs hover:-translate-y-0.5 transition-transform shadow-md flex items-center justify-center gap-2">
                                <i class='bx bx-bookmark-plus text-base'></i> Request Reserve
                            </button>
                        </form>
                    <?php elseif ($user_status === 'suspended'): ?>
                        <button disabled class="w-full py-2 bg-slate-300 dark:bg-slate-700 text-slate-500 font-bold rounded-xl text-xs cursor-not-allowed flex items-center justify-center gap-2">
                            <i class='bx bxs-lock text-base'></i> Suspended
                        </button>
                    <?php else: ?>
                        <button disabled class="w-full py-2 bg-slate-300 dark:bg-slate-700 text-slate-500 font-bold rounded-xl text-xs cursor-not-allowed flex items-center justify-center gap-2">
                            <i class='bx bx-x-circle text-base'></i> Unavailable
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function filterCatalog() {
        const searchInput = document.getElementById('catalogSearch').value.toLowerCase();
        const bookCards = document.querySelectorAll('.book-card');

        bookCards.forEach(card => {
            const title = card.querySelector('.book-title').textContent.toLowerCase();
            const author = card.querySelector('.book-author').textContent.toLowerCase();
            const category = card.querySelector('.book-category').textContent.toLowerCase();

            if (title.includes(searchInput) || author.includes(searchInput) || category.includes(searchInput)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>

<?php require '../components/footer.php'; ?>