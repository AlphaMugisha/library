<?php
session_start();
require 'config/db.php';
require 'config/academic_integration.php';

// Get user info for personalized recommendations
$user_info = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch();
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';
$availability = $_GET['availability'] ?? 'all';
$sort = $_GET['sort'] ?? 'title';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $where_conditions[] = "b.category = ?";
    $params[] = $category;
}

if (!empty($author)) {
    $where_conditions[] = "b.author LIKE ?";
    $params[] = "%$author%";
}

if ($availability === 'available') {
    $where_conditions[] = "b.available_copies > 0";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Sort options
$sort_options = [
    'title' => 'b.title ASC',
    'author' => 'b.author ASC', 
    'newest' => 'b.created_at DESC',
    'popular' => '(SELECT COUNT(*) FROM borrowings WHERE book_id = b.id) DESC',
    'rating' => '(SELECT AVG(rating) FROM book_reviews WHERE book_id = b.id) DESC'
];
$order_by = $sort_options[$sort] ?? 'b.title ASC';

// Get books
$books = [];
$total_books = 0;
try {
    $query = "
        SELECT b.*, 
               (SELECT COUNT(*) FROM borrowings WHERE book_id = b.id) as popularity,
               (SELECT AVG(rating) FROM book_reviews WHERE book_id = b.id) as avg_rating,
               (SELECT COUNT(*) FROM book_reviews WHERE book_id = b.id) as review_count
        FROM books b 
        $where_clause
        ORDER BY $order_by
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $books = $stmt->fetchAll();
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM books b $where_clause";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_books = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = $e->getMessage();
}

// Get categories for filter dropdown
$categories = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

$page_title = "Book Catalog | NGA Library";
$header_title = "Browse Our Collection";
require 'components/header.php'; 
?>

<!-- Search and Filter Section -->
<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6 mb-6">
    <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Search Books</label>
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by title, author, ISBN..." 
                           class="w-full px-4 py-3 pl-12 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    <i class='bx bx-search absolute left-4 top-4 text-slate-400 text-xl'></i>
                </div>
            </div>
            
            <!-- Category Filter -->
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Category</label>
                <select name="category" class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Author Filter -->
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Author</label>
                <input type="text" name="author" value="<?php echo htmlspecialchars($author); ?>" 
                       placeholder="Filter by author..." 
                       class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
            </div>
            
            <!-- Availability Filter -->
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Availability</label>
                <select name="availability" class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    <option value="all" <?php echo ($availability === 'all') ? 'selected' : ''; ?>>All Books</option>
                    <option value="available" <?php echo ($availability === 'available') ? 'selected' : ''; ?>>Available Only</option>
                </select>
            </div>
        </div>
        
        <!-- Sort and Search Row -->
        <div class="flex flex-col md:flex-row gap-4 items-end">
            <!-- Sort -->
            <div class="flex-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Sort By</label>
                <select name="sort" class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    <option value="title" <?php echo ($sort === 'title') ? 'selected' : ''; ?>>Title (A-Z)</option>
                    <option value="author" <?php echo ($sort === 'author') ? 'selected' : ''; ?>>Author (A-Z)</option>
                    <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="popular" <?php echo ($sort === 'popular') ? 'selected' : ''; ?>>Most Popular</option>
                    <option value="rating" <?php echo ($sort === 'rating') ? 'selected' : ''; ?>>Highest Rated</option>
                </select>
            </div>
            
            <!-- Search Button -->
            <div class="flex gap-2">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors flex items-center gap-2">
                    <i class='bx bx-search'></i>
                    Search
                </button>
                <a href="books.php" class="px-6 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                    Clear
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
            <?php if (!empty($search) || !empty($category) || !empty($author)): ?>
                Search Results
            <?php else: ?>
                All Books
            <?php endif; ?>
        </h2>
        <p class="text-slate-600 dark:text-slate-400">
            <?php echo $total_books; ?> books found
            <?php if (!empty($search)): ?>
                for "<?php echo htmlspecialchars($search); ?>"
            <?php endif; ?>
        </p>
    </div>
    
    <?php if (!empty($user_info['academic_department'])): ?>
        <a href="student/recommendations.php" class="px-4 py-2 bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 rounded-xl font-semibold hover:bg-purple-200 dark:hover:bg-purple-500/20 transition-colors flex items-center gap-2">
            <i class='bx bx-bookmark-star'></i>
            View Recommendations
        </a>
    <?php endif; ?>
</div>

<!-- Books Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
    <?php if (count($books) > 0): ?>
        <?php foreach ($books as $book): ?>
            <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-4 hover:shadow-lg transition-all duration-300 group">
                <div class="relative mb-4">
                    <div class="w-full h-48 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center overflow-hidden">
                        <?php if ($book['cover_image']): ?>
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                 class="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-300">
                        <?php else: ?>
                            <i class='bx bx-book text-white text-4xl'></i>
                        <?php endif; ?>
                        
                        <!-- Availability Badge -->
                        <?php if ($book['available_copies'] > 0): ?>
                            <div class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded-lg text-xs font-bold">
                                Available
                            </div>
                        <?php else: ?>
                            <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-lg text-xs font-bold">
                                Out of Stock
                            </div>
                        <?php endif; ?>
                        
                        <!-- Rating Badge -->
                        <?php if ($book['avg_rating']): ?>
                            <div class="absolute top-2 left-2 bg-white/90 dark:bg-slate-900/90 px-2 py-1 rounded-lg flex items-center gap-1">
                                <i class='bx bx-star text-yellow-500 text-sm'></i>
                                <span class="text-xs font-bold"><?php echo number_format($book['avg_rating'], 1); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h3 class="font-bold text-slate-900 dark:text-white mb-1 group-hover:text-blue-600 transition-colors line-clamp-2">
                    <?php echo htmlspecialchars($book['title']); ?>
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                    by <?php echo htmlspecialchars($book['author']); ?>
                </p>
                
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-slate-500">
                        <?php echo $book['available_copies']; ?> of <?php echo $book['total_copies']; ?> available
                    </span>
                    <div class="flex items-center gap-2">
                        <?php if ($book['popularity'] > 0): ?>
                            <div class="flex items-center gap-1">
                                <i class='bx bx-book-reader text-blue-500 text-sm'></i>
                                <span class="text-xs text-slate-500"><?php echo $book['popularity']; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($book['review_count'] > 0): ?>
                            <div class="flex items-center gap-1">
                                <i class='bx bx-star text-yellow-500 text-sm'></i>
                                <span class="text-xs text-slate-500"><?php echo $book['review_count']; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button onclick="viewBookDetails(<?php echo $book['id']; ?>)" 
                            class="flex-1 px-3 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        <i class='bx bx-eye'></i>
                        Details
                    </button>
                    <?php if ($book['available_copies'] > 0): ?>
                        <button onclick="borrowBook(<?php echo $book['id']; ?>)" 
                                class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">
                            <i class='bx bx-book-plus'></i>
                            Borrow
                        </button>
                    <?php else: ?>
                        <button onclick="reserveBook(<?php echo $book['id']; ?>)" 
                                class="flex-1 px-3 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition-colors">
                            <i class='bx bx-bookmark'></i>
                            Reserve
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full text-center py-12">
            <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class='bx bx-search text-4xl text-slate-400'></i>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No books found</h3>
            <p class="text-slate-500 mb-4">
                <?php if (!empty($search)): ?>
                    Try adjusting your search terms or filters
                <?php else: ?>
                    No books match your current filters
                <?php endif; ?>
            </p>
            <a href="books.php" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                <i class='bx bx-refresh'></i>
                Clear Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_books > $limit): ?>
    <div class="flex justify-center items-center gap-2">
        <?php 
        $total_pages = ceil($total_books / $limit);
        $current_page = $page;
        
        // Previous button
        if ($current_page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
               class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i class='bx bx-chevron-left'></i>
                Previous
            </a>
        <?php endif; ?>
        
        // Page numbers
        <?php 
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $current_page): ?>
                <span class="px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold">
                    <?php echo $i; ?>
                </span>
            <?php else: ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <?php echo $i; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
        
        // Next button
        <?php if ($current_page < $total_pages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
               class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                Next
                <i class='bx bx-chevron-right'></i>
            </a>
        <?php endif; ?>
    </div>
    
    <div class="text-center mt-4 text-sm text-slate-500">
        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_books); ?> of <?php echo $total_books; ?> books
    </div>
<?php endif; ?>

<script>
function viewBookDetails(bookId) {
    // This would open a modal or navigate to book details page
    window.location.href = `book_details.php?id=${bookId}`;
}

function borrowBook(bookId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        window.location.href = `borrow.php?book_id=${bookId}`;
    <?php else: ?>
        if (confirm('You need to login to borrow books. Go to login page?')) {
            window.location.href = 'login.php';
        }
    <?php endif; ?>
}

function reserveBook(bookId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        window.location.href = `reserve.php?book_id=${bookId}`;
    <?php else: ?>
        if (confirm('You need to login to reserve books. Go to login page?')) {
            window.location.href = 'login.php';
        }
    <?php endif; ?>
}
</script>

<?php require 'components/footer.php'; ?>
