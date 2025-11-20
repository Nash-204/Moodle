<?php
// Language strings for block_gamification.

// ============================================================================
// Plugin Basics
// ============================================================================
$string['pluginname'] = 'Gamification';

// ============================================================================
// Block Display & User Interface
// ============================================================================
$string['leaderboard'] = 'Leaderboard';
$string['yourxp'] = 'Your XP';
$string['yourrank'] = 'Your rank';
$string['rank'] = 'Rank';
$string['user'] = 'User';
$string['group'] = 'Group';
$string['profile'] = '';
$string['xp'] = 'XP';
$string['na'] = 'N/A';
$string['groups'] = 'Groups';
$string['nogroup'] = 'No Group';
$string['location'] = 'Location';

// Leaderboard Titles
$string['realtimeleaderboard'] = 'Real-time Leaderboard';
$string['monthlyleaderboard'] = 'Top of the Month';
$string['yearlyleaderboard'] = 'Top of the Year';

// No Data Messages
$string['noleaderboarddata'] = 'No leaderboard data available yet.';
$string['nombmonthly'] = 'Monthly leaderboard will appear after the monthly task runs.';
$string['nombyearly'] = 'Yearly leaderboard will appear after the yearly task runs.';

// ============================================================================
// Badges & Achievements
// ============================================================================
$string['yourbadges'] = 'Your Badges';
$string['nobadgesyet'] = 'You haven\'t earned any badges yet. Keep going!';
$string['allbadgespreview'] = 'Admin Preview: All Available Badges';
$string['awardedfor'] = 'Awarded for';
$string['earnedon'] = 'Earned on';
$string['sitebadge'] = 'Site-level badge';
$string['previewmode'] = 'Preview Mode';
$string['mymode'] = 'My Badges';
$string['allbadgespreview'] = 'Previewing all available badges';
$string['mybadgesview'] = 'Viewing my earned badges';
$string['previewmodeindicator'] = '(Preview mode - badge not earned)';

// Badge Tasks
$string['weeklybadgestask'] = 'Weekly badge awards';
$string['monthlybadgestask'] = 'Monthly badge awards';
$string['annualbadgestask'] = 'Annual badge awards';

// ============================================================================
// Admin & Management Features
// ============================================================================
$string['givexp'] = 'Give XP';
$string['takexp'] = 'Take XP';
$string['chooseuser'] = 'Choose a user...';
$string['enterpoints'] = 'Enter XP';
$string['exportcsv'] = 'Export CSV';
$string['exportrealtime'] = 'Export Real-time Leaderboard';
$string['exportmonthly'] = 'Export Monthly Leaderboard';
$string['exportyearly'] = 'Export Yearly Leaderboard';

// Quiz & Course Management
$string['selectquiz'] = 'Select a quiz';
$string['selectcourse'] = 'Select a course';
$string['uncategorized'] = 'Uncategorized';
$string['selectdifficulty'] = 'Difficulty';
$string['selectlevel'] = 'Level';
$string['savequizcategory'] = 'Save Quiz Category';
$string['savecoursecategory'] = 'Save Course Category';

// Quiz Difficulty Options
$string['quiz_easy'] = 'Easy';
$string['quiz_medium'] = 'Medium';
$string['quiz_hard'] = 'Hard';

// Course Level Options
$string['course_beginner'] = 'Beginner';
$string['course_intermediate'] = 'Intermediate';
$string['course_advance'] = 'Advance';

// ============================================================================
// Validation & Error Messages
// ============================================================================
$string['val_user_points'] = 'Please select a user and enter XP.';
$string['val_user'] = 'Please select a user.';
$string['val_points'] = 'Please enter a valid XP amount.';
$string['cannotremovexp'] = 'User selected already has 0 XP, nothing to remove.';
$string['cannotassignguest'] = 'Guest users cannot receive XP.';
$string['val_quiz_select'] = 'Please select a quiz and difficulty.';
$string['val_quiz_value'] = 'Please select a quiz difficulty.';
$string['val_quiz'] = 'Please select a quiz.';
$string['val_course_select'] = 'Please select a course and level.';
$string['val_course_value'] = 'Please select a course level.';
$string['val_course'] = 'Please select a course.';

// ============================================================================
// Notifications & Messages
// ============================================================================
$string['xpgiven'] = 'Successfully given {$a} XP.';
$string['xptaken'] = 'Successfully taken {$a} XP';
$string['confirmtakexp'] = 'Are you sure you want to take XP from this user?';
$string['quizcategorysaved'] = 'Quiz category saved successfully!';
$string['coursecategorysaved'] = 'Course category saved successfully!';

// Notification Providers
$string['messageprovider:xpnotification'] = 'Gamification XP earned notifications';
$string['messageprovider:badgenotification'] = 'Gamification Badge earned notifications';

// XP Earned Notifications
$string['xpearnedsubject'] = 'XP Earned!';
$string['xpearnedfull'] = '🎉 You earned {$a->points} XP for {$a->reason}. Keep it up!';
$string['xpearnedsmall'] = '🎉 You Earned {$a->points} XP for {$a->reason}';

