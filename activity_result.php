<?php
session_start();
require_once __DIR__ . '/config/db.php';

$attemptId = (int)($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) {
    die("Missing attempt_id (URL attempt_id=" . htmlspecialchars($_GET['attempt_id'] ?? 'none') . ")");
}
$rewardMsg = trim($_GET['reward'] ?? '');
// Get attempt + activity + child
$stmt = $pdo->prepare("
    SELECT
        ca.attempt_id,
        ca.is_correct,
        ca.selected_option,
        ca.attempted_at,
        c.child_id,
        c.name AS child_name,
        c.stars,
        a.title,
        a.category,
        a.difficulty,
        a.option_a,
        a.option_b,
        a.option_c,
        a.option_d,
        a.correct_option
    FROM dbo.child_attempts ca
    JOIN dbo.children c ON c.child_id = ca.child_id
    JOIN dbo.activities a ON a.activity_id = ca.activity_id
    WHERE ca.attempt_id = ?
");
$stmt->execute([$attemptId]);
$row = $stmt->fetch();

if (!$row) die("Attempt not found.");

$isCorrect = ((int)$row['is_correct'] === 1);

function optionText(array $r, ?string $letter): string {
    return match ($letter) {
        'A' => (string)$r['option_a'],
        'B' => (string)$r['option_b'],
        'C' => (string)$r['option_c'],
        'D' => (string)$r['option_d'],
        default => ''
    };
}

$correctLetter  = $row['correct_option'];
$selectedLetter = $row['selected_option'];
$correctText    = optionText($row, $correctLetter);
$selectedText   = optionText($row, $selectedLetter);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php if ($rewardMsg !== ''): ?>
  <div class="reward-banner" style="border:2px solid #C0C000;background:#fffbe6;">
    <h3 style="margin:center;">🎉   احسنت عملا ! ربحت جائزة جديدة!</h3>
  </div>
<?php endif; ?>
<title>Activity Result</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .card{border:1px solid #ddd;border-radius:12px;padding:16px;background:#fff;margin-bottom:14px;}
  .good{color:green;font-weight:bold;font-size:18px;}
  .bad{color:red;font-weight:bold;font-size:18px;}
  .pill{display:inline-block;padding:5px 10px;border-radius:999px;background:#f2f2f2;margin:5px 8px 0 0;}
  .row{display:flex;gap:10px;flex-wrap:wrap;}
  .btn{display:inline-block;padding:10px 14px;border-radius:10px;border:1px solid #ccc;text-decoration:none;background:#f9f9f9;}
  .btn-primary{background:#C0C000;color:white;border-color:#C0C000;}
</style>
</head>
<body>

<div class="container" style="width:900px;">

  <div class="card">
    <p style="margin:0;"><b>Child:</b> <?= htmlspecialchars($row['child_name']) ?> (ID: <?= (int)$row['child_id'] ?>)</p>
    <div style="margin-top:10px;">
      <span class="pill">Stars: <?= (int)$row['stars'] ?></span>
      <span class="pill">Area: <?= htmlspecialchars($row['category']) ?></span>
      <span class="pill">Difficulty: <?= (int)$row['difficulty'] ?></span>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0;"><?= htmlspecialchars($row['title']) ?></h3>

    <?php if ($isCorrect): ?>
      <p class="good">✅ Correct!</p>
    <?php else: ?>
      <p class="bad">❌ Wrong!</p>
    <?php endif; ?>

    <hr>

    <p><b>Correct Answer:</b> (<?= htmlspecialchars($correctLetter) ?>) <?= htmlspecialchars($correctText) ?></p>
    <p><b>Your Answer:</b> (<?= htmlspecialchars($selectedLetter) ?>) <?= htmlspecialchars($selectedText) ?></p>
  </div>

  <div class="row">
    <a class="btn btn-primary" href="activity.php?child_id=<?= (int)$row['child_id'] ?>">Next Activity</a>
    <a class="btn" href="activity.php?child_id=<?= (int)$row['child_id'] ?>">Try Another Question</a>
    <a class="btn" href="dashboard.php">Dashboard</a>
  </div>

</div>
</body>
</html>