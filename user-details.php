<?php include 'layouts/session.php';
$crmUserRole = $_SESSION['userRole'] ?? '';
$crmIsAdminPage = $crmUserRole === 'Admin';
$crmIsTeamLeaderPage = $crmUserRole === 'Team Leader';
if (!$crmIsAdminPage && !$crmIsTeamLeaderPage) { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header('Location: list-user.php'); exit; }
?>
<head>
    <title>User Details | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18" id="pageTitle">User Details</h4>
                            <div class="page-title-right d-flex align-items-center gap-3">
                                <a href="add-user.php?id=<?php echo $id; ?>" class="btn btn-outline-primary btn-sm"><i class="bx bx-edit-alt"></i> Edit</a>
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-user.php"><?php echo $crmIsAdminPage ? 'Users' : 'My Recruiters'; ?></a></li>
                                    <li class="breadcrumb-item active">Details</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="loadingNote" class="text-muted text-center py-5">Loading details&hellip;</div>
                <div id="detailsWrap" style="display:none;">

                    <!-- Overview -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-12">
                            <div class="card optima-section-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                        <div>
                                            <h3 class="mb-1" id="ovName">&mdash;</h3>
                                            <p class="text-muted mb-2" id="ovRole"></p>
                                        </div>
                                        <span id="ovStatusBadge"></span>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-3 mb-2"><i class="bx bx-phone text-muted me-1"></i> <strong>Phone:</strong> <span id="ovPhone">-</span></div>
                                        <div class="col-md-3 mb-2"><i class="bx bx-envelope text-muted me-1"></i> <strong>Email:</strong> <span id="ovEmail">-</span></div>
                                        <div class="col-md-3 mb-2" id="ovManagerWrap"><i class="bx bx-user-voice text-muted me-1"></i> <strong>Reports To:</strong> <span id="ovManager">-</span></div>
                                        <div class="col-md-3 mb-2"><i class="bx bx-calendar text-muted me-1"></i> <strong>Member Since:</strong> <span id="ovCreated">-</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overall data -->
                    <p class="optima-chart-subtitle text-uppercase fw-semibold mb-2" style="margin-top:0;">Overall</p>
                    <div class="row optima-stat-row g-4 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card optima-stat-card accent-blue h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-blue"><i class="bx bx-briefcase-alt-2"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statTotalLeads">&mdash;</p>
                                        <p class="optima-stat-label">Total Leads (Requirements)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card optima-stat-card accent-mint h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-mint"><i class="bx bx-user-plus"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statTotalCandidates">&mdash;</p>
                                        <p class="optima-stat-label">Total Candidates Sourced</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card optima-stat-card accent-purple h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-purple"><i class="bx bx-user-check"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statTotalPlacements">&mdash;</p>
                                        <p class="optima-stat-label">Total Placements</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
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

                    <!-- Current month -->
                    <p class="optima-chart-subtitle text-uppercase fw-semibold mb-2" id="monthLabel">This Month</p>
                    <div class="row optima-stat-row g-4 mb-4">
                        <div class="col-xl-4 col-md-4">
                            <div class="card optima-stat-card accent-cyan h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-cyan"><i class="bx bx-briefcase-alt-2"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statMonthLeads">&mdash;</p>
                                        <p class="optima-stat-label">Leads This Month</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-4">
                            <div class="card optima-stat-card accent-amber h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-amber"><i class="bx bx-user-plus"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statMonthCandidates">&mdash;</p>
                                        <p class="optima-stat-label">Candidates This Month</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-4">
                            <div class="card optima-stat-card accent-mint h-100">
                                <div class="card-body">
                                    <div class="optima-stat-icon bg-mint"><i class="bx bx-user-check"></i></div>
                                    <div class="optima-stat-text">
                                        <p class="optima-stat-value" id="statMonthPlacements">&mdash;</p>
                                        <p class="optima-stat-label">Placements This Month</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-12">
                            <div class="card optima-section-card">
                                <div class="card-body">
                                    <h5 class="optima-section-title"><i class="bx bx-bar-chart-alt-2"></i>Monthly Activity</h5>
                                    <p class="optima-chart-subtitle">Leads, candidates and placements over the last 6 months — the current month is highlighted.</p>
                                    <div id="trendChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team members -->
                    <div class="row g-4" id="teamRow" style="display:none;">
                        <div class="col-xl-12">
                            <div class="card optima-section-card">
                                <div class="card-body">
                                    <h5 class="optima-section-title"><i class="bx bx-group"></i>Team Members</h5>
                                    <p class="optima-chart-subtitle">Recruiters reporting to <span id="teamLeadName">this Team Leader</span>.</p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Name</th><th>Role</th><th>Status</th>
                                                    <th>Leads</th><th>Candidates</th><th>This Month</th><th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="teamTableBody"></tbody>
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
var targetUserId = <?php echo $id; ?>;

// Same fixed 8-hue CVD-safe palette used across Reports / Company Details.
var REPORT_COLORS = {
    blue:    { light: '#2a78d6', dark: '#3987e5' },
    orange:  { light: '#eb6834', dark: '#d95926' },
    aqua:    { light: '#1baf7a', dark: '#199e70' },
    gold:    { light: '#eda100', dark: '#c98500' },
    magenta: { light: '#e87ba4', dark: '#d55181' },
    green:   { light: '#008300', dark: '#008300' },
    violet:  { light: '#4a3aa7', dark: '#9085e9' },
    red:     { light: '#e34948', dark: '#e66767' }
};
function isDarkTheme() { return document.body.classList.contains('crm-dark'); }
function slot(name) { return REPORT_COLORS[name][isDarkTheme() ? 'dark' : 'light']; }
function foreColor() { return isDarkTheme() ? '#cbd5e1' : '#5c5876'; }
function mutedColor() { return isDarkTheme() ? '#8b95ab' : '#8b87a3'; }
function gridColor() { return isDarkTheme() ? 'rgba(255,255,255,0.07)' : 'rgba(36,27,69,0.06)'; }
function currentMonthBand() { return isDarkTheme() ? 'rgba(124,92,240,0.16)' : 'rgba(83,48,201,0.08)'; }

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

