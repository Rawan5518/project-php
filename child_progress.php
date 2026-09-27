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

$stmt = $pdo->prepare("SELECT * FROM dbo.children WHERE child_id=? AND parent_id=?");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch();
if (!$child) die("Child not found.");

$stmt = $pdo->prepare("
    SELECT a.category,
           COUNT(*) AS attempts,
           SUM(CASE WHEN ca.is_correct=1 THEN 1 ELSE 0 END) AS correct_cnt
    FROM dbo.child_attempts ca
    JOIN dbo.activities a ON a.activity_id = ca.activity_id
    WHERE ca.child_id = ?
    GROUP BY a.category
    ORDER BY a.category
");
$stmt->execute([$childId]);
$stats = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT TOP 10 ca.attempt_id, ca.attempted_at, ca.is_correct, ca.selected_option, ca.score,
           a.title, a.category, a.difficulty, a.correct_option
    FROM dbo.child_attempts ca
    JOIN dbo.activities a ON a.activity_id = ca.activity_id
    WHERE ca.child_id = ?
    ORDER BY ca.attempt_id DESC
");
$stmt->execute([$childId]);
$recent = $stmt->fetchAll();

function pct($c, $t) {
    if ($t <= 0) return "0%";
    return round(($c/$t)*100) . "%";
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Child Progress</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="page_wide">
  <div class="topbar">
    <h2 style="margin:0;">Child Progress</h2>
    <a class="smallbtn" href="dashboard.php">Back</a>
  </div>

  <div class="card" style="margin-top:15px;">
    <h3 style="margin:0 0 8px 0;">
      Child: <?= htmlspecialchars($child['name']) ?> (ID: <?= (int)$child['child_id'] ?>)
    </h3>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <span class="pill">Age: <?= (int)$child['age'] ?></span>
      <span class="pill">Final Level: <?= (int)$child['final_level'] ?></span>
      <span class="pill">Stars: <?= (int)$child['stars'] ?></span>
    </div>
  </div>

  <div class="card" style="margin-top:15px;">
    <h3 style="margin:0 0 10px 0;">Stats by Area</h3>

    <?php if (!$stats): ?>
      <p>No attempts yet.</p>
    <?php else: ?>
        <div class="table_wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Area</th>
            <th>Attempts</th>
            <th>Correct</th>
            <th>Accuracy</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($stats as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['category']) ?></td>
            <td><?= (int)$s['attempts'] ?></td>
            <td><?= (int)$s['correct_cnt'] ?></td>
            <td><?= pct((int)$s['correct_cnt'], (int)$s['attempts']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
        </div>
    <?php endif; ?>
  </div>

  <div class="card" style="margin-top:15px;">
    <h3 style="margin:0 0 10px 0;">Recent Attempts (Last 10)</h3>

    <?php if (!$recent): ?>
      <p>No recent attempts.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Area</th>
            <th>Diff</th>
            <th>Question</th>
            <th>Your</th>
            <th>Correct</th>
            <th>OK?</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= (int)$r['attempt_id'] ?></td>
            <td><?= htmlspecialchars($r['attempted_at']) ?></td>
            <td><?= htmlspecialchars($r['category']) ?></td>
            <td><?= (int)$r['difficulty'] ?></td>
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['selected_option']) ?></td>
            <td><?= htmlspecialchars($r['correct_option']) ?></td>
            <td><?= ((int)$r['is_correct'] === 1) ? "✅" : "❌" ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn" href="learning_home.php?child_id=<?= $childId ?>">Learning Home</a>
    <a class="btn" href="activity.php?child_id=<?= $childId ?>">Start Activity</a>
  </div>

</div>
</body>
</html>