<?php
session_start();
include "config/database.php";

if(isset($_POST['login'])){

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user){

        // ✅ SECURE PASSWORD CHECK
        if(password_verify($password, $user['password'])){
            $_SESSION['admin'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $error = "Invalid Password!";
        }

    } else {
        $error = "User not found!";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
<title>Login</title>

<style>
body {
    font-family:Poppins;
    background:#0b1d13;
    color:white;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

form {
    background:rgba(255,255,255,0.05);
    padding:30px;
    border-radius:15px;
}

input {
    width:100%;
    padding:10px;
    margin:10px 0;
    border-radius:8px;
    border:none;
}

button {
    width:100%;
    padding:10px;
    background:#2d6a4f;
    color:white;
    border:none;
    border-radius:8px;
}

</style>
</head>

<body>

<form method="POST">
<h2>Admin Login</h2>

<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

<input type="text" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>

<button name="login">Login</button>
</form>

</body>
</html>