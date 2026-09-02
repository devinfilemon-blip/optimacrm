<!-- Late theme overrides -->
<link href="assets/css/crm-dark.css?v=1" rel="stylesheet" type="text/css" />

<!-- JAVASCRIPT -->
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>

<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
(function () {
    try {
        if (localStorage.getItem('crm_theme') === 'dark' && document.body) {
            document.body.classList.add('crm-dark');
            document.documentElement.classList.add('crm-dark', 'crm-dark-preload');
        }
    } catch (e) {}
})();

function initCrmMobileSidebar() {
    var overlay = document.getElementById('crmSidebarOverlay');

    function closeSidebar() {
        document.body.classList.remove('sidebar-enable');
        if (overlay) overlay.setAttribute('aria-hidden', 'true');
    }
    function openSidebar() {
        document.body.classList.add('sidebar-enable');
        if (overlay) overlay.setAttribute('aria-hidden', 'false');
    }
    if (overlay) overlay.addEventListener('click', closeSidebar);

    document.querySelectorAll('#sidebar-menu a[href]').forEach(function (link) {
        var href = link.getAttribute('href') || '';
        if (href && href.indexOf('javascript') !== 0 && href !== '#') {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) closeSidebar();
            });
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.body.classList.contains('sidebar-enable')) closeSidebar();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) closeSidebar();
    });

    var btn = document.getElementById('vertical-menu-btn');
    if (btn) {
        btn.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                document.body.classList.contains('sidebar-enable') ? closeSidebar() : openSidebar();
            } else {
                document.body.classList.toggle('vertical-collpsed');
                document.body.classList.toggle('sidebar-enable');
            }
        });
    }
}
document.addEventListener('DOMContentLoaded', initCrmMobileSidebar);

/* Activate the collapsible sidebar sub-menus (e.g. Masters > Requirement Status / Lead Sources) */
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.metisMenu && $('#side-menu').length) {
        $('#side-menu').metisMenu();
    }
});

/* Theme toggle */
(function () {
    var DARK_CLASS = 'crm-dark';
    var STORAGE_KEY = 'crm_theme';

    function applyTheme(dark) {
        if (dark) {
            document.body.classList.add(DARK_CLASS);
            document.documentElement.classList.add(DARK_CLASS, 'crm-dark-preload');
            document.documentElement.style.colorScheme = 'dark';
            document.documentElement.style.backgroundColor = '#0b0f1e';
        } else {
            document.body.classList.remove(DARK_CLASS);
            document.documentElement.classList.remove(DARK_CLASS, 'crm-dark-preload');
            document.documentElement.style.colorScheme = '';
            document.documentElement.style.backgroundColor = '';
        }
        var meta = document.getElementById('crmThemeColorMeta');
        if (meta) meta.setAttribute('content', dark ? '#0b0f1e' : '#9333ea');
        var icon = document.getElementById('crmThemeIcon');
        if (icon) icon.className = dark ? 'bx bx-sun' : 'bx bx-moon';
        var toggleBtn = document.getElementById('crmThemeToggle');
        if (toggleBtn) toggleBtn.title = dark ? 'Switch to Light theme' : 'Switch to Dark theme';

        try {
            window.dispatchEvent(new CustomEvent('crm-theme-changed', { detail: { dark: !!dark } }));
        } catch (e) {}
    }

    function bindThemeToggle() {
        var toggle = document.getElementById('crmThemeToggle');
        if (!toggle || toggle.getAttribute('data-crm-theme-bound') === '1') return;
        toggle.setAttribute('data-crm-theme-bound', '1');
        toggle.addEventListener('click', function () {
            var nowDark = !document.body.classList.contains(DARK_CLASS);
            localStorage.setItem(STORAGE_KEY, nowDark ? 'dark' : 'light');
            applyTheme(nowDark);
        });
    }

    function initTheme() {
        applyTheme(localStorage.getItem(STORAGE_KEY) === 'dark');
        bindThemeToggle();
    }

    if (document.body) initTheme();
    else document.addEventListener('DOMContentLoaded', initTheme);
})();

