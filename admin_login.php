<?php
session_start();
require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if ($u === ADMIN_USERNAME && $p === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Wrong username or password";
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container" style="max-width:450px;">
  <div class="card">
    <h2>Admin Login</h2>

    <?php if ($error): ?>
      <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
      <label>Username</label>
      <input type="text" name="username" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <button class="btn btn-primary">Login</button>
    </form>
  </div>
</div>

</body>
</html>