<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"] ?? null;

    if ($id && isset($_FILES['foto_kamar']) && $_FILES['foto_kamar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/../../../frontend/assets/image/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['foto_kamar']['name']));
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['foto_kamar']['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("UPDATE kamar SET foto = ? WHERE id = ?");
            $stmt->execute([$fileName, $id]);
        }
    }
}
header("Location: list_kamar.php?success=foto");
exit;
