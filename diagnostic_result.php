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

// 1) Validate child belongs to parent
$stmt = $pdo->prepare("
    SELECT child_id, name, child_level, final_level
    FROM dbo.children
    WHERE child_id=? AND parent_id=?
");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch();
if (!$child) die("Invalid child");

// 2) Get diagnostic summary per area
$stmt = $pdo->prepare("
    SELECT 
        m.difficulty_area,
        SUM(a.score)            AS score_sum,
        SUM(q.weight)           AS max_score,
        COUNT(*)                AS total_questions
    FROM dbo.diag_attempts a
    JOIN dbo.diag_questions q ON q.diag_question_id = a.diag_question_id
    JOIN dbo.diag_mapping m   ON m.diag_question_id = q.diag_question_id
    WHERE a.child_id = ?
    GROUP BY m.difficulty_area
");
$stmt->execute([$childId]);
$areas = $stmt->fetchAll();

// 3) Calculate percentages + detect weaknesses
$weakAreas = [];
foreach ($areas as &$a) {
    $a['percent'] = ($a['max_score'] > 0)
        ? round(($a['score_sum'] / $a['max_score']) * 100)
        : 0;

    if ($a['percent'] < 50) {
        $weakAreas[] = $a['difficulty_area'];
    }
}
unset($a);

// 4) Save weaknesses (overwrite old)
$pdo->beginTransaction();
try {
    $pdo->prepare("DELETE FROM dbo.child_weaknesses WHERE child_id=?")
        ->execute([$childId]);

    foreach ($areas as $a) {
        $severity = 100 - $a['percent']; // higher = weaker
        $pdo->prepare("
            INSERT INTO dbo.child_weaknesses (child_id, area, severity)
            VALUES (?, ?, ?)
        ")->execute([$childId, $a['difficulty_area'], $severity]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    die("DB error: " . $e->getMessage());
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Diagnostic Result</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .box { border:1px solid #ddd; padding:16px; border-radius:10px; margin-bottom:16px; }
  .good { color:green; font-weight:bold; }
  .bad { color:red; font-weight:bold; }
</style>
</head>
<body>

<div class="container" style="width:720px;">
  <div class="topbar">
    <h2 style="margin:0;">Diagnostic Result</h2>
    <a class="smallbtn" href="dashboard.php">Back to Dashboard</a>
  </div>

  <p><b>Child:</b> <?= htmlspecialchars($child['name']) ?> (ID: <?= $childId ?>)</p>

  <div class="box">
    <p><b>Child Level:</b> <?= (int)$child['child_level'] ?></p>
    <p><b>Final Level:</b> <?= (int)$child['final_level'] ?></p>
  </div>

  <h3>Results by Area</h3>

  <?php foreach ($areas as $a): ?>
    <div class="box">
      <p><b><?= htmlspecialchars(ucfirst($a['difficulty_area'])) ?></b></p>
      <p>Score: <?= (int)$a['score_sum'] ?> / <?= (int)$a['max_score'] ?></p>
      <p>Percentage: <?= (int)$a['percent'] ?>%</p>

      <?php if ($a['percent'] >= 50): ?>
        <p class="good">Good performance</p>
      <?php else: ?>
        <p class="bad">Needs support</p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <h3>Overall Recommendation</h3>
  <div class="box">
    <?php if (!$weakAreas): ?>
      <p class="good">No major weaknesses detected. The child can proceed normally.</p>
    <?php else: ?>
      <p class="bad">
        Focus recommended on:
        <?= htmlspecialchars(implode(', ', $weakAreas)) ?>
      </p>
      <p>
        The system will prioritize activities targeting these areas.
      </p>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
