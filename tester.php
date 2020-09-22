<?php
require 'vendor/autoload.php';

use Xendit\Xendit;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$secret = $_ENV['SECRET_API_KEY'];

echo $secret;

Xendit::setApiKey($secret);

$params = [
    'external_id' => 'demo_1475801962607',
    'payer_email' => 'alfina@xendit.co',
    'description' => 'Trip to Bali',
    'amount' => 50000
];

$createInvoice = \Xendit\Invoice::create($params);
var_dump($createInvoice);
