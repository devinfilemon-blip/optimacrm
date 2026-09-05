<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header('Location: list-company.php'); exit; }
?>
<head>
    <title>Company Details | <?php echo APP_NAME; ?></title>
    <?php include 'layouts/head.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<?php include 'layouts/body.php'; ?>
<div id="layout-wrapper">
    <?php include 'layouts/menu.php'; ?>
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18" id="pageTitle">Company Details</h4>
                            <div class="page-title-right d-flex align-items-center gap-3">
                                <a href="add-company.php?id=<?php echo $id; ?>" class="btn btn-outline-primary btn-sm"><i class="bx bx-edit-alt"></i> Edit Company</a>
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-company.php">Companies</a></li>
                                    <li class="breadcrumb-item active">Details</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="loadingNote" class="text-muted text-center py-5">Loading company details&hellip;</div>
                <div id="detailsWrap" style="display:none;">

                    <!-- Overview -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-8">
                            <div class="card optima-section-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                        <div>
                                            <h3 class="mb-1" id="ovName">&mdash;</h3>
                                            <p class="text-muted mb-2" id="ovIndustry"></p>
                                        </div>
                                        <span id="ovStatusBadge"></span>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6 mb-2"><i class="bx bx-user text-muted me-1"></i> <strong>Contact:</strong> <span id="ovContact">-</span></div>
                                        <div class="col-md-6 mb-2"><i class="bx bx-phone text-muted me-1"></i> <strong>Phone:</strong> <span id="ovPhone">-</span></div>
                                        <div class="col-md-6 mb-2"><i class="bx bx-envelope text-muted me-1"></i> <strong>Email:</strong> <span id="ovEmail">-</span></div>
                                        <div class="col-md-6 mb-2"><i class="bx bx-map text-muted me-1"></i> <strong>Location:</strong> <span id="ovLocation">-</span></div>
                                        <div class="col-md-6 mb-2"><i class="bx bx-id-card text-muted me-1"></i> <strong>GSTIN:</strong> <span id="ovGstin">-</span></div>
                                        <div class="col-md-6 mb-2"><i class="bx bx-calendar text-muted me-1"></i> <strong>Client Since:</strong> <span id="ovCreated">-</span></div>
                                        <div class="col-md-12 mb-2"><i class="bx bx-building-house text-muted me-1"></i> <strong>Address:</strong> <span id="ovAddress">-</span></div>
                                    </div>
                                    <div id="ovNotesWrap" style="display:none;">
                                        <hr>
                                        <strong>Notes:</strong>
                                        <p class="text-muted mb-0" id="ovNotes"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card optima-section-card h-100">
                                <div class="card-body">
                                    <h5 class="optima-section-title"><i class="bx bx-map-pin"></i>Hiring Locations</h5>
                                    <p class="optima-chart-subtitle">Where this client's open roles are based.</p>
                                    <div id="locationChart"><p class="text-muted text-center mb-0 py-4">Loading&hellip;</p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stat cards -->
                    <div class="row optima-stat-row g-4 mb-4">
                        <div class="col-xl-2 col-md-4">
                            <div class="card optima-stat-card accent-blue h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-blue"><i class="bx bx-briefcase-alt-2"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statTotalReq">&mdash;</p>
                                        <p class="optima-stat-label">Job Listings</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card optima-stat-card accent-cyan h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-cyan"><i class="bx bx-search-alt"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statOpenReq">&mdash;</p>
                                        <p class="optima-stat-label">Open Positions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card optima-stat-card accent-purple h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-purple"><i class="bx bx-group"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statVacancies">&mdash;</p>
                                        <p class="optima-stat-label">Total Vacancies</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card optima-stat-card accent-mint h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-mint"><i class="bx bx-user-check"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statPlacements">&mdash;</p>
                                        <p class="optima-stat-label">Candidates Placed</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card optima-stat-card accent-amber h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-amber"><i class="bx bx-trending-up"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statFillRate">&mdash;</p>
                                        <p class="optima-stat-label">Fill Rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card optima-stat-card accent-rose h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-rose"><i class="bx bx-rupee"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statRevenue">&mdash;</p>
                                        <p class="optima-stat-label">Revenue Received</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-6">
                            <div class="card optima-section-card h-100">
                                <div class="card-body">
                                    <h5 class="optima-section-title"><i class="bx bx-pie-chart-alt-2"></i>Job Listing Status</h5>
                                    <p class="optima-chart-subtitle">Where this client's postings currently stand.</p>
                                    <div id="statusChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card optima-section-card h-100">
                                <div class="card-body">
                                    <h5 class="optima-section-title"><i class="bx bx-line-chart"></i>Placements &amp; Revenue</h5>
                                    <p class="optima-chart-subtitle">Candidates joined and payments received, month by month.</p>
                                    <div id="trendChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-xl-12">
                            <div class="card optima-section-card">
                                <div class="card-body">
                                    <h5 class="optima-section-title"><i class="bx bx-sitemap"></i>Departments / Roles Hired For</h5>
                                    <p class="optima-chart-subtitle">Positions this client has opened with us, by role.</p>
                                    <div id="postChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Job listings table -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-12">
                            <div class="card optima-section-card">
                                <div class="card-body">
                                    <h5 class="optima-section-title"><i class="bx bx-list-ul"></i>Job Listings</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Req No</th><th>Post</th><th>Type</th><th>Vacancies</th>
                                                    <th>Location</th><th>Status</th><th>Open Date</th><th>Recruiter</th><th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="reqTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placed candidates table -->
                    <div class="row g-4">
                        <div class="col-xl-12">
                            <div class="card optima-section-card">
                                <div class="card-body">
                                    <h5 class="optima-section-title"><i class="bx bx-user-check"></i>Candidates Placed Here</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Candidate</th><th>Post</th><th>Joining Date</th><th>Status</th>
                                                    <th>CTC</th><th>Invoiced</th><th>Received</th><th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="placeTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>
