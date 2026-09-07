<?php include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<head>
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Expense | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18"><?php echo $id ? 'Edit' : 'Add'; ?> Expense</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-expense.php">Revenue &amp; Financial</a></li>
                                    <li class="breadcrumb-item active"><?php echo $id ? 'Edit' : 'Add'; ?></li>
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
                                <input type="hidden" id="id" value="<?php echo $id; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Category *</label>
                                            <input type="text" class="form-control" id="category" list="categorySuggestions" placeholder="e.g. Office Rent" required>
                                            <datalist id="categorySuggestions">
                                                <option value="Salaries &amp; Wages">
                                                <option value="Office Rent">
                                                <option value="Marketing &amp; Advertising">
                                                <option value="Software &amp; Subscriptions">
                                                <option value="Utilities">
                                                <option value="Travel &amp; Conveyance">
                                                <option value="Professional Fees">
                                                <option value="Miscellaneous">
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Amount (₹) *</label>
                                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Expense Date *</label>
                                            <input type="date" class="form-control" id="expenseDate" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Mode</label>
                                            <input type="text" class="form-control" id="paymentMode" placeholder="Cash / Bank / UPI">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <input type="text" class="form-control" id="description" placeholder="What was this expense for?">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Remark</label>
                                            <textarea class="form-control" id="remark" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary w-md" onclick="saveExpense();">Save</button>
                                <a href="list-expense.php" class="btn btn-secondary w-md">Cancel</a>
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

function showMessage(msg, ok) {
    var el = document.getElementById('message');
    el.innerHTML = msg;
    el.className = ok ? 'add-message' : 'error-message';
}

function loadExpense() {
    if (!editId) {
        document.getElementById('expenseDate').value = new Date().toISOString().substring(0, 10);
        return;
    }
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getexpensebyid', id: editId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') { showMessage(res.message, false); return; }
        var d = res.data;
        document.getElementById('category').value = d.sCategory || '';
        document.getElementById('amount').value = d.dAmount || '';
        document.getElementById('expenseDate').value = d.dExpenseDate || '';
        document.getElementById('paymentMode').value = d.sPaymentMode || '';
        document.getElementById('description').value = d.sDescription || '';
        document.getElementById('remark').value = d.sRemark || '';
    });
}

function saveExpense() {
    var category = document.getElementById('category').value.trim();
    var amount = parseFloat(document.getElementById('amount').value);
    var expenseDate = document.getElementById('expenseDate').value;
    if (!category) { alert('Please enter a category.'); return; }
    if (!amount || amount <= 0) { alert('Please enter a valid amount.'); return; }
    if (!expenseDate) { alert('Please choose the expense date.'); return; }

    var data = {
        action: editId ? 'updateexpense' : 'addexpense',
        id: editId,
        category: category,
        amount: amount,
        expenseDate: expenseDate,
        paymentMode: document.getElementById('paymentMode').value,
        description: document.getElementById('description').value,
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
            setTimeout(function () { window.location.href = 'list-expense.php'; }, 500);
        }
    });
}

loadExpense();
</script>
