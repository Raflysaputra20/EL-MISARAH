<?php
require_once __DIR__ . '/../backend/config/database.php';
$pass = password_hash('admin123', PASSWORD_BCRYPT);
$conn->prepare("UPDATE users SET password = ? WHERE email = 'reja@gmail.com'")->execute([$pass]);
echo "Admin password has been reset to 'admin123'.\n";

// Reset user rehan@gmail.com password to 'user123'
$pass2 = password_hash('user123', PASSWORD_BCRYPT);
$conn->prepare("UPDATE users SET password = ? WHERE email = 'rehan@gmail.com'")->execute([$pass2]);
echo "User rehan password has been reset to 'user123'.\n";
