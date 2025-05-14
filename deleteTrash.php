<?php
require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
    throw new Exception('This application must be run on the command line.');
}

// Get custom search query from user input
$defaultQuery = 'in:trash';
echo "Enter Gmail search query (press Enter for default: '$defaultQuery'): ";
$searchQuery = trim(fgets(STDIN));

// Use default if empty input
if (empty($searchQuery)) {
    $searchQuery = $defaultQuery;
}

$gmailDeleter = new \Edrep\Gmail\Deleter(
    __DIR__ . '/auth/credentials.json',
    __DIR__ . '/auth/token.json'
);
$gmailDeleter->deleteMessages($searchQuery);
