<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$candidateId = isset($_GET['candidateId']) ? (int) $_GET['candidateId'] : 0;
?>
<head>
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Placement | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18"><?php echo $id ? 'Edit' : 'Add'; ?> Placement</h4>
                            <div class="page-title-right d-flex align-items-center gap-3">
                                <?php if ($id) : ?>
                                <a href="generate-invoice.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-info btn-sm"><i class="bx bx-file"></i> View Invoice</a>
                                <?php endif; ?>
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-placement.php">Placements</a></li>
                                    <li class="breadcrumb-item active"><?php echo $id ? 'Edit' : 'Add'; ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <div><span id="message"></span></div>
                                <input type="hidden" id="id" value="<?php echo $id; ?>">
                                <input type="hidden" id="initialCandidateId" value="<?php echo $candidateId; ?>">

                                <h5 class="mb-3">Candidate</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Candidate *</label>
                                            <select class="form-control" id="candidateId"><option value="">Loading&hellip;</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center">
                                        <a href="add-candidate.php" target="_blank" class="btn btn-outline-primary btn-sm mt-3">
                                            <i class="bx bx-plus"></i> New Candidate
                                        </a>
                                        <span class="text-muted small ms-2 mt-3">Opens in a new tab — come back and refresh the list once saved.</span>
                                    </div>
                                </div>

                                <h5 class="mb-3 mt-2">Placement</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Company</label>
                                            <select class="form-control" id="companyId"><option value="">Loading&hellip;</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Linked Requirement</label>
                                            <select class="form-control" id="reqId"><option value="">-- None --</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Post</label>
                                            <input type="text" class="form-control" id="post">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Annual CTC (₹)</label>
                                            <input type="number" step="0.01" class="form-control" id="ctc" placeholder="e.g. 216000">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Joining Date</label>
                                            <input type="date" class="form-control" id="joiningDate">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Joining Status</label>
                                            <select class="form-control" id="joiningStatus">
                                                <option value="Offer Accepted">Offer Accepted</option>
                                                <option value="Joined">Joined</option>
                                                <option value="Invoice Sent">Invoice Sent</option>
                                                <option value="Amount Received">Amount Received</option>
                                                <option value="Job Left">Job Left</option>
                                                <option value="Not Joined">Not Joined</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Worked By</label>
                                            <input type="text" class="form-control" id="workedBy" placeholder="Recruiter name">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Remark</label>
                                            <textarea class="form-control" id="remark" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mb-3 mt-2">Invoice &amp; GST</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Invoice Date</label>
                                            <input type="date" class="form-control" id="invoiceDate">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Invoice No</label>
                                            <input type="text" class="form-control" id="invoiceNo" placeholder="e.g. OS/2025-26/145">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Charges (base)</label>
                                            <input type="number" step="0.01" class="form-control gst-input" id="charges">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">GST %</label>
                                            <select class="form-control" id="gstPercent">
                                                <option value="0">0%</option>
                                                <option value="18" selected>18% (9% CGST + 9% SGST)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">CGST</label>
                                            <input type="number" step="0.01" class="form-control" id="cgst" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">SGST</label>
                                            <input type="number" step="0.01" class="form-control" id="sgst" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Total GST</label>
                                            <input type="number" step="0.01" class="form-control" id="totalGst" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Total Amount</label>
                                            <input type="number" step="0.01" class="form-control" id="amount" readonly>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mb-3 mt-2">Payment</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Amount Received</label>
                                            <input type="number" step="0.01" class="form-control" id="recAmount">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Received Date</label>
                                            <input type="date" class="form-control" id="paymentRecDate">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Mode</label>
                                            <input type="text" class="form-control" id="paymentMode" placeholder="Cash / Bank / UPI">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">TDS</label>
                                            <input type="number" step="0.01" class="form-control" id="tds">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">IPC Invoice Date</label>
                                            <input type="date" class="form-control" id="ipcInvDate">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">IPC Invoice No</label>
                                            <input type="text" class="form-control" id="ipcInvNo">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">IPC Invoice Amount</label>
                                            <input type="number" step="0.01" class="form-control" id="ipcInvAmt">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Date</label>
                                            <input type="date" class="form-control" id="paymentDate">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Details</label>
                                            <input type="text" class="form-control" id="paymentDetails">
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-primary w-md" onclick="savePlacement();">Save</button>
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
var editId = parseInt(document.getElementById('id').value) || 0;
var initialCandidateId = parseInt(document.getElementById('initialCandidateId').value) || 0;

function showMessage(msg, ok) {
    var el = document.getElementById('message');
    el.innerHTML = msg;
    el.className = ok ? 'add-message' : 'error-message';
}

function loadCandidateDropdown(selected, cb) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetlistcandidate' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('candidateId');
        sel.innerHTML = '<option value="">-- Select Candidate --</option>';
        (res.data || []).forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.iCandidateId;
            opt.textContent = c.sCandidateName + (c.sMobile ? ' — ' + c.sMobile : '');
            if (selected && parseInt(selected) === parseInt(c.iCandidateId)) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
        if (cb) cb();
    });
}

function loadCompanyDropdown(selected) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetcompanydropdown' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('companyId');
        sel.innerHTML = '<option value="">-- Select Company --</option>';
        (res.data || []).forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.iCompanyId;
            opt.textContent = c.sCompanyName;
            if (selected && parseInt(selected) === parseInt(c.iCompanyId)) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
    });
}

