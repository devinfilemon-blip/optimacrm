<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Dashboard | <?php echo APP_NAME; ?></title>
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
                            <div>
                                <h4 class="mb-0 font-size-18">Dashboard</h4>
                                <p class="optima-dashboard-subtitle">Recruitment pipeline snapshot for <?php echo date('F Y'); ?></p>
                            </div>
                            <div class="page-title-right d-flex align-items-center gap-3">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item active">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row optima-stat-row g-4 mb-4" id="optimaStatCards">
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-purple h-100 optima-card-clickable" data-href="list-company.php">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-purple"><i class="bx bx-buildings"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statCompanies">&mdash;</p>
                                    <p class="optima-stat-label">Active Companies</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-9 col-md-6">
                        <div class="card optima-stat-card optima-combo-card h-100">
                            <div class="card-body optima-combo-body">
                                <p class="optima-combo-heading">Vacancy Overview</p>
                                <div class="optima-combo-grid">
                                    <div class="optima-combo-item tone-blue optima-card-clickable" data-href="list-requirement.php">
                                        <div class="optima-stat-icon bg-blue"><i class="bx bx-briefcase-alt-2"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statTotalVacancy">&mdash;</p>
                                            <p class="optima-stat-label">Total Vacancies</p>
                                        </div>
                                    </div>
                                    <div class="optima-combo-item tone-cyan optima-card-clickable" data-href="list-requirement.php?openOnly=1">
                                        <div class="optima-stat-icon bg-cyan"><i class="bx bx-search-alt"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statSearching">&mdash;</p>
                                            <p class="optima-stat-label">Searching / Open</p>
                                        </div>
                                    </div>
                                    <div class="optima-combo-item tone-rose optima-card-clickable" data-href="list-requirement.php?closedOnly=1">
                                        <div class="optima-stat-icon bg-rose"><i class="bx bx-lock-alt"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statClosed">&mdash;</p>
                                            <p class="optima-stat-label">Closed</p>
                                        </div>
                                    </div>
                                    <div class="optima-combo-item tone-mint optima-card-clickable" data-href="list-requirement.php?q=Selected">
                                        <div class="optima-stat-icon bg-mint"><i class="bx bx-check-circle"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statSelected">&mdash;</p>
                                            <p class="optima-stat-label">Selected Candidates</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-6">
                        <div class="card optima-stat-card optima-combo-card h-100">
                            <div class="card-body optima-combo-body">
                                <p class="optima-combo-heading">Placement Tracking</p>
                                <div class="optima-combo-grid">
                                    <div class="optima-combo-item tone-mint optima-card-clickable" data-href="list-placement.php?period=monthly&amp;dateField=dJoiningDate">
                                        <div class="optima-stat-icon bg-mint"><i class="bx bx-user-check"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statPlacementsMonth">&mdash;</p>
                                            <p class="optima-stat-label">This Month</p>
                                        </div>
                                    </div>
                                    <div class="optima-combo-item tone-purple optima-card-clickable" data-href="list-placement.php">
                                        <div class="optima-stat-icon bg-purple"><i class="bx bx-group"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statPlacementsTillDate">&mdash;</p>
                                            <p class="optima-stat-label">Till Date</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card optima-stat-card accent-blue h-100 optima-card-clickable" data-href="list-reminder.php?dueOnly=1">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-blue"><i class="bx bx-error-circle"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statDueReminders">&mdash;</p>
                                    <p class="optima-stat-label">Due / Overdue Reminders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-12">
                        <div class="card optima-stat-card optima-combo-card">
                            <div class="card-body optima-combo-body">
                                <p class="optima-combo-heading">Revenue &amp; Financial</p>
                                <div class="optima-combo-grid">
                                    <div class="optima-combo-item tone-mint optima-card-clickable" data-href="list-placement.php?period=monthly&amp;dateField=dPaymentRecDate">
                                        <div class="optima-stat-icon bg-mint"><i class="bx bx-trending-up"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statRevenueMonth">&mdash;</p>
                                            <p class="optima-stat-label">Revenue This Month</p>
                                        </div>
                                    </div>
                                    <div class="optima-combo-item tone-blue optima-card-clickable" data-href="list-placement.php">
                                        <div class="optima-stat-icon bg-blue"><i class="bx bx-rupee"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statRevenueTillDate">&mdash;</p>
                                            <p class="optima-stat-label">Revenue Till Date</p>
                                        </div>
                                    </div>
                                    <div class="optima-combo-item tone-amber optima-card-clickable" data-href="list-placement.php">
                                        <div class="optima-stat-icon bg-amber"><i class="bx bx-receipt"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statInvoiced">&mdash;</p>
                                            <p class="optima-stat-label">Total Invoiced</p>
                                        </div>
                                    </div>
                                    <div class="optima-combo-item tone-cyan optima-card-clickable" data-href="list-placement.php">
                                        <div class="optima-stat-icon bg-cyan"><i class="bx bx-check-double"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statReceived">&mdash;</p>
                                            <p class="optima-stat-label">Amount Received</p>
                                        </div>
                                    </div>
                                    <div class="optima-combo-item tone-rose optima-card-clickable" data-href="list-outstanding-invoice.php">
                                        <div class="optima-stat-icon bg-rose"><i class="bx bx-wallet"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statPending">&mdash;</p>
                                            <p class="optima-stat-label">Amount Pending</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-6">
                        <div class="card optima-section-card h-100">
                            <div class="card-body">
                                <h4 class="optima-section-title"><i class="bx bx-briefcase-alt-2"></i>Recent Requirements</h4>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm mb-0 optima-table">
                                        <thead>
                                            <tr><th>Req No</th><th>Company</th><th>Post</th><th>Status</th></tr>
                                        </thead>
                                        <tbody id="recentReqBody">
                                            <tr><td colspan="4" class="text-center text-muted">Loading&hellip;</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="list-requirement.php" class="btn btn-sm btn-outline-primary mt-3">View all requirements</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card optima-section-card h-100">
                            <div class="card-body">
                                <h4 class="optima-section-title"><i class="bx bx-user-check"></i>Recent Placements</h4>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm mb-0 optima-table">
                                        <thead>
                                            <tr><th>Candidate</th><th>Company</th><th>Post</th><th>Status</th></tr>
                                        </thead>
                                        <tbody id="recentPlaceBody">
                                            <tr><td colspan="4" class="text-center text-muted">Loading&hellip;</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="list-placement.php" class="btn btn-sm btn-outline-primary mt-3">View all placements</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-12">
                        <div class="card optima-section-card">
                            <div class="card-body">
                                <h4 class="optima-section-title"><i class="bx bx-pie-chart-alt-2"></i>Requirement Status Breakdown</h4>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm mb-0 optima-table">
                                        <thead>
                                            <tr id="statusBoardHead"><th>Sr No.</th><th>Data</th></tr>
                                        </thead>
                                        <tbody id="statusBoardBody">
                                            <tr><td colspan="2" class="text-center text-muted">Loading&hellip;</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-6">
                        <div class="card optima-section-card h-100">
                            <div class="card-body">
                                <h4 class="optima-section-title"><i class="bx bx-trending-up"></i>Placements &amp; Revenue (6 months)</h4>
                                <div id="trendChart">
                                    <p class="text-muted text-center mb-0 py-5">Loading&hellip;</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card optima-section-card h-100">
                            <div class="card-body">
                                <h4 class="optima-section-title"><i class="bx bx-donate-heart"></i>Lead Source Breakdown</h4>
                                <div id="leadSourceChart">
                                    <p class="text-muted text-center mb-0 py-5">Loading&hellip;</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-12">
                        <div class="card optima-section-card">
                            <div class="card-body">
                                <h4 class="optima-section-title"><i class="bx bx-calendar-check"></i>Openings vs Placements (Month-wise)</h4>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm mb-0 optima-table">
                                        <thead>
                                            <tr><th>Month</th><th>Openings</th><th>Placements Done</th><th>Fill Rate</th></tr>
                                        </thead>
                                        <tbody id="monthlyTrendBody">
                                            <tr><td colspan="4" class="text-center text-muted">Loading&hellip;</td></tr>
                                        </tbody>
                                    </table>
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
function fmtCurrency(n) {
    n = parseFloat(n || 0);
    return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
function badgeForStatus(status) {
    var cls = 'optima-badge-default';
    if (!status) status = 'Unknown';
    var s = status.toLowerCase();
    if (s.indexOf('search') !== -1) cls = 'optima-badge-searching';
    else if (s.indexOf('closed') !== -1 || s === 'joined' || s.indexOf('received') !== -1) cls = 'optima-badge-closed';
    else if (s.indexOf('hold') !== -1 || s.indexOf('invoice') !== -1) cls = 'optima-badge-hold';
    else if (s.indexOf('refine') !== -1 || s.indexOf('not') !== -1) cls = 'optima-badge-refine';
    return '<span class="optima-badge ' + cls + '">' + $('<div>').text(status).html() + '</span>';
}

// A requirement is resolved (not "pending") once it's filled, closed, or the
// company backed out — everything else (Searching, Refine Search, Hold,
// Interview Scheduled, etc.) is still active work that can go overdue.
function isPendingReqStatus(status) {
    return ['Closed by Co.', 'Joined', 'Not Joining'].indexOf(status) === -1;
}
function daysSince(dateStr) {
    if (!dateStr) return null;
    var opened = new Date(dateStr + 'T00:00:00');
    if (isNaN(opened.getTime())) return null;
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    return Math.round((today - opened) / 86400000);
}
// 0 = on track, 1 = crossed 15 days (yellow), 2 = crossed a month (orange).
function overdueTier(status, days) {
    if (!isPendingReqStatus(status) || days === null) return 0;
    if (days > 30) return 2;
    if (days > 15) return 1;
    return 0;
}

function loadDashboard() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'dashboardStats' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') return;
        var d = res.data;
        $('#statCompanies').text(d.companies);
        $('#statTotalVacancy').text(d.totalVacancies);
        $('#statSearching').text(d.openVacancies);
        $('#statClosed').text(d.closedVacancies);
        $('#statSelected').text(d.selectedCandidates);
        $('#statPlacementsMonth').text(d.placementsThisMonth);
        $('#statPlacementsTillDate').text(d.placementsTillDate);
        $('#statRevenueMonth').text(fmtCurrency(d.revenueThisMonth));
        $('#statRevenueTillDate').text(fmtCurrency(d.revenueTillDate));
        $('#statInvoiced').text(fmtCurrency(d.totalInvoicedAmount));
        $('#statReceived').text(fmtCurrency(d.amountReceived));
        $('#statPending').text(fmtCurrency(d.amountPending));
        $('#statDueReminders').text(d.dueReminders);

        var reqRows = '';
        (d.recentRequirements || []).forEach(function (r) {
            var pendingDays = daysSince(r.dOpenDate);
            var tier = overdueTier(r.sStatus, pendingDays);
            var rowClass = tier === 2 ? ' class="optima-row-critical"' : (tier === 1 ? ' class="optima-row-warning"' : '');
            var overdueBadge = tier
                ? ' <span class="optima-badge ' + (tier === 2 ? 'optima-badge-critical' : 'optima-badge-warning') + '" title="Open ' + pendingDays + ' days">' + pendingDays + 'd</span>'
                : '';
            reqRows += '<tr' + rowClass + '><td>' + crmReqNoDisplay(r.sReqNo) + '</td><td>' + (r.sCompanyName || '-') + '</td><td>' + (r.sPost || '') + '</td><td>' + badgeForStatus(r.sStatus) + overdueBadge + '</td></tr>';
        });
        $('#recentReqBody').html(reqRows || '<tr><td colspan="4" class="text-center text-muted">No requirements yet</td></tr>');

        var placeRows = '';
        (d.recentPlacements || []).forEach(function (p) {
            placeRows += '<tr><td>' + (p.sCandidateName || '') + '</td><td>' + (p.sCompanyName || '-') + '</td><td>' + (p.sPost || '') + '</td><td>' + badgeForStatus(p.sJoiningStatus) + '</td></tr>';
        });
        $('#recentPlaceBody').html(placeRows || '<tr><td colspan="4" class="text-center text-muted">No placements yet</td></tr>');

        renderStatusBoard(d.weeklyStatusBoard);
        renderTrendChart(d.monthlyTrend || []);
        renderLeadSourceChart(d.leadSourceBreakdown || []);

        var trendRows = '';
        (d.monthlyTrend || []).forEach(function (m) {
            var rate = m.openings > 0 ? Math.round((m.placements / m.openings) * 100) + '%' : '&mdash;';
            trendRows += '<tr><td>' + (m.ym || '') + '</td><td>' + m.openings + '</td><td>' + m.placements + '</td><td>' + rate + '</td></tr>';
        });
        $('#monthlyTrendBody').html(trendRows || '<tr><td colspan="4" class="text-center text-muted">No data in the last 6 months</td></tr>');
    })
    .catch(() => {});
}

