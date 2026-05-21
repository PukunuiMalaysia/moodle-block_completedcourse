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
 * Completed course block instance settings.
 *
 * @package    block_completedcourse
 * @author     Vinny Stocker <vinny@pukunui.com>
 * @copyright  2026 Pukunui Malaysia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Instance settings form.
 */
class block_completedcourse_edit_form extends block_edit_form {
    /**
     * Add block settings.
     *
     * @param MoodleQuickForm $mform Form instance.
     */
    protected function specific_definition($mform) {
        global $DB;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_completedcourse'));

        $displayoptions = [
            'shortname' => get_string('display_shortname', 'block_completedcourse'),
            'fullname' => get_string('display_fullname', 'block_completedcourse'),
            'both' => get_string('display_both', 'block_completedcourse'),
        ];
        $mform->addElement('select', 'config_displayname', get_string('displayname', 'block_completedcourse'), $displayoptions);
        $mform->setDefault('config_displayname', 'shortname');

        $sortoptions = [
            'DESC' => get_string('sort_desc', 'block_completedcourse'),
            'ASC' => get_string('sort_asc', 'block_completedcourse'),
        ];
        $mform->addElement('select', 'config_sortdirection', get_string('sortdirection', 'block_completedcourse'), $sortoptions);
        $mform->setDefault('config_sortdirection', 'DESC');

        $mform->addElement('text', 'config_rowlimit', get_string('rowlimit', 'block_completedcourse'));
        $mform->setType('config_rowlimit', PARAM_INT);
        $mform->setDefault('config_rowlimit', 10);

        $categories = [0 => get_string('allcategories', 'block_completedcourse')];
        $records = $DB->get_records('course_categories', null, 'name ASC', 'id, name');
        foreach ($records as $category) {
            $categories[$category->id] = format_string($category->name);
        }
        $mform->addElement('select', 'config_categoryid', get_string('categoryfilter', 'block_completedcourse'), $categories);
        $mform->setDefault('config_categoryid', 0);

        $mform->addElement('text', 'config_dateformat', get_string('dateformat', 'block_completedcourse'));
        $mform->setType('config_dateformat', PARAM_TEXT);
        $mform->setDefault('config_dateformat', get_string('strftimedate', 'core_langconfig'));

        $mform->addElement('advcheckbox', 'config_showcategory', get_string('showcategory', 'block_completedcourse'));
        $mform->setDefault('config_showcategory', 0);

        $mform->addElement('advcheckbox', 'config_showgrade', get_string('showgrade', 'block_completedcourse'));
        $mform->setDefault('config_showgrade', 0);

        $mform->addElement('advcheckbox', 'config_showhidden', get_string('showhidden', 'block_completedcourse'));
        $mform->setDefault('config_showhidden', 0);

        $mform->addElement('advcheckbox', 'config_linktarget', get_string('opennewtab', 'block_completedcourse'));
        $mform->setDefault('config_linktarget', 1);
    }
}
