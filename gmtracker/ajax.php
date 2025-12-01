<?php
require('../../config.php');
require_once('lib.php');
require_once('classes/attendance_manager.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('gmtracker', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$gmtracker = $DB->get_record('gmtracker', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

require_sesskey();

header('Content-Type: application/json');

try {
    $attendance_manager = new gmtracker_attendance_manager($gmtracker, $context, $cm, $course);
    $data = $attendance_manager->get_ajax_data();
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}