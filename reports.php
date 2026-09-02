<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Reports | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Reports</h4>
                            <div class="page-title-right d-flex align-items-center gap-3">
                                <select id="reportsPeriod" class="form-select form-select-sm" style="width: auto;">
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly" selected>Yearly</option>
                                </select>
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item active" id="reportsPeriodLabel">This Year</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row optima-stat-row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-blue h-100">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-blue"><i class="bx bx-briefcase-alt-2"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statReqYear">&mdash;</p>
                                    <p class="optima-stat-label">Requirements Opened</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-mint h-100">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-mint"><i class="bx bx-user-check"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statPlaceYear">&mdash;</p>
                                    <p class="optima-stat-label">Candidates Placed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-amber h-100">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-amber"><i class="bx bx-rupee"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statRevenueYear">&mdash;</p>
                                    <p class="optima-stat-label">Revenue Received</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-purple h-100">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-purple"><i class="bx bx-trending-up"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statRateYear">&mdash;</p>
                                    <p class="optima-stat-label">Placement Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-6">
                        <div class="card optima-section-card h-100">
                            <div class="card-body">
                                <h5 class="optima-section-title"><i class="bx bx-bar-chart-alt-2"></i>Requirements vs Placements</h5>
                                <p class="optima-chart-subtitle">Roles opened compared with candidates placed, month by month.</p>
                                <div id="monthlyChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card optima-section-card h-100">
                            <div class="card-body">
                                <h5 class="optima-section-title"><i class="bx bx-rupee"></i>Revenue Received</h5>
                                <p class="optima-chart-subtitle">Payments collected against placement invoices.</p>
                                <div id="revenueChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-6">
                        <div class="card optima-section-card h-100">
                            <div class="card-body">
                                <h5 class="optima-section-title"><i class="bx bx-pie-chart-alt-2"></i>Requirement Status Breakdown</h5>
                                <p class="optima-chart-subtitle">Where open requirements currently stand.</p>
                                <div id="funnelChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card optima-section-card h-100">
                            <div class="card-body">
                                <h5 class="optima-section-title"><i class="bx bx-buildings"></i>Top Companies by Placements</h5>
                                <p class="optima-chart-subtitle">Clients generating the most successful placements.</p>
                                <div id="companyChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-xl-12">
                        <div class="card optima-section-card">
                            <div class="card-body">
                                <h5 class="optima-section-title"><i class="bx bx-group"></i>Placements by Recruiter</h5>
                                <p class="optima-chart-subtitle">Who's closing the most candidates.</p>
                                <div id="recruiterChart"><p class="text-muted text-center mb-0 py-5">Loading&hellip;</p></div>
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
// Validated categorical palette, fixed hue order (see the data-viz skill's
// reference/palette.md) — 8 CVD-safe hues, so a multi-color chart can go
// colorful without cycling colors past the point they stop being distinguishable.
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
// Colors for a set of ranked bars: each of the first 8 rows gets its own fixed
// slot in order; a trailing "Other" bucket (folded server-side past 7) always
// gets the same neutral gray rather than continuing the sequence.
function rankedColors(rows) {
    return rows.map(function (r, i) {
        var isOther = i === rows.length - 1 && (r.sStatus === 'Other' || r.sCompanyName === 'Other' || r.sWorkedBy === 'Other');
        return isOther ? slot('other') : slot(REPORT_PALETTE_ORDER[i % REPORT_PALETTE_ORDER.length]);
    });
}
function foreColor() { return isDarkTheme() ? '#cbd5e1' : '#5c5876'; }
function mutedColor() { return isDarkTheme() ? '#8b95ab' : '#8b87a3'; }
function gridColor() { return isDarkTheme() ? 'rgba(255,255,255,0.07)' : 'rgba(36,27,69,0.06)'; }
function surfaceColor() { return isDarkTheme() ? '#131a2e' : '#ffffff'; }

function fmtCurrency(n) {
    n = parseFloat(n || 0);
    if (n >= 100000) return '₹' + (n / 100000).toFixed(1) + 'L';
    return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
}

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

var currentPeriodLabel = 'this year';

