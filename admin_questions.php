<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$success = "";

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$editId = (int)($_GET['edit_id'] ?? 0);

// ===================== DELETE =====================
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM dbo.activities WHERE activity_id = ?");
        $stmt->execute([$id]);
        header("Location: admin_questions.php?msg=deleted");
        exit;
    }
}

// ===================== ADD / UPDATE =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add','update'], true)) {

    $id         = (int)($_POST['activity_id'] ?? 0);
    $title      = trim((string)($_POST['title'] ?? ''));
    $category   = trim((string)($_POST['category'] ?? ''));
    $difficulty = (int)($_POST['difficulty'] ?? 1);

    $optA = trim((string)($_POST['option_a'] ?? ''));
    $optB = trim((string)($_POST['option_b'] ?? ''));
    $optC = trim((string)($_POST['option_c'] ?? ''));
    $optD = trim((string)($_POST['option_d'] ?? ''));

    $correct = strtoupper(trim((string)($_POST['correct_option'] ?? '')));

    // Validation
    if ($title === '') $errors[] = "Title is required.";
    if ($category === '') $errors[] = "Category is required.";
    if ($difficulty < 1 || $difficulty > 3) $errors[] = "Difficulty must be 1-3.";
    if ($optA === '' || $optB === ''||  $optC === '' || $optD === '') $errors[] = "All options A-D are required.";
    if (!in_array($correct, ['A','B','C','D'], true)) $errors[] = "Correct option must be A/B/C/D.";

    if (!$errors) {
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO dbo.activities (title, category, difficulty, option_a, option_b, option_c, option_d, correct_option)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $category, $difficulty, $optA, $optB, $optC, $optD, $correct]);
            header("Location: admin_questions.php?msg=added");
            exit;

        } else { // update
            if ($id <= 0) $errors[] = "Missing activity_id for update.";
            if (!$errors) {
                $stmt = $pdo->prepare("
                    UPDATE dbo.activities
                    SET title=?, category=?, difficulty=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=?
                    WHERE activity_id=?
                ");
                $stmt->execute([$title, $category, $difficulty, $optA, $optB, $optC, $optD, $correct, $id]);
                header("Location: admin_questions.php?msg=updated");
                exit;
            }
        }
    }
}

// ===================== LOAD EDIT ROW =====================
$editRow = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM dbo.activities WHERE activity_id=?");
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editRow) $errors[] = "Question not found for edit.";
}

