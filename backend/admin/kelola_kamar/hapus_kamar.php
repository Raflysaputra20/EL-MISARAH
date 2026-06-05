<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../api/auth/login.php");
    exit;
}

$id = $_GET["id"] ?? null;

if ($id) {
    $stmt = $conn->prepare("SELECT foto, foto_2, foto_3, foto_4, foto_5, foto_denah FROM kamar WHERE id = ?");
    $stmt->execute([$id]);
    $kamar = $stmt->fetch();
    
    if ($kamar) {
        $fotos = ['foto', 'foto_2', 'foto_3', 'foto_4', 'foto_5', 'foto_denah'];
        foreach ($fotos as $f) {
            if (!empty($kamar[$f])) {
                $path = __DIR__ . '/../../../frontend/assets/image/' . $kamar[$f];
                if (file_exists($path)) unlink($path);
            }
        }
    }

    $stmt = $conn->prepare("DELETE FROM kamar WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: list_kamar.php?success=hapus");
exit;
