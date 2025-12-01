<?php
// Event observers for block_gamification.

defined('MOODLE_INTERNAL') || die();

$observers = [

    // Course completed.
    [
        'eventname' => '\\core\\event\\course_completed',
        'callback'  => '\\block_gamification\\observer::course_completed',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Daily login.
    [
        'eventname' => '\\core\\event\\user_loggedin',
        'callback'  => '\\block_gamification\\observer::user_loggedin',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Profile update.
    [
        'eventname' => '\\core\\event\\user_updated',
        'callback'  => '\\block_gamification\\observer::profile_updated',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Course view.
    [
        'eventname' => '\\core\\event\\course_viewed',
        'callback'  => '\\block_gamification\\observer::course_viewed',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Quiz.
    [
        'eventname' => '\\mod_quiz\\event\\attempt_submitted',
        'callback'  => '\\block_gamification\\observer::quiz_attempt_submitted',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Forum.
    [
        'eventname' => '\\mod_forum\\event\\discussion_created',
        'callback'  => '\\block_gamification\\observer::forum_discussion_created',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Forum post.
    [
        'eventname' => '\\mod_forum\\event\\post_created',
        'callback'  => '\\block_gamification\\observer::forum_post_created',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Assignment.
    [
        'eventname' => '\\mod_assign\\event\\assessable_submitted',
        'callback'  => '\\block_gamification\\observer::assignment_submitted',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Lesson.
    [
        'eventname' => '\\mod_lesson\\event\\lesson_completed',
        'callback'  => '\\block_gamification\\observer::lesson_completed',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Workshop.
    [
        'eventname' => '\\mod_workshop\\event\\assessment_evaluated',
        'callback'  => '\\block_gamification\\observer::workshop_assessed',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Glossary.
    [
        'eventname' => '\\mod_glossary\\event\\entry_created',
        'callback'  => '\\block_gamification\\observer::glossary_entry_created',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Database activity.
    [
        'eventname' => '\\mod_data\\event\\record_created',
        'callback'  => '\\block_gamification\\observer::data_record_created',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Wiki.
    [
        'eventname' => '\\mod_wiki\\event\\page_created',
        'callback'  => '\\block_gamification\\observer::wiki_page_created',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],
    
    // Wiki update.
    [
        'eventname' => '\\mod_wiki\\event\\page_updated',
        'callback'  => '\\block_gamification\\observer::wiki_page_updated',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Resource view.
    [
        'eventname' => '\\mod_resource\\event\\course_module_viewed',
        'callback'  => '\\block_gamification\\observer::resource_viewed',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Feedback submitted.
    [
        'eventname' => '\\mod_feedback\\event\\response_submitted',
        'callback'  => '\\block_gamification\\observer::feedback_submitted',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Choice answered.
    [
        'eventname' => '\\mod_choice\\event\\answer_submitted',
        'callback'  => '\\block_gamification\\observer::choice_answered',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],

    // Activity added.
    [
        'eventname' => '\core\event\course_module_created',
        'callback'  => '\block_gamification\observer::activity_added',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],
    
    // Google Meet completion
    [
        'eventname' => '\\mod_gmtracker\\event\\meeting_left',
        'callback'  => '\\block_gamification\\observer::meeting_left',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],


    [
        'eventname' => '\block_gamification\event\quiz_task_started',
        'callback' => '\block_gamification\observer::quiz_task_started',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],
    [
        'eventname' => '\block_gamification\event\quiz_task_completed',
        'callback' => '\block_gamification\observer::quiz_task_completed',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],
    [
        'eventname' => '\block_gamification\event\quiz_email_sent',
        'callback' => '\block_gamification\observer::quiz_email_sent',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],
    [
        'eventname' => '\block_gamification\event\quiz_email_failed',
        'callback' => '\block_gamification\observer::quiz_email_failed',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],
    [
        'eventname' => '\block_gamification\event\quiz_batch_failed',
        'callback' => '\block_gamification\observer::quiz_batch_failed',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 1000,
    ],
];
