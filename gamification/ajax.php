<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$term = required_param('term', PARAM_RAW_TRIMMED);

global $DB;

// Search users by firstname/lastname only.
$sql = "SELECT id, firstname, lastname
          FROM {user}
         WHERE deleted = 0
           AND suspended = 0
           AND (firstname LIKE :term1 OR lastname LIKE :term2)
      ORDER BY lastname ASC, firstname ASC
         LIMIT 10";

$like = '%' . $DB->sql_like_escape($term) . '%';
$users = $DB->get_records_sql($sql, [
    'term1' => $like,
    'term2' => $like
]);

$data = [];
foreach ($users as $u) {
    $data[] = [
        'id' => $u->id,
        'name' => fullname($u)
    ];
}

echo json_encode($data);
die;
