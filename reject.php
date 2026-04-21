<?php
session_start();
include "config/database.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

if(isset($_GET['id'])){

    $id = intval($_GET['id']);

    $stmt = $conn->prepare("UPDATE species SET status='Rejected' WHERE species_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: admin.php");
exit;
?>