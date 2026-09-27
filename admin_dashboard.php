<?php require_once __DIR__ . '/admin_guard.php'; ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container" style="width:900px;">
  <div class="topbar">
    <h2 style="margin:0;">Admin Dashboard</h2>
    <a class="smallbtn" href="admin_logout.php">Logout</a>
  </div>

  <div class="admin-cards">
    <a class="admin-card" href="admin_questions.php">
      <div class="admin-card-icon">📝</div>
      <div>
        <div class="admin-card-title">Manage Questions</div>
        <div class="admin-card-sub">Add / edit / delete questions</div>
      </div>
      <div class="admin-card-arrow">›</div>
    </a>

    <a class="admin-card" href="admin_parents.php">
      <div class="admin-card-icon">👨‍👩‍👧‍👦</div>
      <div>
        <div class="admin-card-title">Parents & Children</div>
        <div class="admin-card-sub">View parents and their kids</div>
      </div>
      <div class="admin-card-arrow">›</div>
    </a>
  </div>
</div>


</body>
</html>