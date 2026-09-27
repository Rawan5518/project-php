<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit;
}

$parentId = (int)$_SESSION['parent_id'];
$childId  = (int)($_GET['child_id'] ?? $_POST['child_id'] ?? 0);
if ($childId <= 0) die("Missing child_id");

$errors = [];

/**
 * Grants rewards based on child's current stars/level.
 * - No duplicate reward SAME DAY (but can repeat on a new day)
 * - Returns ONLY newly earned rewards (name + link + type)
 */
function grantRewards(PDO $pdo, int $childId): array {
    // Read child state
    $st = $pdo->prepare("SELECT stars, final_level FROM dbo.children WHERE child_id=?");
    $st->execute([$childId]);
    $child = $st->fetch(PDO::FETCH_ASSOC);
    if (!$child) return [];

    $stars = (int)$child['stars'];
    $level = (int)($child['final_level'] ?? 1);

    // ------------------ YOUR VIDEO LINKS ------------------
    $videos = [
        ['name'=>'🎬 فيديو مكافأة 1', 'link'=>'https://www.youtube.com/watch?v=7ZBkqsPLMuM'],
        ['name'=>'🎬 فيديو مكافأة 2', 'link'=>'https://www.youtube.com/watch?v=V6pKAXJFPrY'],
        ['name'=>'🎬 فيديو مكافأة 3', 'link'=>'https://www.youtube.com/watch?v=68To3QmIe-c'],
        ['name'=>'🎬 فيديو مكافأة 4', 'link'=>'https://www.youtube.com/watch?v=jWeWZGV68l8'],
        ['name'=>'🎬 فيديو مكافأة 5', 'link'=>'https://www.youtube.com/watch?v=gTsPitiKwTA'],
        ['name'=>'🎬 فيديو مكافأة 6', 'link'=>'https://www.youtube.com/watch?v=WYSGB5xDJ0s'],
    ];

    // ------------------ YOUR STORY LINKS (mawdoo3) ------------------
    $stories = [
        ['name'=>'📖 قصة: الغرابان الخاسران',        'link'=>'https://mawdoo3.com/%D9%82%D8%B5%D8%B5_%D8%A7%D8%B7%D9%81%D8%A7%D9%84_%D9%82%D8%A8%D9%84_%D8%A7%D9%84%D9%86%D9%88%D9%85#.D8.A7.D9.84.D8.BA.D8.B1.D8.A7.D8.A8.D8.A7.D9.86_.D8.A7.D9.84.D8.AE.D8.A7.D8.B3.D8.B1.D8.A7.D9.86'],
        ['name'=>'📖 قصة: الثعلب الماكر',             'link'=>'https://mawdoo3.com/%D9%82%D8%B5%D8%B5_%D8%A7%D8%B7%D9%81%D8%A7%D9%84_%D9%82%D8%A8%D9%84_%D8%A7%D9%84%D9%86%D9%88%D9%85#.D8.A7.D9.84.D8.AB.D8.B9.D9.84.D8.A8_.D8.A7.D9.84.D9.85.D8.A7.D9.83.D8.B1'],
        ['name'=>'📖 قصة: العصفور والفيل',            'link'=>'https://mawdoo3.com/%D9%82%D8%B5%D8%B5_%D8%A7%D8%B7%D9%81%D8%A7%D9%84_%D9%82%D8%A8%D9%84_%D8%A7%D9%84%D9%86%D9%88%D9%85#.D8.A7.D9.84.D8.B9.D8.B5.D9.81.D9.88.D8.B1_.D9.88.D8.A7.D9.84.D9.81.D9.8A.D9.84'],
        ['name'=>'📖 قصة: الثعلب والعنب',             'link'=>'https://mawdoo3.com/%D9%82%D8%B5%D8%B5_%D8%A7%D8%B7%D9%81%D8%A7%D9%84_%D9%82%D8%A8%D9%84_%D8%A7%D9%84%D9%86%D9%88%D9%85#.D8.A7.D9.84.D8.AB.D8.B9.D9.84.D8.A8_.D9.88.D8.A7.D9.84.D8.B9.D9.86.D8.A8'],
        ['name'=>'📖 قصة: القنفذ والحيوانات الصغيرة', 'link'=>'https://mawdoo3.com/%D9%82%D8%B5%D8%B5_%D8%A7%D8%B7%D9%81%D8%A7%D9%84_%D9%82%D8%A8%D9%84_%D8%A7%D9%84%D9%86%D9%88%D9%85#.D8.A7.D9.84.D9.82.D9.86.D9.81.D8.B0_.D9.88.D8.A7.D9.84.D8.AD.D9.8A.D9.88.D8.A7.D9.86.D8.A7.D8.AA_.D8.A7.D9.84.D8.B5.D8.BA.D9.8A.D8.B1.D8.A9'],
        ['name'=>'📖 قصة: ليلى والذئب',               'link'=>'https://mawdoo3.com/%D9%82%D8%B5%D8%A9_%D9%84%D9%8A%D9%84%D9%89_%D9%88%D8%A7%D9%84%D8%B0%D8%A6%D8%A8'],
    ];

    // ------------------ HOME ACTIVITIES (no link) ------------------
    $homeActs = [
        ['name'=>'🎨 ارسم/ارسمِي 10 دقائق', 'link'=>null],
        ['name'=>'🧩 لعب Puzzle لمدة 10 دقائق', 'link'=>null],
        ['name'=>'📺 فيك تحضر/تحضري تلفزيون 30 دقيقة', 'link'=>null],
        ['name'=>'🏃‍♂️ حركة: اقفز 10 قفزات', 'link'=>null],
        ['name'=>'🧸 لعب بلعبتك المفضلة 10 دقائق', 'link'=>null],
    ];

    // Helper: insert only if NOT earned today
    $insertIfNotToday = function(string $type, string $name, ?string $link) use ($pdo, $childId): bool {
        $ins = $pdo->prepare("
            IF NOT EXISTS (
                SELECT 1 FROM dbo.child_earned_rewards
                WHERE child_id=?
                  AND reward_type=?
                  AND reward_name=?
                  AND CAST(earned_at AS DATE) = CAST(SYSDATETIME() AS DATE)
            )
            INSERT INTO dbo.child_earned_rewards (child_id, reward_type, reward_name, reward_link)
            VALUES (?, ?, ?, ?)
        ");
        $ins->execute([
            $childId, $type, $name,
            $childId, $type, $name, $link
        ]);

        // We can’t know “inserted” directly from this statement, so check quickly:
        $chk = $pdo->prepare("
            SELECT TOP 1 1
            FROM dbo.child_earned_rewards
            WHERE child_id=?
              AND reward_type=?
              AND reward_name=?
              AND CAST(earned_at AS DATE) = CAST(SYSDATETIME() AS DATE)
        ");
        $chk->execute([$childId, $type, $name]);
        return (bool)$chk->fetchColumn();
    };

    $newRewards = [];

    // ✅ Rule 1: every 5 stars => video (rotating)
    if ($stars > 0 && $stars % 5 === 0) {
        $idx = (int)(($stars / 5 - 1) % count($videos));
        $v = $videos[$idx];
        if ($insertIfNotToday('video', $v['name'], $v['link'])) {
            $newRewards[] = ['type'=>'video','name'=>$v['name'],'link'=>$v['link']];
        }
    }

    // ✅ Rule 2: every 10 stars => story (rotating)
    if ($stars > 0 && $stars % 10 === 0) {
        $idx = (int)(($stars / 10 - 1) % count($stories));
        $s = $stories[$idx];
        if ($insertIfNotToday('story', $s['name'], $s['link'])) {
            $newRewards[] = ['type'=>'story','name'=>$s['name'],'link'=>$s['link']];
        }
    }

    // ✅ Rule 3: every 15 stars => home activity (rotating)
    if ($stars > 0 && $stars % 15 === 0) {
        $idx = (int)(($stars / 15 - 1) % count($homeActs));
        $a = $homeActs[$idx];
        if ($insertIfNotToday('activity', $a['name'], $a['link'])) {
            $newRewards[] = ['type'=>'activity','name'=>$a['name'],'link'=>null];
        }
    }

    // ✅ Optional: Level badges (no link)
    if ($level >= 2) {
        $name = '🏅 وصلتي/وصلت للمستوى 2';
        if ($insertIfNotToday('badge', $name, null)) {
            $newRewards[] = ['type'=>'badge','name'=>$name,'link'=>null];
        }
    }
    if ($level >= 3) {
        $name = '🏅 وصلتي/وصلت للمستوى 3';
        if ($insertIfNotToday('badge', $name, null)) {
            $newRewards[] = ['type'=>'badge','name'=>$name,'link'=>null];
        }
    }

    return $newRewards;
}

// ---- load child (ensure belongs to parent)
$stmt = $pdo->prepare("
    SELECT child_id, parent_id, name, final_level, stars
    FROM dbo.children
    WHERE child_id = ? AND parent_id = ?
");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$child) die("Invalid child");

$finalLevel = (int)($child['final_level'] ?? 1);

// ======================= POST: submit answer =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activityId = (int)($_POST['activity_id'] ?? 0);
    $selected   = (string)($_POST['answer'] ?? '');

    if ($activityId <= 0) $errors[] = "Missing activity_id";
    if (!in_array($selected, ['A','B','C','D'], true)) $errors[] = "Choose A/B/C/D";

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT * FROM dbo.activities WHERE activity_id = ?");
        $stmt->execute([$activityId]);
        $activity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$activity) {
            $errors[] = "Activity not found.";
        } else {
            $correct   = (string)$activity['correct_option'];
            $isCorrect = ($selected === $correct);
            $score     = $isCorrect ? 1.00 : 0.00;

            $pdo->beginTransaction();
            try {
                // Insert attempt and get attempt_id
                $stmtIns = $pdo->prepare("
                    INSERT INTO dbo.child_attempts
                        (child_id, activity_id, is_correct, score, attempted_at, selected_option)
                    OUTPUT INSERTED.attempt_id
                    VALUES (?, ?, ?, ?, SYSDATETIME(), ?)
                ");
                $stmtIns->execute([
                    $childId,
                    $activityId,
                    $isCorrect ? 1 : 0,
                    $score,
                    $selected
                ]);

                $attemptId = (int)$stmtIns->fetchColumn();
                if ($attemptId <= 0) {
                    throw new Exception("attempt_id was not returned (attemptId=$attemptId).");
                }

                // Update stars if correct
                if ($isCorrect) {
                    $pdo->prepare("UPDATE dbo.children SET stars = stars + 1 WHERE child_id = ?")
                        ->execute([$childId]);
                }

                // ✅ Grant rewards after updating stars
                $newRewards = grantRewards($pdo, $childId);
                
                $pdo->commit();

                $rewardMsg = '';
               if (!empty($newRewards)) {
                          // خدي أول جائزة بس للعرض (أو خليهم كلهم)
                    $rewardMsg = implode(' | ', $newRewards);
                     }

                       header("Location: activity_result.php?attempt_id=" . $attemptId . "&reward=" . urlencode($rewardMsg));
                         exit;

                // ✅ Flash message for activity_result.php
                if (!empty($newRewards)) {
                    $_SESSION['flash_reward'] = $newRewards; // list
                } else {
                    unset($_SESSION['flash_reward']);
                }

                header("Location: activity_result.php?attempt_id=" . $attemptId);
                exit;
            } catch (Throwable $e) {
                $pdo->rollBack();
                $errors[] = "DB error: " . $e->getMessage();
            }
        }
    }
}

// ======================= GET: pick next activity =======================

// focus area
$stmt = $pdo->prepare("
    SELECT TOP 1 area
    FROM dbo.child_weaknesses
    WHERE child_id = ?
    ORDER BY severity DESC
");
$stmt->execute([$childId]);
$area = $stmt->fetchColumn() ?: 'memory';

// recent accuracy
$stmt = $pdo->prepare("
    SELECT TOP 10 is_correct
    FROM dbo.child_attempts
    WHERE child_id = ?
    ORDER BY attempted_at DESC
");
$stmt->execute([$childId]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

$correctCount = 0;
foreach ($recent as $r) if ((int)$r['is_correct'] === 1) $correctCount++;
$accuracy = count($recent) ? ($correctCount / count($recent)) : 0.6;

// difficulty
$targetDifficulty = $finalLevel;
if ($accuracy < 0.4) $targetDifficulty = max(1, $finalLevel - 1);
if ($accuracy > 0.7) $targetDifficulty = min(3, $finalLevel + 1);

// pick one FULL MCQ row
$stmt = $pdo->prepare("
    SELECT TOP 1 *
    FROM dbo.activities
    WHERE category = ?
      AND difficulty = ?
      AND option_a IS NOT NULL AND LTRIM(RTRIM(option_a)) <> ''
      AND option_b IS NOT NULL AND LTRIM(RTRIM(option_b)) <> ''
      AND option_c IS NOT NULL AND LTRIM(RTRIM(option_c)) <> ''
      AND option_d IS NOT NULL AND LTRIM(RTRIM(option_d)) <> ''
      AND correct_option IN ('A','B','C','D')
    ORDER BY NEWID()
");
$stmt->execute([$area, $targetDifficulty]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

// fallback any difficulty same area
if (!$activity) {
    $stmt = $pdo->prepare("
        SELECT TOP 1 *
        FROM dbo.activities
        WHERE category = ?
          AND option_a IS NOT NULL AND LTRIM(RTRIM(option_a)) <> ''
          AND option_b IS NOT NULL AND LTRIM(RTRIM(option_b)) <> ''
          AND option_c IS NOT NULL AND LTRIM(RTRIM(option_c)) <> ''
          AND option_d IS NOT NULL AND LTRIM(RTRIM(option_d)) <> ''
          AND correct_option IN ('A','B','C','D')
        ORDER BY NEWID()
    ");
    $stmt->execute([$area]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$activity) die("No complete activities found in dbo.activities.");

// ---- force strings
$optA  = (string)$activity['option_a'];
$optB  = (string)$activity['option_b'];
$optC  = (string)$activity['option_c'];
$optD  = (string)$activity['option_d'];
$title = (string)$activity['title'];
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Activity</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .card{max-width:820px;margin:30px auto;background:#fff;border:1px solid #ddd;border-radius:14px;padding:18px;}
  .pill{display:inline-block;background:#f2f2f2;border-radius:999px;padding:6px 10px;margin-right:8px;margin-top:6px;}
  .choice{display:block;margin:10px 0;font-size:18px;}
  .btn{padding:10px 14px;border-radius:10px;border:1px solid #ccc;background:#f8f8f8;cursor:pointer;}
  .btn-primary{background:#C0C000;color:#fff;border-color:#C0C000;}
</style>
</head>
<body>

<div class="card">
  <h2 style="margin-top:0;">Activity</h2>
  <div>
    <div class="pill"><b>Child:</b> <?= htmlspecialchars($child['name']) ?></div>
    <div class="pill"><b>Area:</b> <?= htmlspecialchars($area) ?></div>
    <div class="pill"><b>Level:</b> <?= (int)$finalLevel ?></div>
    <div class="pill"><b>Accuracy:</b> <?= (int)round($accuracy*100) ?>%</div>
  </div>

  <?php if ($errors): ?>
    <div style="color:red;margin-top:12px;">
      <?php foreach ($errors as $e): ?>
        <p style="margin:0 0 6px;"><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <hr>

  <h3><?= htmlspecialchars($title) ?></h3>

  <?php if ($optA==='' || $optB==='' || $optC==='' || $optD===''): ?>
    <div style="color:red;">
      Options are missing for this activity_id = <?= (int)$activity['activity_id'] ?>.
      <pre><?php print_r($activity); ?></pre>
    </div>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="child_id" value="<?= $childId ?>">
      <input type="hidden" name="activity_id" value="<?= (int)$activity['activity_id'] ?>">

      <label class="choice"><input type="radio" name="answer" value="A" required> <?= htmlspecialchars($optA) ?></label>
      <label class="choice"><input type="radio" name="answer" value="B"> <?= htmlspecialchars($optB) ?></label>
      <label class="choice"><input type="radio" name="answer" value="C"> <?= htmlspecialchars($optC) ?></label>
      <label class="choice"><input type="radio" name="answer" value="D"> <?= htmlspecialchars($optD) ?></label>

      <div style="margin-top:14px;">
        <button class="btn btn-primary" type="submit">Submit</button>
        <a class="btn" href="learning_home.php?child_id=<?= $childId ?>">Back</a>
      </div>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
