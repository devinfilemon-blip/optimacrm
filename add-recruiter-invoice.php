<?php include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header('Location: list-recruiter-invoice.php'); exit; }
?>
<head>
    <title>Recruiter Tax Invoice | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Recruiter Tax Invoice</h4>
                            <div class="page-title-right d-flex align-items-center gap-3">
                                <a href="generate-recruiter-invoice.php?id=<?php echo $id; ?>" target="_blank" id="downloadLink" class="btn btn-info btn-sm" style="display:none;"><i class="bx bx-file"></i> View / Download PDF</a>
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-recruiter-invoice.php">Recruiter Tax Invoices</a></li>
                                    <li class="breadcrumb-item active">Edit</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-10">
                        <div class="card">
                            <div class="card-body">
                                <div><span id="message"></span></div>

                                <h5 class="mb-3">Commission Details</h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3"><strong>Recruiter</strong><div id="dRecruiter">-</div></div>
                                    <div class="col-md-3 mb-3"><strong>Candidate</strong><div id="dCandidate">-</div></div>
                                    <div class="col-md-3 mb-3"><strong>Company</strong><div id="dCompany">-</div></div>
                                    <div class="col-md-3 mb-3"><strong>Post</strong><div id="dPost">-</div></div>
                                    <div class="col-md-3 mb-3"><strong>Candidate CTC</strong><div id="dCtc">-</div></div>
                                    <div class="col-md-3 mb-3"><strong>Commission (20% of CTC)</strong><div id="dCommission">-</div></div>
                                </div>

                                <h5 class="mb-3 mt-2">Invoice &amp; GST</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Invoice No</label>
                                            <input type="text" class="form-control" id="invoiceNo" placeholder="e.g. RC/2026-27/12">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Invoice Date</label>
                                            <input type="date" class="form-control" id="invoiceDate">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">GST %</label>
                                            <select class="form-control" id="gstPercent">
                                                <option value="0" selected>0%</option>
                                                <option value="18">18% (9% CGST + 9% SGST)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Total Invoice Amount</label>
                                            <input type="number" step="0.01" class="form-control" id="invoiceAmount" readonly>
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
                                </div>

                                <h5 class="mb-3 mt-2">Payment</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Paid Amount</label>
                                            <input type="number" step="0.01" class="form-control" id="paidAmount">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Date</label>
                                            <input type="date" class="form-control" id="paymentDate">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Mode</label>
                                            <input type="text" class="form-control" id="paymentMode" placeholder="Cash / Bank / UPI">
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-primary w-md" onclick="saveInvoice();">Save</button>
                                <a href="list-recruiter-invoice.php" class="btn btn-secondary w-md">Cancel</a>
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
var editId = <?php echo $id; ?>;
var baseCommission = 0;

function showMessage(msg, ok) {
    var el = document.getElementById('message');
    el.innerHTML = msg;
    el.className = ok ? 'add-message' : 'error-message';
}
function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
function fmtCurrency(n) {
    n = parseFloat(n || 0);
    return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
}

function recalcGst() {
    var pct = parseFloat(document.getElementById('gstPercent').value) || 0;
    var half = Math.round((baseCommission * (pct / 2) / 100) * 100) / 100;
    var totalGst = Math.round((half * 2) * 100) / 100;
    var invoiceAmount = Math.round((baseCommission + totalGst) * 100) / 100;
    document.getElementById('cgst').value = half.toFixed(2);
    document.getElementById('sgst').value = half.toFixed(2);
    document.getElementById('totalGst').value = totalGst.toFixed(2);
    document.getElementById('invoiceAmount').value = invoiceAmount.toFixed(2);
}
$('#gstPercent').on('change', recalcGst);

function loadInvoice() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getrecruiterinvoicebyid', id: editId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') { showMessage(res.message, false); return; }
        var d = res.data;
        baseCommission = parseFloat(d.dAmount) || 0;

        document.getElementById('dRecruiter').textContent = d.recruiterName || '-';
        document.getElementById('dCandidate').textContent = d.sCandidateName || '-';
        document.getElementById('dCompany').textContent = d.sCompanyName || '-';
        document.getElementById('dPost').textContent = d.sPost || '-';
        document.getElementById('dCtc').textContent = fmtCurrency(d.placementCtc);
        document.getElementById('dCommission').textContent = fmtCurrency(d.dAmount);

        document.getElementById('invoiceNo').value = d.sInvoiceNo || '';
        document.getElementById('invoiceDate').value = d.dInvoiceDate || '';
        document.getElementById('gstPercent').value = (parseFloat(d.dGstPercent) === 18) ? '18' : '0';
        crmRefreshSelect2(document.getElementById('gstPercent'));
        document.getElementById('paidAmount').value = d.dPaidAmount || 0;
        document.getElementById('paymentDate').value = d.dPaymentDate || '';
        document.getElementById('paymentMode').value = d.sPaymentMode || '';
        recalcGst();

        if (d.sInvoiceNo && d.dInvoiceDate) {
            document.getElementById('downloadLink').style.display = '';
        }
    });
}

function saveInvoice() {
    var invoiceNo = document.getElementById('invoiceNo').value.trim();
    var invoiceDate = document.getElementById('invoiceDate').value;
    if (invoiceNo && !invoiceDate) { alert('Please choose the invoice date.'); return; }
    if (invoiceDate && !invoiceNo) { alert('Please enter the invoice number.'); return; }

    var data = {
        action: 'updaterecruiterinvoice',
        id: editId,
        invoiceNo: invoiceNo,
        invoiceDate: invoiceDate,
        gstPercent: document.getElementById('gstPercent').value,
        paidAmount: document.getElementById('paidAmount').value,
        paymentDate: document.getElementById('paymentDate').value,
        paymentMode: document.getElementById('paymentMode').value
    };

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        showMessage(res.message, res.status === 'success');
        if (res.status === 'success') {
            setTimeout(function () { window.location.href = 'list-recruiter-invoice.php'; }, 500);
        }
    });
}

loadInvoice();
</script>
