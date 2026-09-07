<?php include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Revenue &amp; Financial | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18" id="pageTitle">Revenue &amp; Financial</h4>
                            <div class="page-title-right d-flex align-items-center gap-2 flex-wrap" id="pageActions">
                                <a href="list-expense.php?trashed=1" class="btn btn-outline-secondary btn-sm"><i class="bx bx-trash"></i> Trash</a>
                                <a href="add-expense.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add Expense</a>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4" id="finTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabOverview" type="button" role="tab"><i class="bx bx-line-chart me-1"></i>Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabExpenses" type="button" role="tab"><i class="bx bx-receipt me-1"></i>Expenses</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabProfit" type="button" role="tab"><i class="bx bx-rupee me-1"></i>Profit</button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- ============ OVERVIEW TAB ============ -->
                    <div class="tab-pane fade show active" id="tabOverview" role="tabpanel">
                        <p class="optima-chart-subtitle text-uppercase fw-semibold mb-2" style="margin-top:0;">Overall</p>
                        <div class="row optima-stat-row g-4 mb-4">
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-mint h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-mint"><i class="bx bx-trending-up"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statRevenue">&mdash;</p>
                                            <p class="optima-stat-label">Revenue (Received)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-rose h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-rose"><i class="bx bx-trending-down"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statExpenses">&mdash;</p>
                                            <p class="optima-stat-label">Expenses</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-purple h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-purple"><i class="bx bx-rupee"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statProfit">&mdash;</p>
                                            <p class="optima-stat-label">Net Profit</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="optima-chart-subtitle text-uppercase fw-semibold mb-2" id="monthLabel">This Month</p>
                        <div class="row optima-stat-row g-4 mb-4">
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-cyan h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-cyan"><i class="bx bx-trending-up"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statMonthRevenue">&mdash;</p>
                                            <p class="optima-stat-label">Revenue This Month</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-amber h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-amber"><i class="bx bx-trending-down"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statMonthExpenses">&mdash;</p>
                                            <p class="optima-stat-label">Expenses This Month</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-blue h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-blue"><i class="bx bx-rupee"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statMonthProfit">&mdash;</p>
                                            <p class="optima-stat-label">Profit This Month</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-xl-8">
                                <div class="card optima-section-card h-100">
                                    <div class="card-body">
                                        <h5 class="optima-section-title"><i class="bx bx-line-chart"></i>Revenue vs Expenses vs Profit</h5>
                                        <p class="optima-chart-subtitle">Last 6 months, based on actual placement payments and recorded expenses.</p>
                                        <div id="trendChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card optima-section-card h-100">
                                    <div class="card-body">
                                        <h5 class="optima-section-title"><i class="bx bx-pie-chart-alt-2"></i>Expenses by Category</h5>
                                        <p class="optima-chart-subtitle">Where spending is going.</p>
                                        <div id="categoryChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ EXPENSES TAB ============ -->
                    <div class="tab-pane fade" id="tabExpenses" role="tabpanel">
                        <div class="row optima-stat-row g-4 mb-4">
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-purple h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-purple"><i class="bx bx-user-voice"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statCommissionTotal">&mdash;</p>
                                            <p class="optima-stat-label">Recruiter Commission</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-amber h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-amber"><i class="bx bx-briefcase"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statOtherTotal">&mdash;</p>
                                            <p class="optima-stat-label">Other Expenses</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="card optima-stat-card accent-rose h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-rose"><i class="bx bx-wallet"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="statExpensesTotal2">&mdash;</p>
                                            <p class="optima-stat-label">Total Expenses</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <h5 class="optima-section-title mb-0" id="tableTitle"><i class="bx bx-list-ul"></i>Expenses</h5>
                            </div>
                            <p class="text-muted small mb-2" id="commissionHint">
                                <span class="optima-badge optima-badge-searching">Commission</span> rows are calculated automatically (20% of the placement's CTC) and stay in sync with the placement — edit the placement's CTC or recruiter to change them.
                            </p>
                            <div><span id="message"></span></div>
                            <table id="datatable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Recruiter / Candidate</th>
                                        <th>Company</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ============ PROFIT TAB ============ -->
                    <div class="tab-pane fade" id="tabProfit" role="tabpanel">
                        <p class="optima-chart-subtitle text-uppercase fw-semibold mb-2" style="margin-top:0;">Overall</p>
                        <div class="row optima-stat-row g-4 mb-4">
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-mint h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-mint"><i class="bx bx-trending-up"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfRevenue">&mdash;</p>
                                            <p class="optima-stat-label">Total Revenue</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-rose h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-rose"><i class="bx bx-trending-down"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfExpenses">&mdash;</p>
                                            <p class="optima-stat-label">Total Expenses</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-purple h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-purple"><i class="bx bx-user-voice"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfCommission">&mdash;</p>
                                            <p class="optima-stat-label">Recruiter Commission</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-amber h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-amber"><i class="bx bx-briefcase"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfOther">&mdash;</p>
                                            <p class="optima-stat-label">Other Expenses</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-blue h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-icon bg-blue"><i class="bx bx-rupee"></i></div>
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfProfit">&mdash;</p>
                                            <p class="optima-stat-label">Net Profit</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="optima-chart-subtitle text-uppercase fw-semibold mb-2" id="pfMonthLabel">This Month</p>
                        <div class="row optima-stat-row g-4 mb-4">
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-mint h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfMonthRevenue">&mdash;</p>
                                            <p class="optima-stat-label">Revenue</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-rose h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfMonthExpenses">&mdash;</p>
                                            <p class="optima-stat-label">Expenses</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-purple h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfMonthCommission">&mdash;</p>
                                            <p class="optima-stat-label">Commission</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-amber h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfMonthOther">&mdash;</p>
                                            <p class="optima-stat-label">Other Expenses</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-6">
                                <div class="card optima-stat-card accent-blue h-100">
                                    <div class="card-body">
                                        <div class="optima-stat-text">
                                            <p class="optima-stat-value" id="pfMonthProfit">&mdash;</p>
                                            <p class="optima-stat-label">Profit</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-xl-8">
                                <div class="card optima-section-card h-100">
                                    <div class="card-body">
                                        <h5 class="optima-section-title"><i class="bx bx-bar-chart-alt-2"></i>Revenue, Commission &amp; Other Expenses</h5
                                        ><p class="optima-chart-subtitle">Last 6 months — profit is what's left after both expense types.</p>
                                        <div id="profitChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card optima-section-card h-100">
                                    <div class="card-body">
                                        <h5 class="optima-section-title"><i class="bx bx-pie-chart-alt-2"></i>Commission vs Other</h5>
                                        <p class="optima-chart-subtitle">Split of total expenses.</p>
                                        <div id="splitChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
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
function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
function fmtCurrency(n) {
    n = parseFloat(n || 0);
    return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
function fmtCurrencyShort(n) {
    n = parseFloat(n || 0);
    if (Math.abs(n) >= 100000) return '₹' + (n / 100000).toFixed(1) + 'L';
    return fmtCurrency(n);
}

var REPORT_COLORS = {
    blue: { light: '#2a78d6', dark: '#3987e5' }, orange: { light: '#eb6834', dark: '#d95926' },
    aqua: { light: '#1baf7a', dark: '#199e70' }, gold: { light: '#eda100', dark: '#c98500' },
    magenta: { light: '#e87ba4', dark: '#d55181' }, green: { light: '#008300', dark: '#008300' },
    violet: { light: '#4a3aa7', dark: '#9085e9' }, red: { light: '#e34948', dark: '#e66767' },
    other: { light: '#b9b6c9', dark: '#565a6e' }
};
var REPORT_PALETTE_ORDER = ['blue', 'orange', 'aqua', 'gold', 'magenta', 'green', 'violet', 'red'];
function isDarkTheme() { return document.body.classList.contains('crm-dark'); }
function slot(name) { return REPORT_COLORS[name][isDarkTheme() ? 'dark' : 'light']; }
function rankedColors(rows) { return rows.map(function (r, i) { return slot(REPORT_PALETTE_ORDER[i % REPORT_PALETTE_ORDER.length]); }); }
function foreColor() { return isDarkTheme() ? '#cbd5e1' : '#5c5876'; }
function mutedColor() { return isDarkTheme() ? '#8b95ab' : '#8b87a3'; }
function gridColor() { return isDarkTheme() ? 'rgba(255,255,255,0.07)' : 'rgba(36,27,69,0.06)'; }
function surfaceColor() { return isDarkTheme() ? '#131a2e' : '#ffffff'; }

var charts = {};
function renderChart(id, options) {
    var el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = '';
    if (charts[id]) { charts[id].destroy(); }
    charts[id] = new ApexCharts(el, options);
    charts[id].render();
}
function emptyState(id, message) {
    var el = document.getElementById(id);
    if (el) el.innerHTML = '<p class="text-muted text-center mb-0 py-5">' + message + '</p>';
}
function monthLbl(ym) {
    var parts = ym.split('-');
    var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, 1);
    return d.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
}

function renderTrendChart(rows) {
    if (!rows.length) { emptyState('trendChart', 'No financial activity yet.'); return; }
    renderChart('trendChart', {
        chart: { height: 320, type: 'line', toolbar: { show: false }, foreColor: mutedColor(), fontFamily: 'inherit' },
        grid: { borderColor: gridColor(), strokeDashArray: 0, padding: { left: 8, right: 8 } },
        colors: [slot('aqua'), slot('red'), slot('violet')],
        stroke: { width: [0, 0, 3], curve: 'smooth' },
        fill: { type: ['gradient', 'gradient', 'solid'], gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.45, opacityFrom: 0.9, opacityTo: 0.6 } },
        series: [
            { name: 'Revenue', type: 'column', data: rows.map(r => Math.round(r.revenue)) },
            { name: 'Expenses', type: 'column', data: rows.map(r => Math.round(r.expenses)) },
            { name: 'Profit', type: 'line', data: rows.map(r => Math.round(r.profit)) }
        ],
        xaxis: { categories: rows.map(r => monthLbl(r.ym)), labels: { style: { colors: mutedColor() } }, axisBorder: { color: gridColor() }, axisTicks: { color: gridColor() } },
        yaxis: { labels: { style: { colors: mutedColor() }, formatter: v => fmtCurrencyShort(v) } },
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'left', labels: { colors: foreColor() }, markers: { radius: 3 } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: v => fmtCurrency(v) } }
    });
}