function isDarkTheme() {
    return document.body.classList.contains('crm-dark');
}

var OPTIMA_CHART_PALETTE = ['#5330c9', '#4d7bff', '#4fd2c0', '#f5a524', '#e0355b', '#14b8c4', '#22c55e', '#a855f7'];
var trendChartInstance = null;
var leadSourceChartInstance = null;

function renderLeadSourceChart(rows) {
    var el = document.getElementById('leadSourceChart');
    if (!rows.length) {
        el.innerHTML = '<p class="text-muted text-center mb-0 py-5">No source data yet</p>';
        return;
    }
    el.innerHTML = '';

    var options = {
        chart: { type: 'donut', height: 300, foreColor: isDarkTheme() ? '#cbd5e1' : '#5c5876' },
        series: rows.map(function (r) { return r.c; }),
        labels: rows.map(function (r) { return r.sSource || 'Unknown'; }),
        colors: OPTIMA_CHART_PALETTE,
        legend: { position: 'bottom', labels: { colors: isDarkTheme() ? '#cbd5e1' : '#5c5876' } },
        dataLabels: { enabled: true, formatter: function (val, opts) { return opts.w.config.series[opts.seriesIndex]; } },
        plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'Total Placements' } } } } }
    };

    if (leadSourceChartInstance) { leadSourceChartInstance.destroy(); }
    leadSourceChartInstance = new ApexCharts(el, options);
    leadSourceChartInstance.render();
}

