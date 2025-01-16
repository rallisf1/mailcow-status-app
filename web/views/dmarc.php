<?php

use App\Database;

if(!APP_LOADED || $user->role === 'user') {
    header('HTTP/1.0 403 Forbidden');
    die("You can't access this file directly");
}

$db = new Database();
$page = isset($query['page']) ? (int)$query['page'] : 1;
$reports = $db->getDmarcReports($user, $page, $query);
$prevUrl = APP_ROOT . '?' . http_build_query(array_merge($query, ['page' => $page - 1]));
$nextUrl = APP_ROOT . '?' . http_build_query(array_merge($query, ['page' => $page + 1]));
?>
<form method="GET" id="search" class="hidden"><input type="hidden" name="task" value="<?php echo $query['task']; ?>" /></form>
<table class="table-sm table table-pin-rows h-full">
    <thead>
        <tr class="text-center">
            <th class="max-w-32">
                <label class="label label-text" for="search-status">Status</label>
                <select id="search-status" name="status" class="select select-sm appearance-none" aria-label="select" form="search" onchange="document.querySelector('form#search').submit()">
                    <?php if(!isset($query['status']) || !isset($db->dmarcStatus[$query['status']])): ?>
                    <option value="" selected>Any</option>
                    <?php else: ?>
                    <option value="">Any</option>
                    <?php endif; ?>
                    <?php foreach($db->dmarcStatus as $index => $status): ?>
                    <?php if($index !== "temperror" && $index !== 'softfail'): ?>
                    <option value="<?php echo $index; ?>" class="text-<?php echo $status['color']; ?>"<?php echo @$query['status'] == $index ? ' selected' : ''; ?>><?php echo $status['label']; ?></option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </th>
            <th>
                <label class="label label-text" for="search-date">Date</label>
                <?php $months = $db->getDmarcMonths(); ?>
                <select id="search-date" name="date" class="select select-sm appearance-none" aria-label="select" form="search" onchange="document.querySelector('form#search').submit()">
                    <?php foreach($months as $index => $month): ?>
                    <option value="<?php echo $month; ?>"<?php echo @$query['date'] == $month || (empty($query['date']) && $index === 0) ? ' selected' : ''; ?>><?php echo $month; ?></option>
                    <?php endforeach; ?>
                </select>
            </th>
            <th class="max-w-32">
                <?php if($user->role === 'admin'): ?>
                <label class="label label-text" for="search-domain">Domain</label>
                <?php $domains = $db->getDmarcDomains(); ?>
                <select id="search-domain" name="domain" class="select select-sm appearance-none" aria-label="select" form="search" onchange="document.querySelector('form#search').submit()">
                    <?php if(!isset($query['domain']) || empty($query['domain'])): ?>
                    <option value="" selected>Any</option>
                    <?php else: ?>
                    <option value="">Any</option>
                    <?php endif; ?>
                    <?php foreach($domains as $domain): ?>
                    <option value="<?php echo $domain; ?>"<?php echo @$query['domain'] == $domain ? ' selected' : ''; ?>><?php echo $domain; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <span class="label label-text">Domain</span>
                <?php endif; ?>
            </th>
            <?php $reporters = $db->getDmarcReporters(); ?>
            <th class="max-w-32">
                <label class="label label-text" for="search-reporter">Reporter</label>
                <select id="search-reporter" name="reporter" class="select select-sm appearance-none" aria-label="select" form="search" onchange="document.querySelector('form#search').submit()">
                    <?php if(!isset($query['reporter']) || empty($query['reporter'])): ?>
                    <option value="" selected>Any</option>
                    <?php else: ?>
                    <option value="">Any</option>
                    <?php endif; ?>
                    <?php foreach($reporters as $reporter): ?>
                    <option value="<?php echo $reporter; ?>"<?php echo @$query['reporter'] == $reporter ? ' selected' : ''; ?>><?php echo $reporter; ?></option>
                    <?php endforeach; ?>
                </select>
            </th>
            <th class="max-w-32">
                <label class="label label-text" for="search-report_id">Report ID</label>
                <input type="text" id="search-report_id" name="report_id" class="input input-sm" minlength="2" form="search" value="<?php echo @$query['report_id']; ?>" />
            </th>
            <th>
                <span class="label label-text">Messages</span>
            </th>
        </tr>
    </thead>
    <tbody id="table-body">
        <?php if(count($reports) === 0): ?>
            <tr>
                <td colspan="6" class="text-center">No reports found</td>
            </tr>
        <?php else: ?>
            <?php foreach($reports as $report): ?>
            <tr class="hover cursor-pointer" onclick="getReport(<?php echo $report['serial']; ?>, '<?php echo $report['status']; ?>');" data-id="<?php echo $report['serial']; ?>">
                <td><span class="badge badge-md badge-<?php echo $db->dmarcStatus[$report['status']]['color']; ?> font-bold"><?php echo $db->dmarcStatus[$report['status']]['label']; ?></span></td>
                <td><?php echo $report['mindate']; ?> - <?php echo $report['maxdate']; ?></td>
                <td><?php echo $report['domain']; ?></td>
                <td><?php echo $report['org']; ?></td>
                <td><?php echo $report['reportId']; ?></td>
                <td><?php echo $report['messages']; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="text-center">
            <td colspan="6">
                <nav class="navbar">
                    <div class="navbar-start gap-2">
                        <input type="submit" class="btn btn-sm btn-success" value="🔍 Search" form="search" />
                        <input type="reset" class="btn btn-sm btn-warning" value="🗘 Reset" form="search" onclick="resetSearch()" />
                    </div>
                    <div class="navbar-center items-center">
                        <span class="badge badge-lg badge-default"><?php echo $db->pagination * ($page - 1); ?> - <?php echo $db->pagination * ($page - 1) + count($reports); ?></span>
                    </div>
                    <div class="navbar-end gap-2">
                        <?php if($db->pagination * ($page - 1) > 0): ?>
                            <a href="<?php echo $prevUrl; ?>" class="btn btn-sm">Previous</a>
                        <?php else: ?>
                            <a href="#" class="btn btn-sm" disabled>Previous</a>
                        <?php endif; ?>
                        <?php if(count($reports) == $db->pagination): ?>
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
<div id="report-modal" class="overlay modal overlay-open:opacity-100 modal-middle-start justify-center hidden" role="dialog" tabindex="-1">
<div class="modal-dialog overlay-open:opacity-100 max-w-full">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"></h3>
            <button type="button" class="btn btn-text btn-circle btn-sm absolute end-3 top-3" aria-label="Close" data-overlay="#report-modal">
                <span class="text-xl">X</span>
            </button>
            <a href="" class="btn btn-info btn-sm absolute end-20 top-3" aria-label="Download XML Report" target="_download">
                <span class="text-xl">XML</span>
            </a>
        </div>
        <div class="modal-body">
            <h4 class="text-center"></h4>
            <table class="table-sm table table-pin-rows">
                <thead>
                    <tr>
                        <th>IP</th>
                        <th>Hostname</th>
                        <th>Messages</th>
                        <th>Disposition</th>
                        <th>Reason</th>
                        <th>DKIM Domain</th>
                        <th>DKIM Auth</th>
                        <th>DKIM Align</th>
                        <th>SPF Domain</th>
                        <th>SPF Auth</th>
                        <th>SPF Align</th>
                        <th>DMARC</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<script>
