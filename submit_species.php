<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $species_name = $_POST['species_name'];
    $scientific_name = $_POST['scientific_name'];
    $species_type = $_POST['species_type'];
    $category = $_POST['category'];
    $threat_level = $_POST['threat_level'];
    $region = $_POST['region'];
    $description = $_POST['description'];

    // Image upload
    $image_name = $_FILES['image']['name'];
    $temp_name = $_FILES['image']['tmp_name'];
    $upload_folder = "../../uploads/" . $image_name;

    if (move_uploaded_file($temp_name, $upload_folder)) {

        $sql = "INSERT INTO species 
        (species_name, scientific_name, species_type, category, threat_level, region, description, image_path, status, submitted_by) 
        VALUES 
        ('$species_name', '$scientific_name', '$species_type', '$category', '$threat_level', '$region', '$description', '$image_name', 'Pending', '{$_SESSION['user_id']}')";

        if ($conn->query($sql) === TRUE) {
            $message = "Species Submitted Successfully! Waiting for Admin Approval.";
        } else {
            $message = "Database Error: " . $conn->error;
        }

    } else {
        $message = "Image Upload Failed!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Species - GreenAtlas</title>
</head>
<body>

<h2>Submit Species</h2>

<form method="POST" enctype="multipart/form-data">
    <label>Species Name:</label><br>
    <input type="text" name="species_name" required><br><br>

    <label>Scientific Name:</label><br>
    <input type="text" name="scientific_name" required><br><br>

    <label>Species Type:</label><br>
    <select name="species_type" required>
        <option value="Animal">Animal</option>
        <option value="Plant">Plant</option>
    </select><br><br>

    <label>Category:</label><br>
    <input type="text" name="category" required><br><br>

    <label>Threat Level:</label><br>
    <input type="text" name="threat_level" required><br><br>

    <label>Region:</label><br>
    <input type="text" name="region" required><br><br>

    <label>Description:</label><br>
    <textarea name="description" required></textarea><br><br>

    <label>Upload Image:</label><br>
    <input type="file" name="image" required><br><br>

    <button type="submit">Submit</button>
</form>

<p><?php echo $message; ?></p>

</body>
</html>
</body>
</html>