// Shared chrome so every chart reads as one system: hairline hairline-solid
// grid, muted axis text, no toolbar clutter.
function baseChart(type, height) {
    return {
        chart: { height: height, type: type, toolbar: { show: false }, foreColor: mutedColor(), fontFamily: 'inherit' },
        grid: { borderColor: gridColor(), strokeDashArray: 0, padding: { left: 8, right: 8 } },
        xaxis: { labels: { style: { colors: mutedColor() } }, axisBorder: { color: gridColor() }, axisTicks: { color: gridColor() } },
        yaxis: { labels: { style: { colors: mutedColor() } } }
    };
}

function renderMonthlyChart(rows) {
    if (!rows.length) { emptyState('monthlyChart', 'No activity ' + currentPeriodLabel); return; }
    renderChart('monthlyChart', $.extend(true, baseChart('bar', 300), {
        colors: [slot('blue'), slot('orange')],
        fill: { type: 'gradient', gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.45, opacityFrom: 0.95, opacityTo: 0.65 } },
        series: [
            { name: 'Requirements Opened', data: rows.map(r => r.openings) },
            { name: 'Candidates Placed', data: rows.map(r => r.placements) }
        ],
        xaxis: { categories: rows.map(r => monthLabel(r.ym)) },
        yaxis: { title: { text: 'Count', style: { color: mutedColor() } }, forceNiceScale: true },
        plotOptions: { bar: { columnWidth: '42%', borderRadius: 4, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'left', labels: { colors: foreColor() }, markers: { radius: 3 } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light' }
    }));
}

function renderRevenueChart(rows) {
    if (!rows.length) { emptyState('revenueChart', 'No revenue ' + currentPeriodLabel); return; }
    renderChart('revenueChart', $.extend(true, baseChart('area', 300), {
        colors: [slot('gold')],
        stroke: { width: 3, curve: 'smooth' },
        fill: { type: 'gradient', gradient: { shade: 'light', shadeIntensity: 1, opacityFrom: 0.55, opacityTo: 0.05, stops: [0, 90, 100] } },
        markers: { size: 0, hover: { size: 6, strokeWidth: 2, strokeColor: surfaceColor() } },
        series: [{ name: 'Revenue Received', data: rows.map(r => Math.round(r.received)) }],
        xaxis: { categories: rows.map(r => monthLabel(r.ym)) },
        yaxis: { labels: { style: { colors: mutedColor() }, formatter: function (val) { return fmtCurrency(val); } } },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: function (val) { return '₹' + Math.round(val).toLocaleString('en-IN'); } } }
    }));
}

// A ranked bar chart is still one series (one metric), but each bar is its
// own category, so it can carry its own color from the fixed 8-hue palette —
// bounded so it never has to cycle past the point colors stop being
// distinguishable (extra rows fold into "Other" server-side).
function renderRankedBarChart(id, rows, seriesName, valueKey, categoryFn, tooltipFormatter) {
    if (!rows.length) { emptyState(id, 'No data ' + currentPeriodLabel); return; }
    renderChart(id, $.extend(true, baseChart('bar', Math.max(220, rows.length * 42)), {
        colors: rankedColors(rows),
        fill: { type: 'gradient', gradient: { shade: 'light', type: 'horizontal', shadeIntensity: 0.45, opacityFrom: 0.95, opacityTo: 0.75 } },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, borderRadiusApplication: 'end', barHeight: '60%', distributed: true } },
        series: [{ name: seriesName, data: rows.map(r => r[valueKey]) }],
        xaxis: { categories: rows.map(categoryFn) },
        dataLabels: { enabled: true, style: { colors: [foreColor()] }, offsetX: 8, background: { enabled: false } },
        legend: { show: false },
        tooltip: tooltipFormatter ? { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: tooltipFormatter } } : { theme: isDarkTheme() ? 'dark' : 'light' }
    }));
}

function renderCompanyChart(rows) {
    renderRankedBarChart('companyChart', rows, 'Placements', 'placements', r => r.sCompanyName || 'Unknown',
        function (val, opts) {
            var row = rows[opts.dataPointIndex];
            return val + ' placements · ₹' + Math.round(row.received).toLocaleString('en-IN') + ' received';
        });
}

