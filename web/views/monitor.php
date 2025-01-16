<?php

use App\Database;

if(!APP_LOADED) {
    header('HTTP/1.0 403 Forbidden');
    die("You can't access this file directly");
}

$db = new Database();
$page = isset($query['page']) ? (int)$query['page'] : 1;
$mails = $db->getMail($user, $page, $query);
$prevUrl = APP_ROOT . '?' . http_build_query(array_merge($query, ['page' => $page - 1]));
$nextUrl = APP_ROOT . '?' . http_build_query(array_merge($query, ['page' => $page + 1]));
?>
<form method="GET" id="search" class="hidden"><input type="hidden" name="task" value="<?php echo $query['task']; ?>" /></form>
<table class="table-sm table table-pin-rows h-full">
    <thead>
        <tr class="text-center">
            <th class="max-w-40">
                <label class="label label-text" for="search-timestamp">Timestamp</label>
                <input type="text" id="search-timestamp" name="timestamp" class="input input-sm flatpickr" placeholder="YYYY-MM-DD" form="search" value="<?php echo @$query['timestamp']; ?>" />
            </th>
            <th class="max-w-32">
                <label class="label label-text" for="search-queue_id">Queue ID</label>
                <input type="text" id="search-queue_id" name="queue_id" class="input input-sm" minlength="2" form="search" value="<?php echo @$query['queue_id']; ?>" />
            </th>
            <th>
                <label class="label label-text" for="search-message_id">Message ID</label>
                <input type="text" id="search-message_id" name="message_id" class="input input-sm" minlength="2" form="search" value="<?php echo @$query['message_id']; ?>" />
            </th>
            <th>
                <label class="label label-text" for="search-sender">From</label>
                <input type="text" id="search-sender" name="sender" placeholder="Full or Partial email" class="input input-sm" minlength="2" form="search" value="<?php echo @$query['sender']; ?>" />
            </th>
            <th>
                <label class="label label-text" for="search-recipient">To</label>
                <input type="text" id="search-recipient" name="recipient" placeholder="Full or Partial email" class="input input-sm" minlength="2" form="search" value="<?php echo @$query['recipient']; ?>" />
            </th>
            <th class="max-w-32">
                <label class="label label-text" for="search-status">Status</label>
                <select id="search-status" name="status" class="select select-sm appearance-none" aria-label="select" form="search" onchange="document.querySelector('form#search').submit()">
                    <?php if(!isset($query['status']) || !isset($db->mailStatus[$query['status']])): ?>
                    <option value="" selected>Any</option>
                    <?php else: ?>
                    <option value="">Any</option>
                    <?php endif; ?>
                    <?php foreach($db->mailStatus as $index => $status): ?>
                    <option value="<?php echo $index; ?>" class="text-<?php echo $status['color']; ?>"<?php echo @$query['status'] == $index ? ' selected' : ''; ?>><?php echo $status['label']; ?></option>
                    <?php endforeach; ?>
                </select>
            </th>
            <th>
                <label class="label label-text" for="search-subject">Subject</label>
                <input type="text" id="search-subject" name="subject" class="input input-sm" minlength="2" form="search" value="<?php echo @$query['subject']; ?>" />
            </th>
            <th>
                <label class="label label-text" for="search-ip">Sender IP</label>
                <input type="text" id="search-ip" name="ip" class="input input-sm" minlength="2" form="search" value="<?php echo @$query['ip']; ?>" />
            </th>
            <th>
                <label class="label label-text" for="search-attachment">Size (bytes)</label>
                <select id="search-attachment" name="attachment" class="select select-sm appearance-none" aria-label="select" form="search" onchange="document.querySelector('form#search').submit()">
                    <?php if(!isset($query['attachment']) || !in_array($query['attachment'], [0,1])): ?>
                        <option value="" selected>Any</option>
                    <?php else: ?>
                        <option value="">Any</option>
                    <?php endif; ?>
                    <option value="1"<?php echo @$query['attachment'] == "1" ? ' selected' : ''; ?>>Has attachment</option>
                    <option value="0"<?php echo @$query['attachment'] == "0" ? ' selected' : ''; ?>>No attachment</option>
                </select>
            </th>
        </tr>
    </thead>
    <tbody id="table-body">
        <?php if(count($mails) === 0): ?>
            <tr>
                <td colspan="9" class="text-center">No emails found</td>
            </tr>
        <?php else: ?>
        <?php foreach($mails as $mail): ?>
            <tr class="hover cursor-pointer" onclick="getHistory(<?php echo $mail['id']; ?>);" data-id="<?php echo $mail['id']; ?>">
                <td><?php echo $mail['timestamp']; ?></td>
                <td><?php echo $mail['queue_id']; ?></td>
                <td class="max-w-32 break-all whitespace-normal"><?php echo $mail['message_id']; ?></td>
                <td class="max-w-32 break-all whitespace-normal"><?php echo $mail['sender']; ?></td>
                <td><?php echo str_replace(' ', '<br>', $mail['recipient']); ?></td>
                <?php if($mail['status'] === null): ?>
                <td><span class="badge badge-md badge-neutral font-bold">-</span></td>
                <?php else: ?>
                <td><span class="badge badge-md badge-<?php echo $db->mailStatus[$mail['status']]['color']; ?> font-bold"><?php echo $db->mailStatus[$mail['status']]['label']; ?></span></td>
                <?php endif; ?>
                <td class="max-w-32 break-word whitespace-normal"><?php echo $mail['subject']; ?></td>
                <td><?php echo $mail['ip']; ?></td>
                <td><?php echo $mail['size']; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="text-center">
            <td colspan="9">
                <nav class="navbar">
                    <div class="navbar-start gap-2">
                        <input type="submit" class="btn btn-sm btn-success" value="🔍 Search" form="search" />
                        <input type="reset" class="btn btn-sm btn-warning" value="🗘 Reset" form="search" onclick="resetSearch()" />
                    </div>
                    <div class="navbar-center items-center">
                        <span class="badge badge-lg badge-default"><?php echo $db->pagination * ($page - 1); ?> - <?php echo $db->pagination * ($page - 1) + count($mails); ?></span>
                    </div>
                    <div class="navbar-end gap-2">
                        <?php if($db->pagination * ($page - 1) > 0): ?>
                            <a href="<?php echo $prevUrl; ?>" class="btn btn-sm">Previous</a>
                        <?php else: ?>
                            <a href="#" class="btn btn-sm" disabled>Previous</a>
                        <?php endif; ?>
                        <?php if(count($mails) == $db->pagination): ?>
                            <a href="<?php echo $nextUrl; ?>" class="btn btn-sm">Next</a>
                        <?php else: ?>
                            <a href="#" class="btn btn-sm" disabled>Next</a>
                        <?php endif; ?>
                    </div>
                </nav>
            </td>
        </tr>
    </tfoot>
