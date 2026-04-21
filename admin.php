<?php
session_start();
include "config/database.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// ADD SPECIES
if(isset($_POST['submit'])){

    $name = $_POST['name'];
    $scientific = $_POST['scientific'];
    $type = $_POST['type'];
    $threat = $_POST['threat'];
    $lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : null;
    $desc = htmlspecialchars($_POST['desc']);

    // IMAGE UPLOAD
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $image_name = $_FILES['image']['name'];
    $temp = $_FILES['image']['tmp_name'];

    $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_types)) {
        $error = "❌ Only JPG, JPEG, PNG allowed";
    } else {

        $new_name = time() . "." . $ext;

        if(move_uploaded_file($temp, "uploads/" . $new_name)){

            // INSERT QUERY
            $sql = "INSERT INTO species 
            (species_name, scientific_name, species_type, threat_level, latitude, longitude, image_path, description, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssddss", 
                $name, 
                $scientific, 
                $type, 
                $threat, 
                $lat, 
                $lng, 
                $new_name,
                $desc
            );

            $stmt->execute();

            // REDIRECT (fix refresh issue)
            header("Location: admin.php?success=1");
            exit;

        } else {
            $error = "❌ Image upload failed";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>

<style>
body {
    font-family: Poppins;
    background: #0b1d13;
    color: white;
    padding: 30px;
}

form {
    max-width: 500px;
    margin: auto;
}

input, textarea, select {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border-radius: 8px;
    border: none;
}

button {
    background: #2d6a4f;
    color: white;
    padding: 10px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

table {
    width: 100%;
    margin-top: 40px;
    border-collapse: collapse;
    background: rgba(255,255,255,0.05);
}

th, td {
    padding: 12px;
    text-align: center;
}

tr:hover {
    background: rgba(255,255,255,0.1);
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.green { background: #2d6a4f; color: #d8f3dc; }
.red { background: #9d0208; color: #ffccd5; }
.yellow { background: #ffba08; color: black; }

a {
    color: #95d5b2;
    text-decoration: none;
}
</style>
</head>

<body>

<a href="logout.php">Logout</a>

<h2>Add Species</h2>

<?php 
if(isset($_GET['success'])){
    echo "<p style='color:lightgreen;'>✅ Species Added!</p>";
}
if(isset($error)){
    echo "<p style='color:red;'>$error</p>";
}
?>

<form method="POST" enctype="multipart/form-data">

<input type="text" name="name" placeholder="Species Name" required>

<input type="text" name="scientific" placeholder="Scientific Name">

<select name="type">
<option>Mammal</option>
<option>Bird</option>
<option>Reptile</option>
</select>

<select name="threat">
<option>Low</option>
<option>Vulnerable</option>
<option>Endangered</option>
</select>

<input type="text" name="lat" placeholder="Latitude">
<input type="text" name="lng" placeholder="Longitude">

<textarea name="desc" placeholder="Description"></textarea>

<input type="file" name="image" required>

<button type="submit" name="submit">Add Species</button>

</form>

<h2>All Species</h2>

<table border="1">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Image</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php
$result = $conn->query("SELECT species_id, species_name, image_path, status FROM species");

while($row = $result->fetch_assoc()){
?>

<tr>
    <td><?php echo $row['species_id']; ?></td>

    <td><?php echo htmlspecialchars($row['species_name']); ?></td>

    <td>
        <img src="uploads/<?php echo htmlspecialchars($row['image_path']); ?>" width="80">
    </td>

    <td>
        <?php 
        if($row['status'] == "Approved"){
            echo "<span class='badge green'>Approved</span>";
        } elseif($row['status'] == "Rejected"){
            echo "<span class='badge red'>Rejected</span>";
        } else {
            echo "<span class='badge yellow'>Pending</span>";
        }
        ?>
    </td>

    <td>
        <a href="edit.php?id=<?php echo $row['species_id']; ?>">Edit</a> |
        <a href="delete.php?id=<?php echo $row['species_id']; ?>" onclick="return confirm('Delete this species?')">Delete</a> |

        <?php if($row['status'] != "Approved"){ ?>
            <a href="approve.php?id=<?php echo $row['species_id']; ?>">Approve</a> |
        <?php } ?>

        <?php if($row['status'] != "Rejected"){ ?>
            <a href="reject.php?id=<?php echo $row['species_id']; ?>">Reject</a>
        <?php } ?>
    </td>
</tr>

<?php } ?>

</table>

</body>
</html>