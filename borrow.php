<?php
session_start();
include 'inc/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['borrow'])) {
  $book_id = (int)$_POST['book_id'];
  $borrow_date = date('Y-m-d');

  // Gunakan prepared statement untuk update stok dengan kondisi stok > 0
  $stmt = $conn->prepare("UPDATE books SET stock = stock - 1 WHERE id = ? AND stock > 0");
  $stmt->bind_param("i", $book_id);
  $stmt->execute();

  // Cek jumlah baris yang terpengaruh agar tahu stok cukup atau tidak
  if ($stmt->affected_rows > 0) {
      // Simpan transaksi menggunakan prepared statement
      $insert = $conn->prepare("INSERT INTO transactions (user_id, book_id, borrow_date, status) VALUES (?, ?, ?, 'Dipinjam')");
      $insert->bind_param("iis", $user_id, $book_id, $borrow_date);
      $insert->execute();
      $insert->close();

      $_SESSION['success_message'] = "Buku berhasil dipinjam!";
  } else {
      $_SESSION['error_message'] = "Stok buku kosong, tidak dapat meminjam!";
  }
  $stmt->close();

  header("Location: transactions.php");
  exit;
}

$books = $conn->query("SELECT * FROM books WHERE stock > 0 ORDER BY title ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pinjam Buku - Perpustakaan Digital</title>
  <link rel="stylesheet" href="assets/modern-styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
  <?php include 'inc/modern_header.php'; ?>

  <div class="container">
    <h1>Pinjam Buku</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <table class="modern-table">
      <thead>
        <tr>
          <th>Judul Buku</th>
          <th>Penulis</th>
          <th>Stok</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while($book = $books->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($book['title']) ?></td>
            <td><?= htmlspecialchars($book['author']) ?></td>
            <td><?= $book['stock'] ?></td>
            <td>
              <form method="POST">
                <input type="hidden" name="book_id" value="<?= $book['id'] ?>" />
                <button type="submit" name="borrow" class="btn btn-success btn-sm">
                  <i class="fas fa-hand-holding"></i> Pinjam
                </button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