</body>
</html>

<?php include 'layouts/vendor-scripts.php'; ?>
<script src="assets/libs/apexcharts/apexcharts.min.js"></script>
<script>
var companyId = <?php echo $id; ?>;

// Same fixed 8-hue CVD-safe palette used on the Reports page, so a company
// scoped to a handful of roles/locations still reads as part of one system.
var REPORT_COLORS = {
    blue:    { light: '#2a78d6', dark: '#3987e5' },
    orange:  { light: '#eb6834', dark: '#d95926' },
    aqua:    { light: '#1baf7a', dark: '#199e70' },
    gold:    { light: '#eda100', dark: '#c98500' },
    magenta: { light: '#e87ba4', dark: '#d55181' },
    green:   { light: '#008300', dark: '#008300' },
    violet:  { light: '#4a3aa7', dark: '#9085e9' },
    red:     { light: '#e34948', dark: '#e66767' },
    other:   { light: '#b9b6c9', dark: '#565a6e' }
};
var REPORT_PALETTE_ORDER = ['blue', 'orange', 'aqua', 'gold', 'magenta', 'green', 'violet', 'red'];
function isDarkTheme() { return document.body.classList.contains('crm-dark'); }
function slot(name) { return REPORT_COLORS[name][isDarkTheme() ? 'dark' : 'light']; }
function rankedColors(rows) { return rows.map(function (r, i) { return slot(REPORT_PALETTE_ORDER[i % REPORT_PALETTE_ORDER.length]); }); }
function foreColor() { return isDarkTheme() ? '#cbd5e1' : '#5c5876'; }
function mutedColor() { return isDarkTheme() ? '#8b95ab' : '#8b87a3'; }
function gridColor() { return isDarkTheme() ? 'rgba(255,255,255,0.07)' : 'rgba(36,27,69,0.06)'; }
function surfaceColor() { return isDarkTheme() ? '#131a2e' : '#ffffff'; }

function fmtCurrency(n) {
    n = parseFloat(n || 0);
    if (n >= 100000) return '₹' + (n / 100000).toFixed(1) + 'L';
    return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
function monthLabel(ym) {
    var parts = ym.split('-');
    var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, 1);
    return d.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
}

function badgeForReqStatus(status) {
    var cls = 'optima-badge-default';
    var s = (status || '').toLowerCase();
    if (s.indexOf('search') !== -1) cls = 'optima-badge-searching';
    else if (s.indexOf('closed') !== -1 || s === 'joined') cls = 'optima-badge-closed';
    else if (s.indexOf('hold') !== -1) cls = 'optima-badge-hold';
    else if (s.indexOf('refine') !== -1 || s.indexOf('not') !== -1) cls = 'optima-badge-refine';
    return '<span class="optima-badge ' + cls + '">' + esc(status || 'Unknown') + '</span>';
}
function badgeForJoiningStatus(status) {
    var cls = 'optima-badge-default';
    var s = (status || '').toLowerCase();
    if (s === 'amount received') cls = 'optima-badge-closed';
    else if (s === 'job left' || s === 'not joined') cls = 'optima-badge-refine';
    else if (s === 'invoice sent') cls = 'optima-badge-hold';
    else if (s === 'joined') cls = 'optima-badge-searching';
    return '<span class="optima-badge ' + cls + '">' + esc(status || 'Offer Accepted') + '</span>';
}

