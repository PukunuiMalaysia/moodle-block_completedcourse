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
 * Completed course data access.
 *
 * @package    block_completedcourse
 * @author     Vinny Stocker <vinny@pukunui.com>
 * @copyright  2026 Pukunui Malaysia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_completedcourse\local;

/**
 * Provides completed course query and formatting helpers.
 */
class completion_service {
    /** @var int Default result limit. */
    private const DEFAULT_ROW_LIMIT = 10;

    /** @var int Maximum result limit for block display. */
    private const MAX_ROW_LIMIT = 100;

    /**
     * Normalize block configuration.
     *
     * @param array $config Block config.
     * @return array
     */
    public static function normalise_options(array $config): array {
        $rowlimit = isset($config['rowlimit']) ? (int)$config['rowlimit'] : self::DEFAULT_ROW_LIMIT;
        if ($rowlimit < 1) {
            $rowlimit = self::DEFAULT_ROW_LIMIT;
        }
        $rowlimit = min($rowlimit, self::MAX_ROW_LIMIT);

        $sortdirection = isset($config['sortdirection']) ? strtoupper((string)$config['sortdirection']) : 'DESC';
        if (!in_array($sortdirection, ['ASC', 'DESC'], true)) {
            $sortdirection = 'DESC';
        }

        $displayname = isset($config['displayname']) ? (string)$config['displayname'] : 'shortname';
        if (!in_array($displayname, ['shortname', 'fullname', 'both'], true)) {
            $displayname = 'shortname';
        }

        $linktarget = !empty($config['linktarget']) ? '_blank' : '_self';
        $dateformat = !empty($config['dateformat']) ? (string)$config['dateformat'] : get_string('strftimedate', 'core_langconfig');

        return [
            'categoryid' => isset($config['categoryid']) ? max(0, (int)$config['categoryid']) : 0,
            'rowlimit' => $rowlimit,
            'sortdirection' => $sortdirection,
            'displayname' => $displayname,
            'showcategory' => !empty($config['showcategory']),
            'showgrade' => !empty($config['showgrade']),
            'showhidden' => !empty($config['showhidden']),
            'linktarget' => $linktarget,
            'dateformat' => $dateformat,
        ];
    }

    /**
     * Count completed courses visible to the viewer.
     *
     * @param int $userid Target user id.
     * @param \stdClass $viewer Viewer user record.
     * @param array $options Normalized options.
     * @return int
     */
    public static function count_completed_courses(int $userid, \stdClass $viewer, array $options): int {
        return count(self::get_all_visible_completed_courses($userid, $viewer, $options));
    }

    /**
     * Get completed courses visible to the viewer.
     *
     * @param int $userid Target user id.
     * @param \stdClass $viewer Viewer user record.
     * @param array $options Normalized options.
     * @param int $offset Offset.
     * @param int $limit Limit.
     * @return array
     */
    public static function get_completed_courses(
        int $userid,
        \stdClass $viewer,
        array $options,
        int $offset = 0,
        int $limit = 0
    ): array {
        $courses = self::get_all_visible_completed_courses($userid, $viewer, $options);
        if ($limit <= 0) {
            return $courses;
        }

        return array_slice($courses, $offset, $limit);
    }

    /**
     * Get course display name according to settings.
     *
     * @param \stdClass $course Course record.
     * @param string $displayname Display mode.
     * @return string
     */
    public static function get_course_display_name(\stdClass $course, string $displayname): string {
        if ($displayname === 'fullname') {
            return $course->fullname;
        }
        if ($displayname === 'both') {
            return $course->fullname . ' (' . $course->shortname . ')';
        }

        return $course->shortname;
    }

    /**
     * Get formatted grade for a course if available.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return string
     */
    public static function get_grade_display(int $userid, int $courseid): string {
        global $CFG;

        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');

        $grade = \grade_get_course_grade($userid, $courseid);
        if (empty($grade) || empty($grade->grade)) {
            return get_string('notavailable', 'block_completedcourse');
        }

        return \grade_format_gradevalue($grade->grade, $grade->item);
    }

    /**
     * Fetch and filter completed courses.
     *
     * @param int $userid Target user id.
     * @param \stdClass $viewer Viewer user record.
     * @param array $options Normalized options.
     * @return array
     */
    private static function get_all_visible_completed_courses(int $userid, \stdClass $viewer, array $options): array {
        global $DB;

        [$where, $params] = self::get_filter_sql($userid, $options);
        $sort = $options['sortdirection'] === 'ASC' ? 'ASC' : 'DESC';
        $sql = "SELECT c.id,
                       c.id AS courseid,
                       c.fullname,
                       c.shortname,
                       c.visible,
                       cc.timecompleted,
                       cat.name AS categoryname
                  FROM {course_completions} cc
                  JOIN {course} c ON c.id = cc.course
                  JOIN {course_categories} cat ON cat.id = c.category
                 WHERE $where
              ORDER BY cc.timecompleted $sort, c.fullname ASC";

        $courses = $DB->get_records_sql($sql, $params);
        $visible = [];
        foreach ($courses as $course) {
            if (self::viewer_can_access_course($course, $viewer, $options)) {
                $visible[] = $course;
            }
        }

        return $visible;
    }

    /**
     * Build SQL filters.
     *
     * @param int $userid Target user id.
     * @param array $options Normalized options.
     * @return array
     */
    private static function get_filter_sql(int $userid, array $options): array {
        $where = ['cc.userid = :userid', 'cc.timecompleted IS NOT NULL'];
        $params = ['userid' => $userid];

        if (empty($options['showhidden'])) {
            $where[] = 'c.visible = :visible';
            $params['visible'] = 1;
        }

        if (!empty($options['categoryid'])) {
            $where[] = 'c.category = :categoryid';
            $params['categoryid'] = $options['categoryid'];
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Check course visibility for the current viewer.
     *
     * @param \stdClass $course Course record.
     * @param \stdClass $viewer Viewer user record.
     * @param array $options Normalized options.
     * @return bool
     */
    private static function viewer_can_access_course(\stdClass $course, \stdClass $viewer, array $options): bool {
        if (empty($options['showhidden']) && empty($course->visible)) {
            return false;
        }

        if (function_exists('can_access_course')) {
            return can_access_course($course, $viewer);
        }

        return !empty($course->visible);
    }
}
