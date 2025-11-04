<?php
// Plugin metadata and basic strings
$string['pluginname'] = 'Training Tracker';
$string['modulename'] = 'Training Tracker';
$string['modulenameplural'] = 'Training Trackers';
$string['moduleintro'] = 'Description';
$string['required'] = 'This field is required';

// Capabilities
$string['gmtracker:addinstance'] = 'Add a new Training Tracker activity';
$string['gmtracker:view'] = 'View Training Tracker';

// Activity form fields
$string['gmtrackername'] = 'Training name';
$string['gmtrackername_placeholder'] = 'e.g., Weekly Math Tutorial Session';
$string['gmtrackername_help'] = 'Enter a descriptive name for this Training session that students will recognize.';

$string['gmeetlink'] = 'Google Meet link';
$string['gmeetlink_help'] = 'Enter the full Google Meet URL. Example: https://meet.google.com/abc-defg-hij';

$string['hostemail'] = 'Host email';
$string['hostemail_help'] = 'Enter the email address of the meeting host (instructor or facilitator).';

$string['meetingdate'] = 'Training date and time';
$string['meetingdate_help'] = 'Select the date and time when the meeting will start.';

$string['duration'] = 'Training duration';
$string['duration_help'] = 'Enter the expected duration of the meeting in minutes. Typically 30-120 minutes for most sessions.';

$string['meetingtype'] = 'Training type';
$string['meetingtype_online'] = 'Online Training';
$string['meetingtype_onsite'] = 'On-site Training';
$string['meetingtype_help'] = 'Choose whether this Training will be conducted online via Google Meet or on-site at a physical location.';

$string['location'] = 'Training location';
$string['location_help'] = 'Enter the physical location where the on-site Training will take place.';
$string['location_placeholder'] = 'e.g., Room 101, Building A';

// Calendar integration
$string['addtocalendar'] = 'Add to calendar';
$string['addtocalendar_help'] = 'If enabled, this training will be automatically added to the Moodle calendar for all course participants.';
$string['calendareventcreated'] = 'Calendar event created';
$string['calendareventupdated'] = 'Calendar event updated';

// Settings and configuration
$string['defaultduration'] = 'Default training duration';
$string['defaultduration_desc'] = 'The default duration in minutes for new meetings when no duration is specified.';

$string['sendemailnotifications'] = 'Send email notifications';
$string['sendemailnotifications_desc'] = 'When enabled, all course participants will receive email notifications when new GM Tracker activities are created.';

// Attendance codes
$string['joincode'] = 'Join code';
$string['leavecode'] = 'Leave code';
$string['codesgenerated'] = 'Attendance codes generated';
$string['attendancecode'] = 'Attendance Code';

// User actions and interface
$string['joinmeeting'] = 'Join Training';
$string['leavemeeting'] = 'Leave Training';
$string['viewattendance'] = 'View attendance list';
$string['enterjoincode'] = 'Enter join code';
$string['enterleavecode'] = 'Enter leave code';
$string['codeinstructions'] = 'Show this code to attendees for them to join the Training';

// Attendance tracking
$string['attendance'] = 'Attendance';
$string['jointime'] = 'Join time';
$string['leavetime'] = 'Leave time';
$string['attendanceduration'] = 'Duration';
$string['attendancerecords'] = 'Attendance Records';

// Status and states
$string['notjoined'] = 'Not joined';
$string['inmeeting'] = 'In Training';
$string['meetingcompleted'] = 'Training completed';
$string['status'] = 'Status';
$string['completed'] = 'Completed';
$string['inprogress'] = 'In Progress';
$string['notstarted'] = 'Not Started';
$string['incomplete'] = 'Incomplete';
$string['incompleteparticipants'] = 'Incomplete';
$string['completedparticipants'] = 'Completed';
$string['hostleftmarkedincomplete'] = 'You have left the meeting as host. {$a} participants were marked as incomplete for not leaving on time.';
$string['hostalreadyleft'] = 'The host has already ended the meeting. You cannot leave at this time.';
$string['markedincomplete'] = 'Marked Incomplete';
$string['hostleftyouincomplete'] = 'The host ended the meeting before you could leave. You have been marked as incomplete.';

// Meeting information display
$string['meetinginfo'] = 'Training Information';
$string['meetinglink'] = 'Training Link';
$string['host'] = 'Host';
$string['datetime'] = 'Date & Time';
$string['actions'] = 'Actions';

// User attendance
$string['yourattendance'] = 'Your Attendance';
$string['timeinsession'] = 'Time in session: {$a}';
$string['attendedfor'] = 'You attended for {$a}';

// Statistics
$string['quickstats'] = 'Quick Stats';
$string['totalparticipants'] = 'Total Participants';
$string['activeparticipants'] = 'Active Now';
$string['completionrate'] = 'Completion Rate';

// Export attendance feature
$string['exportattendance'] = 'Export Attendance';
$string['exportallonsite'] = 'Export All On-site Trainings';
$string['exportallonline'] = 'Export All Online Trainings';
$string['firstname'] = 'First Name';
$string['lastname'] = 'Last Name';
$string['email'] = 'Email';

// Success/status messages
$string['leavesuccess'] = 'You have left the Training. Attendance recorded successfully.';
$string['successfullyjoined'] = 'Successfully joined the Training';
$string['successfullyleft'] = 'Successfully left the Training';
$string['alreadyjoined'] = 'You have already joined this Training';
$string['notjoinedyet'] = 'You have not joined this Training yet';

// Empty states
$string['noattendance'] = 'No attendance records have been recorded yet.';

// Events
$string['event_meeting_left'] = 'User left Google Meet Training';

// Validation messages
$string['gmeetlink_invalid_format'] = 'Please enter a valid Google Meet link in the format: https://meet.google.com/abc-defg-hij';
$string['invalidurl'] = 'Invalid URL format';
$string['invalidemail'] = 'Please enter a valid email address';
$string['meetingdate_past'] = 'Training date cannot be in the past';
$string['duration_validation'] = 'Duration must be a positive number';
$string['duration_range'] = 'Duration must be between 1 and 1440 minutes (24 hours)';
$string['duration_max'] = 'Duration cannot exceed 24 hours';
$string['invalidcode'] = 'Invalid code. Please try again.';