<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/admin.php';

require_auth();
require_admin();

$success = null;
$error = null;

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $level = $_POST['level'] ?? 'beginner';
        $link = trim($_POST['link'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$id || $title === '' || $description === '' || $link === '') {
            $error = "Champs invalides (id, title, description, link).";
        } else {
            $allowedLevels = ['beginner','intermediate','advanced'];
            if (!in_array($level, $allowedLevels, true)) {
                $level = 'beginner';
            }

            $upd = $pdo->prepare("
                UPDATE modules
                SET title = ?, description = ?, level = ?, link = ?, is_active = ?
                WHERE id = ?
            ");
            $upd->execute([$title, $description, $level, $link, $is_active, $id]);
            $success = "Module mis à jour.";
        }
    }
}

// Fetch modules
$stmt = $pdo->query("SELECT id, code, title, description, level, link, is_active, created_at FROM modules ORDER BY id ASC");
$modules = $stmt->fetchAll();

include __DIR__ . '/../_partials/header.php';
?>

<div class="card">
  <div class="hx">Admin — Modules</div>
  <p class="subtle">Activer/désactiver des modules et modifier leur contenu.</p>

  <?php if ($success): ?>
    <p class="badge badge-ok"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>

  <?php if ($error): ?>
    <p class="badge badge-danger"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
</div>

<?php foreach ($modules as $m): ?>
  <div class="card">
    <div class="spread">
      <div>
        <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>
        <p class="subtle" style="margin:0">
          Code: <strong><?= htmlspecialchars($m['code'] ?? '') ?></strong> —
          ID: <strong><?= (int)$m['id'] ?></strong>
        </p>
      </div>
      <div>
        <?php if ((int)$m['is_active'] === 1): ?>
          <span class="badge badge-ok">Actif</span>
        <?php else: ?>
          <span class="badge badge-warn">Inactif</span>
        <?php endif; ?>
      </div>
    </div>

    <form method="post" class="mt-2">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">

      <label class="subtle">Titre</label>
      <input class="input" name="title" value="<?= htmlspecialchars($m['title']) ?>" required>

      <label class="subtle mt-2">Description</label>
      <textarea class="input" name="description" rows="3" required><?= htmlspecialchars($m['description']) ?></textarea>

      <div class="row mt-2">
        <div style="flex:1; min-width:200px">
          <label class="subtle">Niveau</label>
          <select class="input" name="level">
            <option value="beginner" <?= $m['level']==='beginner' ? 'selected' : '' ?>>beginner</option>
            <option value="intermediate" <?= $m['level']==='intermediate' ? 'selected' : '' ?>>intermediate</option>
            <option value="advanced" <?= $m['level']==='advanced' ? 'selected' : '' ?>>advanced</option>
          </select>
        </div>

        <div style="flex:2; min-width:260px">
          <label class="subtle">Lien</label>
          <input class="input" name="link" value="<?= htmlspecialchars($m['link']) ?>" required>
        </div>
      </div>

      <label class="subtle mt-2">
        <input type="checkbox" name="is_active" <?= (int)$m['is_active'] === 1 ? 'checked' : '' ?>>
        Module actif
      </label>

      <div class="mt-2">
        <button class="btn btn-primary" type="submit">Enregistrer</button>
      </div>
    </form>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../_partials/footer.php'; ?>