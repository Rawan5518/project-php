<?php $title="Register"; include "includes/header_public.php"; ?>
<?php
session_start();
require_once __DIR__ . '/config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['confirm'] ?? '';

    if ($name === '' || $email === '' || $pass === '' || $pass2 === '') {
        $errors[] = "Please fill in all fields.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if ($pass !== $pass2) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($pass) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (!$errors) {
        // تأكد الإيميل مش مسجل قبل
        $check = $pdo->prepare("SELECT parent_id FROM parents WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = "This email is already registered. Please login.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $ins = $pdo->prepare("INSERT INTO parents (name, email, password_hash) VALUES (?, ?, ?)");
            $ins->execute([$name, $email, $hash]);

            // تسجيل دخول تلقائي بعد التسجيل
            $parentId = (int)$pdo->lastInsertId();
            $_SESSION['parent_id'] = $parentId;
            $_SESSION['parent_name'] = $name;

            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
  <h2>Create Account</h2>

  <?php if ($errors): ?>
    <div class="msg-error">
      <?php foreach ($errors as $e): ?>
        <p><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <label>Full Name</label>
    <input type="text" name="name" placeholder="Your name">

    <label>Email</label>
    <input type="email" name="email" placeholder="Email">

    <label>Password</label>
    <input type="password" name="password" placeholder="Password (min 6 chars)">

    <label>Confirm Password</label>
    <input type="password" name="confirm" placeholder="Confirm password">

    <button type="submit">Register</button>
  </form>

  <div class="link">
    Already have an account? <a href="login.php">Login</a>
  </div>
</div>

</body>
</html>
<?php include "includes/footer_public.php"; ?>