function loadRequirementDropdown(selected) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetlistrequirement' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('reqId');
        sel.innerHTML = '<option value="">-- None --</option>';
        (res.data || []).forEach(function (r) {
            var opt = document.createElement('option');
            opt.value = r.iReqId;
            opt.textContent = r.sReqNo + ' — ' + r.sPost + (r.sCompanyName ? ' (' + r.sCompanyName + ')' : '');
            if (selected && parseInt(selected) === parseInt(r.iReqId)) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
    });
}

function recalcGst() {
    var charges = parseFloat(document.getElementById('charges').value) || 0;
    var pct = parseFloat(document.getElementById('gstPercent').value) || 0;
    var half = Math.round((charges * (pct / 2) / 100) * 100) / 100;
    var totalGst = Math.round((half * 2) * 100) / 100;
    var amount = Math.round((charges + totalGst) * 100) / 100;
    document.getElementById('cgst').value = half.toFixed(2);
    document.getElementById('sgst').value = half.toFixed(2);
    document.getElementById('totalGst').value = totalGst.toFixed(2);
    document.getElementById('amount').value = amount.toFixed(2);
}

document.getElementById('charges').addEventListener('input', recalcGst);
// jQuery's .on(), not addEventListener: Select2 fires its change
// notifications through jQuery's event system, which a native
// addEventListener('change', ...) listener never sees.
$('#gstPercent').on('change', recalcGst);

function loadPlacement() {
    loadCompanyDropdown(null);
    loadRequirementDropdown(null);

    if (!editId) {
        loadCandidateDropdown(initialCandidateId || null);
        recalcGst();
        return;
    }
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getplacementbyid', id: editId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') { showMessage(res.message, false); return; }
        var d = res.data;
        loadCandidateDropdown(d.iCandidateId);
        loadCompanyDropdown(d.iCompanyId);
        loadRequirementDropdown(d.iReqId);

        document.getElementById('post').value = d.sPost || '';
        document.getElementById('ctc').value = d.dCtc || '';
        document.getElementById('joiningDate').value = d.dJoiningDate || '';
        document.getElementById('joiningStatus').value = d.sJoiningStatus || 'Offer Accepted';
        crmRefreshSelect2(document.getElementById('joiningStatus'));
        document.getElementById('workedBy').value = d.sWorkedBy || '';
        document.getElementById('remark').value = d.sRemark || '';

        document.getElementById('invoiceDate').value = d.dInvoiceDate || '';
        document.getElementById('invoiceNo').value = d.sInvoiceNo || '';
        document.getElementById('charges').value = d.dCharges || 0;
        var pct = (parseFloat(d.dCharges) > 0) ? Math.round((parseFloat(d.dTotalGst) / parseFloat(d.dCharges)) * 100) : 18;
        document.getElementById('gstPercent').value = (pct === 0) ? '0' : '18';
        recalcGst();

        document.getElementById('recAmount').value = d.dRecAmount || 0;
        document.getElementById('paymentRecDate').value = d.dPaymentRecDate || '';
        document.getElementById('paymentMode').value = d.sPaymentMode || '';
        document.getElementById('tds').value = d.dTds || 0;
        document.getElementById('ipcInvDate').value = d.dIpcInvDate || '';
        document.getElementById('ipcInvNo').value = d.sIpcInvNo || '';
        document.getElementById('ipcInvAmt').value = d.dIpcInvAmt || 0;
        document.getElementById('paymentDate').value = d.dPaymentDate || '';
        document.getElementById('paymentDetails').value = d.sPaymentDetails || '';
    });
}

function savePlacement() {
    var candidateId = document.getElementById('candidateId').value;
    if (!candidateId) { alert('Please select a candidate.'); return; }

    var data = {
        action: editId ? 'updateplacement' : 'addplacement',
        id: editId,
        candidateId: candidateId,
        companyId: document.getElementById('companyId').value,
        reqId: document.getElementById('reqId').value,
        post: document.getElementById('post').value,
        ctc: document.getElementById('ctc').value,
        joiningDate: document.getElementById('joiningDate').value,
        joiningStatus: document.getElementById('joiningStatus').value,
        workedBy: document.getElementById('workedBy').value,
        remark: document.getElementById('remark').value,
        invoiceDate: document.getElementById('invoiceDate').value,
        invoiceNo: document.getElementById('invoiceNo').value,
        charges: document.getElementById('charges').value,
        cgst: document.getElementById('cgst').value,
        sgst: document.getElementById('sgst').value,
        recAmount: document.getElementById('recAmount').value,
        paymentRecDate: document.getElementById('paymentRecDate').value,
        paymentMode: document.getElementById('paymentMode').value,
        tds: document.getElementById('tds').value,
        ipcInvDate: document.getElementById('ipcInvDate').value,
        ipcInvNo: document.getElementById('ipcInvNo').value,
        ipcInvAmt: document.getElementById('ipcInvAmt').value,
        paymentDate: document.getElementById('paymentDate').value,
        paymentDetails: document.getElementById('paymentDetails').value
    };

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        showMessage(res.message, res.status === 'success');
        if (res.status !== 'success') return;
        setTimeout(function () { window.location.href = 'list-placement.php'; }, 500);
    });
}

loadPlacement();
</script>
