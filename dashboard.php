<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
require_once __DIR__ . "/includes/config.php";

$user_id = (int)$_SESSION['user_id'];
$msg = "";

/** Helpers */
function slugify($text) {
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  return strtolower(!empty($text) ? $text : 'album');
}

$uploadsBase = __DIR__ . "/uploads";

/** Create album */
if (isset($_POST['action']) && $_POST['action'] === 'create_album') {
  $name = trim($_POST['album_name'] ?? "");
  if ($name === "") {
    $msg = "Album name is required.";
  } else {
    $stmt = $conn->prepare("INSERT INTO albums (user_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $name);
    if ($stmt->execute()) {
      $album_id = $stmt->insert_id;
      // Ensure album folder exists
      if (!is_dir($uploadsBase)) { @mkdir($uploadsBase, 0777, true); }
      $albumDir = $uploadsBase . "/$album_id";
      if (!is_dir($albumDir)) { @mkdir($albumDir, 0777, true); }
      $msg = "Album created successfully.";
    } else {
      $msg = "Error creating album: " . $conn->error;
    }
    $stmt->close();
  }
}

/** Rename album (inline) */
if (isset($_POST['action']) && $_POST['action'] === 'rename_album') {
  $album_id = (int)($_POST['album_id'] ?? 0);
  $new_name = trim($_POST['new_name'] ?? "");
  if ($album_id && $new_name !== "") {
    $stmt = $conn->prepare("UPDATE albums SET name=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sii", $new_name, $album_id, $user_id);
    if ($stmt->execute()) {
      $msg = "Album renamed.";
    } else {
      $msg = "Error renaming album: " . $conn->error;
    }
    $stmt->close();
  } else {
    $msg = "Invalid rename request.";
  }
}

/** Delete album (and its media by FK cascade) */
if (isset($_POST['action']) && $_POST['action'] === 'delete_album') {
  $album_id = (int)($_POST['album_id'] ?? 0);
  if ($album_id) {
    // Verify ownership
    $check = $conn->prepare("SELECT id FROM albums WHERE id=? AND user_id=?");
    $check->bind_param("ii", $album_id, $user_id);
    $check->execute(); $check->store_result();
    if ($check->num_rows === 1) {
      // Delete DB row (media rows are deleted by FK)
      $del = $conn->prepare("DELETE FROM albums WHERE id=? AND user_id=?");
      $del->bind_param("ii", $album_id, $user_id);
      if ($del->execute()) {
        // Delete folder from disk
        $albumDir = $uploadsBase . "/$album_id";
        if (is_dir($albumDir)) {
          // Recursively remove
          $it = new RecursiveDirectoryIterator($albumDir, RecursiveDirectoryIterator::SKIP_DOTS);
          $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
          foreach ($files as $file) {
            $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
          }
          @rmdir($albumDir);
        }
        $msg = "Album deleted.";
      } else {
        $msg = "Error deleting album: " . $conn->error;
      }
      $del->close();
    } else {
      $msg = "Album not found or not yours.";
    }
    $check->close();
  }
}

/** Fetch albums */
$albums = [];
$stmt = $conn->prepare("SELECT id, name, created_at, updated_at FROM albums WHERE user_id=? ORDER BY updated_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $albums[] = $row; }
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Albums — Photo Gallery App</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { font-family: 'Poppins', sans-serif; background:#f7f8fc; }
  .topbar { background: linear-gradient(135deg,#6a11cb,#2575fc); color:#fff; }
  .card-album { transition: transform .25s ease, box-shadow .25s ease; }
  .card-album:hover { transform: translateY(-4px); box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,.1); }
  .album-title[contenteditable="true"] { outline: 2px dashed #ffd76a; background: #fffbe6; }
  .btn-rounded { border-radius: 50px; }
</style>
</head>
<body>

<!-- Top bar -->
<nav class="navbar topbar">
  <div class="container">
    <span class="navbar-brand fw-bold">📸 Photo Gallery</span>
    <div class="d-flex align-items-center gap-2">
      <span class="me-2">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
      <a href="logout.php" class="btn btn-light btn-sm btn-rounded" onclick="return confirm('Logout now?')"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <?php if ($msg): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($msg); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Create Album -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Albums</h3>
    <button class="btn btn-primary btn-rounded" data-bs-toggle="modal" data-bs-target="#createAlbumModal">
      <i class="bi bi-plus-lg me-1"></i>Create Album
    </button>
  </div>

  <!-- Search Bar -->
<div class="input-group mb-3">
  <span class="input-group-text"><i class="bi bi-search"></i></span>
  <input type="text" id="albumSearch" class="form-control" placeholder="Search albums...">
</div>

  <!-- Albums grid -->
  <div class="row g-3" id="albumsContainer">
    <?php if (empty($albums)): ?>
      <div class="col-12">
        <div class="text-center text-muted py-5">
          <i class="bi bi-images display-4"></i>
          <p class="mt-3">No albums yet. Create your first album!</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($albums as $a): ?>
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
          <!-- Hidden form for rename -->
          <form id="rename-form-<?php echo $a['id']; ?>" method="POST" action="" class="d-none">
            <input type="hidden" name="action" value="rename_album">
            <input type="hidden" name="album_id" value="<?php echo $a['id']; ?>">
            <input type="hidden" name="new_name" id="new-name-<?php echo $a['id']; ?>">
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Create Album Modal -->
<div class="modal fade" id="createAlbumModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="" class="modal-content" onsubmit="return confirm('Create this album?');">
      <div class="modal-header">
        <h5 class="modal-title">Create Album</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create_album">
        <div class="mb-3">
          <label class="form-label">Album Name</label>
          <input type="text" class="form-control" name="album_name" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit">Create</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function startRename(id) {
    const title = document.getElementById('title-' + id);
    title.setAttribute('contenteditable', 'true');
    title.focus();
    document.getElementById('rename-controls-' + id).classList.remove('d-none');
  }
  function saveRename(id) {
    const title = document.getElementById('title-' + id);
    const newName = title.innerText.trim();
    if (!newName) {
      alert('Album name cannot be empty.'); return;
    }
    if (!confirm('Rename this album to "' + newName + '"?')) return;
    document.getElementById('new-name-' + id).value = newName;
    document.getElementById('rename-form-' + id).submit();
  }
  function cancelRename(id) {
    const title = document.getElementById('title-' + id);
    title.removeAttribute('contenteditable');
    location.reload(); // simple reset
  }
</script>

<script>
document.getElementById('albumSearch').addEventListener('keyup', function() {
  const query = this.value;
  fetch('search_albums.php?q=' + encodeURIComponent(query))
    .then(res => res.text())
    .then(data => {
      document.getElementById('albumsContainer').innerHTML = data;
    })
    .catch(err => console.error(err));
});
</script>

</body>
</html>