/* Builds a single "⋮" actions cell for a list row instead of separate
   Edit/Delete buttons. items: [{ label, icon (bx-* name), href, target,
   onclick (raw JS string, used when href is omitted), danger (bool) }] */
function crmActionMenu(items) {
    var html = '<td class="text-center"><div class="dropdown">' +
        '<button class="optima-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
        '<i class="bx bx-dots-vertical-rounded"></i></button>' +
        '<ul class="dropdown-menu dropdown-menu-end optima-action-menu">';
    items.forEach(function (it) {
        var cls = 'dropdown-item' + (it.danger ? ' text-danger' : '');
        var attrs = it.href
            ? 'href="' + it.href + '"' + (it.target ? ' target="' + it.target + '"' : '')
            : 'href="javascript:void(0);" onclick="' + it.onclick + '"';
        html += '<li><a class="' + cls + '" ' + attrs + '><i class="bx ' + it.icon + '"></i> ' + it.label + '</a></li>';
    });
    html += '</ul></div></td>';
    return html;
}

function enhanceCrmActionButtons(root) {
    var $scope = root ? $(root) : $(document);
    var $cells = $scope.find('#datatable td, .table-responsive .table td');

    $cells.find('.btn-info').each(function () {
        var $btn = $(this);
        if (!$btn.find('i.bx').length) $btn.prepend('<i class="bx bx-show"></i>');
        $btn.addClass('crm-btn-action crm-btn-view');
    });
    $cells.find('button.btn-success').each(function () {
        var $btn = $(this);
        if (!$btn.find('i.bx').length) $btn.prepend('<i class="bx bx-edit-alt"></i>');
        $btn.addClass('crm-btn-action crm-btn-edit');
    });
    $cells.find('.btn-danger, .delete-btn').each(function () {
        var $btn = $(this);
        if (!$btn.find('i.bx').length) $btn.prepend('<i class="bx bx-trash"></i>');
        $btn.addClass('crm-btn-action crm-btn-delete');
    });
}

/* ---- Double-click a list row to edit it in place ----
   A list page opts in by declaring, before vendor-scripts.php or in its own
   script block, window.crmInlineEdit = {
     getRowId($row)          -> the row's id, usually parseInt($row.data('id'))
     getFullRow(id)          -> the full record object already held for that row
                                (so hidden fields not shown in the table aren't
                                lost when the edited ones are saved)
     fields: [{ cellIndex, key, type: 'text'|'number'|'date'|'select', options }]
                                options is an array of {value,label}, or a
                                function(cb) that resolves one asynchronously
     toPayload(merged, id)   -> object to POST as the update action's body
                                (merged = fullRow with the edited keys overlaid)
     saveAction: 'updaterequirement',
     onSaved()               -> called after a successful save, usually the
                                page's own fngetlistX() to reload the table
   }
   Pages that don't set this fall back to navigating to the row's Edit link. */
var crmInlineEditRow = null;

function crmInlineEditBuildInput(field, value) {
    if (field.type === 'select') {
        var $sel = $('<select class="form-select form-select-sm crm-inline-input"></select>');
        var options = field.optionsList || [];
        // A saved value that no longer matches any option (e.g. a recruiter
        // name that predates the current option list) must not silently fall
        // back to whatever option happens to be first — that would rewrite
        // the field to a different value the moment this row gets saved for
        // any other reason. Keep it visible and selected as an extra option
        // until the user deliberately picks something else.
        var hasCurrent = value == null || value === '' || options.some(function (o) { return String(o.value) === String(value); });
        if (!hasCurrent) options = [{ value: value, label: value + ' (not in list)' }].concat(options);
        options.forEach(function (o) {
            var $opt = $('<option></option>').attr('value', o.value).text(o.label);
            if (String(o.value) === String(value == null ? '' : value)) $opt.prop('selected', true);
            $sel.append($opt);
        });
        return $sel;
    }
    var type = field.type === 'date' ? 'date' : (field.type === 'number' ? 'number' : 'text');
    return $('<input class="form-control form-control-sm crm-inline-input">').attr('type', type).val(value == null ? '' : value);
}

