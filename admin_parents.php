<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/config/db.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$q = trim((string)($_GET['q'] ?? ''));

$sql = "
SELECT TOP 200
    p.parent_id,
    p.name,
    p.email,
    COUNT(c.child_id) AS children_count
FROM dbo.parents p
LEFT JOIN dbo.children c ON c.parent_id = p.parent_id
";
$params = [];

if ($q !== '') {
  $sql .= " WHERE (p.name LIKE ? OR p.email LIKE ?) ";
  $params[] = "%$q%";
  $params[] = "%$q%";
}

$sql .= "
GROUP BY p.parent_id, p.name, p.email
ORDER BY p.parent_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Parents</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .btn-sm{padding:6px 10px;border-radius:8px;font-size:14px;}
</style>
</head>
<body>

<div class="container" style="width:1100px;">
  <div class="topbar">
    <h2 style="margin:0;">Parents & Children</h2>
    <div>
      <a class="smallbtn" href="admin_dashboard.php">Back</a>
      <a class="smallbtn" href="admin_logout.php">Logout</a>
    </div>
  </div>

  <div class="card">
    <form method="get" style="display:flex; gap:10px; flex-wrap:wrap;">
      <input type="text" name="q" placeholder="Search by parent name or email..." value="<?= h($q) ?>" style="flex:1; min-width:240px;">
      <button class="btn btn-primary btn-sm">Search</button>
      <a class="btn btn-sm" href="admin_parents.php">Reset</a>
    </form>
  </div>

  <div class="card" style="margin-top:12px;">
    <table class="table">
      <thead>
        <tr>
          <th>Parent ID</th>
          <th>Name</th>
          <th>Email</th>
          <th># Children</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5">No parents found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['parent_id'] ?></td>
            <td><?= h($r['name']) ?></td>
            <td><?= h($r['email']) ?></td>
            <td><?= (int)$r['children_count'] ?></td>
            <td>
              <a class="btn btn-sm" href="admin_parent_view.php?parent_id=<?= (int)$r['parent_id'] ?>">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>