// Admin XP Change Notifications
$string['xpadminadded'] = 'XP added by admin {$a}';
$string['xpadminremoved'] = 'XP removed by admin {$a}';
$string['xpgivensmall'] = '🎉 {$a} XP was added to your account by an admin';
$string['xptakensmall'] = '😟 {$a} XP was removed from your account by an admin';
$string['xpgivensubject'] = 'XP Added by Administrator';
$string['xptakensubject'] = 'XP Removed by Administrator';
$string['xpgivenfull'] = 'Administrator {$a->admin} has added {$a->points} XP to your account.';
$string['xptakenfull'] = 'Administrator {$a->admin} has removed {$a->points} XP from your account.';

// ============================================================================
// Activity Reasons (for notifications)
// ============================================================================
$string['reason_quiz'] = 'passing a quiz';
$string['reason_course'] = 'completing a course';
$string['reason_daily'] = 'your daily login';
$string['reason_courseview'] = 'viewing a course';
$string['reason_forum_discussion'] = 'starting a forum discussion';
$string['reason_forum_post'] = 'posting in a forum';
$string['reason_assignment'] = 'submitting an assignment';
$string['reason_profilepic'] = 'updating your profile picture';
$string['reason_lesson'] = 'completing a lesson';
$string['reason_resourceview'] = 'viewing a resource';
$string['reason_feedback'] = 'submitting feedback';
$string['reason_choice'] = 'answering a choice activity';
$string['reason_activityadded'] = 'Adding an activity or resource to a course';
$string['reason_meetingleft'] = 'attending Training session';

// ============================================================================
// Settings - Section Headings
// ============================================================================
$string['dailyloginheading'] = 'Daily Login Rewards';
$string['dailyloginheading_desc'] = 'Configure XP rewards for daily user logins.';
$string['coursecompletedheading'] = 'Course Completion Rewards';
$string['coursecompletedheading_desc'] = 'Configure XP rewards for completing courses.';
$string['quizpassheading'] = 'Quiz Submission Rewards';
$string['quizpassheading_desc'] = 'Configure XP rewards for quiz attempts and submissions.';
$string['assignmentheading'] = 'Assignment Submission Rewards';
$string['assignmentheading_desc'] = 'Configure XP rewards for assignment submissions.';
$string['forumheading'] = 'Forum Activity Rewards';
$string['forumheading_desc'] = 'Configure XP rewards for forum participation.';
$string['profileheading'] = 'Profile Activity Rewards';
$string['profileheading_desc'] = 'Configure XP rewards for profile updates.';
$string['lessonheading'] = 'Lesson Completion Rewards';
$string['lessonheading_desc'] = 'Configure XP rewards for completing lessons.';
$string['viewingheading'] = 'Content Viewing Rewards';
$string['viewingheading_desc'] = 'Configure XP rewards for viewing courses and resources.';
$string['interactiveheading'] = 'Interactive Activity Rewards';
$string['interactiveheading_desc'] = 'Configure XP rewards for interactive activities like feedback and choices.';
$string['teachingheading'] = 'Teaching Activity Rewards';
$string['teachingheading_desc'] = 'Configure XP rewards for course creation and content management activities.';

// ============================================================================
// Settings - Enable/Disable & XP Values
// ============================================================================

// Daily Login
$string['enable_dailylogin'] = 'Enable daily login XP';
$string['enable_dailylogin_desc'] = 'If enabled, users earn XP for their first login each day.';
$string['xp_dailylogin'] = 'Daily login';
$string['xp_dailylogin_desc'] = 'XP awarded on first login per day.';

// Course Completed
$string['enable_coursecompleted'] = 'Enable course completion XP';
$string['enable_coursecompleted_desc'] = 'If enabled, users earn XP for completing a course.';
$string['xp_coursecompleted'] = 'Course completed';
$string['xp_coursecompleted_desc'] = 'XP awarded when a user completes a course.';

// Quiz Passed
$string['enable_quizpass'] = 'Enable quiz submission XP';
$string['enable_quizpass_desc'] = 'If enabled, users earn XP when submitting a quiz attempt.';
$string['xp_quizpass'] = 'Quiz attempt submitted';
$string['xp_quizpass_desc'] = 'XP for submitting a quiz attempt.';

// Assignment Submitted
$string['enable_assignment'] = 'Enable assignment submission XP';
$string['enable_assignment_desc'] = 'If enabled, users earn XP when they submit an assignment.';
$string['xp_assignment'] = 'Assignment submitted';
$string['xp_assignment_desc'] = 'XP for submitting an assignment.';

// Forum Activities
$string['enable_forumpost'] = 'Enable forum post XP';
$string['enable_forumpost_desc'] = 'If enabled, users earn XP when posting in a forum.';
$string['xp_forumpost'] = 'Forum post created';
$string['xp_forumpost_desc'] = 'XP for creating a post.';
$string['enable_forumdiscussion'] = 'Enable forum discussion XP';
$string['enable_forumdiscussion_desc'] = 'If enabled, users earn XP when they start a forum discussion.';
$string['xp_forumdiscussion'] = 'Forum discussion created';
$string['xp_forumdiscussion_desc'] = 'XP for creating a discussion.';

