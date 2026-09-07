<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Calendar | <?php echo APP_NAME; ?></title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/@fullcalendar/core/main.min.css" rel="stylesheet" />
    <link href="assets/libs/@fullcalendar/daygrid/main.min.css" rel="stylesheet" />
    <link href="assets/libs/@fullcalendar/timegrid/main.min.css" rel="stylesheet" />
    <link href="assets/libs/@fullcalendar/bootstrap/main.min.css" rel="stylesheet" />
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
                            <h4 class="mb-sm-0 font-size-18">Calendar</h4>
                            <div class="page-title-right d-flex flex-wrap align-items-center gap-3">
                                <span class="crm-calendar-legend-item"><span class="crm-calendar-dot crm-calendar-dot--reminder"></span> Reminders</span>
                                <span class="crm-calendar-legend-item"><span class="crm-calendar-dot crm-calendar-dot--followup"></span> Interview Schedule</span>
                                <span class="crm-calendar-legend-item"><span class="crm-calendar-dot crm-calendar-dot--joining"></span> Candidate Joining</span>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openScheduleModal(null, null);"><i class="bx bx-plus"></i> Add Schedule</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <div id="optimaCalendar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>

<!-- Event detail modal -->
<div class="modal fade" id="calendarEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calendarEventTitle">Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>Date:</strong> <span id="calendarEventDate"></span></p>
                <p class="mb-2"><strong>Type:</strong> <span id="calendarEventType"></span></p>
                <p class="mb-0"><strong>Details:</strong> <span id="calendarEventDesc"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="calendarOpenBtn" class="btn btn-primary" style="display:none;">Open</a>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Schedule modal — every date on the calendar opens this,
     either empty (add) or pre-filled (editing an existing reminder /
     interview schedule entry clicked from the grid). -->
<div class="modal fade" id="calendarScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalTitle">Add Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div><span id="scheduleMessage"></span></div>
                <input type="hidden" id="scheduleId" value="">
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-control" id="scheduleType">
                        <option value="Reminder">Reminder</option>
                        <option value="Interview">Interview Schedule</option>
                        <option value="Joining">Candidate Joining</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description *</label>
                    <input type="text" class="form-control" id="scheduleDescription" placeholder="e.g. Interview with candidate at Company">
                </div>
                <div class="mb-3">
                    <label class="form-label">Date *</label>
                    <input type="date" class="form-control" id="scheduleDate">
                </div>
                <div class="mb-3">
                    <label class="form-label">Candidate (optional)</label>
                    <select class="form-control" id="scheduleCandidate"><option value="">-- None --</option></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Job Requirement (optional)</label>
                    <select class="form-control" id="scheduleReq"><option value="">-- None --</option></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="scheduleDeleteBtn" style="display:none;" onclick="deleteScheduleFromModal();">Delete</button>
                <button type="button" class="btn btn-outline-success" id="scheduleDoneBtn" style="display:none;" onclick="markScheduleDoneFromModal();">Mark Done</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSchedule();">Save</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php include 'layouts/vendor-scripts.php'; ?>
<script src="assets/libs/@fullcalendar/core/main.min.js"></script>
<script src="assets/libs/@fullcalendar/daygrid/main.min.js"></script>
<script src="assets/libs/@fullcalendar/timegrid/main.min.js"></script>
<script src="assets/libs/@fullcalendar/interaction/main.min.js"></script>
<script src="assets/libs/@fullcalendar/bootstrap/main.min.js"></script>
<script>
var crmCalendarInstance = null;

(function () {
    'use strict';

    var calendarEl = document.getElementById('optimaCalendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') return;

    var modalEl = document.getElementById('calendarEventModal');
    var modal = new bootstrap.Modal(modalEl);

    // 'reminder'/'interview'/'joiningreminder' events open the editable
    // Schedule modal instead (see eventClick below) — only 'joining' (the
    // auto-generated one sourced from an actual placement) still uses this
    // read-only one, since that date is edited on the placement itself.
    var TYPE_LABELS = { reminder: 'Reminder', interview: 'Interview Schedule', joining: 'Candidate Joining' };
    var TYPE_LINKS = { joining: 'add-placement.php?id=' };

    function fmtDate(d) {
        if (!d) return '—';
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: [FullCalendarDayGrid.default, FullCalendarTimeGrid.default, FullCalendarInteraction.default, FullCalendarBootstrap.default],
        themeSystem: 'bootstrap',
        defaultView: 'dayGridMonth',
        height: 'auto',
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        buttonText: { today: 'Today', month: 'Month', week: 'Week' },
        events: function (info, successCallback, failureCallback) {
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'calendar_events',
                    start: info.startStr.slice(0, 10),
                    end: info.endStr.slice(0, 10)
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    successCallback(res.data || []);
                } else {
                    failureCallback(new Error(res.message || 'Failed to load events'));
                }
            })
            .catch(function (err) { failureCallback(err); });
        },
        dateClick: function (info) {
            openScheduleModal(null, info.dateStr);
        },
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            var props = info.event.extendedProps || {};
            var type = props.type || 'event';

            if (type === 'reminder' || type === 'interview' || type === 'joiningreminder') {
                openScheduleModal(props.recordId, null);
                return;
            }

            document.getElementById('calendarEventTitle').textContent = info.event.title;
            document.getElementById('calendarEventDate').textContent = fmtDate(info.event.start);
            document.getElementById('calendarEventType').textContent = TYPE_LABELS[type] || 'Event';
            document.getElementById('calendarEventDesc').textContent = props.description || '—';

            var openBtn = document.getElementById('calendarOpenBtn');
            if (TYPE_LINKS[type] && props.recordId) {
                openBtn.href = TYPE_LINKS[type] + props.recordId;
                openBtn.style.display = '';
            } else {
                openBtn.style.display = 'none';
            }

            modal.show();
        }
    });

    calendar.render();
    crmCalendarInstance = calendar;
})();

