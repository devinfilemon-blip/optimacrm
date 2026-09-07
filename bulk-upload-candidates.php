<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Bulk Upload Candidates | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Bulk Upload Candidates</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-candidate.php">Candidates</a></li>
                                    <li class="breadcrumb-item active">Bulk Upload</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-body">
                                <div><span id="message"></span></div>

                                <h5 class="mb-2">1. Download the template</h5>
                                <p class="text-muted mb-3">
                                    Start from the standard template so your columns line up correctly — it matches the
                                    candidate sheet format already used across the team (Name, Mobile No, Email ID, Gender,
                                    Address, Education, Suitable For, Current Company, Designation, Experience, Current CTC,
                                    Expected CTC, Notice Period, Reference Number, Remark, Date Added).
                                    <strong>Name</strong> and <strong>Mobile No</strong> are required for every row; the rest
                                    are optional. A row whose mobile number or email address already exists — in the database,
                                    or earlier in the same file — is skipped as a duplicate, never imported twice.
                                </p>
                                <a href="download-candidate-template.php" class="btn btn-outline-primary btn-sm mb-4">
                                    <i class="bx bx-download"></i> Download Excel Template
                                </a>

                                <h5 class="mb-2">2. Upload your filled-in file</h5>
                                <div class="mb-3">
                                    <input type="file" class="form-control" id="candidateFile" accept=".xlsx">
                                    <div class="form-text">Only .xlsx files, up to 5 MB and 2000 rows.</div>
                                </div>
                                <button type="button" class="btn btn-primary w-md" id="uploadBtn" onclick="uploadCandidates();">
                                    <i class="bx bx-upload"></i> Upload &amp; Import
                                </button>
                                <a href="list-candidate.php" class="btn btn-secondary w-md">Back to List</a>
                            </div>
                        </div>

                        <div class="card" id="summaryCard" style="display:none;">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <h5 class="mb-0">Import Summary</h5>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="downloadReportBtn" style="display:none;" onclick="downloadErrorReport();">
                                        <i class="bx bx-download"></i> Download Failed/Duplicate Report
                                    </button>
                                </div>
                                <div class="row text-center mb-2">
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="fs-3 fw-bold" id="sumTotal">0</div>
                                        <div class="text-muted small">Total Records</div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="fs-3 fw-bold text-success" id="sumSuccess">0</div>
                                        <div class="text-muted small">Successfully Added</div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="fs-3 fw-bold text-warning" id="sumDuplicate">0</div>
                                        <div class="text-muted small">Duplicate Records</div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="fs-3 fw-bold text-danger" id="sumFailed">0</div>
                                        <div class="text-muted small">Failed Records</div>
                                    </div>
                                </div>
                                <p class="text-muted small mb-3" id="emptyRowNote" style="display:none;"></p>
                                <div id="rowErrorsWrap" style="display:none;">
                                    <h6 class="mb-2">Duplicate &amp; Failed Records</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead><tr><th style="width:70px;">Row #</th><th>Name</th><th>Mobile</th><th style="width:110px;">Status</th><th>Reason</th></tr></thead>
                                            <tbody id="rowErrorsBody"></tbody>
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
<script>
function showMessage(msg, ok) {
    var el = document.getElementById('message');
    el.innerHTML = msg;
    el.className = ok ? 'add-message' : 'error-message';
}

function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

function uploadCandidates() {
    var fileInput = document.getElementById('candidateFile');
    var file = fileInput.files[0];
    if (!file) { alert('Please choose an Excel (.xlsx) file first.'); return; }
    if (!/\.xlsx$/i.test(file.name)) { alert('Only .xlsx Excel files are supported.'); return; }

    var btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Importing…';
    showMessage('', true);

    var fd = new FormData();
    fd.append('action', 'importcandidates');
    fd.append('file', file);

    fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-upload"></i> Upload &amp; Import';
            if (res.status !== 'success') { showMessage(res.message, false); return; }
            renderSummary(res.data);
            showMessage(res.message, true);
            fileInput.value = '';
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-upload"></i> Upload &amp; Import';
            showMessage('Upload failed. Please try again.', false);
        });
}

var lastImportResult = null;

function renderSummary(d) {
    lastImportResult = d;
    document.getElementById('summaryCard').style.display = '';
    document.getElementById('sumTotal').textContent = d.totalRecords;
    document.getElementById('sumSuccess').textContent = d.successCount;
    document.getElementById('sumDuplicate').textContent = d.duplicateCount;
    document.getElementById('sumFailed').textContent = d.failedCount;

    var emptyNote = document.getElementById('emptyRowNote');
    if (d.emptyRowCount > 0) {
        emptyNote.style.display = '';
        emptyNote.textContent = d.emptyRowCount + ' blank row(s) in the file were skipped (not counted as failed or duplicate).';
    } else {
        emptyNote.style.display = 'none';
    }

    var wrap = document.getElementById('rowErrorsWrap');
    var body = document.getElementById('rowErrorsBody');
    var reportBtn = document.getElementById('downloadReportBtn');
    body.innerHTML = '';
    if (d.rowErrors && d.rowErrors.length) {
        wrap.style.display = '';
        reportBtn.style.display = '';
        d.rowErrors.forEach(function (re) {
            var isDup = re.status === 'duplicate';
            var badge = '<span class="optima-badge ' + (isDup ? 'optima-badge-hold' : 'optima-badge-refine') + '">' + (isDup ? 'Duplicate' : 'Failed') + '</span>';
            body.innerHTML += '<tr><td>' + re.row + '</td><td>' + esc(re.name || '-') + '</td><td>' + esc(re.mobile || '-') + '</td><td>' + badge + '</td><td>' + esc((re.reasons || []).join(' ')) + '</td></tr>';
        });
    } else {
        wrap.style.display = 'none';
        reportBtn.style.display = 'none';
    }
}

function csvEscape(s) {
    s = s == null ? '' : String(s);
    return '"' + s.replace(/"/g, '""') + '"';
}

function downloadErrorReport() {
    if (!lastImportResult || !lastImportResult.rowErrors || !lastImportResult.rowErrors.length) return;
    var lines = [['Row', 'Name', 'Mobile', 'Status', 'Reason'].map(csvEscape).join(',')];
    lastImportResult.rowErrors.forEach(function (re) {
        lines.push([
            re.row,
            re.name || '',
            re.mobile || '',
            re.status === 'duplicate' ? 'Duplicate' : 'Failed',
            (re.reasons || []).join(' ')
        ].map(csvEscape).join(','));
    });
    var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'candidate-import-errors.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
