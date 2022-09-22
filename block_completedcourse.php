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
 * Completed course block
 *
 * @package    block_course_summary
 * @copyright  2022 Tengku Alauddin <din@pukunui.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_completedcourse extends block_base {
    public function init() {
        $this->title = get_string('completedcourse', 'block_completedcourse');
    }

    public function instance_allow_multiple() {
      return true;
    }

    public function get_content() {
        global $USER, $DB;

        $id = optional_param('id', 0, PARAM_INT);

        // Load user.
        if ($id) {
            $user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);
        } else {
            $user = $USER;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';
        
        //Get data
        $sql = 'SELECT *, c.id as courseid FROM {course_completions} cc
        JOIN {course} c on cc.course = c.id
        WHERE USERID = :userid AND timecompleted IS NOT NULL 
        ORDER BY timecompleted';

        $completedcourse = $DB->get_records_sql($sql, array('userid' => $user->id));
        if (empty($completedcourse)){
            $this->content->text = get_string('nocompletedcourse', 'block_completedcourse');
            return $this->content;
        }

        $table = new html_table();
        $table->width = '100%';
        $table->attributes = array('style'=>'font-size: 90%;', 'class'=>'');

        //header
        $row = new html_table_row();        
        $row->cells[0] = get_string('no', 'block_completedcourse');
        $row->cells[1] = get_string('course', 'block_completedcourse');
        $row->cells[2] = get_string('completedon', 'block_completedcourse');
        $rows[] = $row;

        //render data
        $no = 1;
        foreach ($completedcourse as $c){
            $row = new html_table_row();
            $row->cells[0] = $no;
            $row->cells[1] = html_writer::tag('span',
                            html_writer::link( // Course shortname link.
                                new moodle_url('/course/view.php',['id' => $c->courseid]),
                                $c->shortname,
                                ['target'=>'_blank']
                            ),
                            ['class'=>'coursename']);
            $row->cells[2] = date('d F Y', $c->timecompleted);
            $rows[] = $row;
            $no++;
        }

        $table->data = $rows;
        $this->content->text .= html_writer::table($table);
        // $this->content->footer = html_writer::empty_tag('br');
        return $this->content;
    }
}