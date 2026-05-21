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
 * Completed course block.
 *
 * @package    block_completedcourse
 * @copyright  2022 Tengku Alauddin <din@pukunui.com>
 * @author     Vinny Stocker <vinny@pukunui.com>
 * @copyright  2026 Pukunui Malaysia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_completedcourse\local\completion_service;

/**
 * Displays completed courses for the current user.
 */
class block_completedcourse extends block_base {
    /** @var int Default courses displayed per block page. */
    private const DEFAULT_ROW_LIMIT = 10;

    /**
     * Initialise block title.
     */
    public function init() {
        $this->title = get_string('completedcourse', 'block_completedcourse');
    }

    /**
     * Allow multiple block instances.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Allow per-instance configuration.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Build block content.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $context = $this->context ?? context_block::instance($this->instance->id);
        if (!has_capability('block/completedcourse:viewown', $context)) {
            return $this->content;
        }

        $options = completion_service::normalise_options((array)($this->config ?? new stdClass()));
        $page = optional_param('ccpage', 0, PARAM_INT);
        $page = max(0, $page);

        $total = completion_service::count_completed_courses($USER->id, $USER, $options);
        if ($total === 0) {
            $this->content->text = get_string('nocompletedcourse', 'block_completedcourse');
            return $this->content;
        }

        $courses = completion_service::get_completed_courses(
            $USER->id,
            $USER,
            $options,
            $page * $options['rowlimit'],
            $options['rowlimit']
        );

        $rows = [];
        foreach ($courses as $course) {
            $rows[] = [
                'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->courseid]))->out(false),
                'coursename' => format_string(completion_service::get_course_display_name($course, $options['displayname'])),
                'coursenamehtml' => $this->format_breakable_text(
                    format_string(completion_service::get_course_display_name($course, $options['displayname']))
                ),
                'categoryname' => format_string($course->categoryname),
                'completedon' => userdate($course->timecompleted, $options['dateformat']),
                'grade' => completion_service::get_grade_display($USER->id, $course->courseid),
                'showcategory' => $options['showcategory'],
                'showgrade' => $options['showgrade'],
                'linktarget' => $options['linktarget'],
            ];
        }

        $baseurl = new moodle_url($this->page->url, ['ccpage' => null]);
        $exporturl = new moodle_url('/blocks/completedcourse/export.php', [
            'instanceid' => $this->instance->id,
            'sesskey' => sesskey(),
        ]);

        $this->content->text = $OUTPUT->render_from_template('block_completedcourse/completed_courses', [
            'rows' => array_values($rows),
            'showcategory' => $options['showcategory'],
            'showgrade' => $options['showgrade'],
            'exporturl' => $exporturl->out(false),
            'canexport' => has_capability('block/completedcourse:export', $context),
        ]);

        if ($total > $options['rowlimit']) {
            $this->content->text .= $OUTPUT->paging_bar($total, $page, $options['rowlimit'], $baseurl, 'ccpage');
        }

        return $this->content;
    }

    /**
     * Format text with safe break opportunities for narrow block regions.
     *
     * @param string $text Text to format.
     * @return string HTML-safe text with word break hints.
     */
    private function format_breakable_text(string $text): string {
        return str_replace(['_', '/', '-'], ['_<wbr>', '/<wbr>', '-<wbr>'], s($text));
    }
}
