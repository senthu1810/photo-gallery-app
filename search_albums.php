<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  exit("Unauthorized");
}
require_once __DIR__ . "/includes/config.php";

$user_id = (int)$_SESSION['user_id'];
$q = trim($_GET['q'] ?? "");

$stmt = $conn->prepare("SELECT id, name, updated_at FROM albums WHERE user_id=? AND name LIKE CONCAT('%', ?, '%') ORDER BY updated_at DESC");
$stmt->bind_param("is", $user_id, $q);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  echo '<div class="col-12"><p class="text-muted text-center py-5">No albums found.</p></div>';
} else {
  while ($a = $res->fetch_assoc()) {
    ?>
    <div class="col-12 col-sm-6 col-lg-4">
      <div class="card card-album shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start">
            <h5 class="album-title mb-2" id="title-<?php echo $a['id']; ?>">
              <?php echo htmlspecialchars($a['name']); ?>
            </h5>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" title="Rename" onclick="startRename(<?php echo $a['id']; ?>)">
                <i class="bi bi-pencil-square"></i>
              </button>
              <form method="POST" action="" onsubmit="return confirm('Delete this album and all its contents?');">
                <input type="hidden" name="action" value="delete_album">
                <input type="hidden" name="album_id" value="<?php echo $a['id']; ?>">
                <button type="submit" class="btn btn-outline-danger" title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </div>
          <div class="text-muted small mb-3">
            Updated: <?php echo htmlspecialchars($a['updated_at']); ?>
          </div>
          <div class="mt-auto d-flex justify-content-between">
            <a class="btn btn-success btn-sm btn-rounded" href="album.php?id=<?php echo $a['id']; ?>">
              <i class="bi bi-eye me-1"></i>Open
            </a>
            <div id="rename-controls-<?php echo $a['id']; ?>" class="d-none">
              <button class="btn btn-primary btn-sm btn-rounded me-1" onclick="saveRename(<?php echo $a['id']; ?>)">Save</button>
              <button class="btn btn-secondary btn-sm btn-rounded" onclick="cancelRename(<?php echo $a['id']; ?>)">Cancel</button>
            </div>
          </div>
        </div>
      </div>
      <!-- Hidden rename form -->
      <form id="rename-form-<?php echo $a['id']; ?>" method="POST" action="" class="d-none">
        <input type="hidden" name="action" value="rename_album">
        <input type="hidden" name="album_id" value="<?php echo $a['id']; ?>">
        <input type="hidden" name="new_name" id="new-name-<?php echo $a['id']; ?>">
      </form>
    </div>
    <?php
  }
}
$stmt->close();
