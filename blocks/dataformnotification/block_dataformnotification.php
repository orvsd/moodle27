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
 * @package block_dataformnotification
 * @copyright 2014 Itamar Tzadok {@link http://substantialmethods.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die;

/**
 *
 */
class block_dataformnotification extends block_base {

    public $dataformid;

    static public function get_extra_capabilities() {
        $capabilities = array();

        $capabilities[] = 'mod/dataform:notification';

        return $capabilities;
    }

    /**
     * Set the applicable formats for this block.
     * @return array
     */
    public function applicable_formats() {
        return array('mod-dataform-notification-index' => true);
    }

    /**
     *
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_dataformnotification');
    }

    /**
     *
     */
    public function specialization() {
        global $DB;

        if (!empty($this->config->name)) {
            $this->title = $this->config->name;
        }
        $dataformcmid = $DB->get_field('context', 'instanceid', array('id' => $this->instance->parentcontextid));
        $this->dataformid = $DB->get_field('course_modules', 'instance', array('id' => $dataformcmid));
    }

    /**
     *
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     *
     */
    public function get_content() {
        return null;
    }

    /**
     * Returns true if the entry passed in the data meets the rule filter criteria.
     *
     * @param array $data Expects event name.
     * @return bool
     */
    public function is_applicable(array $data) {
        if (empty($data['event'])) {
            return false;
        }

        $eventname = str_replace('\mod_dataform\event\\', '', $data['event']);
        if (!in_array($eventname, $this->config->events)) {
            return false;
        }
        return true;
    }

    /**
     * Returns array of applicable events.
     *
     * @return array
     */
    public function get_applicable_events() {
        global $CFG;

        $eventnames = array();
        foreach (get_directory_list("$CFG->dirroot/mod/dataform/classes/event") as $filename) {
            if (strpos($filename, '_base.php') !== false) {
                continue;
            }
            $name = basename($filename, '.php');
            $eventnames[] = $name;
        }

        $events = array();
        foreach ($eventnames as $name) {
            $events[$name] = get_string('event_'. $name, 'dataform');
        }
        return $events;
    }
}
