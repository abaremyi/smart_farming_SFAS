<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
require __DIR__ . '/config/database.php';
require __DIR__ . '/modules/Authentication/controllers/AuthController.php';
$ctrl = new AuthController();
$email = 'info.abaremy@gmail.com';
$otp = '957342';
var_dump($ctrl->verifyOtp($email, $otp));
var_dump($ctrl->resetPassword($email, $otp, 'NewPassword123', 'NewPassword123'));