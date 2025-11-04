<?php
require('../../config.php');
require_once('lib.php');

// Support both 'id' (course module id) and 'g' (instance id) parameters
if ($id = optional_param('id', 0, PARAM_INT)) {
    $cm = get_coursemodule_from_id('gmtracker', $id, 0, false, MUST_EXIST);
} else if ($g = optional_param('g', 0, PARAM_INT)) {
    $cm = get_coursemodule_from_instance('gmtracker', $g, 0, false, MUST_EXIST);
    $id = $cm->id; // Make sure $id is set for use in redirects
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

/* -------------------- HELPERS -------------------- */
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

$userid = $USER->id;
$attendance = $DB->get_record('gmtracker_attendance', ['gmtrackerid' => $gmtracker->id, 'userid' => $userid]);

// Handle attendance actions
if ($gmtracker->meetingtype === 'online') {
    // Online meeting logic (existing code)
    if (optional_param('join', false, PARAM_BOOL)) {
        if (!$attendance) {
            $attendance = (object)[
                'gmtrackerid' => $gmtracker->id,
                'userid' => $userid,
                'jointime' => time()
            ];
            $DB->insert_record('gmtracker_attendance', $attendance);
        }
        redirect(new moodle_url('/mod/gmtracker/view.php', ['id' => $cm->id]));
    }

    if (optional_param('leave', false, PARAM_BOOL) && $attendance && empty($attendance->leavetime)) {
        // Check if host has already left
        if (gmtracker_has_host_left($gmtracker)) {
            echo $OUTPUT->notification(get_string('hostalreadyleft', 'gmtracker'), 'notifyerror');
        } else {
            $attendance->leavetime = time();
            $attendance->duration = $attendance->leavetime - $attendance->jointime;
            $DB->update_record('gmtracker_attendance', $attendance);

            $event = \mod_gmtracker\event\meeting_left::create([
                'objectid' => $gmtracker->id,
                'context' => $context,
                'courseid' => $course->id,
                'userid' => $USER->id,
                'other' => [
                    'userduration' => $attendance->duration,
                    'meetingduration' => $gmtracker->duration * 60
                ],
            ]);
            $event->trigger();

            // Check if leaving user is the host
            if ($USER->email === $gmtracker->hostemail) {
                $incomplete_count = gmtracker_mark_incomplete_users($gmtracker);
                if ($incomplete_count > 0) {
                    echo $OUTPUT->notification(get_string('hostleftmarkedincomplete', 'gmtracker', $incomplete_count), 'notifysuccess');
                }
            }

            echo $OUTPUT->notification(get_string('leavesuccess', 'gmtracker'), 'notifysuccess');
        }
    }
} else {
    // Onsite meeting logic - handle code submissions
    $joincode = optional_param('joincode', '', PARAM_ALPHANUM);
    $leavecode = optional_param('leavecode', '', PARAM_ALPHANUM);
    
    if (!empty($joincode)) {
        $result = gmtracker_handle_join_code($gmtracker, $userid, $joincode);
        if ($result === 'success') {
            echo $OUTPUT->notification(get_string('successfullyjoined', 'gmtracker'), 'notifysuccess');
            redirect(new moodle_url('/mod/gmtracker/view.php', ['id' => $cm->id]));
        } elseif ($result === 'invalidcode') {
            echo $OUTPUT->notification(get_string('invalidcode', 'gmtracker'), 'notifyerror');
        } elseif ($result === 'alreadyjoined') {
            echo $OUTPUT->notification(get_string('alreadyjoined', 'gmtracker'), 'notifyerror');
        }
    }
    
    if (!empty($leavecode)) {
        // Check if host has already left
        if (gmtracker_has_host_left($gmtracker)) {
            echo $OUTPUT->notification(get_string('hostalreadyleft', 'gmtracker'), 'notifyerror');
        } else {
            $result = gmtracker_handle_leave_code($gmtracker, $userid, $leavecode);
            if ($result === 'success') {
                // Check if leaving user is the host
                if ($USER->email === $gmtracker->hostemail) {
                    $incomplete_count = gmtracker_mark_incomplete_users($gmtracker);
                    if ($incomplete_count > 0) {
                        echo $OUTPUT->notification(get_string('hostleftmarkedincomplete', 'gmtracker', $incomplete_count), 'notifysuccess');
                    }
                }
                
                echo $OUTPUT->notification(get_string('successfullyleft', 'gmtracker'), 'notifysuccess');
                redirect(new moodle_url('/mod/gmtracker/view.php', ['id' => $cm->id]));
            } elseif ($result === 'invalidcode') {
                echo $OUTPUT->notification(get_string('invalidcode', 'gmtracker'), 'notifyerror');
            } elseif ($result === 'notjoinedyet') {
                echo $OUTPUT->notification(get_string('notjoinedyet', 'gmtracker'), 'notifyerror');
            }
        }
    }
}

/* -------------------- ATTENDANCE DISPLAY -------------------- */
echo html_writer::tag('h5', get_string('yourattendance', 'gmtracker'), ['class' => 'mt-0 mb-3 border-bottom pb-2']);
echo html_writer::start_div('text-center');

if ($gmtracker->meetingtype === 'online') {
    // ONLINE MEETING INTERFACE
    if (!$attendance || empty($attendance->jointime)) {
        // Not joined
        echo html_writer::tag('span', get_string('notjoined', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-3']);
        echo html_writer::empty_tag('br');
        echo html_writer::tag('button', get_string('joinmeeting', 'gmtracker'), [
            'id' => 'meeting-action-btn',
            'class' => 'btn btn-success btn-lg mt-2',
            'data-action' => 'join'
        ]);

    } elseif ($attendance && empty($attendance->leavetime)) {
        // Check if user is marked incomplete
        if ($attendance->incomplete) {
            // User was marked incomplete by host
            echo html_writer::tag('span', get_string('markedincomplete', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-2']);
            echo html_writer::div(get_string('hostleftyouincomplete', 'gmtracker'), 'text-muted small mb-3');
            // Don't show leave button for incomplete users
        } else {
            // In meeting - normal state
            echo html_writer::tag('span', get_string('inmeeting', 'gmtracker'), ['class' => 'badge badge-success badge-lg p-2 mb-2']);
            echo html_writer::div('<span id="time-in-session">' . gmtracker_format_duration(time() - $attendance->jointime) . '</span>', 'text-muted small mb-3');
            echo html_writer::tag('button', get_string('leavemeeting', 'gmtracker'), [
                'id' => 'meeting-action-btn',
                'class' => 'btn btn-warning btn-lg mt-2',
                'data-action' => 'leave'
            ]);
        }

    } else {
        // Meeting completed
        echo html_writer::tag('span', get_string('meetingcompleted', 'gmtracker'), ['class' => 'badge badge-info badge-lg p-2 mb-2']);
        echo html_writer::empty_tag('br');
        echo html_writer::div(get_string('attendedfor', 'gmtracker', gmtracker_format_duration($attendance->duration)), 'text-primary font-weight-bold mt-2');
    }
} else {
    // ONSITE MEETING INTERFACE - SAME FOR ALL USERS (Teachers and Students)
    
    // Show join/leave codes to teachers/admins regardless of their attendance status
    $showTeacherCodes = has_capability('mod/gmtracker:addinstance', $context);
    
    if ($showTeacherCodes) {
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
        // Not joined - Show join code form
        echo html_writer::tag('span', get_string('notjoined', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-3']);
        echo html_writer::empty_tag('br');
        
        // Join code input form for everyone
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'class' => 'mt-3'
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $cm->id
        ]);
        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', get_string('enterjoincode', 'gmtracker'), [
            'for' => 'joincode-input',
            'class' => 'font-weight-bold mb-2'
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'joincode',
            'id' => 'joincode-input',
            'placeholder' => get_string('enterjoincode', 'gmtracker'),
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
        
    } elseif ($attendance && empty($attendance->leavetime)) {
        // Check if user is marked incomplete
        if ($attendance->incomplete) {
            // User was marked incomplete by host
            echo html_writer::tag('span', get_string('markedincomplete', 'gmtracker'), ['class' => 'badge badge-danger badge-lg p-2 mb-2']);
            echo html_writer::div(get_string('hostleftyouincomplete', 'gmtracker'), 'text-muted small mb-3');
            // Don't show leave form for incomplete users
        } else {
            // In meeting - Show leave code form
            echo html_writer::tag('span', get_string('inmeeting', 'gmtracker'), ['class' => 'badge badge-success badge-lg p-2 mb-2']);
            echo html_writer::div('<span id="time-in-session">' . gmtracker_format_duration(time() - $attendance->jointime) . '</span>', 'text-muted small mb-3');
            
            // Leave code input form for everyone
            echo html_writer::start_tag('form', [
                'method' => 'post',
                'class' => 'mt-3'
            ]);
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'id',
                'value' => $cm->id
            ]);
            echo html_writer::start_div('form-group');
            echo html_writer::tag('label', get_string('enterleavecode', 'gmtracker'), [
                'for' => 'leavecode-input',
                'class' => 'font-weight-bold mb-2'
            ]);
            echo html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'leavecode',
                'id' => 'leavecode-input',
                'placeholder' => get_string('enterleavecode', 'gmtracker'),
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
        
    } else {
        // Meeting completed
        echo html_writer::tag('span', get_string('meetingcompleted', 'gmtracker'), ['class' => 'badge badge-info badge-lg p-2 mb-2']);
        echo html_writer::empty_tag('br');
        echo html_writer::div(get_string('attendedfor', 'gmtracker', gmtracker_format_duration($attendance->duration)), 'text-primary font-weight-bold mt-2');
    }
}

echo html_writer::end_div(); // text-center
echo html_writer::end_div(); // border rounded
echo html_writer::end_div(); // col-md-6
echo html_writer::end_div(); // row
echo html_writer::end_div(); // generalbox

/* -------------------- QUICK STATS -------------------- */
if (has_capability('mod/gmtracker:addinstance', $context)) {
    $total_participants = $DB->count_records('gmtracker_attendance', ['gmtrackerid' => $gmtracker->id]);
    $active_participants = $DB->count_records_select('gmtracker_attendance', 'gmtrackerid = ? AND leavetime IS NULL AND incomplete = 0', [$gmtracker->id]);
    $incomplete_participants = $DB->count_records('gmtracker_attendance', ['gmtrackerid' => $gmtracker->id, 'incomplete' => 1]);
    $completed_participants = $DB->count_records_select('gmtracker_attendance', 'gmtrackerid = ? AND leavetime IS NOT NULL', [$gmtracker->id]);

    if ($total_participants > 0) {
        echo html_writer::start_div('generalbox mt-3 p-3 border rounded');
        echo html_writer::tag('h4', get_string('quickstats', 'gmtracker'), ['class' => 'mt-0 mb-3 text-primary']);
        echo html_writer::start_div('row text-center');
        
        echo html_writer::start_div('col-md-3');
        echo html_writer::tag('div', $total_participants, ['class' => 'h3 text-primary mb-1']);
        echo html_writer::tag('div', get_string('totalparticipants', 'gmtracker'), ['class' => 'text-muted small']);
        echo html_writer::end_div();
        
        echo html_writer::start_div('col-md-3');
        echo html_writer::tag('div', $active_participants, ['class' => 'h3 text-success mb-1']);
        echo html_writer::tag('div', get_string('activeparticipants', 'gmtracker'), ['class' => 'text-muted small']);
        echo html_writer::end_div();
        
        echo html_writer::start_div('col-md-3');
        echo html_writer::tag('div', $completed_participants, ['class' => 'h3 text-info mb-1']);
        echo html_writer::tag('div', get_string('completedparticipants', 'gmtracker'), ['class' => 'text-muted small']);
        echo html_writer::end_div();
        
        echo html_writer::start_div('col-md-3');
        echo html_writer::tag('div', $incomplete_participants, ['class' => 'h3 text-danger mb-1']);
        echo html_writer::tag('div', get_string('incompleteparticipants', 'gmtracker'), ['class' => 'text-muted small']);
        echo html_writer::end_div();
        
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

/* -------------------- ATTENDANCE RECORDS (Teacher) -------------------- */
if (has_capability('mod/gmtracker:addinstance', $context)) {
    echo html_writer::start_div('generalbox mt-4 p-3 border rounded');
    
    // Add export button header
    echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
    echo html_writer::tag('h4', get_string('attendancerecords', 'gmtracker'), ['class' => 'mt-0 mb-0 text-primary']);
    
    // Export buttons container - remove btn-group and use regular div with spacing
    echo html_writer::start_div('d-flex gap-2'); // Using Bootstrap 5 gap utility for spacing
    
    // Current meeting export button (always shown)
    $exporturl = new moodle_url('/mod/gmtracker/export.php', [
        'id' => $cm->id,
        'export' => 1
    ]);
    echo html_writer::link($exporturl, get_string('exportattendance', 'gmtracker'), [
        'class' => 'btn btn-secondary mr-2',
        'title' => get_string('exportattendance', 'gmtracker')
    ]);
    
    // Dynamic second button based on meeting type
    if ($gmtracker->meetingtype === 'onsite') {
        // All onsite trainings export button - using distinctive color
        $exportAllUrl = new moodle_url('/mod/gmtracker/export.php', [
            'id' => $cm->id,
            'exportall' => 'onsite'
        ]);
        echo html_writer::link($exportAllUrl, get_string('exportallonsite', 'gmtracker'), [
            'class' => 'btn btn-primary ml-2',
            'title' => get_string('exportallonsite', 'gmtracker',)
        ]);
    } else {
        // All online trainings export button - using distinctive color
        $exportAllUrl = new moodle_url('/mod/gmtracker/export.php', [
            'id' => $cm->id,
            'exportall' => 'online'
        ]);
        echo html_writer::link($exportAllUrl, get_string('exportallonline', 'gmtracker'), [
            'class' => 'btn btn-primary ml-2',
            'title' => get_string('exportallonline', 'gmtracker')
        ]);
    }
    
    echo html_writer::end_div(); // d-flex 
    echo html_writer::end_div(); // d-flex
    
    // Get attendance records ordered by join time (oldest first)
    $attendances = $DB->get_records('gmtracker_attendance', ['gmtrackerid' => $gmtracker->id], 'jointime ASC');
    
    if ($attendances) {
        // Scrollable table container with fixed header
        echo html_writer::start_div('table-container', ['style' => 'max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6;']);
        
        $table = new html_table();
        $table->head = [get_string('user'), get_string('jointime', 'gmtracker'), get_string('leavetime', 'gmtracker'), get_string('duration', 'gmtracker'), get_string('status', 'gmtracker')];
        $table->attributes['class'] = 'table table-striped table-bordered table-hover mb-0';
        $table->colclasses = ['', 'text-center', 'text-center', 'text-center', 'text-center'];
        
        // Add sticky header styles
        echo '<style>
            .table-container thead th {
                position: sticky;
                top: 0;
                background: #f8f9fa;
                z-index: 10;
                border-bottom: 2px solid #dee2e6;
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
            
            // Updated status logic with incomplete
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
        echo html_writer::end_div(); // table-container
    } else {
        echo $OUTPUT->notification(get_string('noattendance', 'gmtracker'), 'info');
    }
    echo html_writer::end_div();
}
?>

<!-- -------------------- JS SECTION -------------------- -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Online meeting button handler
    const btn = document.getElementById('meeting-action-btn');
    if (btn) {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            if (action === 'join') {
                // Open GMeet in new tab first
                window.open('<?php echo s($gmtracker->gmeetlink); ?>', '_blank');
            }

            // Create a form and submit it instead of using fetch
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
            idInput.value = '<?php echo $cm->id; ?>';
            form.appendChild(idInput);
            
            document.body.appendChild(form);
            form.submit();
        });
    }

    // Live timer for online meetings
    const timeLabel = document.getElementById('time-in-session');
    if (timeLabel) {
        let elapsed = <?php echo time() - ($attendance->jointime ?? time()); ?>;
        setInterval(() => {
            elapsed++;
            timeLabel.textContent = formatDuration(elapsed);
        }, 1000);
    }

    // Auto-uppercase and focus for code inputs
    const codeInputs = document.querySelectorAll('input[name="joincode"], input[name="leavecode"]');
    codeInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        // Focus on the first code input
        if (input.name === 'joincode' && !input.value) {
            input.focus();
        }
    });

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
});
</script>

<?php
echo $OUTPUT->footer();