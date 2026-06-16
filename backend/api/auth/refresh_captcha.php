<?php
session_start();
$_SESSION['captcha_num1'] = rand(1, 10);
$_SESSION['captcha_num2'] = rand(1, 10);

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'captcha_num1' => $_SESSION['captcha_num1'],
    'captcha_num2' => $_SESSION['captcha_num2']
]);
exit;
