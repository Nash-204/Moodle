<?php
// This file contains the CSV export functionality for GMTracker

require_once('../../config.php');
require_once('lib.php');

// Check required parameters
$id = required_param('id', PARAM_INT);
$export = optional_param('export', 0, PARAM_BOOL);
$exportall = optional_param('exportall', '', PARAM_ALPHA);

// Get course module and course
$cm = get_coursemodule_from_id('gmtracker', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$gmtracker = $DB->get_record('gmtracker', array('id' => $cm->instance), '*', MUST_EXIST);

// Require login and capabilities
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/gmtracker:addinstance', $context);

/**
 * Format duration for display
 */
function gmtracker_export_format_duration($seconds) {
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

/**
 * Format minutes for display
 */
function gmtracker_export_format_minutes($minutes) {
    return gmtracker_export_format_duration($minutes * 60);
}

/**
 * Export ALL onsite trainings across the system
 */
function gmtracker_export_all_onsite_trainings($current_cm) {
    global $DB, $CFG;
    
    $filename = clean_filename("all_onsite_trainings_" . userdate(time(), '%Y%m%d-%H%M'));
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
    
    // Header
    fwrite($output, "ALL ONSITE TRAININGS - COMPLETE REPORT\n\n");
    fwrite($output, "Generated," . userdate(time(), '%Y-%m-%d %H:%M:%S') . "\n");
    fwrite($output, "Report Type,All Onsite Trainings\n");
    fwrite($output, "Generated From," . $current_cm->name . "\n\n");
    
    // Get all onsite trainings
    $onsite_trainings = $DB->get_records('gmtracker', ['meetingtype' => 'onsite'], 'meetingdate ASC');
    
    if (!$onsite_trainings) {
        fwrite($output, "No onsite trainings found in the system.\n");
        fclose($output);
        exit;
    }
    
    foreach ($onsite_trainings as $training) {
        // Get course information
        $course = $DB->get_record('course', ['id' => $training->course], 'fullname, shortname');
        $coursename = $course ? $course->fullname : 'Unknown Course';
        
        // Get course module
        $training_cm = $DB->get_record('course_modules', [
            'instance' => $training->id,
            'module' => $DB->get_field('modules', 'id', ['name' => 'gmtracker'])
        ]);
        
        // Training header
        fwrite($output, "TRAINING: " . $training->name . "\n");
        fwrite($output, "Course," . $coursename . "\n");
        fwrite($output, "Date," . userdate($training->meetingdate, '%Y-%m-%d') . "\n");
        fwrite($output, "Time," . userdate($training->meetingdate, '%H:%M') . "\n");
        fwrite($output, "Duration," . gmtracker_export_format_minutes($training->duration) . "\n");
        fwrite($output, "Location," . (!empty($training->location) ? $training->location : 'Not specified') . "\n");
        fwrite($output, "Host," . (!empty($training->hostemail) ? $training->hostemail : 'Not specified') . "\n");
        
        // Get attendance for this training
        $sql = "SELECT a.*, u.firstname, u.lastname, u.email
                FROM {gmtracker_attendance} a
                JOIN {user} u ON u.id = a.userid
                WHERE a.gmtrackerid = ?
                ORDER BY a.jointime ASC";
        $attendances = $DB->get_records_sql($sql, [$training->id]);
        
        fwrite($output, "Total Participants," . count($attendances) . "\n");
        
        if ($attendances) {
            fwrite($output, "\nATTENDANCE:\n");
            $headers = ['First Name', 'Last Name', 'Email', 'Join Time', 'Leave Time', 'Duration', 'Status'];
            fputcsv($output, $headers);
            
            foreach ($attendances as $a) {
                $jointime = $a->jointime ? userdate($a->jointime, '%Y-%m-%d %H:%M:%S') : '';
                $leavetime = $a->leavetime ? userdate($a->leavetime, '%Y-%m-%d %H:%M:%S') : '';
                $duration = $a->duration ? gmtracker_export_format_duration($a->duration) : '';
                
                if ($a->leavetime) {
                    $status = 'Completed';
                } elseif ($a->incomplete) {
                    $status = 'Incomplete';
                } elseif ($a->jointime) {
                    $status = 'In Progress';
                } else {
                    $status = 'Not Started';
                }
                
                $row = [$a->firstname, $a->lastname, $a->email, $jointime, $leavetime, $duration, $status];
                fputcsv($output, $row);
            }
        } else {
            fwrite($output, "ATTENDANCE: No attendance records\n");
        }
        
        fwrite($output, "\n" . str_repeat("-", 80) . "\n\n");
    }
    
    fclose($output);
    exit;
}

/**
 * Export ALL online trainings across the system
 */
function gmtracker_export_all_online_trainings($current_cm) {
    global $DB, $CFG;
    
    $filename = clean_filename("all_online_trainings_" . userdate(time(), '%Y%m%d-%H%M'));
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
    
    // Header
    fwrite($output, "ALL ONLINE TRAININGS - COMPLETE REPORT\n\n");
    fwrite($output, "Generated," . userdate(time(), '%Y-%m-%d %H:%M:%S') . "\n");
    fwrite($output, "Report Type,All Online Trainings\n");
    fwrite($output, "Generated From," . $current_cm->name . "\n\n");
    
    // Get all online trainings
    $online_trainings = $DB->get_records('gmtracker', ['meetingtype' => 'online'], 'meetingdate ASC');
    
    if (!$online_trainings) {
        fwrite($output, "No online trainings found in the system.\n");
        fclose($output);
        exit;
    }
    
    foreach ($online_trainings as $training) {
        // Get course information
        $course = $DB->get_record('course', ['id' => $training->course], 'fullname, shortname');
        $coursename = $course ? $course->fullname : 'Unknown Course';
        
        // Training header
        fwrite($output, "TRAINING: " . $training->name . "\n");
        fwrite($output, "Course," . $coursename . "\n");
        fwrite($output, "Date," . userdate($training->meetingdate, '%Y-%m-%d') . "\n");
        fwrite($output, "Time," . userdate($training->meetingdate, '%H:%M') . "\n");
        fwrite($output, "Duration," . gmtracker_export_format_minutes($training->duration) . "\n");
        fwrite($output, "Host," . (!empty($training->hostemail) ? $training->hostemail : 'Not specified') . "\n");
        
        // Get attendance for this training
        $sql = "SELECT a.*, u.firstname, u.lastname, u.email
                FROM {gmtracker_attendance} a
                JOIN {user} u ON u.id = a.userid
                WHERE a.gmtrackerid = ?
                ORDER BY a.jointime ASC";
        $attendances = $DB->get_records_sql($sql, [$training->id]);
        
        fwrite($output, "Total Participants," . count($attendances) . "\n");
        
        if ($attendances) {
            fwrite($output, "\nATTENDANCE:\n");
            $headers = ['First Name', 'Last Name', 'Email', 'Join Time', 'Leave Time', 'Duration', 'Status'];
            fputcsv($output, $headers);
            
            foreach ($attendances as $a) {
                $jointime = $a->jointime ? userdate($a->jointime, '%Y-%m-%d %H:%M:%S') : '';
                $leavetime = $a->leavetime ? userdate($a->leavetime, '%Y-%m-%d %H:%M:%S') : '';
                $duration = $a->duration ? gmtracker_export_format_duration($a->duration) : '';
                
                if ($a->leavetime) {
                    $status = 'Completed';
                } elseif ($a->incomplete) {
                    $status = 'Incomplete';
                } elseif ($a->jointime) {
                    $status = 'In Progress';
                } else {
                    $status = 'Not Started';
                }
                
                $row = [$a->firstname, $a->lastname, $a->email, $jointime, $leavetime, $duration, $status];
                fputcsv($output, $row);
            }
        } else {
            fwrite($output, "ATTENDANCE: No attendance records\n");
        }
        
        fwrite($output, "\n" . str_repeat("-", 80) . "\n\n");
    }
    
    fclose($output);
    exit;
}

/**
 * Export attendance records for current meeting to CSV
 */
function gmtracker_export_attendance_csv($gmtracker, $course, $cm) {
    global $DB, $CFG;
    
    $filename = clean_filename("attendance_{$gmtracker->name}_" . userdate(time(), '%Y%m%d-%H%M'));
    
    // Send CSV headers - ensure no output before this
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM to help with UTF-8 in Excel
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Write training information header - using simple fwrite to avoid CSV formatting
    fwrite($output, "TRAINING INFORMATION\n\n");
    
    $training_info = array(
        "Training Name," . $gmtracker->name,
        "Course," . $course->fullname,
        "Date," . userdate($gmtracker->meetingdate, '%Y-%m-%d'),
        "Time," . userdate($gmtracker->meetingdate, '%H:%M'),
        "Duration," . gmtracker_export_format_minutes($gmtracker->duration)
    );
    
    if ($gmtracker->meetingtype === 'online') {
        $training_info[] = "Training Type,Online";
        if (!empty($gmtracker->hostemail)) {
            $training_info[] = "Host Email," . $gmtracker->hostemail;
        }
    } else {
        $training_info[] = "Training Type,On-Site";
        if (!empty($gmtracker->location)) {
            $training_info[] = "Location," . $gmtracker->location;
        }
        if (!empty($gmtracker->hostemail)) {
            $training_info[] = "Host Email," . $gmtracker->hostemail;
        }
    }
    
    foreach ($training_info as $line) {
        fwrite($output, $line . "\n");
    }
    
    fwrite($output, "\n");
    fwrite($output, "Generated," . userdate(time(), '%Y-%m-%d %H:%M:%S') . "\n");
    fwrite($output, "\n\n");
    
    // Write attendance data header
    fwrite($output, "ATTENDANCE RECORDS\n\n");
    
    // CSV headers for attendance data
    $headers = array(
        get_string('firstname'),
        get_string('lastname'),
        get_string('email'),
        get_string('jointime', 'gmtracker'),
        get_string('leavetime', 'gmtracker'),
        get_string('duration', 'gmtracker'),
        get_string('status', 'gmtracker')
    );
    fputcsv($output, $headers);
    
    // Get attendance records with user data - ordered by join time
    $sql = "SELECT a.*, u.firstname, u.lastname, u.email
            FROM {gmtracker_attendance} a
            JOIN {user} u ON u.id = a.userid
            WHERE a.gmtrackerid = ?
            ORDER BY a.jointime ASC";
    $attendances = $DB->get_records_sql($sql, array($gmtracker->id));
    
    foreach ($attendances as $a) {
        $jointime = $a->jointime ? userdate($a->jointime, '%Y-%m-%d %H:%M:%S') : '';
        $leavetime = $a->leavetime ? userdate($a->leavetime, '%Y-%m-%d %H:%M:%S') : '';
        $duration = $a->duration ? gmtracker_export_format_duration($a->duration) : '';
        
        // Updated status logic with incomplete
        if ($a->leavetime) {
            $status = get_string('completed', 'gmtracker');
        } elseif ($a->incomplete) {
            $status = get_string('incomplete', 'gmtracker');
        } elseif ($a->jointime) {
            $status = get_string('inprogress', 'gmtracker');
        } else {
            $status = get_string('notstarted', 'gmtracker');
        }
        
        $row = array(
            $a->firstname,
            $a->lastname,
            $a->email,
            $jointime,
            $leavetime,
            $duration,
            $status
        );
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Determine which export to perform
if ($exportall === 'onsite') {
    gmtracker_export_all_onsite_trainings($cm);
} elseif ($exportall === 'online') {
    gmtracker_export_all_online_trainings($cm);
} elseif ($export) {
    gmtracker_export_attendance_csv($gmtracker, $course, $cm);
} else {
    throw new moodle_exception('invalidexport', 'gmtracker');
}