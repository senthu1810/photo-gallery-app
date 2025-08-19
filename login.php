<?php 
session_start();
include("includes/config.php"); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    //  JS Validation
    function validateLoginForm() {
      let email = document.forms["loginForm"]["email"].value.trim();
      let password = document.forms["loginForm"]["password"].value.trim();

      if (email === "" || password === "") {
        alert("Both email and password are required!");
        return false;
      }
      let emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
      if (!email.match(emailPattern)) {
        alert("Enter a valid email address!");
        return false;
      }
      return true;
    }
  </script>
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card p-4 shadow col-md-6 mx-auto">
    <h3 class="mb-4">Login</h3>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                header("Location: dashboard.php");
                exit();
            } else {
                echo "<div class='alert alert-danger'>Invalid password!</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>No account found with this email!</div>";
        }
    }
    ?>

    <form name="loginForm" method="POST" action="" onsubmit="return validateLoginForm()">
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control">
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control">
      </div>
      <button type="submit" class="btn btn-success">Login</button>
      <a href="signup.php" class="btn btn-link">Don’t have an account? Sign Up</a>
    </form>
  </div>
</div>

</body>
</html>
