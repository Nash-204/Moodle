<?php
class gmtracker_attendance_manager {
    private $gmtracker;
    private $context;
    private $cm;
    private $course;
    
    public function __construct($gmtracker, $context, $cm, $course) {
        $this->gmtracker = $gmtracker;
        $this->context = $context;
        $this->cm = $cm;
        $this->course = $course;
    }
    
    public function handle_actions() {
        global $USER, $DB, $OUTPUT;
        
        $attendance = $this->get_user_attendance($USER->id);
        
        if ($this->gmtracker->meetingtype === 'online') {
            $this->handle_online_actions($attendance);
        } else {
            $this->handle_onsite_actions($attendance);
        }
    }
    
    private function handle_online_actions($attendance) {
        global $USER, $DB, $OUTPUT;
        
        if (optional_param('join', false, PARAM_BOOL)) {
            if (!$attendance) {
                $attendance = (object)[
                    'gmtrackerid' => $this->gmtracker->id,
                    'userid' => $USER->id,
                    'jointime' => time()
                ];
                $DB->insert_record('gmtracker_attendance', $attendance);
            }
            redirect(new moodle_url('/mod/gmtracker/view.php', ['id' => $this->cm->id]));
        }

        if (optional_param('leave', false, PARAM_BOOL) && $attendance && empty($attendance->leavetime)) {
            if (gmtracker_has_host_left($this->gmtracker)) {
                echo $OUTPUT->notification(get_string('hostalreadyleft', 'gmtracker'), 'notifyerror');
            } else {
                $this->process_leave($attendance);
            }
        }
    }
    
    private function handle_onsite_actions($attendance) {
        global $USER, $OUTPUT;
        
        $joincode = optional_param('joincode', '', PARAM_ALPHANUM);
        $leavecode = optional_param('leavecode', '', PARAM_ALPHANUM);
        
        if (!empty($joincode)) {
            $result = gmtracker_handle_join_code($this->gmtracker, $USER->id, $joincode);
            $this->handle_code_result($result, 'join');
        }
        
        if (!empty($leavecode)) {
            if (gmtracker_has_host_left($this->gmtracker)) {
                echo $OUTPUT->notification(get_string('hostalreadyleft', 'gmtracker'), 'notifyerror');
            } else {
                $result = gmtracker_handle_leave_code($this->gmtracker, $USER->id, $leavecode);
                if ($result === 'success') {
                    $user_attendance = $this->get_user_attendance($USER->id);
                    if ($user_attendance) {
                        $this->trigger_leave_event($user_attendance->duration);
                    }
                    
                    if ($USER->email === $this->gmtracker->hostemail) {
                        $incomplete_count = gmtracker_mark_incomplete_users($this->gmtracker);
                        if ($incomplete_count > 0) {
                            echo $OUTPUT->notification(
                                get_string('hostleftmarkedincomplete', 'gmtracker', $incomplete_count), 
                                'notifysuccess'
                            );
                        }
                    }
                    
                    echo $OUTPUT->notification(get_string('successfullyleft', 'gmtracker'), 'notifysuccess');
                    redirect(new moodle_url('/mod/gmtracker/view.php', ['id' => $this->cm->id]));
                } else {
                    $this->handle_code_result($result, 'leave');
                }
            }
        }
    }
    
    private function process_leave($attendance) {
        global $USER, $DB, $OUTPUT;
        
        $attendance->leavetime = time();
        $attendance->duration = $attendance->leavetime - $attendance->jointime;
        $DB->update_record('gmtracker_attendance', $attendance);

        $this->trigger_leave_event($attendance->duration);

        if ($USER->email === $this->gmtracker->hostemail) {
            $incomplete_count = gmtracker_mark_incomplete_users($this->gmtracker);
            if ($incomplete_count > 0) {
                echo $OUTPUT->notification(
                    get_string('hostleftmarkedincomplete', 'gmtracker', $incomplete_count), 
                    'notifysuccess'
                );
            }
        }

        echo $OUTPUT->notification(get_string('leavesuccess', 'gmtracker'), 'notifysuccess');
    }
    