// ---- Add / Edit Schedule modal — global scope since its buttons use
//      inline onclick, same convention as the rest of this app. ----
var scheduleModal = new bootstrap.Modal(document.getElementById('calendarScheduleModal'));

function scheduleShowMessage(msg, ok) {
    var el = document.getElementById('scheduleMessage');
    el.innerHTML = msg;
    el.className = ok ? 'add-message' : 'error-message';
}

function loadScheduleCandidateDropdown(selected) {
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'fngetlistcandidate' }) })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var sel = document.getElementById('scheduleCandidate');
            sel.innerHTML = '<option value="">-- None --</option>';
            (res.data || []).forEach(function (c) {
                var opt = document.createElement('option');
                opt.value = c.iCandidateId;
                opt.textContent = c.sCandidateName + (c.sMobile ? ' — ' + c.sMobile : '');
                if (selected && parseInt(selected) === parseInt(c.iCandidateId)) opt.selected = true;
                sel.appendChild(opt);
            });
            crmRefreshSelect2(sel);
        });
}

function loadScheduleReqDropdown(selected) {
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'fngetlistrequirement' }) })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var sel = document.getElementById('scheduleReq');
            sel.innerHTML = '<option value="">-- None --</option>';
            (res.data || []).forEach(function (r) {
                var opt = document.createElement('option');
                opt.value = r.iReqId;
                opt.textContent = crmReqNoDisplay(r.sReqNo) + ' — ' + r.sPost + (r.sCompanyName ? ' (' + r.sCompanyName + ')' : '');
                if (selected && parseInt(selected) === parseInt(r.iReqId)) opt.selected = true;
                sel.appendChild(opt);
            });
            crmRefreshSelect2(sel);
        });
}

function openScheduleModal(id, dateStr) {
    document.getElementById('scheduleId').value = id || '';
    scheduleShowMessage('', true);
    document.getElementById('scheduleDeleteBtn').style.display = id ? '' : 'none';
    document.getElementById('scheduleDoneBtn').style.display = 'none';

    if (!id) {
        document.getElementById('scheduleModalTitle').textContent = 'Add Schedule';
        document.getElementById('scheduleType').value = 'Reminder';
        document.getElementById('scheduleDescription').value = '';
        document.getElementById('scheduleDate').value = dateStr || '';
        loadScheduleCandidateDropdown(null);
        loadScheduleReqDropdown(null);
        crmRefreshSelect2(document.getElementById('scheduleType'));
        scheduleModal.show();
        return;
    }

    document.getElementById('scheduleModalTitle').textContent = 'Edit Schedule';
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'getreminderbyid', id: id }) })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.status !== 'success') { scheduleShowMessage(res.message, false); return; }
            var d = res.data;
            document.getElementById('scheduleType').value = d.sType || 'Reminder';
            crmRefreshSelect2(document.getElementById('scheduleType'));
            document.getElementById('scheduleDescription').value = d.sDescription || '';
            document.getElementById('scheduleDate').value = d.sDate || '';
            loadScheduleCandidateDropdown(d.iCandidateId);
            loadScheduleReqDropdown(d.iReqId);
            document.getElementById('scheduleDoneBtn').style.display = (d.sStatus === 'Done') ? 'none' : '';
            scheduleModal.show();
        });
}

function saveSchedule() {
    var id = document.getElementById('scheduleId').value;
    var description = document.getElementById('scheduleDescription').value.trim();
    var date = document.getElementById('scheduleDate').value;
    if (!description || !date) { scheduleShowMessage('Please enter description and date.', false); return; }

    var data = {
        action: id ? 'updatereminder' : 'addreminder',
        id: id,
        description: description,
        date: date,
        type: document.getElementById('scheduleType').value,
        candidateId: document.getElementById('scheduleCandidate').value,
        reqId: document.getElementById('scheduleReq').value
    };

    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            scheduleShowMessage(res.message, res.status === 'success');
            if (res.status === 'success') {
                if (crmCalendarInstance) crmCalendarInstance.refetchEvents();
                setTimeout(function () { scheduleModal.hide(); }, 400);
            }
        });
}

function markScheduleDoneFromModal() {
    var id = document.getElementById('scheduleId').value;
    if (!id) return;
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'markreminderdone', id: id }) })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            scheduleShowMessage(res.message, res.status === 'success');
            if (res.status === 'success') {
                document.getElementById('scheduleDoneBtn').style.display = 'none';
                if (crmCalendarInstance) crmCalendarInstance.refetchEvents();
            }
        });
}

function deleteScheduleFromModal() {
    var id = document.getElementById('scheduleId').value;
    if (!id) return;
    if (!confirm('Delete this schedule entry?')) return;
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'deletereminder', id: id }) })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.status === 'success') {
                if (crmCalendarInstance) crmCalendarInstance.refetchEvents();
                scheduleModal.hide();
            } else {
                scheduleShowMessage(res.message, false);
            }
        });
}
</script>
