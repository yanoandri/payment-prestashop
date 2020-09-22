<?php

$params = [
    'external_id' => 'demo_1475801962607',
    'payer_email' => 'alfina@xendit.co',
    'description' => 'Trip to Bali',
    'amount' => 50000
];
$url = 'https://api.xendit.co/v2/invoices';

$ch 	= curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_ENCODING, "");
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSLVERSION, 6);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

$headers = array();
$headers[] =  "Content-Type: application/x-www-form-urlencoded";
$headers[] =  "Authorization: Basic eG5kX2RldmVsb3BtZW50X25BTEJoUDdWQjdldlBYVGc2eHZGbm1VZEFSVThHQnhtVDBvNER0d0h1alEyMzk3cE1CYk9rRnpHc29WUGdFOg==";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

var_dump($result);
var_dump($status);