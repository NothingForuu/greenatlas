<?php
include "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required!";
    } else {

        // Check if email already exists
        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Email already registered!";
        } else {

            // Hash password 🔐
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $message = "Registration successful! 🎉";
            } else {
                $message = "Something went wrong!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Register - GreenAtlas</title>

<style>
body {
    margin: 0;
    font-family: Poppins, sans-serif;
    background: linear-gradient(135deg, #0b0f14, #0f2027);
}

/* CENTER SECTION */
.auth-section {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* CARD */
.auth-card {
    width: 380px;
    padding: 40px;
    border-radius: 20px;

    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(20px);

    box-shadow: 0 0 40px rgba(0,255,150,0.15);

    text-align: center;
}

/* TITLE */
.auth-card h2 {
    color: #b7e4c7;
    margin-bottom: 20px;
}

/* INPUTS */
.auth-card input {
    width: 100%;
    padding: 12px;
    margin: 10px 0;

    border: none;
    border-radius: 10px;

    background: rgba(255,255,255,0.08);
    color: white;

    outline: none;
}

.auth-card input:focus {
    box-shadow: 0 0 10px #00ff9d;
}

/* BUTTON */
.auth-card button {
    width: 100%;
    padding: 12px;
    margin-top: 15px;

    border: none;
    border-radius: 10px;

    background: linear-gradient(45deg, #00ff9d, #00c853);
    color: black;
    font-weight: bold;

    cursor: pointer;
    transition: 0.3s;
}

.auth-card button:hover {
    transform: scale(1.05);
}

/* MESSAGE */
.message {
    margin-bottom: 10px;
    color: #ff7675;
}

/* LINK */
.auth-card a {
    display: block;
    margin-top: 15px;
    color: #b7e4c7;
    text-decoration: none;
}
</style>

</head>

<body>

<div class="auth-section">

    <div class="auth-card">
        <h2>Create Account 🌿</h2>

        <?php if($message != "") { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <form method="POST">
            <input type="text" name="name" placeholder="Full Name">
            <input type="email" name="email" placeholder="Email Address">
            <input type="password" name="password" placeholder="Password">

            <button type="submit">Register</button>
        </form>

        <a href="login.php">Already have an account? Login</a>
    </div>

</div>

</body>
</html>