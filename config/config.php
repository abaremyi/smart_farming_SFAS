<?php
/**
 * SFAS — Config
 * File: config/config.php
 */
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

define('JWT_SECRET_KEY',    $_ENV['JWT_SECRET_KEY']    ?? 'sfas_fallback_secret_2026');
define('ANTHROPIC_API_KEY', $_ENV['ANTHROPIC_API_KEY'] ?? '');

// SMTP (for PHPMailer)
define('SMTP_HOST',      $_ENV['SMTP_HOST']      ?? 'smtp.gmail.com');
define('SMTP_PORT',      (int)($_ENV['SMTP_PORT'] ?? 465));
define('SMTP_USER',      $_ENV['SMTP_USER']      ?? '');
define('SMTP_PASS',      $_ENV['SMTP_PASS']      ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'SFAS Smart Farming');
