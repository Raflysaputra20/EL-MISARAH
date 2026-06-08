<?php
session_start();
$_SESSION['user_id'] = 37;
$_SESSION['role'] = 'penghuni';
$_SESSION['nama'] = 'rere rere';

// Mock POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['tab'] = 'info';
$_POST['nama_depan'] = 'rere';
$_POST['nama_belakang'] = 'updated';
$_POST['no_hp'] = '1231555';
$_POST['alamat'] = 'Alamat baru';
$_POST['tanggal_lahir'] = '2005-11-20';
$_POST['pekerjaan'] = 'GURU AKTIF';

// Include the profil.php script
// We buffer output because it sends HTML headers and body
ob_start();
include __DIR__ . '/../backend/penghuni/profil.php';
$html = ob_get_clean();

echo "HTML output length: " . strlen($html) . "\n";
echo "Success variable: '" . ($success ?? '') . "'\n";
echo "Error variable: '" . ($error ?? '') . "'\n";

// Query the DB to check if it actually updated
require_once __DIR__ . '/../backend/config/database.php';
$stmt = $conn->prepare("SELECT nama, no_hp, alamat, pekerjaan FROM users WHERE id = 37");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
