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

class block_dataformnotification_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $notificationformhelper = '\mod_dataform\pluginbase\dataformnotificationform_helper';
        $notificationformhelper::general_definition($mform, $this->block->dataformid, 'config_');

        // Events selector.
        $events = $this->block->get_applicable_events();
        $select = &$mform->addElement('select', 'config_events', get_string('events', 'dataform'), $events);
        $select->setMultiple(true);
        $mform->addRule('config_events', null, 'required', null, 'client');

        $notificationformhelper::notification_definition($mform, $this->block->dataformid, 'config_');
    }

    /**
     *
     */
    public function validation($data, $files) {
        if ($errors = parent::validation($data, $files)) {
            return $errors;
        }

        $notificationformhelper = '\mod_dataform\pluginbase\dataformnotificationform_helper';
        if ($errors = $notificationformhelper::general_validation($data, $files, 'config_')) {
            return $errors;
        }

        if ($errors = $notificationformhelper::notification_validation($data, $files, 'config_')) {
            return $errors;
        }

    }
}
