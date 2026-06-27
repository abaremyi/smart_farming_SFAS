<?php
/**
 * SFAS — App Config
 * File: config/config.php
 */
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // won't crash if .env is missing a key

define('JWT_SECRET_KEY',    $_ENV['JWT_SECRET_KEY']    ?? 'sfas_fallback_secret_key_min32chars');
define('APP_ENV',           $_ENV['APP_ENV']           ?? 'development');
define('APP_NAME',          $_ENV['APP_NAME']          ?? 'SFAS');

define('SMTP_HOST',         $_ENV['SMTP_HOST']         ?? 'smtp.gmail.com');
define('SMTP_USER',         $_ENV['SMTP_USER']         ?? '');
define('SMTP_PASS',         $_ENV['SMTP_PASS']         ?? '');
define('SMTP_FROM_NAME',    $_ENV['SMTP_FROM_NAME']    ?? 'SFAS Smart Farming');

define('ANTHROPIC_API_KEY', $_ENV['ANTHROPIC_API_KEY'] ?? '');