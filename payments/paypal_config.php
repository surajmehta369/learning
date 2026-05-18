<?php
require __DIR__ . '../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$paypal_url = $_ENV['PAYPAL_SANDBOX'] ? "https://api-m.sandbox.paypal.com" : "https://api-m.paypal.com";