function renderCategoryChart(rows) {
    if (!rows.length) { emptyState('categoryChart', 'No expenses recorded yet.'); return; }
    var total = rows.reduce(function (sum, r) { return sum + r.amount; }, 0);
    renderChart('categoryChart', {
        chart: { height: 300, type: 'donut', foreColor: mutedColor(), fontFamily: 'inherit' },
        colors: rankedColors(rows),
        labels: rows.map(r => r.label || 'Uncategorized'),
        series: rows.map(r => r.amount),
        stroke: { width: 2, colors: [surfaceColor()] },
        fill: { type: 'gradient', gradient: { shade: 'light', shadeIntensity: 0.45, opacityFrom: 1, opacityTo: 0.85 } },
        dataLabels: { enabled: true, formatter: function (val) { return Math.round(val) + '%'; }, style: { colors: ['#ffffff'] }, dropShadow: { enabled: false } },
        legend: { position: 'bottom', labels: { colors: foreColor() }, markers: { radius: 3 } },
        plotOptions: { pie: { donut: { size: '62%', labels: {
            show: true, value: { color: foreColor(), fontSize: '18px', fontWeight: 700, formatter: v => fmtCurrencyShort(v) },
            total: { show: true, label: 'Total Expenses', color: mutedColor(), formatter: function () { return fmtCurrencyShort(total); } }
        } } } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: v => fmtCurrency(v) } }
    });
}

