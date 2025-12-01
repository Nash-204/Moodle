<?php
require('../../config.php');

$id = required_param('id', PARAM_INT); // Course ID
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);
$PAGE->set_url('/mod/gmtracker/index.php', ['id' => $id]);
$PAGE->set_title('Google Meet Tracker');
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading('Google Meet Tracker Activities');

if (!$gmtrackers = get_all_instances_in_course('gmtracker', $course)) {
    notice('No Google Meet Tracker activities found.', new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = ['Name', 'Date', 'Duration', 'Host'];
foreach ($gmtrackers as $gm) {
    $link = html_writer::link(new moodle_url('/mod/gmtracker/view.php', ['id' => $gm->coursemodule]), format_string($gm->name));
    $table->data[] = [$link, userdate($gm->meetingdate), $gm->duration.' mins', $gm->hostemail];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