// Recruiters get an upright column chart instead of a horizontal bar, purely
// so the page doesn't read as "five bar charts in a row" — same distributed
// palette, different silhouette.
function renderRecruiterChart(rows) {
    if (!rows.length) { emptyState('recruiterChart', 'No data ' + currentPeriodLabel); return; }
    renderChart('recruiterChart', $.extend(true, baseChart('bar', 340), {
        colors: rankedColors(rows),
        fill: { type: 'gradient', gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.45, opacityFrom: 0.95, opacityTo: 0.75 } },
        plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '55%', distributed: true } },
        series: [{ name: 'Placements', data: rows.map(r => r.placements) }],
        xaxis: { categories: rows.map(r => r.sWorkedBy) },
        dataLabels: { enabled: true, style: { colors: [foreColor()] }, offsetY: -20, background: { enabled: false } },
        legend: { show: false },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light' }
    }));
}

// Status breakdown is a part-to-whole story (how the open pipeline currently
// splits), so it gets a donut instead of another bar — the one place on this
// page where category identity needs a legend, since there's no axis to read
// labels from.
function renderFunnelChart(rows) {
    if (!rows.length) { emptyState('funnelChart', 'No data ' + currentPeriodLabel); return; }
    var total = rows.reduce(function (sum, r) { return sum + r.c; }, 0);
    renderChart('funnelChart', {
        chart: { height: 320, type: 'donut', foreColor: mutedColor(), fontFamily: 'inherit' },
        colors: rankedColors(rows),
        labels: rows.map(r => r.sStatus || 'Unknown'),
        series: rows.map(r => r.c),
        stroke: { width: 2, colors: [surfaceColor()] },
        fill: { type: 'gradient', gradient: { shade: 'light', shadeIntensity: 0.45, opacityFrom: 1, opacityTo: 0.85 } },
        dataLabels: { enabled: true, formatter: function (val) { return Math.round(val) + '%'; }, style: { colors: ['#ffffff'] }, dropShadow: { enabled: false } },
        legend: { position: 'bottom', labels: { colors: foreColor() }, markers: { radius: 3 } },
        plotOptions: { pie: { donut: { size: '62%', labels: {
            show: true,
            value: { color: foreColor(), fontSize: '22px', fontWeight: 700 },
            total: { show: true, label: 'Requirements', color: mutedColor(), formatter: function () { return total; } }
        } } } },
        tooltip: { theme: isDarkTheme() ? 'dark' : 'light', y: { formatter: function (val) { return val + ' requirements'; } } }
    });
}

function renderSummary(s) {
    document.getElementById('statReqYear').textContent = s.requirements;
    document.getElementById('statPlaceYear').textContent = s.placements;
    document.getElementById('statRevenueYear').textContent = fmtCurrency(s.revenue);
    document.getElementById('statRateYear').textContent = s.placementRate + '%';
}

function getSelectedReportsPeriod() {
    var stored = null;
    try { stored = localStorage.getItem('optimaReportsPeriod'); } catch (e) {}
    return stored || 'yearly';
}

function loadReports() {
    var period = getSelectedReportsPeriod();
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'reportsData', period: period })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') return;
        var d = res.data;
        var label = d.periodLabel || 'This Year';
        currentPeriodLabel = label.toLowerCase();
        document.getElementById('reportsPeriodLabel').textContent = label;
        renderSummary(d.summary || { requirements: 0, placements: 0, revenue: 0, placementRate: 0 });
        renderMonthlyChart(d.monthly || []);
        renderRevenueChart(d.monthly || []);
        renderFunnelChart(d.requirementFunnel || []);
        renderCompanyChart(d.byCompany || []);
        renderRecruiterChart(d.byRecruiter || []);
    })
    .catch(() => {});
}

$(document).ready(function () {
    $('#reportsPeriod').val(getSelectedReportsPeriod());
    loadReports();
    window.addEventListener('crm-theme-changed', loadReports);

    $('#reportsPeriod').on('change', function () {
        try { localStorage.setItem('optimaReportsPeriod', this.value); } catch (e) {}
        loadReports();
    });
});
</script>
