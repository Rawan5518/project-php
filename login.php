<?php
session_start();
require_once __DIR__ . '/config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $errors[] = "Please enter email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT parent_id, name, password_hash FROM parents WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($pass, $u['password_hash'])) {
            $_SESSION['parent_id'] = (int)$u['parent_id'];
            $_SESSION['parent_name'] = $u['name'];
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
  <h2>Login</h2>

  <?php if ($errors): ?>
    <div class="msg-error">
      <?php foreach ($errors as $e): ?>
        <p><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <label>Email</label>
    <input type="email" name="email" placeholder="Email">

    <label>Password</label>
    <input type="password" name="password" placeholder="Password">

    <button type="submit">Login</button>
  </form>

  <div class="link">
    Don’t have an account? <a href="register.php">Register</a>
  </div>
</div>

</body>
</html>
