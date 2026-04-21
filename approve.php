<?php
session_start();
include "config/database.php";

// Check admin login
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// Check if ID exists
if(isset($_GET['id'])){

    $id = intval($_GET['id']); // basic safety

    $stmt = $conn->prepare("UPDATE species SET status='Approved' WHERE species_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: admin.php");
exit;
?>