</table>
</div>
<div id="history-modal" class="overlay modal overlay-open:opacity-100 modal-middle-start justify-center hidden" role="dialog" tabindex="-1">
<div class="modal-dialog overlay-open:opacity-100 max-w-7xl">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"></h3>
            <button type="button" class="btn btn-text btn-circle btn-sm absolute end-3 top-3" aria-label="Close" data-overlay="#history-modal">
                <span class="text-xl">X</span>
            </button>
        </div>
        <div class="modal-body">
            <table class="table-sm table table-pin-rows">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/json.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
const mailStatus = <?php echo json_encode($db->mailStatus); ?>;
// date picker
flatpickr(".flatpickr", {
    maxDate: "today",
    onChange: function() {
        document.querySelector('form#search').submit();
    }
});
// reset
function resetSearch() {
    const fields = document.querySelectorAll('thead input, thead select');
    for(i = 0; i < fields.length; i++) {
        fields[i].value = "";
    }
    document.querySelector('form#search').submit();
}
// modal
async function getHistory(mail_id) {
    const tbody = document.querySelector('#history-modal tbody')
    tbody.innerHTML = "";
    const mailCols = document.querySelectorAll(`tbody tr[data-id="${mail_id}"] td`);
    document.querySelector('#history-modal .modal-title').innerText = `${mailCols[1].innerText} | ${mailCols[0].innerText}`;
    const historyRes = await fetch(`<?php echo APP_ROOT; ?>?task=ajax&action=mail-history&mail_id=${mail_id}`);
    const history = await historyRes.json();
    if(history.success) {
        if(history.data.length) {
            for(let i = 0; i < history.data.length; i++) {
                tbody.innerHTML += `
                    <tr>
                        <td>${history.data[i]['timestamp']}</td>
                        <td><span class="badge badge-md badge-${mailStatus[history.data[i]['status']]['color']} font-bold">${mailStatus[history.data[i]['status']]['label']}</span></td>
                        <td class="max-w-2xl break-all whitespace-normal max-h-48 overflow-auto block">${history.data[i]['description']}</td>
                    </tr>
                `;
            }
            hljs.highlightAll(); // format rspamd json
        } else {
            tbody.innerHTML = '<tr><td colspan="3">No history records found.</td></tr>';
        }
        HSOverlay.open('#history-modal');
    } else {
        alert(`Error Fetching History: ${history.message}`);
    }
}
</script>