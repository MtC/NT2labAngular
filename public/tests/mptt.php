<?php
require_once '../../config.php';
require_once '../../Mptt/Mptt.php';

$_connection = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['name'], $config['password']);

$mptt = new \Mptt\Mptt($_connection);

$mptt->setProperties('testing2');
$mptt->addPropertie('_value', 'text', 'NOT NULL', 'default ""', 'AFTER _key');