var charts = {};
function renderChart(id, options) {
    var el = document.getElementById(id);
    el.innerHTML = '';
    if (charts[id]) { charts[id].destroy(); }
    charts[id] = new ApexCharts(el, options);
    charts[id].render();
}
function emptyState(id, message) {
    document.getElementById(id).innerHTML = '<p class="text-muted text-center mb-0 py-4">' + message + '</p>';
}
function baseChart(type, height) {
    return {
        chart: { height: height, type: type, toolbar: { show: false }, foreColor: mutedColor(), fontFamily: 'inherit' },
        grid: { borderColor: gridColor(), strokeDashArray: 0, padding: { left: 8, right: 8 } },
        xaxis: { labels: { style: { colors: mutedColor() } }, axisBorder: { color: gridColor() }, axisTicks: { color: gridColor() } },
        yaxis: { labels: { style: { colors: mutedColor() } } }
    };
}

function renderStatusChart(rows) {
    if (!rows.length) { emptyState('statusChart', 'No job listings yet.'); return; }
    var total = rows.reduce(function (sum, r) { return sum + r.count; }, 0);
    renderChart('statusChart', {
        chart: { height: 300, type: 'donut', foreColor: mutedColor(), fontFamily: 'inherit' },
        colors: rankedColors(rows),
        labels: rows.map(r => r.label || 'Unknown'),
        series: rows.map(r => r.count),
        stroke: { width: 2, colors: [surfaceColor()] },
        fill: { type: 'gradient', gradient: { shade: 'light', shadeIntensity: 0.45, opacityFrom: 1, opacityTo: 0.85 } },
        dataLabels: { enabled: true, formatter: function (val) { return Math.round(val) + '%'; }, style: { colors: ['#ffffff'] }, dropShadow: { enabled: false } },
        legend: { position: 'bottom', labels: { colors: foreColor() }, markers: { radius: 3 } },
        plotOptions: { pie: { donut: { size: '62%', labels: {
            show: true, value: { color: foreColor(), fontSize: '22px', fontWeight: 700 },
            total: { show: true, label: 'Listings', color: mutedColor(), formatter: function () { return total; } }
        } } } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: function (val) { return val + ' listing(s)'; } } }
    });
}

function renderTrendChart(rows) {
    if (!rows.length) { emptyState('trendChart', 'No placements yet.'); return; }
    renderChart('trendChart', $.extend(true, baseChart('line', 300), {
        colors: [slot('aqua'), slot('gold')],
        stroke: { width: [3, 3], curve: 'smooth' },
        series: [
            { name: 'Placements', type: 'column', data: rows.map(r => r.placements) },
            { name: 'Revenue Received', type: 'line', data: rows.map(r => Math.round(r.revenue)) }
        ],
        xaxis: { categories: rows.map(r => monthLabel(r.ym)) },
        yaxis: [
            { title: { text: 'Placements', style: { color: mutedColor() } }, forceNiceScale: true },
            { opposite: true, title: { text: 'Revenue (₹)', style: { color: mutedColor() } }, labels: { style: { colors: mutedColor() }, formatter: v => fmtCurrency(v) } }
        ],
        plotOptions: { bar: { columnWidth: '40%', borderRadius: 4, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'left', labels: { colors: foreColor() }, markers: { radius: 3 } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: function (val, opts) { return opts.seriesIndex === 1 ? fmtCurrency(val) : val; } } }
    }));
}

function renderRankedBar(id, rows, seriesName, emptyMsg) {
    if (!rows.length) { emptyState(id, emptyMsg); return; }
    renderChart(id, $.extend(true, baseChart('bar', Math.max(180, rows.length * 42)), {
        colors: rankedColors(rows),
        fill: { type: 'gradient', gradient: { shade: 'light', type: 'horizontal', shadeIntensity: 0.45, opacityFrom: 0.95, opacityTo: 0.75 } },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, borderRadiusApplication: 'end', barHeight: '60%', distributed: true } },
        series: [{ name: seriesName, data: rows.map(r => r.count) }],
        xaxis: { categories: rows.map(r => r.label) },
        dataLabels: { enabled: true, style: { colors: [foreColor()] }, offsetX: 8, background: { enabled: false } },
        legend: { show: false },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light' }
    }));
}

var lastData = null;