function crmInlineEditCommit($row) {
    var state = $row.data('crmEditState');
    if (!state || state.saving) return;
    state.saving = true;

    var edited = {};
    state.config.fields.forEach(function (f) {
        edited[f.key] = $row.find('td').eq(f.cellIndex).find('.crm-inline-input').val();
    });
    var merged = $.extend({}, state.fullRow || {}, edited);
    var payload = state.config.toPayload(merged, state.id);
    payload.action = state.config.saveAction;

    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
        .then(r => r.json())
        .then(res => {
            var msgEl = document.getElementById('message');
            if (msgEl) { msgEl.innerHTML = res.message; msgEl.className = res.status === 'success' ? 'add-message' : 'error-message'; }
            if (res.status === 'success') {
                if (crmInlineEditRow && crmInlineEditRow[0] === $row[0]) crmInlineEditRow = null;
                state.config.onSaved();
            } else {
                state.saving = false;
            }
        })
        .catch(function () { state.saving = false; });
}

function crmInlineEditRevert($row) {
    $row.find('td').each(function () {
        var $cell = $(this);
        var orig = $cell.data('crmOriginalHtml');
        if (orig !== undefined) $cell.html(orig).removeData('crmOriginalHtml');
    });
    $row.removeClass('crm-row-editing').off('.crmInline').removeData('crmEditState');
    if (crmInlineEditRow && crmInlineEditRow[0] === $row[0]) crmInlineEditRow = null;
}

function crmInlineEditStart($row, config, id) {
    if (crmInlineEditRow && crmInlineEditRow[0] === $row[0]) return;
    if (crmInlineEditRow) crmInlineEditCommit(crmInlineEditRow);

    var fullRow = config.getFullRow(id) || {};
    $row.addClass('crm-row-editing');
    $row.data('crmEditState', { config: config, id: id, fullRow: fullRow, saving: false });
    crmInlineEditRow = $row;

    config.fields.forEach(function (f) {
        var $cell = $row.find('td').eq(f.cellIndex);
        $cell.data('crmOriginalHtml', $cell.html());
        var rawValue = fullRow[f.key];

        function place(optionsList) {
            f.optionsList = optionsList;
            $cell.empty().append(crmInlineEditBuildInput(f, rawValue));
        }
        if (f.type === 'select' && typeof f.options === 'function') f.options(place);
        else place(f.options || []);
    });

    $row.find('.crm-inline-input').first().trigger('focus');

    $row.on('keydown.crmInline', '.crm-inline-input', function (e) {
        if (e.key === 'Escape') { e.stopPropagation(); crmInlineEditRevert($row); }
        else if (e.key === 'Enter') { e.preventDefault(); crmInlineEditCommit($row); }
    });
    $row.on('focusout.crmInline', function () {
        setTimeout(function () {
            if (crmInlineEditRow && crmInlineEditRow[0] === $row[0] && $row.find('.crm-inline-input:focus').length === 0) {
                crmInlineEditCommit($row);
            }
        }, 0);
    });
}

$(document).on('dblclick', '#datatable tbody tr', function (e) {
    if ($(e.target).closest('a, button, .btn, input, select, textarea, label').length) return;
    var $row = $(this);
    if (window.crmInlineEdit) {
        var id = window.crmInlineEdit.getRowId($row);
        if (id !== null && id !== undefined && !isNaN(id)) crmInlineEditStart($row, window.crmInlineEdit, id);
        return;
    }
    var $edit = $row.find('a.btn-success[href]').first();
    if ($edit.length) window.location.href = $edit.attr('href');
});

$(document).ready(function () {
    enhanceCrmActionButtons();

    if ($.fn.DataTable && $('#datatable').length && !$.fn.DataTable.isDataTable('#datatable')) {
        var isMobile = window.matchMedia('(max-width: 991.98px)').matches;
        var table = $('#datatable').DataTable({
            responsive: false,
            scrollX: isMobile,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            language: {
                search: '',
                searchPlaceholder: 'Search records...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                paginate: { previous: 'Prev', next: 'Next' }
            }
        });
        table.on('draw.dt', function () { enhanceCrmActionButtons('#datatable'); });
    } else if ($('#datatable').length) {
        $('#datatable').on('draw.dt', function () { enhanceCrmActionButtons('#datatable'); });
    }
});
</script>
