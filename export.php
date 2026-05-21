<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CSV export for completed courses.
 *
 * @package    block_completedcourse
 * @author     Vinny Stocker <vinny@pukunui.com>
 * @copyright  2026 Pukunui Malaysia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use block_completedcourse\local\completion_service;

$instanceid = required_param('instanceid', PARAM_INT);

require_login();
require_sesskey();

$blockrecord = $DB->get_record('block_instances', ['id' => $instanceid, 'blockname' => 'completedcourse'], '*', MUST_EXIST);
$context = context_block::instance($blockrecord->id);
require_capability('block/completedcourse:export', $context);
require_capability('block/completedcourse:viewown', $context);

$config = [];
if (!empty($blockrecord->configdata)) {
    $config = (array)unserialize_object(base64_decode($blockrecord->configdata));
}
$options = completion_service::normalise_options($config);
$courses = completion_service::get_completed_courses($USER->id, $USER, $options);

$columns = [
    'coursename' => get_string('course', 'block_completedcourse'),
    'category' => get_string('category', 'block_completedcourse'),
    'completedon' => get_string('completedon', 'block_completedcourse'),
    'grade' => get_string('grade', 'block_completedcourse'),
];

$data = [];
foreach ($courses as $course) {
    $data[] = [
        'coursename' => completion_service::get_course_display_name($course, $options['displayname']),
        'category' => $course->categoryname,
        'completedon' => userdate($course->timecompleted, $options['dateformat']),
        'grade' => completion_service::get_grade_display($USER->id, $course->courseid),
    ];
}

\core\dataformat::download_data('completed-courses-' . $USER->id, 'csv', $columns, $data);
