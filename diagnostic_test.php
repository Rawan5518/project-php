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

// 1) Validate child belongs to this parent + get parent_level
$stmt = $pdo->prepare("SELECT child_id, parent_level FROM dbo.children WHERE child_id=? AND parent_id=?");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch();
if (!$child) die("Invalid child");

$parentLevel = (int)($child['parent_level'] ?? 2);
if ($parentLevel <= 0) $parentLevel = 2;

// 2) Get weakest area (optional)
$stmt = $pdo->prepare("
    SELECT TOP 1 area
    FROM dbo.child_weaknesses
    WHERE child_id=?
    ORDER BY severity DESC, area
");
$stmt->execute([$childId]);
$weakArea = $stmt->fetchColumn() ?: null;

// 3) Load questions (MCQ only + must have options)
function loadQuestions(PDO $pdo, ?string $area, int $limit = 5): array {
    if ($area) {
        $stmt = $pdo->prepare("
            SELECT TOP ($limit)
                q.diag_question_id, q.question_text, q.type, q.media_path, q.weight,
                q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option
            FROM dbo.diag_mapping m
            JOIN dbo.diag_questions q ON q.diag_question_id = m.diag_question_id
            WHERE m.difficulty_area = ?
              AND q.type = 'mcq'
              AND q.option_a IS NOT NULL
              AND q.option_b IS NOT NULL
              AND q.option_c IS NOT NULL
              AND q.option_d IS NOT NULL
              AND q.correct_option IS NOT NULL
            ORDER BY NEWID()
        ");
        $stmt->execute([$area]);
        $rows = $stmt->fetchAll();
        if (count($rows) >= $limit) return $rows;

        // If not enough questions in that area, fill the rest randomly from any area
        $need = $limit - count($rows);
        $stmt2 = $pdo->prepare("
            SELECT TOP ($need)
                diag_question_id, question_text, type, media_path, weight,
                option_a, option_b, option_c, option_d, correct_option
            FROM dbo.diag_questions
            WHERE type = 'mcq'
              AND option_a IS NOT NULL
              AND option_b IS NOT NULL
              AND option_c IS NOT NULL
              AND option_d IS NOT NULL
              AND correct_option IS NOT NULL
              AND diag_question_id NOT IN (" . implode(",", array_map(fn($r)=> (int)$r['diag_question_id'], $rows) ?: [0]) . ")
            ORDER BY NEWID()
        ");
        $stmt2->execute();
        return array_merge($rows, $stmt2->fetchAll());
    }

    // no area -> random questions
    $stmt = $pdo->prepare("
        SELECT TOP ($limit)
            diag_question_id, question_text, type, media_path, weight,
            option_a, option_b, option_c, option_d, correct_option
        FROM dbo.diag_questions
        WHERE type = 'mcq'
          AND option_a IS NOT NULL
          AND option_b IS NOT NULL
          AND option_c IS NOT NULL
          AND option_d IS NOT NULL
          AND correct_option IS NOT NULL
        ORDER BY NEWID()
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

$questions = loadQuestions($pdo, $weakArea, 5);
if (!$questions) die("No MCQ diagnostic questions found. Check diag_questions data.");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answer'] ?? [];

    // Validate: must answer every question
    foreach ($questions as $q) {
        $qid = (int)$q['diag_question_id'];
        if (!isset($answers[$qid]) || !in_array($answers[$qid], ['A','B','C','D'], true)) {
            $errors[] = "Please answer all questions.";
            break;
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            // Remove old attempts (optional)
            $pdo->prepare("DELETE FROM dbo.diag_attempts WHERE child_id=?")->execute([$childId]);

            $totalWeight = 0;
            $scoreSum = 0;

            foreach ($questions as $q) {
                $qid = (int)$q['diag_question_id'];
                $user = (string)$answers[$qid];            // A/B/C/D
                $correct = (string)$q['correct_option'];   // A/B/C/D
                $weight = (int)($q['weight'] ?? 1);
                if ($weight <= 0) $weight = 1;

                $isCorrect = ($user === $correct) ? 1 : 0;
                $score = $isCorrect ? $weight : 0;

                $pdo->prepare("
                    INSERT INTO dbo.diag_attempts (child_id, diag_question_id, is_correct, score)
                    VALUES (?, ?, ?, ?)
                ")->execute([$childId, $qid, $isCorrect, $score]);

                $totalWeight += $weight;
                $scoreSum += $score;
            }

            $percent = ($totalWeight > 0) ? ($scoreSum / $totalWeight) * 100 : 0;

            // Convert to child_level
            if ($percent < 40)      $childLevel = 1;
            elseif ($percent < 70)  $childLevel = 2;
            else                    $childLevel = 3;

            // final_level = average parent & child levels
            $finalLevel = (int)round(($parentLevel + $childLevel) / 2);

            $pdo->prepare("
                UPDATE dbo.children
                SET child_level=?, final_level=?
                WHERE child_id=?
            ")->execute([$childLevel, $finalLevel, $childId]);

            $pdo->commit();

            header("Location: diagnostic_result.php?child_id=" . $childId);
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Diagnostic Test</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .choice { display:block; margin:8px 0; }
</style>
</head>
<body>

<div class="container" style="width:720px;">
  <div class="topbar">
    <h2 style="margin:0;">Diagnostic Test</h2>
    <a class="smallbtn" href="dashboard.php">Back</a>
  </div>

  <p><b>Child ID:</b> <?= $childId ?></p>
  <?php if ($weakArea): ?>
    <p><b>Focused area:</b> <?= htmlspecialchars($weakArea) ?></p>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="msg-error">
      <?php foreach ($errors as $e): ?>
        <p style="color:red;"><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <?php foreach ($questions as $q): ?>
      <?php $qid = (int)$q['diag_question_id']; ?>
      <div class="card">
        <b><?= htmlspecialchars($q['question_text']) ?></b>

        <?php if (!empty($q['media_path'])): ?>
          <div style="margin-top:10px;">
            <img src="<?= htmlspecialchars($q['media_path']) ?>" alt="media" style="max-width:100%; border-radius:8px;">
          </div>
        <?php endif; ?>

        <div style="margin-top:10px;">
          <label class="choice">
            <input type="radio" name="answer[<?= $qid ?>]" value="A" required>
            <?= htmlspecialchars($q['option_a']) ?>
          </label>
          <label class="choice">
            <input type="radio" name="answer[<?= $qid ?>]" value="B">
            <?= htmlspecialchars($q['option_b']) ?>
          </label>
          <label class="choice">
            <input type="radio" name="answer[<?= $qid ?>]" value="C">
            <?= htmlspecialchars($q['option_c']) ?>
          </label>
          <label class="choice">
            <input type="radio" name="answer[<?= $qid ?>]" value="D">
            <?= htmlspecialchars($q['option_d']) ?>
          </label>
        </div>
      </div>
    <?php endforeach; ?>

    <button type="submit">Finish Test</button>
  </form>

</div>

</body>
</html>
