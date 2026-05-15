<?php
session_start();
require '../config/db.php';

// Security: Only Librarians
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// --- 1. HANDLE ADD BOOK ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_book'])) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $category = $_POST['category'];
    $copies = (int)$_POST['total_copies'];

    try {
        $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, category, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $author, $isbn, $category, $copies, $copies]);
        $success_msg = "Book added to catalog successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error adding book: " . $e->getMessage();
    }
}

// --- 2. HANDLE DELETE BOOK ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM books WHERE id = ?")->execute([$id]);
    header("Location: books.php?success=Book deleted");
    exit;
}

// Fetch all books
$books = $pdo->query("SELECT * FROM books ORDER BY id DESC")->fetchAll();

// --- UI SETUP ---
$page_title = "Inventory Management | NGA Library";
$header_title = "Book Catalog";
require '../components/header.php'; // THIS BRINGS IN THE FRESH SIDEBAR
?>

<div class="flex flex-col sm:flex-row justify-between items-center gap-4 glass p-4 rounded-2xl border border-slate-200 dark:border-slate-800 mb-8 shadow-sm">
    <div class="relative w-full sm:w-96">
        <i class='bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl'></i>
        <input type="text" id="bookSearch" onkeyup="searchBooks()" placeholder="Search by title, author, or ISBN..." class="w-full bg-slate-100 dark:bg-slate-800/50 border-none rounded-xl py-2.5 pl-10 pr-4 text-sm font-medium focus:ring-2 focus:ring-[#FF6600] text-slate-900 dark:text-white transition-all">
    </div>
    <button onclick="toggleModal('addBookModal')" class="w-full sm:w-auto px-6 py-2.5 bg-[#FF6600] text-white font-bold rounded-xl shadow-lg shadow-orange-500/20 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
        <i class='bx bx-plus-circle text-lg'></i> Add New Book
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="bookContainer">
    <?php if($books): foreach($books as $book): ?>
        <div class="book-card card-hover relative glass p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 flex flex-col h-full shadow-sm">
            <div class="absolute top-4 right-4">
                <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider <?php echo ($book['available_copies'] > 0) ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                    <?php echo $book['available_copies']; ?> / <?php echo $book['total_copies']; ?> Available
                </span>
            </div>
            
            <div class="w-12 h-16 bg-slate-100 dark:bg-slate-800 rounded-lg flex items-center justify-center mb-4">
                <i class='bx bxs-book text-slate-400 text-2xl'></i>
            </div>

            <div class="flex-1">
                <p class="text-[10px] font-bold text-[#FF6600] uppercase tracking-widest mb-1"><?php echo htmlspecialchars($book['category']); ?></p>
                <h3 class="text-lg font-black text-slate-900 dark:text-white leading-tight mb-1 book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($book['author']); ?></p>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <span class="text-[10px] font-mono text-slate-400">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></span>
                <div class="flex gap-2">
                    <a href="?delete=<?php echo $book['id']; ?>" onclick="return confirm('Are you sure?')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                        <i class='bx bx-trash text-lg'></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; else: ?>
        <div class="col-span-full py-20 text-center">
            <i class='bx bx-book-open text-5xl text-slate-300 mb-4'></i>
            <p class="text-slate-500 font-bold">Your library is currently empty.</p>
        </div>
    <?php endif; ?>
</div>

<div id="addBookModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay">
    <div class="modal-content w-full max-w-md bg-white dark:bg-[#0f172a] rounded-[24px] shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-[#020617]/50">
            <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2"><i class='bx bx-book-add text-[#FF6600]'></i> Add to Catalog</h3>
            <button onclick="toggleModal('addBookModal')" class="text-slate-500 hover:text-red-500 transition-colors"><i class='bx bx-x text-xl'></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="add_book" value="1">
            <div class="grid grid-cols-1 gap-4">
                <input type="text" name="title" placeholder="Book Title" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold dark:text-white focus:ring-2 focus:ring-[#FF6600]">
                <input type="text" name="author" placeholder="Author Name" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold dark:text-white focus:ring-2 focus:ring-[#FF6600]">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="isbn" placeholder="ISBN Number" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold dark:text-white focus:ring-2 focus:ring-[#FF6600]">
                    <select name="category" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold dark:text-white focus:ring-2 focus:ring-[#FF6600]">
                        <option value="Computer Science">Computer Science</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="Physics">Physics</option>
                        <option value="Fiction">Fiction</option>
                    </select>
                </div>
                <input type="number" name="total_copies" placeholder="Number of Copies" min="1" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold dark:text-white focus:ring-2 focus:ring-[#FF6600]">
            </div>
            
            <div class="pt-4 flex justify-end gap-3 mt-6 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="toggleModal('addBookModal')" class="px-5 py-2.5 rounded-xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-[#FF6600] text-white font-bold rounded-xl shadow-lg shadow-orange-500/30">Add Book</button>
            </div>
        </form>
    </div>
</div>

<script>
    function searchBooks() {
        let input = document.getElementById('bookSearch').value.toLowerCase();
        let cards = document.getElementsByClassName('book-card');
        for (let card of cards) {
            let title = card.querySelector('.book-title').innerText.toLowerCase();
            card.style.display = title.includes(input) ? "flex" : "none";
        }
    }
</script>

<?php require '../components/footer.php'; ?>