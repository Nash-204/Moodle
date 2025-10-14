<?php
// Language strings for block_gamification.
$string['pluginname'] = 'Gamification';
$string['leaderboard'] = 'Leaderboard';
$string['yourxp'] = 'Your XP';
$string['yourrank'] = 'Your rank';
$string['rank'] = 'Rank';
$string['user'] = 'User';
$string['group'] = 'Group';
$string['profile'] = '';
$string['xp'] = 'XP';
$string['na'] = 'N/A';
$string['exportcsv'] = 'Export CSV';
$string['givexp'] = 'Give XP';
$string['takexp'] = 'Take XP';
$string['chooseuser'] = 'Choose a user...';
$string['enterpoints'] = 'Enter XP';
$string['yourbadges'] = 'Your Badges';
$string['nobadgesyet'] = 'You haven’t earned any badges yet. Keep going!';
$string['allbadgespreview'] = 'Admin Preview: All Available Badges';
$string['groups'] = 'Groups';
$string['nogroup'] = 'No Group';
$string['location'] = 'Location';
$string['awardedfor'] = 'Awarded for';
$string['earnedon'] = 'Earned on';
$string['sitebadge'] = 'Site-level badge';

// Leaderboard titles
$string['realtimeleaderboard'] = 'Real-time Leaderboard';
$string['monthlyleaderboard'] = 'Top of the Month';
$string['yearlyleaderboard']  = 'Top of the Year';

// No data messages.
$string['noleaderboarddata'] = 'No leaderboard data available yet.';
$string['nombmonthly'] = 'Monthly leaderboard will appear after the monthly task runs.';
$string['nombyearly']  = 'Yearly leaderboard will appear after the yearly task runs.';

// Validation messages.
$string['val_user_points'] = 'Please select a user and enter XP.';
$string['val_user'] = 'Please select a user.';
$string['val_points'] = 'Please enter a valid XP amount.';
$string['cannotremovexp'] = 'User selected already has 0 XP, nothing to remove.';
$string['cannotassignguest'] = 'Guest users cannot receive XP.';

// Notifications.
$string['xpgiven'] = 'Successfully given {$a} XP.';
$string['xptaken'] = 'Successfully taken {$a} XP';
$string['confirmtakexp'] = 'Are you sure you want to take XP from this user?';
$string['messageprovider:xpnotification'] = 'Gamification XP earned notifications';
$string['xpearnedsubject'] = 'XP Earned!';
$string['xpearnedfull'] = '🎉 You earned {$a->points} XP for {$a->reason}. Keep it up!';
$string['xpearnedsmall'] = '🎉 You Earned {$a->points} XP for {$a->reason}';
$string['messageprovider:badgenotification'] = 'Gamification Badge earned notifications';

// Admin XP change strings
$string['xpadminadded'] = 'XP added by admin {$a}';
$string['xpadminremoved'] = 'XP removed by admin {$a}';
$string['xpgivensmall'] = '🎉 {$a} XP was added to your account by an admin';
$string['xptakensmall'] = '😟 {$a} XP was removed from your account by an admin';
$string['xpgivensubject'] = 'XP Added by Administrator';
$string['xptakensubject'] = 'XP Removed by Administrator';
$string['xpgivenfull'] = 'Administrator {$a->admin} has added {$a->points} XP to your account.';
$string['xptakenfull'] = 'Administrator {$a->admin} has removed {$a->points} XP from your account.';

// Quiz & course selection
$string['selectquiz'] = 'Select a quiz';
$string['selectcourse'] = 'Select a course';
$string['uncategorized'] = 'Uncategorized';
$string['selectdifficulty'] = 'Difficulty';
$string['selectlevel'] = 'Level';
$string['savequizcategory'] = 'Save Quiz Category';
$string['savecoursecategory'] = 'Save Course Category';
$string['val_quiz_select'] = 'Please select a quiz and difficulty.';
$string['val_quiz_value'] = 'Please select a quiz difficulty.';
$string['val_quiz'] = 'Please select a quiz.';
$string['val_course_select'] = 'Please select a course and level.';
$string['val_course_value'] = 'Please select a course level.';
$string['val_course'] = 'Please select a course.';
$string['quizcategorysaved'] = 'Quiz category saved successfully!';
$string['coursecategorysaved'] = 'Course category saved successfully!';


// Quiz difficulty options
$string['quiz_easy'] = 'Easy';
$string['quiz_medium'] = 'Medium';
$string['quiz_hard'] = 'Hard';

