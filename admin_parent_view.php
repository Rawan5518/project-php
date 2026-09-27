<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/config/db.php';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$parentId = (int)($_GET['parent_id'] ?? 0);
if ($parentId <= 0) die("Missing parent_id");

$errors = [];
$msg = (string)($_GET['msg'] ?? '');

// Update parent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_parent') {
  $name  = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));

  if ($name === '') $errors[] = "Parent name required.";
  if ($email === '') $errors[] = "Email required.";

  if (!$errors) {
    $st = $pdo->prepare("UPDATE dbo.parents SET name=?, email=? WHERE parent_id=?");
    $st->execute([$name, $email, $parentId]);
    header("Location: admin_parent_view.php?parent_id=$parentId&msg=parent_updated");
    exit;
  }
}

// Delete child
if (isset($_GET['delete_child_id'])) {
  $childId = (int)$_GET['delete_child_id'];
  if ($childId > 0) {
    // optional: delete related tables first if you have FK constraints
    $pdo->prepare("DELETE FROM dbo.children WHERE child_id=? AND parent_id=?")->execute([$childId, $parentId]);
    header("Location: admin_parent_view.php?parent_id=$parentId&msg=child_deleted");
    exit;
  }
}

// Load parent
$st = $pdo->prepare("SELECT parent_id, name, email FROM dbo.parents WHERE parent_id=?");
$st->execute([$parentId]);
$parent = $st->fetch(PDO::FETCH_ASSOC);
if (!$parent) die("Parent not found");

// Load children
$st = $pdo->prepare("
  SELECT child_id, name, age, final_level, stars
  FROM dbo.children
  WHERE parent_id=?
  ORDER BY child_id DESC
");
$st->execute([$parentId]);
$children = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Parent</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .btn-danger{background:#dc3545;color:#fff;border-color:#dc3545;}
  .btn-sm{padding:6px 10px;border-radius:8px;font-size:14px;}
</style>
</head>
<body>

<div class="container" style="width:1100px;">
  <div class="topbar">
    <h2 style="margin:0;">Parent Details</h2>
    <div>
      <a class="smallbtn" href="admin_parents.php">Back</a>
      <a class="smallbtn" href="admin_logout.php">Logout</a>
    </div>
  </div>

  <?php if ($msg === 'parent_updated'): ?>
    <div class="card"><b>✅ Parent updated.</b></div>
  <?php elseif ($msg === 'child_deleted'): ?>
    <div class="card"><b>🗑️ Child deleted.</b></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="card" style="border-color:#dc3545;">
      <?php foreach ($errors as $e): ?>
        <div style="color:#dc3545;"><?= h($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <h3 style="margin-top:0;">Parent #<?= (int)$parent['parent_id'] ?></h3>
    <form method="post">
      <input type="hidden" name="action" value="update_parent">

      <label>Full Name</label>
      <input name="name" value="<?= h($parent['name']) ?>" required>

      <label>Email</label>
      <input name="email" value="<?= h($parent['email']) ?>" required>

      <button class="btn btn-primary" type="submit">Save Parent</button>
    </form>
  </div>

  <div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Children</h3>

    <?php if (!$children): ?>
      <p>No children for this parent.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Child ID</th>
            <th>Name</th>
            <th>Age</th>
            <th>Final Level</th>
            <th>Stars</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($children as $c): ?>
          <tr>
            <td><?= (int)$c['child_id'] ?></td>
            <td><?= h($c['name']) ?></td>
            <td><?= (int)$c['age'] ?></td>
            <td><?= (int)$c['final_level'] ?></td>
            <td><?= (int)$c['stars'] ?></td>
            <td style="white-space:nowrap;">
              <a class="btn btn-sm" href="admin_child_edit.php?child_id=<?= (int)$c['child_id'] ?>&parent_id=<?= $parentId ?>">Edit</a>
              <a class="btn btn-danger btn-sm"
                 href="admin_parent_view.php?parent_id=<?= $parentId ?>&delete_child_id=<?= (int)$c['child_id'] ?>"
                 onclick="return confirm('Delete this child?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>
</body>
</html>