function renderStatusBoard(weekly) {
    var head = document.getElementById('statusBoardHead');
    var body = document.getElementById('statusBoardBody');
    var days = (weekly && weekly.days) || [];
    var dates = (weekly && weekly.dates) || [];
    var rows = (weekly && weekly.rows) || [];

    var headHtml = '<th>Sr No.</th><th>Data</th>';
    days.forEach(function (d, i) {
        headHtml += '<th title="' + (dates[i] || '') + '">' + d + '</th>';
    });
    head.innerHTML = headHtml;

    if (!rows.length) {
        body.innerHTML = '<tr><td colspan="' + (2 + days.length) + '" class="text-center text-muted">No data yet</td></tr>';
        return;
    }
    var todayStr = new Date().toISOString().slice(0, 10);
    var html = '';
    rows.forEach(function (r, i) {
        html += '<tr><td>' + (i + 1) + '</td><td>' + $('<div>').text(r.label).html() + '</td>';
        (r.values || []).forEach(function (v, j) {
            var isToday = dates[j] === todayStr;
            var cell = v === null || v === undefined ? '&mdash;' : v;
            html += '<td' + (isToday ? ' class="fw-bold"' : '') + '>' + cell + '</td>';
        });
        html += '</tr>';
    });
    body.innerHTML = html;
}