    private function trigger_leave_event($duration) {
        $event = \mod_gmtracker\event\meeting_left::create([
            'objectid' => $this->gmtracker->id,
            'context' => $this->context,
            'courseid' => $this->course->id,
            'userid' => $USER->id,
            'other' => [
                'userduration' => $duration,
                'meetingduration' => $this->gmtracker->duration * 60
            ],
        ]);
        $event->trigger();
    }
    
    private function handle_code_result($result, $type) {
        global $OUTPUT;
        
        $messages = [
            'success' => $type === 'join' ? 'successfullyjoined' : 'successfullyleft',
            'invalidcode' => 'invalidcode',
            'alreadyjoined' => 'alreadyjoined',
            'notjoinedyet' => 'notjoinedyet'
        ];
        
        if (isset($messages[$result])) {
            $notification_type = $result === 'success' ? 'notifysuccess' : 'notifyerror';
            echo $OUTPUT->notification(get_string($messages[$result], 'gmtracker'), $notification_type);
            
            if ($result === 'success') {
                redirect(new moodle_url('/mod/gmtracker/view.php', ['id' => $this->cm->id]));
            }
        }
    }
    
    public function get_user_attendance($userid) {
        global $DB;
        return $DB->get_record('gmtracker_attendance', [
            'gmtrackerid' => $this->gmtracker->id, 
            'userid' => $userid
        ]);
    }
    
    public function get_ajax_data() {
        global $DB, $USER;
        
        $data = [];
        
        // Quick stats for teachers
        if (has_capability('mod/gmtracker:addinstance', $this->context)) {
            $data['total_participants'] = $DB->count_records('gmtracker_attendance', ['gmtrackerid' => $this->gmtracker->id]);
            $data['active_participants'] = $DB->count_records_select(
                'gmtracker_attendance', 
                'gmtrackerid = ? AND leavetime IS NULL AND incomplete = 0', 
                [$this->gmtracker->id]
            );
            $data['incomplete_participants'] = $DB->count_records(
                'gmtracker_attendance', 
                ['gmtrackerid' => $this->gmtracker->id, 'incomplete' => 1]
            );
            $data['completed_participants'] = $DB->count_records_select(
                'gmtracker_attendance', 
                'gmtrackerid = ? AND leavetime IS NOT NULL', 
                [$this->gmtracker->id]
            );
        }
        
        // Current user attendance status
        $user_attendance = $this->get_user_attendance($USER->id);
        if ($user_attendance) {
            $data['user_status'] = [
                'jointime' => (int)$user_attendance->jointime,
                'leavetime' => $user_attendance->leavetime ? (int)$user_attendance->leavetime : null,
                'duration' => $user_attendance->duration ? (int)$user_attendance->duration : null,
                'incomplete' => (bool)$user_attendance->incomplete,
                'current_duration' => $user_attendance->jointime && !$user_attendance->leavetime && !$user_attendance->incomplete ? 
                    time() - $user_attendance->jointime : null
            ];
        } else {
            $data['user_status'] = null;
        }
        
        // Attendance records for teachers
        if (has_capability('mod/gmtracker:addinstance', $this->context)) {
            $attendances = $DB->get_records('gmtracker_attendance', ['gmtrackerid' => $this->gmtracker->id], 'jointime ASC');
            $data['attendance_records'] = [];
            
            foreach ($attendances as $a) {
                $user = $DB->get_record('user', ['id' => $a->userid], 'firstname, lastname, id');
                
                if (!$user) continue;
                
                if ($a->leavetime) {
                    $status = 'completed';
                } elseif ($a->incomplete) {
                    $status = 'incomplete';
                } elseif ($a->jointime) {
                    $status = 'inprogress';
                } else {
                    $status = 'notstarted';
                }
                
                $data['attendance_records'][] = [
                    'user_name' => fullname($user),
                    'jointime' => $a->jointime ? userdate($a->jointime) : '-', 
                    'leavetime' => $a->leavetime ? userdate($a->leavetime) : '-', 
                    'duration' => $a->duration ? $this->format_duration($a->duration) : '-',
                    'status' => $status,
                    'status_text' => get_string($status, 'gmtracker')
                ];
            }
        } else {
            $data['attendance_records'] = null;
        }
        
        return $data;
    }
    
    private function format_duration($seconds) {
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
}