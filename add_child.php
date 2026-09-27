<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age  = (int)($_POST['age'] ?? 0);

    if ($name === '') {
        $errors[] = "Please enter the child's name.";
    }

    if ($age <= 0) {
        $errors[] = "Please enter a valid age.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO dbo.children
            (parent_id, name, age, stars)
            VALUES (?, ?, ?, 0)
        ");

        $stmt->execute([
            (int)$_SESSION['parent_id'],
            $name,
            $age
        ]);

        header("Location: dashboard.php");
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Add Child</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
  <div class="topbar">
    <h2 style="margin:0;">Add Child</h2>
    <a class="smallbtn" href="dashboard.php">Back</a>
  </div>

  <?php if ($errors): ?>
    <div class="msg-error">
      <?php foreach ($errors as $e): ?>
        <p><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <label>Child Name</label>
    <input type="text" name="name" placeholder="Child name" required>

    <label>Age</label>
    <input type="number" name="age" min="1" placeholder="Age" required>

    <button type="submit">Save Child</button>
  </form>
</div>

</body>
</html>
