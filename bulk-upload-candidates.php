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
                                    Start from the standard template so your columns line up correctly.
                                    <strong>Candidate Name</strong> and <strong>Mobile Number</strong> are required for every row;
                                    the rest are optional. Mobile numbers must be unique — a row with a mobile number that
                                    already exists (in the database, or earlier in the same file) will be rejected.
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
                                <h5 class="mb-3">Import Summary</h5>
                                <div class="row text-center mb-3">
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="fs-3 fw-bold" id="sumTotal">0</div>
                                        <div class="text-muted small">Total Rows</div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="fs-3 fw-bold text-success" id="sumSuccess">0</div>
                                        <div class="text-muted small">Imported</div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="fs-3 fw-bold text-danger" id="sumFailed">0</div>
                                        <div class="text-muted small">Failed</div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="fs-3 fw-bold text-warning" id="sumDuplicate">0</div>
                                        <div class="text-muted small">Duplicates</div>
                                    </div>
                                </div>
                                <div id="rowErrorsWrap" style="display:none;">
                                    <h6 class="mb-2">Row-wise Details</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead><tr><th style="width:80px;">Row #</th><th>Issue</th></tr></thead>
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

function renderSummary(d) {
    document.getElementById('summaryCard').style.display = '';
    document.getElementById('sumTotal').textContent = d.totalRows;
    document.getElementById('sumSuccess').textContent = d.successCount;
    document.getElementById('sumFailed').textContent = d.failedCount;
    document.getElementById('sumDuplicate').textContent = d.duplicateCount;

    var wrap = document.getElementById('rowErrorsWrap');
    var body = document.getElementById('rowErrorsBody');
    body.innerHTML = '';
    if (d.rowErrors && d.rowErrors.length) {
        wrap.style.display = '';
        d.rowErrors.forEach(function (re) {
            body.innerHTML += '<tr><td>' + re.row + '</td><td>' + esc(re.errors.join(' ')) + '</td></tr>';
        });
    } else {
        wrap.style.display = 'none';
    }
}
</script>
