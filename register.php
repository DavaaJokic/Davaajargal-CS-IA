<?php
/*
|--------------------------------------------------------------------------
| REGISTER PAGE
|--------------------------------------------------------------------------
| Allows new users to create an account.
| Saves username, full name, and hashed password into the database.
*/
include "connection.php"; // include DB connection ($conn available)
?>

<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<title>Бүртгүүлэх</title>
<!-- Bootstrap CSS for styling -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">
<h2 class="text-center mb-4">📝 Бүртгүүлэх</h2>

<?php
/*
|--------------------------------------------------------------------------
| FORM SUBMISSION
|--------------------------------------------------------------------------
| Run when user submits registration form (POST request)
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Remove extra spaces
    $username = trim($_POST['username']); 
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password']; // do NOT trim passwords; spaces may be intentional

    /*
    |--------------------------------------------------------------------------
    | Hash password
    |--------------------------------------------------------------------------
    | password_hash() encrypts the password
    | So even if database is hacked, raw password is not stored
    | PASSWORD_DEFAULT automatically chooses secure hashing algorithm
    */
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    /*
    |--------------------------------------------------------------------------
    | Prepared statement for INSERT
    |--------------------------------------------------------------------------
    | Prevents SQL injection
    | ? placeholders are replaced by actual values
    */
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (username, full_name, password) VALUES (?, ?, ?)"
    );

    // Bind values to placeholders
    // "sss" = three strings
    mysqli_stmt_bind_param($stmt, "sss", $username, $full_name, $hashed);

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        // Registration successful
        echo "<div class='alert alert-success'>Амжилттай бүртгэгдлээ</div>";
    } else {
        // Something went wrong (e.g., username already exists)
        echo "<div class='alert alert-danger'>Алдаа гарлаа</div>";
    }
}
?>

<!-- REGISTRATION FORM -->
<form method="POST" class="card p-4 shadow">
    <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
    <input type="text" name="full_name" class="form-control mb-3" placeholder="Full name" required>
    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
    <button class="btn btn-success w-100">Бүртгүүлэх</button>
</form>

</div>
</body>
</html>
