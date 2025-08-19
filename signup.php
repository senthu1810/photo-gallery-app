<?php include("includes/config.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    // JS Validation
    function validateSignupForm() {
      let name = document.forms["signupForm"]["name"].value.trim();
      let phone = document.forms["signupForm"]["phone"].value.trim();
      let email = document.forms["signupForm"]["email"].value.trim();
      let password = document.forms["signupForm"]["password"].value.trim();

      if (name === "" || phone === "" || email === "" || password === "") {
        alert("All fields are required!");
        return false;
      }
      if (!/^\d{10}$/.test(phone)) {
        alert("Phone number must be 10 digits!");
        return false;
      }
      let emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
      if (!email.match(emailPattern)) {
        alert("Enter a valid email address!");
        return false;
      }
      if (password.length < 6) {
        alert("Password must be at least 6 characters!");
        return false;
      }
      return true;
    }
  </script>
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card p-4 shadow col-md-6 mx-auto">
    <h3 class="mb-4">Sign Up</h3>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($name) || empty($phone) || empty($email) || empty($password)) {
            echo "<div class='alert alert-danger'>All fields are required!</div>";
        } else {
            $sql_check = "SELECT * FROM users WHERE email='$email'";
            $result = $conn->query($sql_check);
            if ($result->num_rows > 0) {
                echo "<div class='alert alert-warning'>Email already exists!</div>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, phone, email, password) 
                        VALUES ('$name', '$phone', '$email', '$hashed_password')";

                if ($conn->query($sql) === TRUE) {
                    echo "<div class='alert alert-success'>Registration successful! <a href='login.php'>Login now</a></div>";
                } else {
                    echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
                }
            }
        }
    }
    ?>

    <form name="signupForm" method="POST" action="" onsubmit="return validateSignupForm()">
      <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control">
      </div>
      <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control">
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control">
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control">
      </div>
      <button type="submit" class="btn btn-primary">Sign Up</button>
      <a href="login.php" class="btn btn-link">Already have an account? Login</a>
    </form>
  </div>
</div>

</body>
</html>
