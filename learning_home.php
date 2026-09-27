<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit;
}

$parentId = (int)$_SESSION['parent_id'];
$childId  = (int)($_GET['child_id'] ?? 0);
if ($childId <= 0) die("Missing child_id");

// Child info
$stmt = $pdo->prepare("
    SELECT child_id, parent_id, name, age, parent_level, child_level, final_level, stars
    FROM dbo.children
    WHERE child_id=? AND parent_id=?
");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch();
if (!$child) die("Invalid child");

// Weak areas (sorted by severity desc)
$stmt = $pdo->prepare("
    SELECT area, severity
    FROM dbo.child_weaknesses
    WHERE child_id=?
    ORDER BY severity DESC
");
$stmt->execute([$childId]);
$weaknesses = $stmt->fetchAll();

$finalLevel = (int)($child['final_level'] ?? 0);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Learning Home</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .box{border:1px solid #ddd;border-radius:12px;padding:16px;background:#fff;margin-bottom:14px;}
  .row{display:flex;gap:10px;flex-wrap:wrap;}
  .btn{display:inline-block;padding:10px 14px;border-radius:10px;border:1px solid #ccc;text-decoration:none;}
  .btn-primary{background:#1e73ff;color:white;border-color:#1e73ff;}
  .pill{display:inline-block;padding:5px 10px;border-radius:999px;background:#f2f2f2;margin:5px 8px 0 0;}
</style>
</head>
<body>

<div class="container" style="width:900px;">
  <div class="topbar">
    <h2 style="margin:0;">Learning Home</h2>
    <a class="smallbtn" href="dashboard.php">Back</a>
  </div>

  <div class="box">
    <h3 style="margin:0;"><?= htmlspecialchars($child['name']) ?> <span style="color:#777;font-weight:normal;">(ID: <?= $childId ?>)</span></h3>
    <div style="margin-top:10px;">
      <span class="pill">Age: <?= ($child['age'] === null ? 'N/A' : (int)$child['age']) ?></span>
      <span class="pill">Final Level: <?= ($child['final_level'] === null ? '—' : (int)$child['final_level']) ?></span>
      <span class="pill">Stars: <?= (int)$child['stars'] ?></span>
    </div>
  </div>

  <div class="box">
    <h3 style="margin-top:0;">Weak Areas (from Diagnostic)</h3>

    <?php if (!$weaknesses): ?>
      <p>No weaknesses saved yet. Run the diagnostic test first.</p>
      <div class="row">
        <a class="btn" href="diagnostic_test.php?child_id=<?= $childId ?>">Go to Diagnostic</a>
        <a class="btn" href="diagnostic_result.php?child_id=<?= $childId ?>">View Diagnostic Result</a>
      </div>
    <?php else: ?>
      <?php foreach ($weaknesses as $w): ?>
        <span class="pill">
          <?= htmlspecialchars($w['area']) ?> (severity: <?= (int)$w['severity'] ?>)
        </span>
      <?php endforeach; ?>
      <p style="margin-top:12px;color:#666;">
        The system will start with the highest severity area.
      </p>
    <?php endif; ?>
  </div>

  <div class="box">
    <h3 style="margin-top:0;">Start Learning</h3>

    <?php if ($finalLevel <= 0): ?>
      <p>Please run Parent Assessment + Diagnostic Test first to generate the final level.</p>
      <div class="row">
        <a class="btn" href="parent_assessment.php?child_id=<?= $childId ?>">Parent Assessment</a>
        <a class="btn" href="diagnostic_test.php?child_id=<?= $childId ?>">Diagnostic Test</a>
      </div>
    <?php else: ?>
      <p>Ready. Click below to start the next recommended activity.</p>
      <div class="row">
        <a class="btn " href="activity.php?child_id=<?= $childId ?>">Start Next Activity</a>
      </div>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
