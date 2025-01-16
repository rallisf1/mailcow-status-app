<?php
namespace App;

error_reporting(E_ALL ^ E_WARNING); // Mailcow.php:170,179 produce some stupid warnings

require dirname(__DIR__).'/vendor/autoload.php';

define("APP_LOADED", true);

use App\Mailcow;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if(@$_GET['key'] != $_ENV['CRON_KEY']) {
    header('HTTP/1.0 403 Forbidden');
    die("Cron key mismatch!");
}

if(!file_exists(__DIR__ . '/cron.lock')) {
    touch(__DIR__ . '/cron.lock');
    Mailcow::getLogs();
    unlink(__DIR__ . '/cron.lock');
    echo 'ok';
} else {
    echo 'busy';
}

