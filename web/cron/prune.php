<?php
namespace App;

require dirname(__DIR__).'/vendor/autoload.php';

define("APP_LOADED", true);

use App\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if(@$_GET['key'] != $_ENV['CRON_KEY']) {
    header('HTTP/1.0 403 Forbidden');
    die("Cron key mismatch!");
}

$db = new Database();

$db->prune();

echo 'ok';