function renderTrendChart(rows) {
    var el = document.getElementById('trendChart');
    if (!rows.length) {
        el.innerHTML = '<p class="text-muted text-center mb-0 py-5">No placements in the last 6 months</p>';
        return;
    }
    el.innerHTML = '';

    var categories = rows.map(function (r) { return r.ym; });
    var placements = rows.map(function (r) { return r.placements; });
    var received = rows.map(function (r) { return r.received; });

    var options = {
        chart: { height: 300, type: 'line', toolbar: { show: false }, foreColor: isDarkTheme() ? '#cbd5e1' : '#5c5876' },
        stroke: { width: [0, 3], curve: 'smooth' },
        colors: ['#4d7bff', '#4fd2c0'],
        series: [
            { name: 'Placements', type: 'column', data: placements },
            { name: 'Revenue Received (₹)', type: 'line', data: received }
        ],
        xaxis: { categories: categories },
        yaxis: [
            { title: { text: 'Placements' } },
            { opposite: true, title: { text: 'Revenue (₹)' } }
        ],
        dataLabels: { enabled: false },
        legend: { position: 'bottom', labels: { colors: isDarkTheme() ? '#cbd5e1' : '#5c5876' } }
    };

    if (trendChartInstance) { trendChartInstance.destroy(); }
    trendChartInstance = new ApexCharts(el, options);
    trendChartInstance.render();
}

$(document).ready(function () {
    loadDashboard();
    window.addEventListener('crm-theme-changed', function () { loadDashboard(); });

    $(document).on('click', '.optima-card-clickable', function (e) {
        var href = $(this).data('href');
        if (href) window.location.href = href;
    });
});
</script>
