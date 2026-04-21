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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle Approve / Reject
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == "approve") {
        $conn->query("UPDATE species SET status='Approved' WHERE species_id=$id");
    } elseif ($action == "reject") {
        $conn->query("UPDATE species SET status='Rejected' WHERE species_id=$id");
    }
}

// Fetch Pending Species
$result = $conn->query("SELECT * FROM species WHERE status='Pending'");
?>

<h2>Admin Panel - Pending Species</h2>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Type</th>
    <th>Image</th>
    <th>Action</th>
</tr>

<?php while ($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?php echo $row['species_id']; ?></td>
    <td><?php echo $row['species_name']; ?></td>
    <td><?php echo $row['species_type']; ?></td>
    <td>
        <img src="../../uploads/<?php echo $row['image_path']; ?>" width="100">
    </td>
    <td>
        <a href="?action=approve&id=<?php echo $row['species_id']; ?>">Approve</a> |
        <a href="?action=reject&id=<?php echo $row['species_id']; ?>">Reject</a>
    </td>
</tr>
<?php } ?>

</table>

</body>
</html>
