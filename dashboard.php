<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card p-4 shadow col-md-6 mx-auto text-center">
    <h3>Welcome, <?php echo $_SESSION['user_name']; ?> 🎉</h3>
    <p>You are now logged in.</p>
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>
</div>

</body>
</html>
