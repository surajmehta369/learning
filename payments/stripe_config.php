<?php
require __DIR__ . '../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
