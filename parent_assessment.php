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

// تأكد الطفل تابع لنفس ال parent
$chk = $pdo->prepare("SELECT child_id FROM children WHERE child_id=? AND parent_id=?");
$chk->execute([$childId, $parentId]);
if (!$chk->fetch()) die("Invalid child.");

// جيب الأسئلة من DB
$questions = $pdo->query("SELECT question_id, question_text, area, weight FROM parent_questions ORDER BY question_id")->fetchAll();
if (!$questions) die("No parent_questions found. Please insert questions first.");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answer'] ?? [];

    // تحقق إنو جاوبوا على كل الأسئلة
    foreach ($questions as $q) {
        $qid = (int)$q['question_id'];
        if (!isset($answers[$qid])) {
            $errors[] = "Please answer all questions.";
            break;
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            // امسح تقييم قديم (اختياري)
            $pdo->prepare("DELETE FROM parent_assessment WHERE child_id=?")->execute([$childId]);
            $pdo->prepare("DELETE FROM child_weaknesses WHERE child_id=?")->execute([$childId]);

            // تجميع حسب area
            $areaSum = [];  // مجموع (answer * weight)
            $areaW   = [];  // مجموع weights

            foreach ($questions as $q) {
                $qid   = (int)$q['question_id'];
                $area  = (string)$q['area'];
                $w     = (int)$q['weight'];
                $val   = (int)$answers[$qid]; // 1/3/5

                // خزّن الإجابة
                $pdo->prepare("INSERT INTO parent_assessment (child_id, question_id, answer_value) VALUES (?, ?, ?)")
                    ->execute([$childId, $qid, $val]);

                $areaSum[$area] = ($areaSum[$area] ?? 0) + ($val * $w);
                $areaW[$area]   = ($areaW[$area] ?? 0) + $w;
            }

            // حساب parent_level العام
            $total = 0; $tw = 0;
            foreach ($areaSum as $a => $s) {
                $total += $s;
                $tw    += $areaW[$a];
            }
            $avg = $tw ? ($total / $tw) : 0; // تقريباً بين 1..5

            if ($avg < 2.0) $parentLevel = 1;
            elseif ($avg < 3.5) $parentLevel = 2;
            else $parentLevel = 3;

            // خزّن parent_level في children
            $pdo->prepare("UPDATE children SET parent_level=? WHERE child_id=?")->execute([$parentLevel, $childId]);

            // خزّن child_weaknesses (كل ما avg أقل => ضعف أعلى)
            foreach ($areaSum as $a => $s) {
                $aAvg = $s / max(1, $areaW[$a]);

                // severity: 3 severe, 2 moderate, 1 mild
                if ($aAvg < 2.0) $sev = 3;
                elseif ($aAvg < 3.5) $sev = 2;
                else $sev = 1;

                $pdo->prepare("INSERT INTO child_weaknesses (child_id, area, severity) VALUES (?, ?, ?)")
                    ->execute([$childId, $a, $sev]);
            }

            $pdo->commit();

            // بعد الاستبيان → نروح على Diagnostic Test
            header("Location: diagnostic_test.php?child_id=" . $childId);
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Parent Assessment</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container" style="width:720px;">
  <div class="topbar">
    <h2 style="margin:0;">Parent Assessment</h2>
    <a class="smallbtn" href="dashboard.php">Back</a>
  </div>

  <p>Child ID: <b><?= $childId ?></b></p>
  <p>Please answer using: Never (1) / Sometimes (3) / Often (5).</p>

  <?php if ($errors): ?>
    <div class="msg-error">
      <?php foreach ($errors as $e): ?>
        <p><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <?php foreach ($questions as $q): ?>
      <div class="card">
        <b><?= htmlspecialchars($q['question_text']) ?></b>
        <div style="margin-top:8px;">
          <?php $qid = (int)$q['question_id']; ?>
          <label><input type="radio" name="answer[<?= $qid ?>]" value="1"> Never (1)</label>
          <label><input type="radio" name="answer[<?= $qid ?>]" value="3"> Sometimes (3)</label>
          <label><input type="radio" name="answer[<?= $qid ?>]" value="5"> Often (5)</label>
        </div>
      </div>
    <?php endforeach; ?>

    <button type="submit">Submit Assessment</button>
  </form>
</div>

</body>
</html>
