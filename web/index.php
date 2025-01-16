<?php

session_start();

define("APP_LOADED", true);
define("APP_ROOT", in_array(basename(__DIR__), ['web', 'htdocs', 'public_html', 'www']) ? '/' : '/' . basename(__DIR__) . '/');

require __DIR__.'/vendor/autoload.php';

use App\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if(isset($_SESSION['expires'])) {
    if($_SESSION['expires'] < time()) {
        session_destroy();
        header('Location: ' . APP_ROOT);
    }
    parse_str($_SERVER["QUERY_STRING"], $query);
    if(!isset($query['task'])) {
        header('Location: ' . APP_ROOT . '?task=monitor');
        exit();
    }
    switch($query['task']) {
        case 'ajax':
            header("Content-type: application/json; charset=utf-8");
            if(!isset($query['action'])) {
                header('HTTP/1.0 400 Bad Request');
                echo json_encode([
                    'success' => false,
                    'message' => 'action not defined'
                ]);
                exit();
            }
            switch($query['action']){
                case 'mail-history':
                    if((int)$query['mail_id'] > 0) {
                        $db = new Database();
                        $history = $db->getMailHistory((int)$query['mail_id']);
                        echo json_encode([
                            'success' => true,
                            'data' => $history
                        ]);
                    } else {
                        header('HTTP/1.0 400 Bad Request');
                        echo json_encode([
                            'success' => false,
                            'message' => 'mail_id needs to be a positive integer.'
                        ]);
                    }
                    break;
                case 'dmarc-report':
                    if((int)$query['report_id'] > 0) {
                        $db = new Database();
                        $data = $db->getDmarcReportInfo((int)$query['report_id']);
                        echo json_encode([
                            'success' => true,
                            'data' => $data
                        ]);
                    } else {
                        header('HTTP/1.0 400 Bad Request');
                        echo json_encode([
                            'success' => false,
                            'message' => 'report_id needs to be a positive integer.'
                        ]);
                    }
                    break;
                default:
                    header('HTTP/1.0 400 Bad Request');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Task not found.'
                    ]);
            }
            exit();
        case 'download':
            switch($query['action']){
                case 'dmarc-xml':
                    if((int)$query['report_id']){
                        $db = new Database();
                        $data = $db->getDmarcReportXML((int)$query['report_id']);
                        if($data) {
                            header("Content-type: text/xml");
                            header('Content-Disposition: attachment; filename="dmarc_'.$query['report_id'].'.xml"');
                            fpassthru($data);
                            exit();
                        } else {
                            header('HTTP/1.0 404 Not Found');
                            echo 'Report XML missing!';
                            exit();
                        }
                    } else {
                        header('HTTP/1.0 400 Bad Request');
                        echo 'report_id needs to be a positive integer.';
                        exit();
                    }
                    break;
                default:
                    header('HTTP/1.0 400 Bad Request');
                    echo 'Action not provided!';
                    exit();
            }
        case 'logout':
            session_destroy();
            header('Location: ' . APP_ROOT);
            exit();
        case 'monitor':
        case 'dmarc':
            require __DIR__ . '/views/partial/header.php';
            require __DIR__ . '/views/' . $query['task'] . '.php';
            require __DIR__ . '/views/partial/footer.php';
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            require __DIR__ . '/views/partial/header.php';
            require __DIR__ . '/views/404.php';
            require __DIR__ . '/views/partial/footer.php';
            break;
    }
} else {
    $provider = new OAuth2\Provider([
        'client_id' => $_ENV['MAILCOW_CLIENT_ID'],
        'client_secret' => $_ENV['MAILCOW_CLIENT_SECRET'],
        'endpoints' => [
            'auth_url' => 'https://'.$_ENV['MAILCOW_DOMAIN'].'/oauth/authorize',
            'token_url' => 'https://'.$_ENV['MAILCOW_DOMAIN'].'/oauth/token'
        ],
    ]);
    
    $grant = $provider->initGrant(OAuth2\Grant\AuthorizationCode::class);
    
    // $state is a random value used to protect aginst CSRF attacks.
    $state = $grant->generateState();
    
    $authUrl = $grant->getAuthorizationUrl($state, [
        // Additional options, for example "scope" to allow fine grained control of your app's permissions.
        // The options here will differ depending on the provider
        'scope' => 'profile',
    ]);
    
    // Store the state somewhere, we'll need to verify it later when the user is redirected back to our app
    $_SESSION['state'] = $state;
    
    // Redirect the user to approve the app
    header('Location: ' . $authUrl);
}
?>
