<?php
session_start();

require_once "../config/database.php";

/* ADMIN ACCESS */

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}


/* GET PRODUCT ID */

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($id <= 0) {
    header("Location: products.php");
    exit();
}


/* GET PRODUCT IMAGE */

$stmt = $conn->prepare("
    SELECT image
    FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

$stmt->close();


/* DELETE PRODUCT */

$stmt = $conn->prepare("
    DELETE FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    /* DELETE IMAGE FROM FOLDER */

    if (!empty($product["image"])) {

        $image_path =
            "../assets/images/" . $product["image"];

        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    $stmt->close();

    header("Location: products.php?success=deleted");
    exit();

} else {

    $stmt->close();

    header("Location: products.php?error=delete_failed");
    exit();
}
?>