// Profile Picture
$string['enable_profilepic'] = 'Enable profile picture XP';
$string['enable_profilepic_desc'] = 'If enabled, users earn XP when updating their profile picture.';
$string['xp_profilepic'] = 'Profile picture updated';
$string['xp_profilepic_desc'] = 'XP for updating profile picture.';

// Lesson Completed
$string['enable_lesson'] = 'Enable lesson completion XP';
$string['enable_lesson_desc'] = 'If enabled, users earn XP for completing a lesson.';
$string['xp_lesson'] = 'Lesson completed';
$string['xp_lesson_desc'] = 'XP for completing a lesson.';

// Viewing Activities
$string['enable_courseview'] = 'Enable course view XP';
$string['enable_courseview_desc'] = 'Allow users to earn XP for viewing courses (once per day per course)';
$string['xp_courseview'] = 'Course viewed';
$string['xp_courseview_desc'] = 'XP for viewing a course.';
$string['enable_resourceview'] = 'Enable resource view XP';
$string['enable_resourceview_desc'] = 'If enabled, users earn XP when viewing a resource.';
$string['xp_resourceview'] = 'Resource viewed';
$string['xp_resourceview_desc'] = 'XP for viewing a resource.';

// Interactive Activities
$string['enable_feedback'] = 'Enable feedback submission XP';
$string['enable_feedback_desc'] = 'If enabled, users earn XP when submitting feedback.';
$string['xp_feedback'] = 'Feedback submitted';
$string['xp_feedback_desc'] = 'XP for submitting feedback.';
$string['enable_choice'] = 'Enable choice activity XP';
$string['enable_choice_desc'] = 'If enabled, users earn XP when answering a choice activity.';
$string['xp_choice'] = 'Choice answered';
$string['xp_choice_desc'] = 'XP for answering a choice activity.';

// Teaching Activities
$string['enable_activityadded'] = 'Enable XP for adding activities/resources';
$string['enable_activityadded_desc'] = 'If enabled, users will earn XP when they add any activity or resource to a course.';
$string['xp_activityadded'] = 'XP for adding activity/resource';
$string['xp_activityadded_desc'] = 'Number of XP points a user earns when they add a new activity or resource to a course.';

// ============================================================================
// Training Tracker Settings
// ============================================================================
$string['gmtrackerheading'] = 'Training Tracker';
$string['gmtrackerheading_desc'] = 'Configure XP rewards for Training tracked by the Training Tracker module.';
$string['enable_gmtracker'] = 'Enable Training Tracker XP';
$string['enable_gmtracker_desc'] = 'If enabled, users will earn XP when they attend trainings created using the Training Tracker plugin.';
$string['xp_gmtracker'] = 'XP for full training attendance';
$string['xp_gmtracker_desc'] = 'The base XP awarded for completing a full training. Partial attendance awards less.';

// For quiz email invite 
$string['sendquizinvite'] = 'Send Quiz Invite';
$string['val_quiz_email'] = 'Please select a quiz to send invitation for.';
$string['quizinvitesubject'] = 'Quiz Invitation: {$a}';
$string['invitesentsuccess'] = 'Quiz invitation emails sent successfully to {$a} users.';
$string['noemailssent'] = 'No emails were sent. Please check if there are enrolled users in the course.';
$string['noenrolledusers'] = 'No enrolled users found in this course to send invitations to.';
$string['unlimited'] = 'Unlimited';

// Quiz invite logging
$string['eventquizinvitebulkstart'] = 'Bulk quiz invitations started';
$string['eventquizinvitebulkcomplete'] = 'Bulk quiz invitations completed';
$string['eventquizinvitesent'] = 'Quiz invitation sent';
$string['eventquizinvitefailed'] = 'Quiz invitation failed';

$string['invitequeued'] = 'Quiz invitations queued for background processing! {$a} users will receive invitations shortly.';
$string['quizinvitecompletesubject'] = 'Quiz invitations completed: {$a}';
$string['quizinvitecompletebody'] = 'Your quiz invitations for "{$a->quizname}" have been processed.

Successfully sent: {$a->sentcount} out of {$a->totalusers} users';
$string['quizinvitefailedbody'] = 'Failed to send: {$a} invitations (check logs for details)';
$string['eventquizinvitequeued'] = 'Quiz invitations queued';


$string['awardweeklychampion'] = 'Award Weekly Champion';
$string['generatemonthlyleaderboard'] = 'Generate Monthly Leaderboard';
$string['generateyearlyleaderboard'] = 'Generate Yearly Leaderboard';
$string['weeklychampionawarded'] = 'Weekly champion has been awarded!';
$string['monthlyleaderboardgenerated'] = 'Monthly leaderboard updated and champion awarded!';
$string['yearlyleaderboardgenerated'] = 'Yearly leaderboard updated and champion awarded!';
$string['monthlyleadergenerated'] = 'Monthly leaderboard generated and badge awarded';
$string['yearlyleadergenerated'] = 'Yearly leaderboard generated and badge awarded';
$string['invalidtype'] = 'Invalid leaderboard type';