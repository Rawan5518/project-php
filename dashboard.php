<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit;
}

$parentId = (int)$_SESSION['parent_id'];
$parentName = $_SESSION['parent_name'] ?? 'Parent';

// Fetch children
$stmt = $pdo->prepare("
    SELECT child_id, name, age, parent_level, child_level, final_level, stars, created_at
    FROM dbo.children
    WHERE parent_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$parentId]);
$children = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
  .cards{display:flex;flex-direction:column;gap:14px;}
  .card{border:1px solid #ddd;border-radius:12px;padding:16px;background:#fff;}
  .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;}
  .btn{display:inline-block;padding:10px 14px;border-radius:10px;border:1px solid #ccc;text-decoration:none;}
  .btn-primary{background:#C0C000;color:white;border-color:#C0C000;}
  .btn-danger{background: linear-gradient(180deg, #8C6A00, #6F5200) !important;color: #fff !important;border: 1px solid rgba(0,0,0,.25);}
  .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f2f2;margin-right:8px;margin-top:6px;}
</style>
</head>
<body>

<div class="container" style="width:900px;">
  <div class="topbar">
    <div>
      <h2 style="margin:0;">Dashboard</h2>
      <p style="margin:6px 0 0;">Welcome, <b><?= htmlspecialchars($parentName) ?></b></p>
      <?php if (isset($_GET['logged_out'])): ?>
        <p style="color:green;margin:6px 0 0;">You have been logged out.</p>
      <?php endif; ?>
    </div>
    <div class="row" style="margin-top:0;">
      <a class="btn btn-primary" href="add_child.php">+ Add Child</a>
      <a class="btn btn-danger" href="logout.php">Logout</a>
    </div>
  </div>

  <?php if (!$children): ?>
    <div class="card">
      <p>No children added yet.</p>
      <a class="btn btn-primary" href="add_child.php">Add your first child</a>
    </div>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($children as $c): ?>
        <?php
          $childId = (int)$c['child_id'];
          $name = $c['name'];
          $age = $c['age'];
          $parentLevel = $c['parent_level'];
          $childLevel  = $c['child_level'];
          $finalLevel  = $c['final_level'];
          $stars       = (int)$c['stars'];
        ?>
        <div class="card">
          <h3 style="margin:0;"><?= htmlspecialchars($name) ?> <span style="color:#777;font-weight:normal;">(ID: <?= $childId ?>)</span></h3>
          <div style="margin-top:10px;">
            <span class="pill">Age: <?= ($age === null ? 'N/A' : (int)$age) ?></span>
            <span class="pill">Parent Level: <?= ($parentLevel === null ? '—' : (int)$parentLevel) ?></span>
            <span class="pill">Child Level: <?= ($childLevel === null ? '—' : (int)$childLevel) ?></span>
            <span class="pill">Final Level: <?= ($finalLevel === null ? '—' : (int)$finalLevel) ?></span>
            <span class="pill">Stars: <?= $stars ?></span>
          </div>

          <div class="row" style="margin-top:12px;">
                <a class="btn"
              href="parent_assessment.php?child_id=<?= $childId ?>">
              Parent Assessment
             </a>

             <a class="btn"
               href="diagnostic_test.php?child_id=<?= $childId ?>">
              Diagnostic Test
              </a>

             <a class="btn"
              href="diagnostic_result.php?child_id=<?= $childId ?>">
              Diagnostic Result
              </a>

              <a class="btn" 
              href="child_progress.php?child_id=<?= $childId ?>"> 
              Child Progress
             </a>

               <a class="btn"
               href="learning_home.php?child_id=<?= $childId ?>">
              Start Learning
             </a>

             <a class="btn" 
             href="child_rewards.php?child_id=<?= $childId ?>"> 
             Child Rewards
            </a>
         </div>

          <div style="margin-top:10px;color:#666;font-size:14px;">
            Tip: Run Parent Assessment first, then Diagnostic Test.
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>


</body>
</html>