function renderProfitChart(rows) {
    if (!rows.length) { emptyState('profitChart', 'No financial activity yet.'); return; }
    renderChart('profitChart', {
        chart: { height: 320, type: 'line', toolbar: { show: false }, foreColor: mutedColor(), fontFamily: 'inherit' },
        grid: { borderColor: gridColor(), strokeDashArray: 0, padding: { left: 8, right: 8 } },
        colors: [slot('aqua'), slot('violet'), slot('orange'), slot('green')],
        stroke: { width: [0, 0, 0, 3], curve: 'smooth' },
        fill: { type: ['gradient', 'gradient', 'gradient', 'solid'], gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.45, opacityFrom: 0.9, opacityTo: 0.6 } },
        series: [
            { name: 'Revenue', type: 'column', data: rows.map(r => Math.round(r.revenue)) },
            { name: 'Commission', type: 'column', data: rows.map(r => Math.round(r.commission)) },
            { name: 'Other Expenses', type: 'column', data: rows.map(r => Math.round(r.other)) },
            { name: 'Profit', type: 'line', data: rows.map(r => Math.round(r.profit)) }
        ],
        xaxis: { categories: rows.map(r => monthLbl(r.ym)), labels: { style: { colors: mutedColor() } }, axisBorder: { color: gridColor() }, axisTicks: { color: gridColor() } },
        yaxis: { labels: { style: { colors: mutedColor() }, formatter: v => fmtCurrencyShort(v) } },
        plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'left', labels: { colors: foreColor() }, markers: { radius: 3 } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: v => fmtCurrency(v) } }
    });
}

