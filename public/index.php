<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ? '1' : '0');

// Start session
session_start();

// Basic routing
$request = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($basePath, '', $request);

// Simple router
switch ($path) {
    case '/':
        require __DIR__ . '/../templates/landing/index.php';
        break;
    case '/pricing':
        require __DIR__ . '/../templates/landing/pricing.php';
        break;
    case '/faq':
        require __DIR__ . '/../templates/landing/faq.php';
        break;
    case '/dashboard':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/../templates/dashboard/index.php';
        break;
    case '/admin':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/../templates/admin/index.php';
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/../templates/landing/404.php';
        break;
} 