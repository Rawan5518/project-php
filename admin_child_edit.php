<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/config/db.php';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$childId = (int)($_GET['child_id'] ?? 0);
$parentId = (int)($_GET['parent_id'] ?? 0);
if ($childId <= 0) die("Missing child_id");

$errors = [];
$msg = (string)($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim((string)($_POST['name'] ?? ''));
  $age  = (int)($_POST['age'] ?? 0);
  $finalLevel = (int)($_POST['final_level'] ?? 1);
  $stars = (int)($_POST['stars'] ?? 0);

  if ($name === '') $errors[] = "Child name required.";
  if ($age <= 0) $errors[] = "Valid age required.";
  if ($finalLevel < 1 || $finalLevel > 3) $errors[] = "Final level must be 1-3.";
  if ($stars < 0) $errors[] = "Stars cannot be negative.";

  if (!$errors) {
    $st = $pdo->prepare("
      UPDATE dbo.children
      SET name=?, age=?, final_level=?, stars=?
      WHERE child_id=?
    ");
    $st->execute([$name, $age, $finalLevel, $stars, $childId]);
    header("Location: admin_child_edit.php?child_id=$childId&parent_id=$parentId&msg=updated");
    exit;
  }
}

$st = $pdo->prepare("SELECT child_id, parent_id, name, age, final_level, stars FROM dbo.children WHERE child_id=?");
$st->execute([$childId]);
$child = $st->fetch(PDO::FETCH_ASSOC);
if (!$child) die("Child not found");

if ($parentId <= 0) $parentId = (int)$child['parent_id'];
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Edit Child</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container" style="max-width:700px;">
  <div class="topbar">
    <h2 style="margin:0;">Edit Child</h2>
    <a class="smallbtn" href="admin_parent_view.php?parent_id=<?= (int)$parentId ?>">Back</a>
  </div>

  <?php if ($msg === 'updated'): ?>
    <div class="card"><b>✅ Child updated.</b></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="card" style="border-color:#dc3545;">
      <?php foreach ($errors as $e): ?>
        <div style="color:#dc3545;"><?= h($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="post">
      <label>Name</label>
      <input name="name" value="<?= h($child['name']) ?>" required>

      <label>Age</label>
      <input type="number" name="age" min="1" value="<?= (int)$child['age'] ?>" required>

      <label>Final Level (1-3)</label>
      <input type="number" name="final_level" min="1" max="3" value="<?= (int)$child['final_level'] ?>" required>

      <label>Stars</label>
      <input type="number" name="stars" min="0" value="<?= (int)$child['stars'] ?>" required>

      <button class="btn btn-primary" type="submit">Save</button>
    </form>
  </div>

</div>
</body>
</html>