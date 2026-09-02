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
                            <div class="page-title-right d-flex flex-wrap gap-3">
                                <span class="crm-calendar-legend-item"><span class="crm-calendar-dot crm-calendar-dot--reminder"></span> Reminders</span>
                                <span class="crm-calendar-legend-item"><span class="crm-calendar-dot crm-calendar-dot--followup"></span> Requirement Follow-ups</span>
                                <span class="crm-calendar-legend-item"><span class="crm-calendar-dot crm-calendar-dot--joining"></span> Candidate Joining</span>
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

</body>
</html>

<?php include 'layouts/vendor-scripts.php'; ?>
<script src="assets/libs/@fullcalendar/core/main.min.js"></script>
<script src="assets/libs/@fullcalendar/daygrid/main.min.js"></script>
<script src="assets/libs/@fullcalendar/timegrid/main.min.js"></script>
<script src="assets/libs/@fullcalendar/interaction/main.min.js"></script>
<script src="assets/libs/@fullcalendar/bootstrap/main.min.js"></script>
<script>
(function () {
    'use strict';

    var calendarEl = document.getElementById('optimaCalendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') return;

    var modalEl = document.getElementById('calendarEventModal');
    var modal = new bootstrap.Modal(modalEl);

    var TYPE_LABELS = { reminder: 'Reminder', followup: 'Requirement Follow-up', joining: 'Candidate Joining' };
    var TYPE_LINKS = { followup: 'add-requirement.php?id=', joining: 'add-placement.php?id=' };

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
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            var props = info.event.extendedProps || {};
            var type = props.type || 'event';

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
})();
</script>
