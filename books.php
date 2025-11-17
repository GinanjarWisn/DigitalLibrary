<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

include 'inc/db.php';

// Handle book operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    if (isset($_POST['add_book'])) {
        $title = $_POST['title'];
        $author = $_POST['author'];
        $year = $_POST['year'];
        $stock = $_POST['stock'];
        
        $stmt = $conn->prepare("INSERT INTO books (title, author, year, stock) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $title, $author, $year, $stock);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Buku berhasil ditambahkan!";
        header("Location: books.php");
        exit;
    }
    
    if (isset($_POST['delete_book'])) {
        $book_id = $_POST['book_id'];
        
        // Check if book is currently borrowed
        $check_borrow = $conn->query("SELECT * FROM transactions WHERE book_id = $book_id AND return_date IS NULL");
        if ($check_borrow->num_rows > 0) {
            $_SESSION['error_message'] = "Buku sedang dipinjam dan tidak dapat dihapus!";
        } else {
            $conn->query("DELETE FROM books WHERE id = $book_id");
            $_SESSION['success_message'] = "Buku berhasil dihapus!";
        }
        
        header("Location: books.php");
        exit;
    }
}

// Get all books with search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = $search ? "WHERE title LIKE '%$search%' OR author LIKE '%$search%'" : '';
$books = $conn->query("SELECT * FROM books $where_clause ORDER BY title ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Buku - Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/modern-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .books-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            color: #666;
            font-weight: 300;
        }
        
        .controls-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }
        
        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 233, 123, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(250, 112, 154, 0.3);
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9rem;
            border-radius: 8px;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .modern-table th {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .modern-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        .modern-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .modern-table tbody tr:hover {
            background: rgba(79, 172, 254, 0.05);
            transform: scale(1.01);
        }
        
        .book-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .book-author {
            color: #666;
            font-size: 0.9rem;
        }
        
        .book-year {
            color: #888;
            font-size: 0.85rem;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-available {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
        }
        
        .status-borrowed {
            background: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }
        
        .close {
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <?php include 'inc/modern_header.php'; ?>
    
    <div class="books-container">
        <div class="page-header fade-in-up">
            <h1 class="page-title"><i class="fas fa-book"></i> Data Buku</h1>
            <p class="page-subtitle">Kelola koleksi buku perpustakaan dengan mudah</p>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success fade-in-up"><i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger fade-in-up"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="controls-section fade-in-up">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Cari buku berdasarkan judul atau penulis..." value="<?= htmlspecialchars($search) ?>" autocomplete="off" />
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                <?php if ($search): ?>
                    <a href="books.php" class="btn btn-danger"><i class="fas fa-times"></i> Hapus Filter</a>
                <?php endif; ?>
            </form>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <button class="btn btn-success" onclick="openAddModal()"><i class="fas fa-plus"></i> Tambah Buku Baru</button>
            <?php endif; ?>
        </div>
        
        <?php if ($books->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="modern-table fade-in-up" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Judul Buku</th>
                            <th>Penulis</th>
                            <th>Tahun</th>
                            <th>Status</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $books->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                                    <div class="book-year">ID: #<?= $book['id'] ?></div>
                                </td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= $book['year'] ?></td>
                                <td>
                                    <span class="status-badge <?= $book['stock'] > 0 ? 'status-available' : 'status-borrowed' ?>">
                                        <?= $book['stock'] > 0 ? 'Tersedia' : 'Dipinjam' ?>
                                    </span>
                                </td>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="editBook(<?= $book['id'] ?>, '<?= htmlspecialchars(addslashes($book['title'])) ?>', '<?= htmlspecialchars(addslashes($book['author'])) ?>', '<?= $book['year'] ?>', <?= $book['stock'] ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline-block" onsubmit="return confirm('Yakin ingin menghapus buku ini?');">
                                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>" />
                                        <button type="submit" name="delete_book" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-results fade-in-up" style="text-align:center; padding: 60px 20px; color:#999;">
                <i class="fas fa-book-open" style="font-size:48px; margin-bottom: 20px;"></i>
                <h3>Belum ada data buku</h3>
                <p>Silakan tambahkan buku ke dalam sistem</p>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button class="btn btn-success" onclick="openAddModal()"><i class="fas fa-plus"></i> Tambah Buku Pertama</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Add/Edit Book -->
    <div id="bookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" class="modal-title">Tambah Buku Baru</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="bookForm">
                <input type="hidden" id="book_id" name="book_id" value="">
                <div class="form-group">
                    <label for="title" class="form-label">Judul Buku</label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="200" />
                </div>
                <div class="form-group">
                    <label for="author" class="form-label">Penulis</label>
                    <input type="text" id="author" name="author" class="form-control" required maxlength="100" />
                </div>
                <div class="form-group">
                    <label for="year" class="form-label">Tahun Terbit</label>
                    <input type="number" id="year" name="year" class="form-control" min="1900" max="<?= date('Y') ?>" required />
                </div>
                <div class="form-group">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" id="stock" name="stock" class="form-control" min="0" required />
                </div>
                <div class="form-actions" style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Batal</button>
                    <button type="submit" name="add_book" id="submitBtn" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Buku Baru';
            document.getElementById('bookForm').reset();
            document.getElementById('submitBtn').name = 'add_book';
            document.getElementById('submitBtn').textContent = 'Simpan';
            document.getElementById('book_id').value = '';
            document.getElementById('bookModal').style.display = 'block';
        }
        function editBook(id, title, author, year, stock) {
            document.getElementById('modalTitle').textContent = 'Edit Buku';
            document.getElementById('title').value = title;
            document.getElementById('author').value = author;
            document.getElementById('year').value = year;
            document.getElementById('stock').value = stock;
            document.getElementById('submitBtn').name = 'edit_book';
            document.getElementById('submitBtn').textContent = 'Update';
            document.getElementById('book_id').value = id;
            document.getElementById('bookModal').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('bookModal').style.display = 'none';
        }
        window.onclick = function(event) {
            const modal = document.getElementById('bookModal');
            if(event.target == modal) {
                closeModal();
            }
        }

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    </script>
</body>
</html>
