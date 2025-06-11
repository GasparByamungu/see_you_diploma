<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $license_number = trim($_POST['license_number']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];

    // Validate input
    $errors = [];
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($license_number)) {
        $errors[] = "License number is required";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    if (!in_array($status, ['available', 'assigned', 'off'])) {
        $errors[] = "Invalid status";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE drivers SET name = ?, license_number = ?, phone = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $license_number, $phone, $status, $id]);
            header("Location: drivers.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error updating driver: " . $e->getMessage();
        }
    }
}

// If there are errors or it's not a POST request, redirect back to drivers page
header("Location: drivers.php");
exit(); 