var charts = {};
function renderChart(id, options) {
    var el = document.getElementById(id);
    el.innerHTML = '';
    if (charts[id]) { charts[id].destroy(); }
    charts[id] = new ApexCharts(el, options);
    charts[id].render();
}
function emptyState(id, message) {
    document.getElementById(id).innerHTML = '<p class="text-muted text-center mb-0 py-5">' + message + '</p>';
}

function renderTrendChart(rows) {
    if (!rows.length) { emptyState('trendChart', 'No activity yet.'); return; }
    var currentYm = rows[rows.length - 1].ym;
    renderChart('trendChart', {
        chart: { height: 340, type: 'bar', toolbar: { show: false }, foreColor: mutedColor(), fontFamily: 'inherit' },
        grid: { borderColor: gridColor(), strokeDashArray: 0, padding: { left: 8, right: 8 } },
        colors: [slot('blue'), slot('aqua'), slot('gold')],
        fill: { type: 'gradient', gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.45, opacityFrom: 0.95, opacityTo: 0.7 } },
        series: [
            { name: 'Leads', data: rows.map(r => r.leads) },
            { name: 'Candidates', data: rows.map(r => r.candidates) },
            { name: 'Placements', data: rows.map(r => r.placements) }
        ],
        xaxis: {
            categories: rows.map(r => monthLabel(r.ym)),
            labels: { style: { colors: mutedColor() } },
            axisBorder: { color: gridColor() }, axisTicks: { color: gridColor() }
        },
        yaxis: { labels: { style: { colors: mutedColor() } }, forceNiceScale: true },
        annotations: {
            xaxis: [{
                x: monthLabel(currentYm),
                fillColor: currentMonthBand(),
                label: { text: 'Current Month', style: { color: foreColor(), background: 'transparent' }, orientation: 'horizontal' }
            }]
        },
        plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'left', labels: { colors: foreColor() }, markers: { radius: 3 } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light' }
    });
}

var lastData = null;

function renderOverview(u) {
    document.getElementById('pageTitle').textContent = u.sName + ' — Details';
    document.getElementById('ovName').textContent = u.sName;
    document.getElementById('ovRole').textContent = u.sRole;
    document.getElementById('ovStatusBadge').innerHTML = parseInt(u.sIs_active) === 1
        ? '<span class="optima-badge optima-badge-closed">Active</span>'
        : '<span class="optima-badge optima-badge-default">Inactive</span>';
    document.getElementById('ovPhone').textContent = u.sPhone || '-';
    document.getElementById('ovEmail').textContent = u.sEmail || '-';
    document.getElementById('ovCreated').textContent = u.sCreatedTimeStamp ? u.sCreatedTimeStamp.substring(0, 10) : '-';
    if (u.sManagerName) {
        document.getElementById('ovManager').textContent = u.sManagerName;
    } else {
        document.getElementById('ovManagerWrap').style.display = 'none';
    }

    var now = new Date();
    document.getElementById('monthLabel').textContent = 'This Month — ' + now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function renderStats(s) {
    document.getElementById('statTotalLeads').textContent = s.totalLeads;
    document.getElementById('statTotalCandidates').textContent = s.totalCandidates;
    document.getElementById('statTotalPlacements').textContent = s.totalPlacements;
    document.getElementById('statRevenue').textContent = fmtCurrency(s.totalRevenue);
    document.getElementById('statMonthLeads').textContent = s.monthLeads;
    document.getElementById('statMonthCandidates').textContent = s.monthCandidates;
    document.getElementById('statMonthPlacements').textContent = s.monthPlacements;
}

function renderTeam(u) {
    var team = u.team || [];
    if (!team.length) return;
    document.getElementById('teamRow').style.display = '';
    document.getElementById('teamLeadName').textContent = u.sName;
    document.getElementById('teamTableBody').innerHTML = team.map(function (m) {
        var statusBadge = parseInt(m.sIs_active) === 1
            ? '<span class="optima-badge optima-badge-closed">Active</span>'
            : '<span class="optima-badge optima-badge-default">Inactive</span>';
        return '<tr>' +
            '<td>' + esc(m.sName) + '</td>' +
            '<td>' + esc(m.sRole) + '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td>' + m.totalLeads + '</td>' +
            '<td>' + m.totalCandidates + '</td>' +
            '<td>' + m.monthActivity + '</td>' +
            '<td><a href="user-details.php?id=' + m.iUserid + '" class="btn btn-outline-primary btn-sm">Open</a></td>' +
            '</tr>';
    }).join('');
}

function renderAll(u) {
    lastData = u;
    renderOverview(u);
    renderStats(u.stats);
    renderTrendChart(u.stats.monthlyTrend || []);
    renderTeam(u);
}

function loadUserDetails() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getuserdetails', id: targetUserId })
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
        document.getElementById('loadingNote').textContent = 'Could not load user details. Please try again.';
    });
}

window.addEventListener('crm-theme-changed', function () { if (lastData) renderAll(lastData); });
loadUserDetails();
</script>
