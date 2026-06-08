<?php
$_SESSION = [
    'user_id' => 37,
    'role' => 'penghuni',
    'nama' => 'rere kucing'
];

$userId = 37;

// Create dummy image file to simulate upload
$dummy_image = __DIR__ . '/dummy_profile.jpg';
file_put_contents($dummy_image, 'dummy image data');

// Setup $_FILES global
$_FILES['foto'] = [
    'name' => 'test_avatar.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => $dummy_image,
    'error' => 0,
    'size' => 16
];

// Setup $_POST global
$_POST['tab'] = 'info';
$_POST['nama_depan'] = 'rere';
$_POST['nama_belakang'] = 'kucing';
$_POST['no_hp'] = '1231555';
$_POST['alamat'] = 'Alamat baru';
$_POST['tanggal_lahir'] = '2005-11-20';
$_POST['pekerjaan'] = 'GURU AKTIF';

// Include DB config
require_once __DIR__ . '/../backend/config/database.php';

// Include profil.php processing block
$success = $error = '';
$user = ['foto' => ''];
$namaUser = 'rere kucing';
$activeTab = 'info';

// Run upload code
if (!empty($_FILES['foto']['name'])) {
    $ext=strtolower(pathinfo($_FILES['foto']['name'],PATHINFO_EXTENSION));
    if (in_array($ext,['jpg','jpeg','png','webp'])) {
        $dir1 = __DIR__ . '/../backend/uploads/profil/';
        $dir2 = __DIR__ . '/../uploads/profil/';
        if(!is_dir($dir1)) mkdir($dir1,0755,true);
        if(!is_dir($dir2)) mkdir($dir2,0755,true);
        $fn='user_'.$userId.'_'.time().'.'.$ext;
        
        // Use copy instead of move_uploaded_file because it's not a real HTTP POST upload in CLI
        if(copy($_FILES['foto']['tmp_name'], $dir1.$fn)) {
            copy($dir1.$fn, $dir2.$fn);
            $foto=$fn;
            echo "✅ Files successfully saved to:\n";
            echo "1. " . realpath($dir1.$fn) . "\n";
            echo "2. " . realpath($dir2.$fn) . "\n";
        } else {
            echo "❌ Failed to copy to dir1\n";
        }
    }
}

// Clean up dummy
@unlink($dummy_image);