// Course level options
$string['course_beginner'] = 'Beginner';
$string['course_intermediate'] = 'Intermediate';
$string['course_advance'] = 'Advance';

// Badge notifications.
$string['weeklybadgestask'] = 'Weekly badge awards';
$string['monthlybadgestask'] = 'Monthly badge awards';
$string['annualbadgestask'] = 'Annual badge awards';

// Reasons (observer messages).
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

// Settings labels/descriptions.
$string['xp_coursecompleted'] = 'Course completed';
$string['xp_coursecompleted_desc'] = 'XP awarded when a user completes a course.';
$string['enable_coursecompleted'] = 'Enable course completion XP';
$string['enable_coursecompleted_desc'] = 'If enabled, users earn XP for completing a course.';

$string['xp_dailylogin'] = 'Daily login';
$string['xp_dailylogin_desc'] = 'XP awarded on first login per day.';
$string['enable_dailylogin'] = 'Enable daily login XP';
$string['enable_dailylogin_desc'] = 'If enabled, users earn XP for their first login each day.';

$string['xp_quizpass'] = 'Quiz attempt submitted';
$string['xp_quizpass_desc'] = 'XP for submitting a quiz attempt.';
$string['enable_quizpass'] = 'Enable quiz submission XP';
$string['enable_quizpass_desc'] = 'If enabled, users earn XP when submitting a quiz attempt.';

$string['xp_forumdiscussion'] = 'Forum discussion created';
$string['xp_forumdiscussion_desc'] = 'XP for creating a discussion.';
$string['enable_forumdiscussion'] = 'Enable forum discussion XP';
$string['enable_forumdiscussion_desc'] = 'If enabled, users earn XP when they start a forum discussion.';

$string['xp_forumpost'] = 'Forum post created';
$string['xp_forumpost_desc'] = 'XP for creating a post.';
$string['enable_forumpost'] = 'Enable forum post XP';
$string['enable_forumpost_desc'] = 'If enabled, users earn XP when posting in a forum.';

$string['xp_assignment'] = 'Assignment submitted';
$string['xp_assignment_desc'] = 'XP for submitting an assignment.';
$string['enable_assignment'] = 'Enable assignment submission XP';
$string['enable_assignment_desc'] = 'If enabled, users earn XP when they submit an assignment.';

$string['xp_profilepic'] = 'Profile picture updated';
$string['xp_profilepic_desc'] = 'XP for updating profile picture.';
$string['enable_profilepic'] = 'Enable profile picture XP';
$string['enable_profilepic_desc'] = 'If enabled, users earn XP when updating their profile picture.';

$string['xp_lesson'] = 'Lesson completed';
$string['xp_lesson_desc'] = 'XP for completing a lesson.';
$string['enable_lesson'] = 'Enable lesson completion XP';
$string['enable_lesson_desc'] = 'If enabled, users earn XP for completing a lesson.';

$string['xp_courseview'] = 'Course viewed';
$string['xp_courseview_desc'] = 'XP for viewing a course.';
$string['enable_courseview'] = 'Enable course view XP';
$string['enable_courseview_desc'] = 'Allow users to earn XP for viewing courses (once per day per course)';

$string['xp_resourceview'] = 'Resource viewed';
$string['xp_resourceview_desc'] = 'XP for viewing a resource.';
$string['enable_resourceview'] = 'Enable resource view XP';
$string['enable_resourceview_desc'] = 'If enabled, users earn XP when viewing a resource.';

$string['xp_feedback'] = 'Feedback submitted';
$string['xp_feedback_desc'] = 'XP for submitting feedback.';
$string['enable_feedback'] = 'Enable feedback submission XP';
$string['enable_feedback_desc'] = 'If enabled, users earn XP when submitting feedback.';

$string['xp_choice'] = 'Choice answered';
$string['xp_choice_desc'] = 'XP for answering a choice activity.';
$string['enable_choice'] = 'Enable choice activity XP';
$string['enable_choice_desc'] = 'If enabled, users earn XP when answering a choice activity.';

$string['enable_activityadded'] = 'Enable XP for adding activities/resources';
$string['enable_activityadded_desc'] = 'If enabled, users will earn XP when they add any activity or resource to a course.';
$string['xp_activityadded'] = 'XP for adding activity/resource';
$string['xp_activityadded_desc'] = 'Number of XP points a user earns when they add a new activity or resource to a course.';

