<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<head>
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Company Agreement | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18"><?php echo $id ? 'Edit' : 'Add'; ?> Company Agreement</h4>
                            <div class="page-title-right d-flex align-items-center gap-3">
                                <?php if ($id) : ?>
                                <a href="generate-company-agreement.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-info btn-sm"><i class="bx bx-printer"></i> Print Agreement</a>
                                <?php endif; ?>
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-company-agreement.php">Company Agreements</a></li>
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
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Company *</label>
                                            <div class="d-flex gap-2">
                                                <select class="form-control" id="companyId" required><option value="">Loading&hellip;</option></select>
                                                <button type="button" class="btn btn-outline-primary flex-shrink-0" id="addCompanyBtn" onclick="openAddCompanyModal();" title="Add new company"><i class="bx bx-plus"></i> Add Company</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" id="status">
                                                <option value="Pending">Pending</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Company Owner Name</label>
                                            <input type="text" class="form-control" id="ownerName" placeholder="e.g. Rajesh Sharma">
                                            <div class="form-text">Printed on the agreement letter as "Company Owner name".</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Company Address</label>
                                            <textarea class="form-control" id="ownerAddress" rows="2" placeholder="Printed on the agreement letter as Company Address"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">% of CTC</label>
                                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="ctcPercentage" placeholder="e.g. 8.33">
                                            <div class="form-text">Professional fee — % of the candidate's Annual CTC.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Government Tax (%)</label>
                                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="governmentTax" value="18">
                                            <div class="form-text">GST on professional fees.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Agreement Effective Date</label>
                                            <input type="date" class="form-control" id="effectiveDate">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Replacement Guarantee (Months of Joining)</label>
                                            <input type="number" step="1" min="0" class="form-control" id="replacementMonths" value="3">
                                            <div class="form-text">Free replacement if the candidate resigns on their own within this many months of joining.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Replacement Period &mdash; Payment Within (Days)</label>
                                            <input type="number" step="1" min="0" class="form-control" id="paymentWithinDays" value="15">
                                            <div class="form-text">Replacement guarantee applies only if payment is made within this many days of the invoice date.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Replacement Period &mdash; Billing Within (Days)</label>
                                            <input type="number" step="1" min="0" class="form-control" id="billingWithinDays" value="8">
                                            <div class="form-text">All bills to be submitted within this many days of the candidate's joining.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Remark</label>
                                            <textarea class="form-control" id="remark" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary w-md" onclick="saveAgreement();">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="addCompanyModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Company</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div><span id="addCompanyMessage"></span></div>
                                <div class="mb-0">
                                    <label class="form-label">Company Name *</label>
                                    <input type="text" class="form-control" id="newCompanyName" placeholder="e.g. Acme Industries">
                                    <div class="form-text">Other company details (contact, GSTIN, address, etc.) can be filled in later from the Companies list.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="saveNewCompany();">Save</button>
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
// A company's agreement can't be reassigned to a different company after
// creation, so there's nothing to add a new one for on the edit screen.
if (editId) document.getElementById('addCompanyBtn').style.display = 'none';

function showMessage(msg, ok) {
    var el = document.getElementById('message');
    el.innerHTML = msg;
    el.className = ok ? 'add-message' : 'error-message';
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
        // A company's agreement can't be reassigned to a different company
        // after creation — only its terms/status change on edit.
        if (editId) {
            sel.disabled = true;
            crmRefreshSelect2(sel);
        }
    });
}

function openAddCompanyModal() {
    document.getElementById('newCompanyName').value = '';
    document.getElementById('addCompanyMessage').innerHTML = '';
    new bootstrap.Modal(document.getElementById('addCompanyModal')).show();
}

function saveNewCompany() {
    var name = document.getElementById('newCompanyName').value.trim();
    if (!name) { alert('Enter a company name.'); return; }
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'addcompany', companyName: name })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') {
            var el = document.getElementById('addCompanyMessage');
            el.innerHTML = res.message; el.className = 'error-message';
            return;
        }
        loadCompanyDropdown(res.data && res.data.id);
        var modalEl = document.getElementById('addCompanyModal');
        bootstrap.Modal.getInstance(modalEl).hide();
    });
}

// New agreement only: pre-fill Owner Name / Address from the company's own
// record as a starting point, editable per-agreement from there — the
// company record rarely has these filled in (see add-company.php), so this
// just saves retyping when it does.
function prefillFromCompany() {
    if (editId) return;
    var companyId = document.getElementById('companyId').value;
    if (!companyId) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getcompanybyid', id: companyId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') return;
        var d = res.data;
        if (!document.getElementById('ownerName').value) document.getElementById('ownerName').value = d.sContactPerson || '';
        if (!document.getElementById('ownerAddress').value) document.getElementById('ownerAddress').value = d.sAddress || '';
    });
}
$('#companyId').on('change', prefillFromCompany);

function loadAgreement() {
    loadCompanyDropdown(null);
    if (!editId) return;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getcompanyagreementbyid', id: editId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') { showMessage(res.message, false); return; }
        var d = res.data;
        loadCompanyDropdown(d.iCompanyId);
        document.getElementById('ownerName').value = d.sOwnerName || '';
        document.getElementById('ownerAddress').value = d.sAddress || '';
        document.getElementById('status').value = d.sStatus || 'Pending';
        crmRefreshSelect2(document.getElementById('status'));
        document.getElementById('ctcPercentage').value = d.dCtcPercentage || '';
        document.getElementById('governmentTax').value = d.dGovernmentTax || '';
        document.getElementById('effectiveDate').value = d.dEffectiveDate || '';
        document.getElementById('replacementMonths').value = d.iReplacementMonths || '';
        document.getElementById('paymentWithinDays').value = d.iPaymentWithinDays || '';
        document.getElementById('billingWithinDays').value = d.iBillingWithinDays || '';
        document.getElementById('remark').value = d.sRemark || '';
    });
}

function saveAgreement() {
    var companyId = document.getElementById('companyId').value;
    if (!companyId) { alert('Please select a company.'); return; }

    var data = {
        action: editId ? 'updatecompanyagreement' : 'addcompanyagreement',
        id: editId,
        companyId: companyId,
        ownerName: document.getElementById('ownerName').value,
        address: document.getElementById('ownerAddress').value,
        status: document.getElementById('status').value,
        ctcPercentage: document.getElementById('ctcPercentage').value,
        governmentTax: document.getElementById('governmentTax').value,
        effectiveDate: document.getElementById('effectiveDate').value,
        replacementMonths: document.getElementById('replacementMonths').value,
        paymentWithinDays: document.getElementById('paymentWithinDays').value,
        billingWithinDays: document.getElementById('billingWithinDays').value,
        remark: document.getElementById('remark').value
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
            setTimeout(function () { window.location.href = 'list-company-agreement.php'; }, 500);
        }
    });
}

loadAgreement();
</script>
