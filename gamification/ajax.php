<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $DB;

$action = optional_param('action', '', PARAM_ALPHA); // 'search' or 'getcategory'
$type   = optional_param('type', 'user', PARAM_ALPHA); // user, quiz, course
$data   = [];

// === Case 1: Fetch existing quiz/course category (difficulty/level) ===
if ($action === 'getcategory') {
    $id = required_param('id', PARAM_INT);

    if ($type === 'quiz') {
        $difficulty = $DB->get_field('block_gamif_quizdiff', 'difficulty', ['quizid' => $id]);
        $data = ['difficulty' => $difficulty ?: 'Easy'];
    }
    else if ($type === 'course') {
        $level = $DB->get_field('block_gamif_coursediff', 'level', ['courseid' => $id]);
        $data = ['level' => $level ?: 'Beginner'];
    }

    echo json_encode($data);
    exit;
}

// === Case 2: Search (default) ===
$term = optional_param('term', '', PARAM_RAW_TRIMMED);

// Return empty array if term is too short (matches your JS condition)
if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

if ($type === 'user') {
    $sql = "SELECT id, firstname, lastname,
                   CASE 
                     WHEN LOWER(firstname) LIKE LOWER(:start1) OR LOWER(lastname) LIKE LOWER(:start2) THEN 1
                     WHEN LOWER(firstname) LIKE LOWER(:contain1) OR LOWER(lastname) LIKE LOWER(:contain2) THEN 2
                     ELSE 3
                   END as match_priority
              FROM {user}
             WHERE deleted = 0
               AND suspended = 0
               AND id != 1
               AND (LOWER(firstname) LIKE LOWER(:search1) OR LOWER(lastname) LIKE LOWER(:search2))
          ORDER BY match_priority ASC, lastname ASC, firstname ASC
             LIMIT 15";
    
    $search_term = $DB->sql_like_escape($term);
    $like_contain = '%' . $search_term . '%';
    $like_start = $search_term . '%';
    
    $records = $DB->get_records_sql($sql, [
        'search1' => $like_contain,
        'search2' => $like_contain,
        'start1' => $like_start,
        'start2' => $like_start,
        'contain1' => $like_contain,
        'contain2' => $like_contain
    ]);
    
    foreach ($records as $r) {
        $data[] = ['id' => $r->id, 'name' => fullname($r)];
    }
}
else if ($type === 'quiz') {
    $sql = "SELECT id, name,
                   CASE 
                     WHEN LOWER(name) LIKE LOWER(:start) THEN 1
                     WHEN LOWER(name) LIKE LOWER(:contain) THEN 2
                     ELSE 3
                   END as match_priority
              FROM {quiz}
             WHERE LOWER(name) LIKE LOWER(:search)
          ORDER BY match_priority ASC, name ASC
             LIMIT 15";
    
    $search_term = $DB->sql_like_escape($term);
    $like_contain = '%' . $search_term . '%';
    $like_start = $search_term . '%';
    
    $records = $DB->get_records_sql($sql, [
        'search' => $like_contain,
        'start' => $like_start,
        'contain' => $like_contain
    ]);
    
    foreach ($records as $r) {
        $data[] = ['id' => $r->id, 'name' => format_string($r->name)];
    }
}
else if ($type === 'course') {
    $sql = "SELECT id, fullname,
                   CASE 
                     WHEN LOWER(fullname) LIKE LOWER(:start) THEN 1
                     WHEN LOWER(fullname) LIKE LOWER(:contain) THEN 2
                     ELSE 3
                   END as match_priority
              FROM {course}
             WHERE id != 1
               AND LOWER(fullname) LIKE LOWER(:search)
          ORDER BY match_priority ASC, fullname ASC
             LIMIT 15";
    
    $search_term = $DB->sql_like_escape($term);
    $like_contain = '%' . $search_term . '%';
    $like_start = $search_term . '%';
    
    $records = $DB->get_records_sql($sql, [
        'search' => $like_contain,
        'start' => $like_start,
        'contain' => $like_contain
    ]);
    
    foreach ($records as $r) {
        $data[] = ['id' => $r->id, 'name' => format_string($r->fullname)];
    }
}

echo json_encode($data);
exit;