function renderSplitChart(commission, other) {
    if (commission <= 0 && other <= 0) { emptyState('splitChart', 'No expenses recorded yet.'); return; }
    renderChart('splitChart', {
        chart: { height: 300, type: 'donut', foreColor: mutedColor(), fontFamily: 'inherit' },
        colors: [slot('violet'), slot('orange')],
        labels: ['Recruiter Commission', 'Other Expenses'],
        series: [commission, other],
        stroke: { width: 2, colors: [surfaceColor()] },
        fill: { type: 'gradient', gradient: { shade: 'light', shadeIntensity: 0.45, opacityFrom: 1, opacityTo: 0.85 } },
        dataLabels: { enabled: true, formatter: function (val) { return Math.round(val) + '%'; }, style: { colors: ['#ffffff'] }, dropShadow: { enabled: false } },
        legend: { position: 'bottom', labels: { colors: foreColor() }, markers: { radius: 3 } },
        plotOptions: { pie: { donut: { size: '62%' } } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: v => fmtCurrency(v) } }
    });
}

var lastFinancialData = null;

function renderFinancialSummary(d) {
    lastFinancialData = d;

    document.getElementById('statRevenue').textContent = fmtCurrency(d.revenue.total);
    document.getElementById('statExpenses').textContent = fmtCurrency(d.expenses.total);
    document.getElementById('statProfit').textContent = fmtCurrency(d.profit.total);
    document.getElementById('statMonthRevenue').textContent = fmtCurrency(d.revenue.thisMonth);
    document.getElementById('statMonthExpenses').textContent = fmtCurrency(d.expenses.thisMonth);
    document.getElementById('statMonthProfit').textContent = fmtCurrency(d.profit.thisMonth);

    var now = new Date();
    var monthName = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    document.getElementById('monthLabel').textContent = 'This Month — ' + monthName;
    document.getElementById('pfMonthLabel').textContent = 'This Month — ' + monthName;

    document.getElementById('statCommissionTotal').textContent = fmtCurrency(d.expenses.totalCommission);
    document.getElementById('statOtherTotal').textContent = fmtCurrency(d.expenses.totalOther);
    document.getElementById('statExpensesTotal2').textContent = fmtCurrency(d.expenses.total);

    document.getElementById('pfRevenue').textContent = fmtCurrency(d.revenue.total);
    document.getElementById('pfExpenses').textContent = fmtCurrency(d.expenses.total);
    document.getElementById('pfCommission').textContent = fmtCurrency(d.expenses.totalCommission);
    document.getElementById('pfOther').textContent = fmtCurrency(d.expenses.totalOther);
    document.getElementById('pfProfit').textContent = fmtCurrency(d.profit.total);

    document.getElementById('pfMonthRevenue').textContent = fmtCurrency(d.revenue.thisMonth);
    document.getElementById('pfMonthExpenses').textContent = fmtCurrency(d.expenses.thisMonth);
    document.getElementById('pfMonthCommission').textContent = fmtCurrency(d.expenses.monthCommission);
    document.getElementById('pfMonthOther').textContent = fmtCurrency(d.expenses.monthOther);
    document.getElementById('pfMonthProfit').textContent = fmtCurrency(d.profit.thisMonth);

    renderTrendChart(d.monthlyTrend || []);
    renderCategoryChart(d.expenses.byCategory || []);
    renderProfitChart(d.monthlyTrend || []);
    renderSplitChart(d.expenses.totalCommission, d.expenses.totalOther);
}

