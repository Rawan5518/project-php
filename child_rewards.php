<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit;
}

$parentId = (int)$_SESSION['parent_id'];

$childId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
if ($childId <= 0) { die("Missing child_id"); }

// تأكد الطفل تابع لهالـ parent
$stmt = $pdo->prepare("
    SELECT child_id, name, age, final_level, stars
    FROM dbo.children
    WHERE child_id = ? AND parent_id = ?
");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch();
if (!$child) { die("Child not found"); }

// إحصائيات الجوائز
$stmt = $pdo->prepare("
    SELECT reward_type, COUNT(*) AS cnt
    FROM dbo.child_earned_rewards
    WHERE child_id = ?
    GROUP BY reward_type
    ORDER BY reward_type
");
$stmt->execute([$childId]);
$rewardStats = $stmt->fetchAll();

// آخر الجوائز (مع اللينك)
$stmt = $pdo->prepare("SELECT TOP 20 reward_id, reward_type, reward_name, reward_link, earned_at
FROM dbo.child_earned_rewards
WHERE child_id = ?
ORDER BY earned_at DESC, reward_id DESC
    
");
$stmt->execute([$childId]);
$recentRewards = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Child Rewards</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container" style="width:900px;">
  <div class="topbar">
    <h2 style="margin:0;">Child Rewards</h2>
    <a class="smallbtn" href="dashboard.php">Back</a>
  </div>

  <div class="card" style="margin-top:15px;">
    <h3 style="margin:0 0 8px 0;">
      Child: <?= htmlspecialchars($child['name']) ?> (ID: <?= (int)$child['child_id'] ?>)
    </h3>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <span class="pill">Age: <?= $child['age'] === null ? 'N/A' : (int)$child['age'] ?></span>
      <span class="pill">Final Level: <?= $child['final_level'] === null ? 'N/A' : (int)$child['final_level'] ?></span>
      <span class="pill">Stars: <?= (int)$child['stars'] ?></span>
    </div>
  </div>

  <div class="card" style="margin-top:15px;">
    <h3 style="margin:0 0 10px 0;">Rewards Summary</h3>

    <?php if (!$rewardStats): ?>
      <p>No rewards earned yet.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Type</th>
            <th>Count</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rewardStats as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['reward_type']) ?></td>
            <td><?= (int)$r['cnt'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card" style="margin-top:15px;">
    <h3 style="margin:0 0 10px 0;">Recent Rewards (Last 20)</h3>

    <?php if (!$recentRewards): ?>
      <p>No rewards yet.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Reward</th>
            <th>Action</th>
          </tr>
        </thead>
       <tbody>
<?php foreach ($recentRewards as $rw): ?>
  <tr>
    <td><?= htmlspecialchars($rw['earned_at']) ?></td>
    <td><?= htmlspecialchars($rw['reward_type']) ?></td>
    <td><?= htmlspecialchars($rw['reward_name']) ?></td>
    <td>
      <?php if (!empty($rw['reward_link'])): ?>
        <a class="btn btn-primary"
           href="<?= htmlspecialchars($rw['reward_link']) ?>"
           target="_blank" rel="noopener">Open</a>
      <?php else: ?>
        <span style="color:#777;">—</span>
      <?php endif; ?>
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