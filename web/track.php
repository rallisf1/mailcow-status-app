<?php
namespace App;

require __DIR__.'/vendor/autoload.php';

define("APP_LOADED", true);

use App\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if(isset($_GET['msg']) && !empty($_GET['msg'])) {
    $raw = hex2bin($_GET['msg']);
    [$queue_id, $message_id] = explode('|', $raw);
    $user_ip = @$_SERVER["HTTP_CF_CONNECTING_IP"] ?? @$_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $fingerprint = hash('sha256', $user_ip . $_SERVER["HTTP_USER_AGENT"]);
    $db = new Database();
    $mail_id = $db->getMailByQueueIdMessageId($queue_id, $message_id, $fingerprint);
    if($mail_id) {
        $db->addMailHistory($mail_id, date('Y:m:d H:i:s'), 'open', $fingerprint);
        $db->updateMailStatus($mail_id, 'open');
    }
}

header('Content-Type: image/gif');
// 1x1px white gif
die("\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2a\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b");