const dmarcStatus = <?php echo json_encode($db->dmarcStatus); ?>;
const dispositionColors = {
    'none': 'success',
    'quarantine': 'warning',
    'reject': 'error'
};
// reset
function resetSearch() {
    const fields = document.querySelectorAll('thead input, thead select');
    for(i = 0; i < fields.length; i++) {
        fields[i].value = "";
    }
    document.querySelector('form#search').submit();
}
// modal
async function getReport(report_id, status) {
    const tbody = document.querySelector('#report-modal tbody')
    tbody.innerHTML = "";
    const reportRes = await fetch(`<?php echo APP_ROOT; ?>?task=ajax&action=dmarc-report&report_id=${report_id}`);
    const report = await reportRes.json();
    if(report.success) {
        document.querySelector('#report-modal .modal-title').innerText = `${report.data.info.reportid}`;
        document.querySelector('#report-modal h4').innerHTML = `From ${report.data.info.org} for ${report.data.info.domain}<br>${report.data.info.mindate} - ${report.data.info.maxdate}<br>Policies: adkim=${report.data.info.policy_adkim}, aspf=${report.data.info.policy_aspf}, p=${report.data.info.policy_p}, sp=${report.data.info.policy_sp}, pct=${report.data.info.policy_pct}`;
        document.querySelector('#report-modal a').href = `<?php echo APP_ROOT;?>?task=download&action=dmarc-xml&report_id=${report_id}`;
        if(report.data.records.length) {
            for(let i = 0; i < report.data.records.length; i++) {
                tbody.innerHTML += `
                    <tr>
                        <td>${report.data.records[i]['ip']}</td>
                        <td>${report.data.records[i]['hostname']}</td>
                        <td>${report.data.records[i]['rcount']}</td>
                        <td><span class="badge badge-md badge-${dispositionColors[report.data.records[i]['disposition']]} font-bold">${report.data.records[i]['disposition']}</span></td>
                        <td>${report.data.records[i]['reason']}</td>
                        <td>${report.data.records[i]['dkimdomain']}</td>
                        <td><span class="badge badge-md badge-${dmarcStatus[report.data.records[i]['dkimresult']]['color']} font-bold">${dmarcStatus[report.data.records[i]['dkimresult']]['label']}</span></td>
                        <td><span class="badge badge-md badge-${dmarcStatus[report.data.records[i]['dkim_align']]['color']} font-bold">${dmarcStatus[report.data.records[i]['dkim_align']]['label']}</span></td>
                        <td>${report.data.records[i]['spfdomain']}</td>
                        <td><span class="badge badge-md badge-${dmarcStatus[report.data.records[i]['spfresult']]['color']} font-bold">${dmarcStatus[report.data.records[i]['spfresult']]['label']}</span></td>
                        <td><span class="badge badge-md badge-${dmarcStatus[report.data.records[i]['spf_align']]['color']} font-bold">${dmarcStatus[report.data.records[i]['spf_align']]['label']}</span></td>
                        <td><span class="badge badge-md badge-${dmarcStatus[status]['color']} font-bold">${dmarcStatus[status]['label']}</span></td>
                    </tr>
                `;
            }
        } else {
            tbody.innerHTML = '<tr><td colspan="12">No report records found.</td></tr>';
        }
        HSOverlay.open('#report-modal');
    } else {
        alert(`Error Fetching report: ${report.message}`);
    }
}
</script>