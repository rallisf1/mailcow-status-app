<?php
namespace App;

if(!APP_LOADED) {
    header('HTTP/1.0 403 Forbidden');
    die("You can't access this file directly");
}

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use stdClass;
use App\Database;
use DateTime;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

class Mailcow {

    public static function getUser() {
        $client = new Client();
        $user = new stdClass;
        $response = $client->request('GET', 'https://'.$_ENV['MAILCOW_DOMAIN'].'/oauth/profile', [
            'headers' => [
                'Authorization' => 'Bearer ' . $_SESSION['token'],
            ],
        ]);
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody());
            $response2 = $client->request('GET', 'https://'.$_ENV['MAILCOW_DOMAIN'].'/api/v1/get/mailbox/'.$data->email, [
                'headers' => [
                    'X-API-Key' => $_ENV['MAILCOW_API_KEY'],
                ],
            ]);
            if ($response2->getStatusCode() === 200) {
                $data2 = json_decode($response2->getBody());
                $user->name = $data2->name;
                $user->email = $data->email;
                $user->domain = $data2->domain;
                $admins = explode(',', $_ENV['ADMIN']);
                if(in_array($user->email, $admins)) {
                    $user->role = 'admin';
                } elseif($data2->local_part == $_ENV['DOMAIN_ADMIN_PREFIX']) {
                    $user->role = 'domain_admin';
                } else {
                    $user->role = 'user';
                }
                return $user;
            }
        }
        return false;
    }

    public static function getLogs() { // cron every 5 minutes
        $client = new Client();
        $db = new Database();
        $mails = [];
        // get last logged timestamp so we can quickly skip existing records
        $lastTimestamp = DateTime::createFromFormat('Y-m-d H:i:s', $db->getLastTimestamp());
        // get postfix logs
        $response = $client->request('GET', 'https://'.$_ENV['MAILCOW_DOMAIN'].'/api/v1/get/logs/postfix/'.$_ENV['POSTFIX_COUNT'], [
            'headers' => [
                'X-API-Key' => $_ENV['MAILCOW_API_KEY'],
            ],
        ]);
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            for($i = 0; $i < count($data); $i++) {
                $timestamp = DateTime::createFromFormat("U", $data[$i]['time']);
                if($timestamp < $lastTimestamp) continue;
                preg_match_all('/status=([\w-]+)/', $data[$i]['message'], $matches, PREG_PATTERN_ORDER, 0);
                $status = isset($matches[1][0]) ? $matches[1][0] : false;
                preg_match_all('/\b[A-F0-9]{10,}\b/', $data[$i]['message'], $matches, PREG_SET_ORDER, 0);
                $queue_id = isset($matches[0][0]) ? $matches[0][0] : false;
                preg_match_all('/message-id=<([^>]+)>/', $data[$i]['message'], $matches, PREG_PATTERN_ORDER, 0);
                $message_id = isset($matches[1][0]) ? $matches[1][0] : false;
                preg_match_all('/to=<([^>]+)>/', $data[$i]['message'], $matches, PREG_PATTERN_ORDER, 0);
                $recipient = isset($matches[1][0]) ? $matches[1][0] : false;
                preg_match_all('/from=<([^>]+)>/', $data[$i]['message'], $matches, PREG_PATTERN_ORDER, 0);
                $sender = isset($matches[1][0]) ? $matches[1][0] : false;
                if($queue_id && ($message_id || $status || $recipient || $sender)) {
                    if(!isset($mails[$queue_id])) {
                        $mails[$queue_id] = [
                            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
                            'history' => []
                        ];
                    }
                    if($message_id) {
                        $mails[$queue_id]['message_id'] = $message_id;
                        $mails[$queue_id]['timestamp'] = $timestamp->format('Y-m-d H:i:s');
                    }
                    if($recipient) $mails[$queue_id]['recipient'] = $recipient;
                    if($sender) $mails[$queue_id]['sender'] = $sender;
                    if($status) {
                        if(!isset($mails[$queue_id]['status'])) $mails[$queue_id]['status'] = $status;
                        $mails[$queue_id]['history'][] = [
                            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
                            'status' => $status,
                            'description' => $data[$i]['message']
                        ];
                    }
                }
            }
        }
        // get rspamd logs
        $response = $client->request('GET', 'https://'.$_ENV['MAILCOW_DOMAIN'].'/api/v1/get/logs/rspamd-history/'.$_ENV['RSPAMD_COUNT'], [
            'headers' => [
                'X-API-Key' => $_ENV['MAILCOW_API_KEY'],
            ],
        ]);
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            $data = array_reverse($data); // this way the last status is always the latest, no need to check with db
            for($i = 0; $i < count($data); $i++) {
                $timestamp = DateTime::createFromFormat("U", $data[$i]['unix_time']);
                if($timestamp < $lastTimestamp) continue;
                $found = false;
                foreach($mails as $queue_id => $mail) {
                    if(isset($mail['message_id']) && $mail['message_id'] == $data[$i]['message-id']) {
                        $mails[$queue_id]['subject'] = $data[$i]['subject'];
                        $mails[$queue_id]['ip'] = $data[$i]['ip'];
                        $mails[$queue_id]['size'] = $data[$i]['size'];
                        if(count($data[$i]['rcpt_mime']) > 1) {
                            $mails[$queue_id]['recipient'] = implode(', ', $data[$i]['rcpt_mime']);
                        }
                        if($data[$i]['action'] != 'no action') { // only log flagged/rejected emails
                            $mails[$queue_id]['history'][] = [
                                'timestamp' => $timestamp->format('Y-m-d H:i:s'),
                                'status' => $data[$i]['action'],
                                'description' => 'Spam Score ' .  $data[$i]['score'] . ' : <pre><code class="language-json">' . json_encode($data[$i]['symbols'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK) . '</code></pre>'
                            ];
                            $mails[$queue_id]['status'] = $data[$i]['action'];
                        }
                        $found = true;
                        break;
                    }
                }
                if(!$found) {
                    // either the rspamd log contains newer emails, which we should skip or old emails we should update
                    $timestampLimit = $timestamp;
                    $timestampLimit->sub(new \DateInterval("P2D")); // bounces / defers can take up to 48h, assume unique $message_id for the period
                    $existing = $db->getMailByMessageIdSpam($data[$i]['message-id'], $data[$i]['action'], $timestamp->format('Y-m-d H:i:s'), $timestampLimit->format('Y-m-d H:i:s'));
                    if(!empty($existing)) {
                        $db->updateMailWithRspamdData($existing['id'], $data[$i]['subject'], $data[$i]['ip'], $data[$i]['size'], count($data[$i]['rcpt_mime']) > 1 ? implode(', ', $data[$i]['rcpt_mime']) : $existing['recipient']);
                        if($mails[$queue_id]['action'] != 'no action') { // only log flagged/rejected emails
                            $db->addMailHistory($existing['id'], $timestamp->format('Y-m-d H:i:s'), $data[$i]['action'], 'Spam Score ' .  $data[$i]['score'] . ' : ' . json_encode($data[$i]['symbols'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));
                            $db->updateMailStatus($existing['id'], $data[$i]['action']);
                        }
                    }
                }
            }
        }
        foreach($mails as $queue_id => $mail) {
            $timestampLimit = DateTime::createFromFormat('Y-m-d H:i:s', $mail['timestamp']);
            $timestampLimit->sub(new \DateInterval("P2D")); // bounces / defers can take up to 48h, assume unique $queue_id for the period
            $existing = $db->getMailByQueueId($queue_id, $timestampLimit->format('Y-m-d H:i:s'));

            if(!empty($existing)) {
                if(empty($existing['subject']) && !empty($mail['subject'])) {
                    $db->updateMailWithRspamdData($existing['id'], $mail['subject'], $mail['ip'], $mail['size'], $mail['recipient']);
                }
                $existingHistory = $db->getMailHistory($existing['id']);
                foreach($mail['history'] as $history) {
                    $found = false;
                    foreach($existingHistory as $h) {
                        if($h['timestamp'] == $history['timestamp'] && $db->mailStatus[$h['status']]['code'] == $history['status']) {
                            $found = true;
                            break;
                        }
                    }
                    if(!$found) {
                        $db->addMailHistory($existing['id'], $history['timestamp'], $history['status'], $history['description']);
                    }
                }
                if(!empty($mail['status']) && $mail['status'] != $db->mailStatus[$existing['status']]['code']) $db->updateMailStatus($existing['id'], $mail['status']);
            } else {
                $mail_id = $db->addMail($queue_id, @$mail['message_id'], $mail['recipient'], $mail['sender'], $mail['timestamp'], @$mail['status']);
                foreach($mail['history'] as $history) {
                    $db->addMailHistory($mail_id, $history['timestamp'], $history['status'], $history['description']);
                }
            }
        }
    }
}