function renderOverview(c) {
    document.getElementById('pageTitle').textContent = c.sCompanyName;
    document.getElementById('ovName').textContent = c.sCompanyName;
    document.getElementById('ovIndustry').textContent = c.sIndustry || 'Industry not specified';
    document.getElementById('ovStatusBadge').innerHTML = c.sStatus === 'Active'
        ? '<span class="optima-badge optima-badge-closed">Active</span>'
        : '<span class="optima-badge optima-badge-default">Inactive</span>';
    document.getElementById('ovContact').textContent = c.sContactPerson || '-';
    document.getElementById('ovPhone').textContent = c.sPhone || '-';
    document.getElementById('ovEmail').textContent = c.sEmail || '-';
    document.getElementById('ovLocation').textContent = c.sLocation || '-';
    document.getElementById('ovGstin').textContent = c.sGstin || '-';
    document.getElementById('ovCreated').textContent = c.dCreatedAt ? c.dCreatedAt.substring(0, 10) : '-';
    document.getElementById('ovAddress').textContent = c.sAddress || '-';
    if (c.sNotes) {
        document.getElementById('ovNotesWrap').style.display = '';
        document.getElementById('ovNotes').textContent = c.sNotes;
    }
}

function renderStats(s) {
    document.getElementById('statTotalReq').textContent = s.totalRequirements;
    document.getElementById('statOpenReq').textContent = s.openRequirements;
    document.getElementById('statVacancies').textContent = s.totalVacancies;
    document.getElementById('statPlacements').textContent = s.totalPlacements;
    document.getElementById('statFillRate').textContent = s.fillRate + '%';
    document.getElementById('statRevenue').textContent = fmtCurrency(s.totalRevenue);
}

function renderReqTable(rows) {
    var body = document.getElementById('reqTableBody');
    if (!rows.length) { body.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No job listings for this company yet.</td></tr>'; return; }
    body.innerHTML = rows.map(function (r) {
        return '<tr>' +
            '<td>' + esc(crmReqNoDisplay(r.sReqNo)) + '</td>' +
            '<td>' + esc(r.sPost) + '</td>' +
            '<td>' + esc(r.sType) + '</td>' +
            '<td>' + esc(r.iNoOfVacancy) + '</td>' +
            '<td>' + esc(r.sLocation || '-') + '</td>' +
            '<td>' + badgeForReqStatus(r.sStatus) + '</td>' +
            '<td>' + esc(r.dOpenDate || '-') + '</td>' +
            '<td>' + esc(r.sRecruiter || '-') + '</td>' +
            '<td><a href="add-requirement.php?id=' + r.iReqId + '" class="btn btn-outline-primary btn-sm">Open</a></td>' +
            '</tr>';
    }).join('');
}

function renderPlaceTable(rows) {
    var body = document.getElementById('placeTableBody');
    if (!rows.length) { body.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No candidates placed at this company yet.</td></tr>'; return; }
    body.innerHTML = rows.map(function (p) {
        var nameCell = p.iCandidateId
            ? '<a href="add-candidate.php?id=' + p.iCandidateId + '" target="_blank">' + esc(p.sCandidateName || 'Candidate') + '</a>'
            : esc(p.sCandidateName || 'Candidate');
        return '<tr>' +
            '<td>' + nameCell + '</td>' +
            '<td>' + esc(p.sPost || '-') + '</td>' +
            '<td>' + esc(p.dJoiningDate || '-') + '</td>' +
            '<td>' + badgeForJoiningStatus(p.sJoiningStatus) + '</td>' +
            '<td>' + fmtCurrency(p.dCtc) + '</td>' +
            '<td>' + fmtCurrency(p.dAmount) + '</td>' +
            '<td>' + fmtCurrency(p.dRecAmount) + '</td>' +
            '<td><a href="add-placement.php?id=' + p.iPlacementId + '" class="btn btn-outline-primary btn-sm">Open</a></td>' +
            '</tr>';
    }).join('');
}

function renderAll(d) {
    lastData = d;
    renderOverview(d);
    renderStats(d.stats);
    renderStatusChart(d.stats.statusBreakdown || []);
    renderTrendChart(d.stats.monthlyTrend || []);
    renderRankedBar('postChart', d.stats.postBreakdown || [], 'Job Listings', 'No roles posted for this company yet.');
    renderRankedBar('locationChart', d.stats.locationBreakdown || [], 'Job Listings', 'No location data yet.');
    renderReqTable(d.requirements || []);
    renderPlaceTable(d.placements || []);
}

function loadCompanyDetails() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getcompanydetails', id: companyId })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('loadingNote').style.display = 'none';
        if (res.status !== 'success') {
            document.getElementById('loadingNote').style.display = '';
            document.getElementById('loadingNote').textContent = res.message;
            return;
        }
        document.getElementById('detailsWrap').style.display = '';
        renderAll(res.data);
    })
    .catch(function () {
        document.getElementById('loadingNote').textContent = 'Could not load company details. Please try again.';
    });
}

window.addEventListener('crm-theme-changed', function () { if (lastData) renderAll(lastData); });
loadCompanyDetails();
</script>