function loadFinancialSummary() {
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'getfinancialsummary' }) })
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            renderFinancialSummary(res.data);
        });
}
window.addEventListener('crm-theme-changed', function () { if (lastFinancialData) renderFinancialSummary(lastFinancialData); });

// Charts inside an inactive Bootstrap tab render at 0 width, since the pane
// is display:none when ApexCharts measures it — re-render once each tab is
// actually shown for the first time.
var chartsRenderedForTab = {};
document.querySelectorAll('#finTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function (e) {
        var target = e.target.getAttribute('data-bs-target');
        if (!chartsRenderedForTab[target] && lastFinancialData) {
            renderFinancialSummary(lastFinancialData);
        }
        chartsRenderedForTab[target] = true;
    });
});

var CRM_TRASH_MODE = new URLSearchParams(window.location.search).get('trashed') === '1';
var expenseRowsById = {};

if (CRM_TRASH_MODE) {
    document.getElementById('pageTitle').textContent = 'Revenue & Financial — Trash';
    document.getElementById('pageActions').innerHTML = '<a href="list-expense.php" class="btn btn-primary btn-sm"><i class="bx bx-arrow-back"></i> Back to List</a>';
    document.getElementById('finTabs').style.display = 'none';
    document.getElementById('tabOverview').classList.remove('show', 'active');
    document.getElementById('tabProfit').classList.remove('show', 'active');
    document.getElementById('tabExpenses').classList.add('show', 'active');
    document.getElementById('tableTitle').innerHTML = '<i class="bx bx-list-ul"></i>Deleted Expenses';
    document.querySelector('#tabExpenses .row.optima-stat-row').style.display = 'none';
    document.getElementById('commissionHint').style.display = 'none';
} else {
    loadFinancialSummary();
}

function statusBadge(e) {
    return e.sExpenseType === 'Commission'
        ? '<span class="optima-badge optima-badge-searching">Auto-Generated</span>'
        : '<span class="optima-badge optima-badge-default">Manual</span>';
}
function typeBadge(e) {
    return e.sExpenseType === 'Commission'
        ? '<span class="optima-badge optima-badge-closed">Commission</span>'
        : '<span class="optima-badge optima-badge-hold">Other</span>';
}