// ===================== LIST + SEARCH =====================
$q = trim((string)($_GET['q'] ?? ''));
$cat = trim((string)($_GET['cat'] ?? ''));
$diff = (int)($_GET['diff'] ?? 0);

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(title LIKE ? OR option_a LIKE ? OR option_b LIKE ? OR option_c LIKE ? OR option_d LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if ($cat !== '') {
    $where[] = "category = ?";
    $params[] = $cat;
}
if ($diff >= 1 && $diff <= 3) {
    $where[] = "difficulty = ?";
    $params[] = $diff;
}

$sql = "SELECT TOP 200 activity_id, title, category, difficulty, correct_option FROM dbo.activities";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY activity_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// categories for filter dropdown
$cats = $pdo->query("SELECT DISTINCT category FROM dbo.activities ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$msg = (string)($_GET['msg'] ?? '');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Questions</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .grid{display:grid;grid-template-columns: 1fr 1fr; gap:14px;}
  @media (max-width: 900px){ .grid{grid-template-columns: 1fr;} }
  .btn-danger{background:#dc3545;color:#fff;border-color:#dc3545;}
  .btn-sm{padding:6px 10px;border-radius:8px;font-size:14px;}
  .muted{color:#666;font-size:14px;}
</style>
</head>
<body>

<div class="container" style="width:1100px;">
  <div class="topbar">
    <h2 style="margin:0;">Manage Questions</h2>
    <div>
      <a class="smallbtn" href="admin_dashboard.php">Back</a>
      <a class="smallbtn" href="admin_logout.php">Logout</a>
    </div>
  </div>

  <?php if ($msg === 'added'): ?>
    <div class="card"><b>✅ Added successfully.</b></div>
  <?php elseif ($msg === 'updated'): ?>
    <div class="card"><b>✅ Updated successfully.</b></div>
  <?php elseif ($msg === 'deleted'): ?>
    <div class="card"><b>🗑️ Deleted successfully.</b></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="card" style="border-color:#dc3545;">
      <?php foreach ($errors as $e): ?>
        <div style="color:#dc3545;"><?= h($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="grid">

    <!-- ADD / EDIT FORM -->
    <div class="card">
      <h3 style="margin-top:0;"><?= $editRow ? "Edit Question #".(int)$editRow['activity_id'] : "Add New Question" ?></h3>

      <form method="post">
        <input type="hidden" name="action" value="<?= $editRow ? "update" : "add" ?>">
        <?php if ($editRow): ?>
          <input type="hidden" name="activity_id" value="<?= (int)$editRow['activity_id'] ?>">
        <?php endif; ?>

        <label>Title / Question</label>
        <input type="text" name="title" value="<?= h($editRow['title'] ?? '') ?>" required>

        <div style="display:flex; gap:10px;">
          <div style="flex:1;">
            <label>Category</label>
            <input type="text" name="category" value="<?= h($editRow['category'] ?? '') ?>" placeholder="memory / numbers / reading" required>
          </div>
          <div style="width:160px;">
            <label>Difficulty (1-3)</label>
            <input type="number" min="1" max="3" name="difficulty" value="<?= (int)($editRow['difficulty'] ?? 1) ?>" required>
          </div>
        </div>

        <label>Option A</label>
        <input type="text" name="option_a" value="<?= h($editRow['option_a'] ?? '') ?>" required>

        <label>Option B</label>
        <input type="text" name="option_b" value="<?= h($editRow['option_b'] ?? '') ?>" required>

        <label>Option C</label>
        <input type="text" name="option_c" value="<?= h($editRow['option_c'] ?? '') ?>" required>

        <label>Option D</label>
        <input type="text" name="option_d" value="<?= h($editRow['option_d'] ?? '') ?>" required>

        <label>Correct Option</label>
        <select name="correct_option" required>
          <?php
            $cur = strtoupper((string)($editRow['correct_option'] ?? 'A'));
            foreach (['A','B','C','D'] as $c) {
              $sel = ($cur === $c) ? 'selected' : '';
              echo "<option value=\"$c\" $sel>$c</option>";
            }
          ?>
        </select>

        <div style="margin-top:12px; display:flex; gap:10px;">
          <button class="btn btn-primary" type="submit"><?= $editRow ? "Save Changes" : "Add Question" ?></button>
          <?php if ($editRow): ?>
            <a class="btn" href="admin_questions.php">Cancel</a>
          <?php endif; ?>
        </div>

        <p class="muted" style="margin-top:10px;">
          Tip: استعمل category ثابتة مثل: <b>memory</b> / <b>numbers</b> / <b>reading</b> (لا تغيّر الإملاء).
        </p>
      </form>
    </div>

    <!-- LIST -->
    <div class="card">
      <h3 style="margin-top:0;">Questions List</h3>
      <form method="get" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
        <input type="text" name="q" placeholder="Search..." value="<?= h($q) ?>" style="flex:1; min-width:200px;">
        <select name="cat">
          <option value="">All categories</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= h($c) ?>" <?= ($cat===$c?'selected':'') ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="diff">
          <option value="0">All difficulty</option>
          <option value="1" <?= ($diff===1?'selected':'') ?>>1</option>
          <option value="2" <?= ($diff===2?'selected':'') ?>>2</option>
          <option value="3" <?= ($diff===3?'selected':'') ?>>3</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        <a class="btn btn-sm" href="admin_questions.php">Reset</a>
      </form>

      <div class="muted">Showing up to 200 latest results.</div>

      <table class="table" style="margin-top:10px;">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Category</th>
            <th>Diff</th>
            <th>Correct</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6">No questions found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['activity_id'] ?></td>
              <td><?= h($r['title']) ?></td>
              <td><?= h($r['category']) ?></td>
              <td><?= (int)$r['difficulty'] ?></td>
              <td><?= h($r['correct_option']) ?></td>
              <td style="white-space:nowrap;">
                <a class="btn btn-sm" href="admin_questions.php?edit_id=<?= (int)$r['activity_id'] ?>">Edit</a>
                <a class="btn btn-danger btn-sm"
                   href="admin_questions.php?action=delete&id=<?= (int)$r['activity_id'] ?>"
                   onclick="return confirm('Delete this question?');">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>

    </div>

  </div>
</div>

</body>
</html>