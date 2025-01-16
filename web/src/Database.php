<?php
namespace App;

if(!APP_LOADED) {
    header('HTTP/1.0 403 Access Denied');
    die("You can't access this file directly");
}

use DateTime;
use stdClass;

class Database {

    private $db;
    private $tables = [
        "CREATE TABLE `mail` (`id` SERIAL PRIMARY KEY, `queue_id` VARCHAR(20) NOT NULL, `message_id` VARCHAR(255), `recipient` TEXT, `sender` VARCHAR(255), `timestamp` DATETIME NOT NULL, `status` TINYINT UNSIGNED, `subject` VARCHAR(255), `ip` VARCHAR(40), `size` INT UNSIGNED, UNIQUE(`queue_id`, `message_id`));",
        "CREATE TABLE `mail_history` (`id` SERIAL PRIMARY KEY, `mail_id` BIGINT UNSIGNED NOT NULL, `timestamp` DATETIME NOT NULL, `status` TINYINT UNSIGNED NOT NULL, `description` TEXT, FOREIGN KEY (`mail_id`) REFERENCES mail(`id`) ON DELETE CASCADE, UNIQUE(`mail_id`, `timestamp`, `status`));"
    ];
    private $indexes = [
        "CREATE INDEX idx_mail_timestamp ON mail(`timestamp`);",
        "CREATE INDEX idx_mail_queue_id ON mail(`queue_id`);",
        "CREATE INDEX idx_mail_recipient ON mail(`recipient`);",
        "CREATE INDEX idx_mail_sender ON mail(`sender`);",
        "CREATE INDEX idx_mail_status ON mail(`status`);",
        "CREATE INDEX idx_mail_history_timestamp ON mail_history(`timestamp`);",
        "CREATE INDEX idx_mail_history_status ON mail_history(`status`);"
    ];

    public $mailStatus = [
        ['code' => 'sent', 'label' => 'Sent', 'color' => 'success'],
        ['code' => 'deferred', 'label' => 'Delayed', 'color' => 'warning'],
        ['code' => 'bounced', 'label' => 'Bounced', 'color' => 'error'],
        ['code' => 'open', 'label' => 'Open', 'color' => 'info'],
        ['code' => 'soft reject', 'label' => 'Soft Rejected', 'color' => 'warning'],
        ['code' => 'reject', 'label' => 'Rejected', 'color' => 'error'],
        ['code' => 'add header', 'label' => 'Moved to Junk', 'color' => 'warning']
    ];

    public $dmarcStatus = [
        'pass' => ['label' => 'Pass', 'color' => 'success'],
        'fail' => ['label' => 'Fail', 'color' => 'error'],
        'temperror' => ['label' => 'Temp Error', 'color' => 'warning'],
        'softfail' => ['label' => 'Soft Fail', 'color' => 'warning'],
        'mixed' => ['label' => 'Mixed', 'color' => 'primary'],
        'other' => ['label' => 'Other', 'color' => 'accent']
    ];

    public $pagination = 50;

