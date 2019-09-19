<?php
require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
    throw new Exception('This application must be run on the command line.');
}

$gmailDeleter = new \Edrep\Gmail\Deleter(
    __DIR__ . '/auth/credentials.json',
    __DIR__ . '/auth/token.json'
);
$gmailDeleter->deleteTrash();