function fngetlistexpense() {
    $.ajax({
        url: 'api.php', method: 'POST', contentType: 'application/json',
        data: JSON.stringify({ action: CRM_TRASH_MODE ? 'fngetlisttrashexpense' : 'fngetlistexpense' }),
        success: function (response) {
            var rows = '';
            expenseRowsById = {};
            (response.data || []).forEach(function (e) {
                expenseRowsById[e.iExpenseId] = e;
                var isCommission = e.sExpenseType === 'Commission';
                var actions = CRM_TRASH_MODE
                    ? crmActionMenu([
                        { label: 'Restore', icon: 'bx-undo', onclick: 'restoreexpense(' + e.iExpenseId + ')' },
                        { label: 'Delete Forever', icon: 'bx-trash', danger: true, onclick: 'permanentlydeleteexpense(' + e.iExpenseId + ')' }
                      ])
                    : crmActionMenu(isCommission ? [
                        { label: 'View Placement', icon: 'bx-link-external', href: 'add-placement.php?id=' + e.iPlacementId },
                        { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteexpense(' + e.iExpenseId + ', true)' }
                      ] : [
                        { label: 'Edit', icon: 'bx-edit-alt', href: 'add-expense.php?id=' + e.iExpenseId },
                        { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteexpense(' + e.iExpenseId + ', false)' }
                      ]);
                var who = isCommission
                    ? esc(e.recruiterName || '-') + (e.sCandidateName ? ' <span class="text-muted">&middot; ' + esc(e.sCandidateName) + '</span>' : '')
                    : '-';
                rows += '<tr data-id="' + e.iExpenseId + '">' +
                    '<td>' + e.iExpenseId + '</td>' +
                    '<td>' + typeBadge(e) + '</td>' +
                    '<td>' + esc(e.sCategory) + '</td>' +
                    '<td>' + esc(e.sDescription || '-') + '</td>' +
                    '<td>' + who + '</td>' +
                    '<td>' + esc(e.sCompanyName || '-') + '</td>' +
                    '<td>' + fmtCurrency(e.dAmount) + '</td>' +
                    '<td>' + esc(e.dExpenseDate) + '</td>' +
                    '<td>' + statusBadge(e) + '</td>' +
                    actions +
                    '</tr>';
            });
            if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
            $('#datatable tbody').html(rows || '<tr><td colspan="10">' + (CRM_TRASH_MODE ? 'Trash is empty' : 'No expenses recorded yet') + '</td></tr>');
            if (rows) $('#datatable').DataTable({ order: [[7, 'desc']] });
        }
    });
}

function deleteexpense(id, isCommission) {
    var msg = isCommission
        ? 'This commission is calculated automatically from the placement. Deleting it here will remove it from Profit calculations, but it will reappear if the placement is saved again. Continue?'
        : 'Move this expense to trash? You can restore it later from the Trash view.';
    if (!confirm(msg)) return;
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'deleteexpense', id: id }) })
        .then(r => r.json()).then(res => {
            document.getElementById('message').innerHTML = res.message;
            document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
            fngetlistexpense();
            loadFinancialSummary();
        });
}

function restoreexpense(id) {
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'restoreexpense', id: id }) })
        .then(r => r.json()).then(res => {
            document.getElementById('message').innerHTML = res.message;
            document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
            fngetlistexpense();
        });
}

function permanentlydeleteexpense(id) {
    if (!confirm('Permanently delete this expense? This cannot be undone.')) return;
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'permanentlydeleteexpense', id: id }) })
        .then(r => r.json()).then(res => {
            document.getElementById('message').innerHTML = res.message;
            document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
            fngetlistexpense();
        });
}

// Commission rows aren't inline-editable — they're derived, not entered —
// and trashed records aren't inline-editable either (restore them first).
if (!CRM_TRASH_MODE) {
    window.crmInlineEdit = {
        // Commission rows are derived, not entered — returning null here is
        // what actually stops crmInlineEditStart from ever being called for
        // one (the shared dblclick handler checks this before starting).
        getRowId: function ($row) {
            var id = parseInt($row.data('id'), 10);
            var row = expenseRowsById[id];
            return (row && row.sExpenseType === 'Commission') ? null : id;
        },
        getFullRow: function (id) { return expenseRowsById[id]; },
        fields: [
            { cellIndex: 2, key: 'sCategory', type: 'text' },
            { cellIndex: 3, key: 'sDescription', type: 'text' },
            { cellIndex: 6, key: 'dAmount', type: 'number' },
            { cellIndex: 7, key: 'dExpenseDate', type: 'date' }
        ],
        toPayload: function (merged, id) {
            return { id: id, category: merged.sCategory, description: merged.sDescription, amount: merged.dAmount, expenseDate: merged.dExpenseDate, paymentMode: merged.sPaymentMode, remark: merged.sRemark };
        },
        saveAction: 'updateexpense',
        onSaved: function () { fngetlistexpense(); loadFinancialSummary(); }
    };
}

$(document).ready(function () { fngetlistexpense(); });
</script>
