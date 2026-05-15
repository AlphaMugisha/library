<?php
session_start();
require 'config/db.php';
require 'config/academic_integration.php';

// Get book ID
$book_id = $_GET['id'] ?? 0;
if (!is_numeric($book_id) || $book_id <= 0) {
    header("Location: books.php");
    exit;
}

// Get book details
$book = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               (SELECT COUNT(*) FROM borrowings WHERE book_id = b.id) as popularity,
               (SELECT AVG(rating) FROM book_reviews WHERE book_id = b.id) as avg_rating,
               (SELECT COUNT(*) FROM book_reviews WHERE book_id = b.id) as review_count
        FROM books b 
        WHERE b.id = ?
    ");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
} catch (PDOException $e) {}

if (!$book) {
    header("Location: books.php");
    exit;
}

// Get user info for personalized actions
$user_info = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch();
}

// Get related books
$related_books = [];
try {
    $relatedQuery = $pdo->prepare("
        SELECT b.*, 
               (SELECT COUNT(*) FROM borrowings WHERE book_id = b.id) as popularity,
               (SELECT AVG(rating) FROM book_reviews WHERE book_id = b.id) as avg_rating
        FROM books b 
        WHERE (b.category = ? OR b.author = ?) 
        AND b.id != ?
        ORDER BY popularity DESC, avg_rating DESC
        LIMIT 6
    ");
    $relatedQuery->execute([$book['category'], $book['author'], $book_id]);
    $related_books = $relatedQuery->fetchAll();
} catch (PDOException $e) {}

// Get book reviews
$reviews = [];
try {
    $reviewQuery = $pdo->prepare("
        SELECT br.*, u.full_name 
        FROM book_reviews br 
        JOIN users u ON br.user_id = u.id 
        WHERE br.book_id = ? 
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $reviewQuery->execute([$book_id]);
    $reviews = $reviewQuery->fetchAll();
} catch (PDOException $e) {}

// Check if user has borrowed this book
$user_borrowed = false;
if (isset($_SESSION['user_id'])) {
    try {
        $borrowQuery = $pdo->prepare("
            SELECT * FROM borrowings 
            WHERE user_id = ? AND book_id = ? AND status = 'Issued'
        ");
        $borrowQuery->execute([$_SESSION['user_id'], $book_id]);
        $user_borrowed = $borrowQuery->fetch() !== false;
    } catch (PDOException $e) {}
}

$page_title = $book['title'] . " | NGA Library";
$header_title = "Book Details";
require 'components/header.php'; 
?>

<!-- Book Details Container -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Book Info -->
    <div class="lg:col-span-2">
        <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Book Cover -->
                <div>
                    <div class="relative mb-6">
                        <div class="w-full h-80 bg-gradient-to-br from-blue-400 to-purple-500 rounded-2xl flex items-center justify-center overflow-hidden shadow-lg">
                            <?php if ($book['cover_image']): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                     class="w-full h-full object-cover rounded-2xl">
                            <?php else: ?>
                                <i class='bx bx-book text-white text-6xl'></i>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Availability Badge -->
                        <?php if ($book['available_copies'] > 0): ?>
                            <div class="absolute top-4 right-4 bg-green-500 text-white px-3 py-2 rounded-xl font-bold">
                                <i class='bx bx-check-circle'></i> Available
                            </div>
                        <?php else: ?>
                            <div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-2 rounded-xl font-bold">
                                <i class='bx bx-x-circle'></i> Unavailable
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Book Information -->
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-4">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h1>
                    
                    <div class="space-y-4 mb-6">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Author:</span>
                            <span class="text-slate-900 dark:text-white font-semibold"><?php echo htmlspecialchars($book['author']); ?></span>
                        </div>
                        
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
                        
                        <?php if ($book['published_year']): ?>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Published:</span>
                            <span class="text-slate-900 dark:text-white font-semibold"><?php echo htmlspecialchars($book['published_year']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Pages:</span>
                            <span class="text-slate-900 dark:text-white font-semibold"><?php echo htmlspecialchars($book['pages']); ?></span>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Language:</span>
                            <span class="text-slate-900 dark:text-white font-semibold"><?php echo htmlspecialchars($book['language']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Description</h3>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description available.')); ?>
                        </p>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <i class='bx bx-book-reader text-blue-500'></i>
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Borrowed</span>
                            </div>
                            <p class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $book['popularity']; ?></p>
                            <p class="text-xs text-slate-500">times</p>
                        </div>
                        
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <i class='bx bx-star text-yellow-500'></i>
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Rating</span>
                            </div>
                            <p class="text-2xl font-black text-slate-900 dark:text-white">
                                <?php echo $book['avg_rating'] ? number_format($book['avg_rating'], 1) : 'N/A'; ?>
                            </p>
                            <p class="text-xs text-slate-500">
                                <?php echo $book['review_count']; ?> reviews
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($book['available_copies'] > 0): ?>
                                <?php if ($user_borrowed): ?>
                                    <button disabled class="flex-1 px-6 py-3 bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-xl font-semibold cursor-not-allowed">
                                        <i class='bx bx-book-reader'></i>
                                        Already Borrowed
                                    </button>
                                <?php else: ?>
                                    <button onclick="borrowBook(<?php echo $book_id; ?>)" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors">
                                        <i class='bx bx-book-plus'></i>
                                        Borrow Book
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button onclick="reserveBook(<?php echo $book_id; ?>)" class="flex-1 px-6 py-3 bg-orange-600 text-white rounded-xl font-semibold hover:bg-orange-700 transition-colors">
                                    <i class='bx bx-bookmark'></i>
                                    Reserve Book
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button onclick="location.href='login.php'" class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors">
                                <i class='bx bx-log-in'></i>
                                Login to Borrow
                            </button>
                        <?php endif; ?>
                        
                        <button onclick="window.history.back()" class="px-6 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                            <i class='bx bx-arrow-back'></i>
                            Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Availability Info -->
        <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <i class='bx bx-info-circle text-blue-500'></i>
                Availability
            </h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Total Copies</span>
                    <span class="font-bold text-slate-900 dark:text-white"><?php echo $book['total_copies']; ?></span>
                </div>
                
                <div class="flex justify-between items-center p-3 <?php echo ($book['available_copies'] > 0) ? 'bg-green-50 dark:bg-green-500/10' : 'bg-red-50 dark:bg-red-500/10'; ?> rounded-xl">
                    <span class="text-sm <?php echo ($book['available_copies'] > 0) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">Available</span>
                    <span class="font-bold <?php echo ($book['available_copies'] > 0) ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'; ?>">
                        <?php echo $book['available_copies']; ?>
                    </span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Location</span>
                    <span class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($book['location'] ?? 'Main Library'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Related Books -->
        <?php if (count($related_books) > 0): ?>
            <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class='bx bx-book-open text-purple-500'></i>
                    Related Books
                </h3>
                
                <div class="space-y-3">
                    <?php foreach (array_slice($related_books, 0, 4) as $related): ?>
                        <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors cursor-pointer" onclick="viewBook(<?php echo $related['id']; ?>)">
                            <div class="w-12 h-16 bg-gradient-to-br from-purple-400 to-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <?php if ($related['cover_image']): ?>
                                    <img src="<?php echo htmlspecialchars($related['cover_image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="w-full h-full object-cover rounded-lg">
                                <?php else: ?>
                                    <i class='bx bx-book text-white text-sm'></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white text-sm truncate"><?php echo htmlspecialchars($related['title']); ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?php echo htmlspecialchars($related['author']); ?></p>
                                <?php if ($related['available_copies'] > 0): ?>
                                    <p class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1">Available</p>
                                <?php else: ?>
                                    <p class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">Unavailable</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Academic Recommendation -->
        <?php if (!empty($user_info['academic_department']) && $book['category'] === $user_info['academic_department']): ?>
            <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class='bx bx-graduation text-orange-500'></i>
                    Perfect for You
                </h3>
                
                <div class="p-4 bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-xl">
                    <p class="text-sm text-orange-700 dark:text-orange-400 font-semibold mb-2">
                        This book matches your department:
                    </p>
                    <p class="text-orange-900 dark:text-orange-100 font-bold">
                        <?php echo htmlspecialchars($user_info['academic_department']); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reviews Section -->
<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8 mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class='bx bx-star text-yellow-500'></i>
            Reviews & Ratings
        </h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <button onclick="addReview(<?php echo $book_id; ?>)" class="px-4 py-2 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors flex items-center gap-2">
                <i class='bx bx-edit'></i>
                Add Review
            </button>
        <?php endif; ?>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold"><?php echo strtoupper(substr($review['full_name'], 0, 1)); ?></span>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($review['full_name']); ?></p>
                                <p class="text-xs text-slate-500"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class='bx bx-star text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-500' : 'text-slate-300'; ?>'></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-8">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class='bx bx-star text-3xl text-slate-400'></i>
                </div>
                <p class="font-bold text-slate-900 dark:text-white">No reviews yet</p>
                <p class="text-sm text-slate-500 mt-1">Be the first to review this book!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function viewBook(bookId) {
    window.location.href = `book_details.php?id=${bookId}`;
}

function borrowBook(bookId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        window.location.href = `borrow.php?book_id=${bookId}`;
    <?php else: ?>
        if (confirm('You need to login to borrow this book. Go to login page?')) {
            window.location.href = 'login.php';
        }
    <?php endif; ?>
}

function reserveBook(bookId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        window.location.href = `reserve.php?book_id=${bookId}`;
    <?php else: ?>
        if (confirm('You need to login to reserve this book. Go to login page?')) {
            window.location.href = 'login.php';
        }
    <?php endif; ?>
}

function addReview(bookId) {
    window.location.href = `add_review.php?book_id=${bookId}`;
}
</script>

<?php require 'components/footer.php'; ?>
