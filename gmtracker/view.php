<?php
require('../../config.php');
require_once('lib.php');
require_once('classes/attendance_manager.php');

// Support both 'id' (course module id) and 'g' (instance id) parameters
if ($id = optional_param('id', 0, PARAM_INT)) {
    $cm = get_coursemodule_from_id('gmtracker', $id, 0, false, MUST_EXIST);
} else if ($g = optional_param('g', 0, PARAM_INT)) {
    $cm = get_coursemodule_from_instance('gmtracker', $g, 0, false, MUST_EXIST);
    $id = $cm->id;
} else {
    throw new moodle_exception('missingparameter', 'error', '', 'id or g');
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$gmtracker = $DB->get_record('gmtracker', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/gmtracker/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($gmtracker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($gmtracker);

// Ensure calendar event exists and is synchronized
gmtracker_set_calendar_event($gmtracker);

echo $OUTPUT->header();

// Initialize attendance manager
$attendance_manager = new gmtracker_attendance_manager($gmtracker, $context, $cm, $course);

// Handle attendance actions
$attendance_manager->handle_actions();

// Get current user attendance
$user_attendance = $attendance_manager->get_user_attendance($USER->id);

/* -------------------- MEETING INFORMATION -------------------- */
echo html_writer::start_div('generalbox mt-3 p-3 border rounded bg-light');
echo html_writer::tag('h4', get_string('meetinginfo', 'gmtracker'), ['class' => 'mt-0 mb-3 text-primary']);

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-6');

$meetinginfo = [];
if (has_capability('mod/gmtracker:addinstance', $context)) {
    $meetinginfo[get_string('host', 'gmtracker')] = html_writer::tag('span', s($gmtracker->hostemail), ['class' => 'text-dark font-weight-bold']);
}

$meetinginfo[get_string('datetime', 'gmtracker')] = html_writer::tag('span', userdate($gmtracker->meetingdate), ['class' => 'text-dark font-weight-bold']);
$meetinginfo[get_string('duration', 'gmtracker')] = html_writer::tag('span', gmtracker_format_minutes($gmtracker->duration), ['class' => 'text-dark font-weight-bold']);

// Add meeting type and location
$meetingtype_display = $gmtracker->meetingtype === 'online' ? get_string('meetingtype_online', 'gmtracker') : get_string('meetingtype_onsite', 'gmtracker');
$meetinginfo[get_string('meetingtype', 'gmtracker')] = html_writer::tag('span', $meetingtype_display, ['class' => 'text-dark font-weight-bold']);

if ($gmtracker->meetingtype === 'onsite' && !empty($gmtracker->location)) {
    $meetinginfo[get_string('location', 'gmtracker')] = html_writer::tag('span', s($gmtracker->location), ['class' => 'text-dark font-weight-bold']);
}

foreach ($meetinginfo as $label => $value) {
    echo html_writer::start_div('d-flex align-items-center mb-2 p-2 bg-white rounded');
    echo html_writer::tag('strong', $label . ': ', ['class' => 'mr-2', 'style' => 'min-width: 120px; color: #495057;']);
    echo $value;
    echo html_writer::end_div();
}

echo html_writer::end_div(); // col-md-6

/* -------------------- ATTENDANCE PANEL -------------------- */
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('border rounded p-3 bg-white h-100');

echo html_writer::tag('h5', get_string('yourattendance', 'gmtracker'), ['class' => 'mt-0 mb-3 border-bottom pb-2']);
echo html_writer::start_div('text-center');

if ($gmtracker->meetingtype === 'online') {
    gmtracker_display_online_interface($user_attendance);
} else {
    gmtracker_display_onsite_interface($gmtracker, $user_attendance, $context, $cm);
}

echo html_writer::end_div(); // text-center
echo html_writer::end_div(); // border rounded
echo html_writer::end_div(); // col-md-6
echo html_writer::end_div(); // row
echo html_writer::end_div(); // generalbox

/* -------------------- QUICK STATS -------------------- */
if (has_capability('mod/gmtracker:addinstance', $context)) {
    gmtracker_display_quick_stats($gmtracker);
}

/* -------------------- ATTENDANCE RECORDS (Teacher) -------------------- */
if (has_capability('mod/gmtracker:addinstance', $context)) {
    gmtracker_display_attendance_records($gmtracker, $cm);
}

// Output JavaScript
gmtracker_output_js($gmtracker, $cm, $context, $user_attendance);

echo $OUTPUT->footer();

/* -------------------- HELPER FUNCTIONS -------------------- */

function gmtracker_format_duration($seconds) {
    if ($seconds < 60) return $seconds . ' sec';
    elseif ($seconds < 3600) {
        $mins = floor($seconds / 60);
        $secs = $seconds % 60;
        return $secs ? "{$mins} min {$secs} sec" : "{$mins} min";
    } else {
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        $str = "{$hours} hr";
        if ($hours > 1) $str .= "s";
        if ($mins > 0) $str .= " {$mins} min";
        if ($secs > 0 && $hours < 1) $str .= " {$secs} sec";
        return $str;
    }
}

function gmtracker_format_minutes($minutes) {
    return gmtracker_format_duration($minutes * 60);
}

function gmtracker_display_online_interface($attendance) {
    // Check if user was marked as incomplete without ever joining
    if ($attendance && $attendance->incomplete && empty($attendance->jointime)) {
        echo html_writer::tag('span', get_string('markedincomplete', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-2']);
        echo html_writer::div(get_string('hostleftyouincomplete', 'gmtracker'), 'text-muted small mb-3');
        return;
    }
    
    if (!$attendance || empty($attendance->jointime)) {
        echo html_writer::tag('span', get_string('notjoined', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-3']);
        echo html_writer::empty_tag('br');
        echo html_writer::tag('button', get_string('joinmeeting', 'gmtracker'), [
            'id' => 'meeting-action-btn',
            'class' => 'btn btn-success btn-lg mt-2',
            'data-action' => 'join'
        ]);
    } elseif ($attendance && empty($attendance->leavetime)) {
        if ($attendance->incomplete) {
            echo html_writer::tag('span', get_string('markedincomplete', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-2']);
            echo html_writer::div(get_string('hostleftyouincomplete', 'gmtracker'), 'text-muted small mb-3');
        } else {
            echo html_writer::tag('span', get_string('inmeeting', 'gmtracker'), ['class' => 'badge badge-success badge-lg p-2 mb-2']);
            echo html_writer::div('<span id="time-in-session">' . gmtracker_format_duration(time() - $attendance->jointime) . '</span>', 'text-muted small mb-3');
            echo html_writer::tag('button', get_string('leavemeeting', 'gmtracker'), [
                'id' => 'meeting-action-btn',
                'class' => 'btn btn-warning btn-lg mt-2',
                'data-action' => 'leave'
            ]);
        }
    } else {
        echo html_writer::tag('span', get_string('meetingcompleted', 'gmtracker'), ['class' => 'badge badge-info badge-lg p-2 mb-2']);
        echo html_writer::empty_tag('br');
        echo html_writer::div(get_string('attendedfor', 'gmtracker', gmtracker_format_duration($attendance->duration)), 'text-primary font-weight-bold mt-2');
    }
}

function gmtracker_display_onsite_interface($gmtracker, $attendance, $context, $cm) {
    // Check if user was marked as incomplete without ever joining
    if ($attendance && $attendance->incomplete && empty($attendance->jointime)) {
        echo html_writer::tag('span', get_string('markedincomplete', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-2']);
        echo html_writer::div(get_string('hostleftyouincomplete', 'gmtracker'), 'text-muted small mb-3');
        return;
    }
    
    if (has_capability('mod/gmtracker:addinstance', $context)) {
        echo html_writer::start_div('teacher-codes mb-4 p-3 border rounded bg-light');
        echo html_writer::tag('h6', get_string('codesgenerated', 'gmtracker'), ['class' => 'text-primary mb-2']);
        echo html_writer::start_div('row text-center');
        
        echo html_writer::start_div('col-md-6');
        echo html_writer::tag('div', get_string('joincode', 'gmtracker'), ['class' => 'font-weight-bold text-muted small mb-1']);
        echo html_writer::tag('div', $gmtracker->joincode, [
            'class' => 'h4 font-weight-bold text-dark',
            'style' => 'letter-spacing: 2px; font-family: monospace;'
        ]);
        echo html_writer::end_div();
        
        echo html_writer::start_div('col-md-6');
        echo html_writer::tag('div', get_string('leavecode', 'gmtracker'), ['class' => 'font-weight-bold text-muted small mb-1']);
        echo html_writer::tag('div', $gmtracker->leavecode, [
            'class' => 'h4 font-weight-bold text-dark',
            'style' => 'letter-spacing: 2px; font-family: monospace;'
        ]);
        echo html_writer::end_div();
        
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    
    if (!$attendance || empty($attendance->jointime)) {
        gmtracker_display_join_form($cm);
    } elseif ($attendance && empty($attendance->leavetime)) {
        if ($attendance->incomplete) {
            echo html_writer::tag('span', get_string('markedincomplete', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-2']);
            echo html_writer::div(get_string('hostleftyouincomplete', 'gmtracker'), 'text-muted small mb-3');
        } else {
            echo html_writer::tag('span', get_string('inmeeting', 'gmtracker'), ['class' => 'badge badge-success badge-lg p-2 mb-2']);
            echo html_writer::div('<span id="time-in-session">' . gmtracker_format_duration(time() - $attendance->jointime) . '</span>', 'text-muted small mb-3');
            gmtracker_display_leave_form($cm);
        }
    } else {
        echo html_writer::tag('span', get_string('meetingcompleted', 'gmtracker'), ['class' => 'badge badge-info badge-lg p-2 mb-2']);
        echo html_writer::empty_tag('br');
        echo html_writer::div(get_string('attendedfor', 'gmtracker', gmtracker_format_duration($attendance->duration)), 'text-primary font-weight-bold mt-2');
    }
}

function gmtracker_display_join_form($cm) {
    echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mt-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('enterjoincode', 'gmtracker'), [
        'for' => 'joincode-input',
        'class' => 'font-weight-bold mb-2'
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'joincode',
        'id' => 'joincode-input',
        'class' => 'form-control form-control-lg text-center',
        'style' => 'font-size: 1.5rem; letter-spacing: 2px; font-family: monospace; max-width: 300px; margin: 0 auto;',
        'maxlength' => '6',
        'required' => 'required'
    ]);
    echo html_writer::end_div();
    echo html_writer::tag('button', get_string('joinmeeting', 'gmtracker'), [
        'type' => 'submit',
        'class' => 'btn btn-success btn-lg btn-block mt-3',
        'style' => 'max-width: 300px; margin: 0 auto;'
    ]);
    echo html_writer::end_tag('form');
}

function gmtracker_display_leave_form($cm) {
    echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mt-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('enterleavecode', 'gmtracker'), [
        'for' => 'leavecode-input',
        'class' => 'font-weight-bold mb-2'
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'leavecode',
        'id' => 'leavecode-input',
        'class' => 'form-control form-control-lg text-center',
        'style' => 'font-size: 1.5rem; letter-spacing: 2px; font-family: monospace; max-width: 300px; margin: 0 auto;',
        'maxlength' => '6',
        'required' => 'required'
    ]);
    echo html_writer::end_div();
    echo html_writer::tag('button', get_string('leavemeeting', 'gmtracker'), [
        'type' => 'submit',
        'class' => 'btn btn-warning btn-lg btn-block mt-3',
        'style' => 'max-width: 300px; margin: 0 auto;'
    ]);
    echo html_writer::end_tag('form');
}

function gmtracker_display_quick_stats($gmtracker) {
    global $DB;
    
    $total_participants = $DB->count_records('gmtracker_attendance', ['gmtrackerid' => $gmtracker->id]);
    $active_participants = $DB->count_records_select('gmtracker_attendance', 'gmtrackerid = ? AND leavetime IS NULL AND incomplete = 0', [$gmtracker->id]);
    $incomplete_participants = $DB->count_records('gmtracker_attendance', ['gmtrackerid' => $gmtracker->id, 'incomplete' => 1]);
    $completed_participants = $DB->count_records_select('gmtracker_attendance', 'gmtrackerid = ? AND leavetime IS NOT NULL', [$gmtracker->id]);

    echo html_writer::start_div('generalbox mt-3 p-3 border rounded');
    echo html_writer::tag('h4', get_string('quickstats', 'gmtracker'), ['class' => 'mt-0 mb-3 text-primary']);
    echo html_writer::start_div('row text-center');
    
    $stats = [
        ['value' => $total_participants, 'id' => 'stat-total', 'label' => 'totalparticipants', 'class' => 'text-primary'],
        ['value' => $active_participants, 'id' => 'stat-active', 'label' => 'activeparticipants', 'class' => 'text-success'],
        ['value' => $completed_participants, 'id' => 'stat-completed', 'label' => 'completedparticipants', 'class' => 'text-info'],
        ['value' => $incomplete_participants, 'id' => 'stat-incomplete', 'label' => 'incompleteparticipants', 'class' => 'text-danger']
    ];
    
    foreach ($stats as $stat) {
        echo html_writer::start_div('col-md-3');
        echo html_writer::tag('div', $stat['value'], ['class' => 'h3 ' . $stat['class'] . ' mb-1', 'id' => $stat['id']]);
        echo html_writer::tag('div', get_string($stat['label'], 'gmtracker'), ['class' => 'text-muted small']);
        echo html_writer::end_div();
    }
    
    echo html_writer::end_div();
    echo html_writer::end_div();
}

function gmtracker_display_attendance_records($gmtracker, $cm) {
    global $DB, $OUTPUT;
    
    echo html_writer::start_div('generalbox mt-4 p-3 border rounded');
    
    // Header with export buttons
    echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
    echo html_writer::tag('h4', get_string('attendancerecords', 'gmtracker'), ['class' => 'mt-0 mb-0 text-primary']);
    echo html_writer::start_div('d-flex gap-2');
    
    // Export buttons
    $exporturl = new moodle_url('/mod/gmtracker/export.php', ['id' => $cm->id, 'export' => 1]);
    echo html_writer::link($exporturl, get_string('exportattendance', 'gmtracker'), ['class' => 'btn btn-secondary mr-2']);
    
    $exportAllUrl = new moodle_url('/mod/gmtracker/export.php', [
        'id' => $cm->id,
        'exportall' => $gmtracker->meetingtype === 'onsite' ? 'onsite' : 'online'
    ]);
    $exportAllLabel = $gmtracker->meetingtype === 'onsite' ? 'exportallonsite' : 'exportallonline';
    echo html_writer::link($exportAllUrl, get_string($exportAllLabel, 'gmtracker'), ['class' => 'btn btn-primary ml-2']);
    
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    // Attendance table
    $attendances = $DB->get_records('gmtracker_attendance', ['gmtrackerid' => $gmtracker->id], 'jointime ASC');
    
    if ($attendances) {
        echo html_writer::start_div('table-container', ['style' => 'max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6;']);
        
        $table = new html_table();
        $table->head = [
            get_string('user'), 
            get_string('jointime', 'gmtracker'), 
            get_string('leavetime', 'gmtracker'), 
            get_string('duration', 'gmtracker'), 
            get_string('status', 'gmtracker')
        ];
        $table->attributes['class'] = 'table table-striped table-bordered table-hover mb-0';
        
        
        $table->colclasses = [
            '', 
            'text-center', 
            'text-center', 
            'text-center', 
            'text-center'
        ];
        $table->id = 'attendance-table';
        
        echo '<style>
            .table-container thead th {
                position: sticky;
                top: 0;
                background: #f8f9fa;
                z-index: 10;
                border-bottom: 2px solid #dee2e6;
            }
            .table-container thead th:first-child {
                text-align: center;
            }
            .table-container tbody td:first-child {
                text-align: left;
            }
            .table-container {
                position: relative;
            }
        </style>';
        
        foreach ($attendances as $a) {
            $user = $DB->get_record('user', ['id' => $a->userid], 'firstname, lastname');
            $jointime = $a->jointime ? userdate($a->jointime) : '-';
            $leavetime = $a->leavetime ? userdate($a->leavetime) : '-';
            $duration = $a->duration ? gmtracker_format_duration($a->duration) : '-';
            
            if ($a->leavetime) {
                $status = html_writer::span(get_string('completed', 'gmtracker'), 'badge badge-success');
            } elseif ($a->incomplete) {
                $status = html_writer::span(get_string('incomplete', 'gmtracker'), 'badge badge-danger');
            } elseif ($a->jointime) {
                $status = html_writer::span(get_string('inprogress', 'gmtracker'), 'badge badge-warning');
            } else {
                $status = html_writer::span(get_string('notstarted', 'gmtracker'), 'badge badge-secondary');
            }
            
            $table->data[] = [fullname($user), $jointime, $leavetime, $duration, $status];
        }
        
        echo html_writer::table($table);
        echo html_writer::end_div();
    } else {
        echo html_writer::div(
            $OUTPUT->notification(get_string('noattendance', 'gmtracker'), 'info'), 
            '', 
            ['id' => 'no-attendance-message']
        );
    }
    
    echo html_writer::end_div();
}

function gmtracker_output_js($gmtracker, $cm, $context, $user_attendance) {
    $is_teacher = has_capability('mod/gmtracker:addinstance', $context) ? 'true' : 'false';
    $user_joined = ($user_attendance && $user_attendance->jointime && !$user_attendance->leavetime && !$user_attendance->incomplete) ? 'true' : 'false';
    $user_left = ($user_attendance && $user_attendance->leavetime) ? 'true' : 'false';
    $user_incomplete = ($user_attendance && $user_attendance->incomplete) ? 'true' : 'false';
    $current_duration = ($user_attendance && $user_attendance->jointime) ? (time() - $user_attendance->jointime) : 0;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const cmid = '<?php echo $cm->id; ?>';
        const isTeacher = <?php echo $is_teacher; ?>;
        const sesskey = '<?php echo sesskey(); ?>';
        
        let currentUserStatus = {
            joined: <?php echo $user_joined; ?>,
            left: <?php echo $user_left; ?>,
            incomplete: <?php echo $user_incomplete; ?>
        };

        initMeetingInterface();

        function initMeetingInterface() {
            // Online meeting button handler
            const btn = document.getElementById('meeting-action-btn');
            if (btn) {
                btn.addEventListener('click', handleMeetingAction);
            }

            // Live timer for online meetings
            const timeLabel = document.getElementById('time-in-session');
            let elapsed = <?php echo $current_duration; ?>;
            let timerInterval = null;
            
            if (timeLabel && currentUserStatus.joined && !currentUserStatus.left && !currentUserStatus.incomplete) {
                startTimer();
            }

            // Auto-uppercase and focus for code inputs
            const codeInputs = document.querySelectorAll('input[name="joincode"], input[name="leavecode"]');
            codeInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
                if (input.name === 'joincode' && !input.value) {
                    input.focus();
                }
            });

            // AJAX Polling for real-time updates
            if (isTeacher || currentUserStatus.joined) {
                startSmoothAjaxPolling();
            }
        }

        function startTimer() {
            if (window.timerInterval) {
                clearInterval(window.timerInterval);
            }
            
            window.timerInterval = setInterval(() => {
                const timeLabel = document.getElementById('time-in-session');
                if (timeLabel) {
                    const currentText = timeLabel.textContent;
                    let seconds = parseDurationToSeconds(currentText);
                    seconds++;
                    timeLabel.textContent = formatDuration(seconds);
                }
            }, 1000);
        }

        function parseDurationToSeconds(durationText) {
            let seconds = 0;
            const hoursMatch = durationText.match(/(\d+)\s*hr/);
            const minsMatch = durationText.match(/(\d+)\s*min/);
            const secsMatch = durationText.match(/(\d+)\s*sec/);
            
            if (hoursMatch) seconds += parseInt(hoursMatch[1]) * 3600;
            if (minsMatch) seconds += parseInt(minsMatch[1]) * 60;
            if (secsMatch) seconds += parseInt(secsMatch[1]);
            
            return seconds;
        }

        function startSmoothAjaxPolling() {
            let isUpdating = false;
            
            setInterval(() => {
                if (isUpdating) return;
                
                isUpdating = true;
                fetch(`ajax.php?id=${cmid}&sesskey=${sesskey}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network error');
                        return response.json();
                    })
                    .then(data => {
                        if (isTeacher) {
                            updateQuickStats(data);
                            updateAttendanceTable(data);
                        }
                        updateUserStatusSmoothly(data.user_status);
                    })
                    .catch(error => console.error('AJAX Error:', error))
                    .finally(() => {
                        isUpdating = false;
                    });
            }, 3000);
        }

        function updateQuickStats(data) {
            const stats = [
                { id: 'stat-total', value: data.total_participants || 0 },
                { id: 'stat-active', value: data.active_participants || 0 },
                { id: 'stat-completed', value: data.completed_participants || 0 },
                { id: 'stat-incomplete', value: data.incomplete_participants || 0 }
            ];
            
            stats.forEach(stat => {
                const element = document.getElementById(stat.id);
                if (element && element.textContent != stat.value) {
                    element.style.transform = 'scale(1.1)';
                    element.style.transition = 'transform 0.3s ease';
                    element.textContent = stat.value;
                    
                    setTimeout(() => {
                        element.style.transform = 'scale(1)';
                    }, 300);
                }
            });
        }

        function updateAttendanceTable(data) {
            if (!isTeacher || !data.attendance_records) {
                return;
            }

            const table = document.getElementById('attendance-table');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            // Show/hide no attendance message
            const noAttendanceMsg = document.getElementById('no-attendance-message');
            if (data.attendance_records.length === 0) {
                if (noAttendanceMsg) noAttendanceMsg.style.display = 'block';
                return;
            } else {
                if (noAttendanceMsg) noAttendanceMsg.style.display = 'none';
            }

            // Check if update is needed
            const currentRows = Array.from(tbody.querySelectorAll('tr'));
            const newRecords = data.attendance_records;
            
            if (!needsTableUpdate(currentRows, newRecords)) {
                return;
            }

            // Smooth update with fade animation
            tbody.style.opacity = '0.7';
            tbody.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                tbody.innerHTML = '';
                newRecords.forEach((record, index) => {
                    addTableRow(tbody, record, index);
                });
                tbody.style.opacity = '1';
            }, 300);
        }

        function needsTableUpdate(currentRows, newRecords) {
            if (currentRows.length !== newRecords.length) {
                return true;
            }
            
            for (let i = 0; i < currentRows.length; i++) {
                const cells = currentRows[i].cells;
                const newRecord = newRecords[i];
                
                // Check user name
                if (cells[0].textContent !== newRecord.user_name) {
                    return true;
                }
                
                // Check status by looking at the badge text
                const statusBadge = cells[4].querySelector('.badge');
                const currentStatus = statusBadge ? statusBadge.textContent : '';
                if (currentStatus !== newRecord.status_text) {
                    return true;
                }
            }
            
            return false;
        }

        function addTableRow(tbody, record, index) {
            const row = tbody.insertRow();
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s ease';
            
            // User name - left aligned
            const cellUser = row.insertCell(0);
            cellUser.textContent = record.user_name;
            cellUser.style.textAlign = 'left'; 
            
            // Join time - centered
            const cellJoin = row.insertCell(1);
            cellJoin.textContent = record.jointime;
            cellJoin.className = 'text-center';
            
            // Leave time - centered
            const cellLeave = row.insertCell(2);
            cellLeave.textContent = record.leavetime;
            cellLeave.className = 'text-center';
            
            // Duration - centered
            const cellDuration = row.insertCell(3);
            cellDuration.textContent = record.duration;
            cellDuration.className = 'text-center';
            
            // Status - centered
            const cellStatus = row.insertCell(4);
            const badgeClass = getBadgeClass(record.status);
            cellStatus.innerHTML = `<span class="badge ${badgeClass}">${record.status_text}</span>`;
            cellStatus.className = 'text-center';
            
            // Fade in new row
            setTimeout(() => {
                row.style.opacity = '1';
            }, index * 50);
        }

        function updateUserStatusSmoothly(userStatus) {
            if (!userStatus) {
                checkAndUpdateStatus({ joined: false, left: false, incomplete: false });
                return;
            }

            const newStatus = {
                joined: !!userStatus.jointime && !userStatus.leavetime && !userStatus.incomplete,
                left: !!userStatus.leavetime,
                incomplete: userStatus.incomplete === true || userStatus.incomplete === 1
            };

            // Update timer display
            if (userStatus.current_duration !== null && userStatus.current_duration !== undefined) {
                const timeLabel = document.getElementById('time-in-session');
                if (timeLabel) {
                    timeLabel.textContent = formatDuration(userStatus.current_duration);
                }
            }

            checkAndUpdateStatus(newStatus);
        }

        function checkAndUpdateStatus(newStatus) {
            const statusChanged = 
                newStatus.joined !== currentUserStatus.joined ||
                newStatus.left !== currentUserStatus.left ||
                newStatus.incomplete !== currentUserStatus.incomplete;

            if (statusChanged) {
                currentUserStatus = newStatus;
                updateAttendanceUI();
            }
        }

        function updateAttendanceUI() {
            const attendancePanel = document.querySelector('.col-md-6 .border.rounded.p-3.bg-white');
            if (!attendancePanel) return;

            const newHTML = generateAttendanceHTML();
            
            if (attendancePanel.innerHTML !== newHTML) {
                attendancePanel.innerHTML = newHTML;
                reattachEventListeners();
            }
        }

        function generateAttendanceHTML() {
            // Check incomplete FIRST
            if (currentUserStatus.incomplete) {
                return `
                    <h5 class="mt-0 mb-3 border-bottom pb-2">${getString('yourattendance')}</h5>
                    <div class="text-center">
                        <span class="badge badge-danger badge-lg p-2 mb-2">${getString('markedincomplete')}</span>
                        <div class="text-muted small mb-3">${getString('hostleftyouincomplete')}</div>
                    </div>
                `;
            } 
            // Check if user has left (completed)
            else if (currentUserStatus.left) {
                return `
                    <h5 class="mt-0 mb-3 border-bottom pb-2">${getString('yourattendance')}</h5>
                    <div class="text-center">
                        <span class="badge badge-info badge-lg p-2 mb-2">${getString('meetingcompleted')}</span>
                        <br>
                        <div class="text-primary font-weight-bold mt-2">
                            ${getString('attendedfor').replace('{$a}', getCurrentDuration())}
                        </div>
                    </div>
                `;
            }
            // Check if user is currently joined
            else if (currentUserStatus.joined && !currentUserStatus.left && !currentUserStatus.incomplete) {
                return `
                    <h5 class="mt-0 mb-3 border-bottom pb-2">${getString('yourattendance')}</h5>
                    <div class="text-center">
                        <span class="badge badge-success badge-lg p-2 mb-2">${getString('inmeeting')}</span>
                        <div class="text-muted small mb-3">
                            <span id="time-in-session">${getCurrentDuration()}</span>
                        </div>
                        <button id="meeting-action-btn" class="btn btn-warning btn-lg mt-2" data-action="leave">
                            ${getString('leavemeeting')}
                        </button>
                    </div>
                `;
            }
            // User hasn't joined yet
            else {
                return `
                    <h5 class="mt-0 mb-3 border-bottom pb-2">${getString('yourattendance')}</h5>
                    <div class="text-center">
                        <span class="badge badge-danger badge-lg p-2 mb-3">${getString('notjoined')}</span>
                        <br>
                        <button id="meeting-action-btn" class="btn btn-success btn-lg mt-2" data-action="join">
                            ${getString('joinmeeting')}
                        </button>
                    </div>
                `;
            }
        }

        function getCurrentDuration() {
            const timeLabel = document.getElementById('time-in-session');
            return timeLabel ? timeLabel.textContent : '0 sec';
        }

        function reattachEventListeners() {
            const actionBtn = document.getElementById('meeting-action-btn');
            if (actionBtn) {
                actionBtn.addEventListener('click', handleMeetingAction);
            }
            
            if (currentUserStatus.joined && !currentUserStatus.left && !currentUserStatus.incomplete) {
                startTimer();
            }
        }

        function handleMeetingAction(e) {
            const action = e.target.dataset.action;
            if (action === 'join') {
                window.open('<?php echo s($gmtracker->gmeetlink); ?>', '_blank');
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = action;
            actionInput.value = '1';
            form.appendChild(actionInput);
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = cmid;
            form.appendChild(idInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        function getBadgeClass(status) {
            const classes = {
                'completed': 'badge-success',
                'inprogress': 'badge-warning', 
                'incomplete': 'badge-danger',
                'default': 'badge-secondary'
            };
            return classes[status] || classes.default;
        }

        function formatDuration(seconds) {
            if (seconds < 60) return seconds + ' sec';
            if (seconds < 3600) {
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                return s ? `${m} min ${s} sec` : `${m} min`;
            }
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            let str = `${h} hr`;
            if (h > 1) str += 's';
            if (m > 0) str += ` ${m} min`;
            if (s > 0 && h < 1) str += ` ${s} sec`;
            return str;
        }

        // Helper function to get language strings
        function getString(key) {
            const strings = {
                'yourattendance': '<?php echo get_string("yourattendance", "gmtracker"); ?>',
                'notjoined': '<?php echo get_string("notjoined", "gmtracker"); ?>',
                'joinmeeting': '<?php echo get_string("joinmeeting", "gmtracker"); ?>',
                'inmeeting': '<?php echo get_string("inmeeting", "gmtracker"); ?>',
                'leavemeeting': '<?php echo get_string("leavemeeting", "gmtracker"); ?>',
                'markedincomplete': '<?php echo get_string("markedincomplete", "gmtracker"); ?>',
                'hostleftyouincomplete': '<?php echo get_string("hostleftyouincomplete", "gmtracker"); ?>',
                'meetingcompleted': '<?php echo get_string("meetingcompleted", "gmtracker"); ?>',
                'attendedfor': '<?php echo get_string("attendedfor", "gmtracker"); ?>'
            };
            return strings[key] || key;
        }
    });
    </script>
    <?php
}