    public function __construct() {
        try {
            $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'] . ";charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,       // Fetch associative arrays
                \PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
            ];
            $this->db = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $options);
        } catch (\PDOException $e) {
            header('HTTP/1.0 500 Internal Server Error');
            die("Failed to connect to MySQL: " . $e->getMessage());
        }

        $stmt = $this->db->prepare("SHOW TABLES LIKE 'mail'");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            foreach ($this->tables as $table) {
                $this->db->exec($table);
            }
            foreach ($this->indexes as $index) {
                $this->db->exec($index);
            }
        }
    }

    private function getMailStatus($code, $indexOnly = false) {
        $index = array_search($code, array_column($this->mailStatus, 'code'));
        if($index === false) return null;
        return $indexOnly ? $index : $this->mailStatus[$index];
    }

    public function getMail($user, $page = 1, $filter = []) {
        $sql = "SELECT * FROM mail";
        $filters = [];
        if(isset($filter['queue_id']) && strlen($filter['queue_id']) > 2) {
            $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "queue_id LIKE ?";
            $filters[] = '%'.$filter['queue_id'].'%';
        }
        if(isset($filter['message_id']) && strlen($filter['message_id']) > 2){
            $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "message_id LIKE ?";
            $filters[] = '%'.$filter['message_id'].'%';
        }
        $recipientFilterExists = isset($filter['recipient']) && strlen($filter['recipient']) > 1;
        $senderFilterExists = isset($filter['sender']) && strlen($filter['sender']) > 1;
        switch($user->role) {
            case 'admin':
                if($recipientFilterExists) {
                    $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "recipient LIKE ?";
                    $filters[] = '%'.$filter['recipient'].'%';
                }
                if($senderFilterExists) {
                    $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "sender LIKE ?";
                    $filters[] = '%'.$filter['sender'].'%';
                }
                break;
            case 'domain_admin':
                if($recipientFilterExists || $senderFilterExists) {
                    $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "((recipient LIKE ? AND sender LIKE ?) OR (recipient LIKE ? AND sender LIKE ?))";
                    $filters[] = '%'.$filter['recipient'].'%';
                    $filters[] = '%'.$filter['sender'].'%@'.$user->domain;
                    $filters[] = '%'.$filter['recipient'].'%@'.$user->domain.'%';
                    $filters[] = '%'.$filter['sender'].'%';
                } else {
                    $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "(recipient LIKE ? OR sender LIKE ?)";
                    $filters[] = '%@'.$user->domain.'%';
                    $filters[] = '%@'.$user->domain;
                }
                break;
            default: // user
                if($recipientFilterExists || $senderFilterExists) {
                    $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "((recipient LIKE ? AND sender LIKE ?) OR (recipient LIKE ? AND sender LIKE ?))";
                    $filters[] = '%'.$filter['recipient'].'%';
                    $filters[] = $user->email;
                    $filters[] = '%'.$user->email.'%';
                    $filters[] = '%'.$filter['sender'].'%';
                } else {
                    $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "(recipient LIKE ? OR sender LIKE ?)";
                    $filters[] = '%'.$user->email.'%';
                    $filters[] = $user->email;
                }
        }
        if(isset($filter['timestamp']) && (strlen($filter['timestamp']) == 10 || (is_array($filter['timestamp']) && count($filter['timestamp']) == 2))) {
            $filterDate = true;
            try {
                $from = DateTime::createFromFormat('Y-m-d', is_array($filter['timestamp']) ? $filter['timestamp'][0] : $filter['timestamp']);
                $from->setTime(0, 0, 0);
                $to = DateTime::createFromFormat('Y-m-d', is_array($filter['timestamp']) ? $filter['timestamp'][1] : $filter['timestamp']);
                $to->setTime(23, 59, 59);
            } catch (\Exception $e) {
                $filterDate = false;
            }
            if($filterDate) {
                $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "`timestamp` BETWEEN ? AND ?";
                $filters[] = $from->format('Y-m-d H:i:s');
                $filters[] = $to->format('Y-m-d H:i:s');
            }
        }
        if(isset($filter['status']) && $filter['status'] >= 0 && $filter['status'] < count($this->mailStatus)) {
            $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "`status` = ?";
            $filters[] = (int)$filter['status'];
        }
        if(isset($filter['subject']) && strlen($filter['subject']) > 2) {
            $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "`subject` LIKE ?";
            $filters[] = '%'.$filter['subject'].'%';
        }
        if(isset($filter['ip']) && strlen($filter['ip']) > 2) {
            $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "`ip` LIKE ?";
            $filters[] = '%'.$filter['ip'].'%';
        }
        if(isset($filter['attachment']) && !empty($filter['attachment'])) {
            if($filter['attachment'] == 1) {
                $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "`size` >= ?";
                $filters[] = 100000;
            }
            if($filter['attachment'] == 0) {
                $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "`size` < ?";
                $filters[] = 100000;
            }
        }
        $sql .= " ORDER BY `timestamp` DESC LIMIT ?, ?";
        $filters[] = $this->pagination * ($page - 1);
        $filters[] = $this->pagination;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($filters);
        return $stmt->fetchAll();
    }

    public function getMailHistory($mail_id) {
        $stmt = $this->db->prepare("SELECT `timestamp`, `status`, `description` FROM mail_history WHERE mail_id = ? ORDER BY `timestamp` DESC");
        $stmt->execute([(int)$mail_id]);
        return $stmt->fetchAll();
    }

    public function addMail($queue_id, $message_id, $recipient, $sender, $timestamp, $status) {
        $status = $this->getMailStatus($status, true);
        $stmt = $this->db->prepare("INSERT INTO mail (`queue_id`, `message_id`, `recipient`, `sender`, `timestamp`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$queue_id, $message_id, $recipient, $sender, $timestamp, $status]);
        return $this->db->lastInsertId();
    }

    public function addMailHistory($mail_id, $timestamp, $status, $description = '') {
        $status = $this->getMailStatus($status, true);
        $stmt = $this->db->prepare("INSERT IGNORE INTO mail_history (`mail_id`, `timestamp`, `status`, `description`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$mail_id, $timestamp, $status, $description]);
    }

    public function getLastTimestamp() {
        $stmt = $this->db->prepare("SELECT `timestamp` FROM mail_history ORDER BY `timestamp` DESC LIMIT 1");
        $stmt->execute();
        $last_timestamp = $stmt->fetchColumn();
        return $last_timestamp ? $last_timestamp : '1970-01-01 00:00:00';
    }

    public function getMailByQueueId($queue_id, $from_date) {
        $stmt = $this->db->prepare("SELECT * FROM mail WHERE queue_id = ? AND `timestamp` >= ? ORDER BY `timestamp` DESC LIMIT 1");
        $stmt->execute([$queue_id, $from_date]);
        return $stmt->fetch();
    }

    public function getMailByMessageIdSpam($message_id, $status, $timestamp, $from_date) {
        $status = $this->getMailStatus($status, true);
        $stmt = $this->db->prepare("SELECT m.* FROM mail AS m LEFT JOIN mail_history AS mh ON mh.mail_id = m.id WHERE m.message_id = ? AND m.`timestamp` >= ? AND (mh.id IS NULL OR (mh.`status` != ? AND mh.`timestamp` != ?)) ORDER BY m.`timestamp` DESC LIMIT 1");
        $stmt->execute([$message_id, $from_date, $status, $timestamp]);
        return $stmt->fetch();
    }

    public function getMailByQueueIdMessageId($queue_id, $message_id, $fingerprint) {
        // SELECT m.id, (SELECT COUNT(*) FROM mail_history AS submh WHERE submh.mail_id = m.id AND submh.description LIKE '65bada16c953a353513917e1493e53fb56c249b0d8e3bb57ffcbd190b4f49bc6') AS opens FROM mail AS m LEFT JOIN mail_history AS mh on mh.mail_id = m.id WHERE m.queue_id = 'CA99280262' AND m.message_id = '000201db635a$10425270$30c6f750$@fcsadvance.co.uk' HAVING opens = 0 LIMIT 1;
        $stmt = $this->db->prepare("SELECT m.id, (SELECT COUNT(*) FROM mail_history AS submh WHERE submh.mail_id = m.id AND submh.description LIKE ?) AS opens FROM mail AS m LEFT JOIN mail_history AS mh on mh.mail_id = m.id WHERE m.queue_id = ? AND m.message_id = ? HAVING opens = 0 LIMIT 1");
        $stmt->execute([$fingerprint, $queue_id, $message_id]);
        return $stmt->fetchColumn();
    }

    public function updateMailStatus($mail_id, $status) {
        $status = $this->getMailStatus($status, true);
        $stmt = $this->db->prepare("UPDATE mail SET `status` = ? WHERE id = ?");
        $stmt->execute([$status, $mail_id]);
        return $stmt->rowCount();
    }

    public function updateMailWithRspamdData($mail_id, $subject, $ip, $size, $recipient) {
        if(mb_strlen($subject) > 253) $subject = mb_substr($subject, 0, 250) . '...';
        $stmt = $this->db->prepare("UPDATE mail SET `subject` = ?, ip = ?, size = ?, recipient = ? WHERE id = ?");
        $stmt->execute([$subject, $ip, $size, $recipient, $mail_id]);
        return $stmt->rowCount();
    }

    public function prune() {
        $date = new DateTime('now');
        $date->sub(new \DateInterval("P".$_ENV['RETENTION']."D"));
        $stmt = $this->db->prepare("DELETE FROM mail WHERE `timestamp` < ?");
        $stmt->execute([$date->format('Y-m-d H:i:s')]);
        return $stmt->rowCount();
    }



    public function getDmarcReports($user, $page = 1, $filter = []) {
        $sql = "SELECT r.`serial`, r.mindate, r.maxdate, r.domain, r.org, r.reportId, COUNT(rp.serial) AS messages, MIN((CASE WHEN rp.dkim_align = 'fail' THEN 0 WHEN rp.dkim_align = 'pass' THEN 1 ELSE 3 END) + (CASE WHEN rp.spf_align = 'fail' THEN 0 WHEN rp.spf_align = 'pass' THEN 1 ELSE 3 END)) AS dmarc_result_min, MAX((CASE WHEN rp.dkim_align = 'fail' THEN 0 WHEN rp.dkim_align = 'pass' THEN 1 ELSE 3 END) + (CASE WHEN rp.spf_align = 'fail' THEN 0 WHEN rp.spf_align = 'pass' THEN 1 ELSE 3 END)) AS dmarc_result_max FROM report AS r LEFT JOIN rptrecord AS rp ON r.serial = rp.serial";
        $filters = [];

        if($user->role === 'admin') {
            if(isset($filter['domain']) && strlen($filter['domain']) > 2) {
                $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "domain LIKE ?";
                $filters[] = $filter['domain'];
            }
        } else {
            $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "domain LIKE ?";
            $filters[] = explode('@', $user->email)[1];
        }

        if(isset($filter['reporter']) && strlen($filter['reporter']) > 2){
            $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "org LIKE ?";
            $filters[] = $filter['reporter'];
        }

        if(isset($filter['report_id']) && strlen($filter['report_id']) > 2) {
            $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "`reportid` LIKE ?";
            $filters[] = '%'.$filter['report_id'].'%';
        }

        if(!isset($filter['date']) || strlen($filter['date']) !== 7) $filter['date'] = date('Y-m');
        $sql .= (count($filters) > 0 ? ' AND ' : ' WHERE ') . "(`mindate` BETWEEN ? AND ? OR `maxdate` BETWEEN ? AND ?)";
        $filters[] = $filter['date'].'-01 00:00:00';
        $filters[] = $filter['date'].'-31 23:59:59';
        $filters[] = $filter['date'].'-01 00:00:00';
        $filters[] = $filter['date'].'-31 23:59:59';

        $sql .= " GROUP BY r.`serial`";

        if(isset($filter['status']) && isset($this->dmarcStatus[$filter['status']])) {
            switch ($filter['status']) {
                case "fail":
                    $sql .= " HAVING dmarc_result_min = 0 AND dmarc_result_max = 0";
                    break;
                case "mixed": // pass and fail
                    $sql .= " HAVING dmarc_result_min = 0 AND (dmarc_result_max = 1 OR dmarc_result_max = 2)";
                    break;
                case "other":
                    $sql .= " HAVING dmarc_result_min >= 3 AND dmarc_result_max >= 3";
                    break;
                case "pass":
                    $sql .= " HAVING (dmarc_result_min = 1 OR dmarc_result_min = 2) AND (dmarc_result_max <= 2)";
                    break;
                default:
                    break;
            }
        }

        $sql .= " ORDER BY `maxdate` DESC LIMIT ?, ?";
        $filters[] = $this->pagination * ($page - 1);
        $filters[] = $this->pagination;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($filters);
        $reports = $stmt->fetchAll();
        foreach($reports as $index => $report) {
            if($report['dmarc_result_min'] == 0 && $report['dmarc_result_max'] == 0) {
                $reports[$index]['status'] = 'fail';
            } elseif ($report['dmarc_result_min'] == 0 && ($report['dmarc_result_max'] == 1 || $report['dmarc_result_max'] == 2)) {
                $reports[$index]['status'] = 'mixed';
            } elseif (($report['dmarc_result_min'] == 1 || $report['dmarc_result_min'] == 2) && $report['dmarc_result_max'] <= 2) {
                $reports[$index]['status'] = 'pass';
            } else {
                $reports[$index]['status'] = 'other';
            }
        }
        return $reports;
    }

    public function getDmarcMonths() {
        $stmt = $this->db->prepare("SELECT MIN(mindate) AS `start`, MAX(maxdate) AS `end` FROM report");
        $stmt->execute();
        $row = $stmt->fetch();
        $start = DateTime::createFromFormat('Y-m-d H:i:s', $row['start']);
        $end = DateTime::createFromFormat('Y-m-d H:i:s', $row['end']);
    
        $months = [];
    
        while ($start <= $end) {
            $currentMonth = $start->format('Y-m');
            $months[] = $currentMonth;
            $start->modify('first day of next month');
        }
    
        return array_reverse($months);
    }

    public function getDmarcDomains() {
        $stmt = $this->db->prepare("SELECT DISTINCT domain FROM report ORDER BY domain ASC");
        $stmt->execute();
        $domains = $stmt->fetchAll();
        return array_column($domains, 'domain');
    }

    public function getDmarcReporters() {
        $stmt = $this->db->prepare("SELECT DISTINCT org FROM report ORDER BY org ASC");
        $stmt->execute();
        $reporters = $stmt->fetchAll();
        return array_column($reporters, 'org');
    }

    public function getDmarcReportInfo($serial) {
        $response = new stdClass();
        $stmt = $this->db->prepare("SELECT mindate, maxdate, domain, org, reportid, policy_adkim, policy_aspf, policy_p, policy_sp, policy_pct FROM report WHERE `serial` = ?");
        $stmt->execute([$serial]);
        $response->info = $stmt->fetchAll();
        $stmt = $this->db->prepare("SELECT * FROM rptrecord WHERE `serial` = ? ORDER BY rcount DESC");
        $stmt->execute([$serial]);
        $response->records = $stmt->fetchAll();
        foreach($response->records as $index => $row) {
            if ( $row['ip'] ) {
                $response->records[$index]['ip'] = long2ip(intval($row['ip']));
                $response->records[$index]['hostname'] = gethostbyaddr($response->records[$index]['ip']);
            } elseif ( $row['ip6'] ) {
                $response->records[$index]['ip'] = inet_ntop($row['ip6']);
                $response->records[$index]['hostname'] = gethostbyaddr($response->records[$index]['ip']);
            } else {
                $response->records[$index]['ip'] = "-";
                $response->records[$index]['hostname'] = "-";
            }
            unset($response->records[$index]['ip6']);
        }
        return $response;
    }

    public function getDmarcReportXML($serial) {
        $stmt = $this->db->prepare("SELECT raw_xml FROM report WHERE `serial` = ?");
        $stmt->execute([$serial]);
        $stmt->bindColumn(1, $result, \PDO::PARAM_LOB);
        $stmt->fetch(\PDO::FETCH_BOUND);
        return $result;
    }

}