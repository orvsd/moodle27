<?php
// This file is part of Moodle - http://moodle.org/.
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
 * @package dataformview
 * @subpackage grid
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformview_grid_grid extends mod_dataform\pluginbase\dataformview {

    protected $_editors = array('section', 'param2');

    /**
     *
     */
    public static function get_file_areas() {
        return array('section', 'param2');
    }

    /**
     * Generates the default entry template for a new view instance or when reseting an existing instance.
     *
     * @return void
     */
    public function set_default_entry_template($content = null) {
        // get all the fields
        if (!$fields = $this->df->field_manager->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        if ($content === null) {
            // Set the entry template
            $table = new html_table();
            $table->attributes['cellpadding'] = '2';
            // fields
            foreach ($fields as $field) {
                if ($field->id > 0) {
                    $name = new html_table_cell($field->name. ':');
                    $name->style = 'text-align:right;width:100px;';
                    $content = new html_table_cell("[[{$field->name}]]");
                    $row = new html_table_row();
                    $row->cells = array($name, $content);
                    $table->data[] = $row;
                }
            }
            // actions
            $row = new html_table_row();
            $entryactions = get_string('fieldname', 'dataformfield_entryactions');
            $actions = new html_table_cell("[[$entryactions:edit]]  [[$entryactions:delete]]");
            $actions->colspan = 2;
            $row->cells = array($actions);
            $table->data[] = $row;
            // construct the table
            $entrydefault = html_writer::table($table);
            $content = html_writer::tag('div', $entrydefault, array('class' => 'entry'));
        }
        $this->param2 = $content;
    }

    /**
     *
     */
    protected function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;

        $elements = array();

        // Prepare grid table if needed
        if ($name != 'newentry' and $this->param3) {
            $entriescount = count($entriesset);
            list($cols, $rows) = explode(' ', $this->param3);
            if ($entriescount < $cols) {
                $cols = $entriescount;
                $rows = 1;
            } else {
                if ($rows) {
                    $rows = ceil($entriescount / $cols);
                } else {
                    $rows = 1;
                    $percol = ceil($entriescount / $cols) > 1 ? ceil($entriescount / $cols) : null;
                }
            }

            $table = $this->make_table($cols, $rows);
            $grouphtml = html_writer::table($table);
            // now split $tablehtml to cells by ##begintablecell##
            $cells = explode('##begintablecell##', $grouphtml);
            // the first part is everything before first cell
            $elements[] = array_shift($cells);
        }

        // flatten the set to a list of elements
        $count = 0;
        foreach ($entriesset as $entrydefinitions) {
            $elements = array_merge($elements, $entrydefinitions);
            if (!empty($cells)) {
                if (empty($percol) or $count >= $percol - 1) {
                    $count = 0;
                    $elements[] = array_shift($cells);

                } else {
                    $count++;
                }
            }
        }

        // Add remaining cells
        if (!empty($cells)) {
            foreach ($cells as $cell) {
                $elements[] = $cell;
            }
        }

        // Add group heading
        $name = ($name == 'newentry') ? get_string('entrynew', 'dataform') : $name;
        if ($name) {
            array_unshift($elements, $OUTPUT->heading($name, 3, 'main'));
        }

        return $elements;
    }

    /**
     *
     */
    protected function entry_definition($fielddefinitions, array $options = null) {
        $elements = array();

        // If not editing, do simple replacement and return the html
        if (empty($options['edit'])) {
            $elements[] = str_replace(array_keys($fielddefinitions), $fielddefinitions, $this->param2);
            return $elements;
        }

        // Editing so split the entry template to tags and html
        $tags = array_keys($fielddefinitions);
        $parts = $this->split_tags($tags, $this->param2);

        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($def = $fielddefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = $part;
            }
        }
        return $elements;
    }

    /**
     *
     */
    protected function new_entry_definition($entryid = -1) {
        $elements = array();

        // get patterns definitions
        $fields = $this->get_fields();
        $tags = array();
        $patterndefinitions = array();
        $entry = new object;

        if ($fieldpatterns = $this->get_pattern_set('field')) {
            foreach ($fieldpatterns as $fieldid => $patterns) {
                $field = $fields[$fieldid];
                $entry->id = $entryid;
                $options = array('edit' => true);
                if ($fielddefinitions = $field->get_definitions($patterns, $entry, $options)) {
                    $patterndefinitions = array_merge($patterndefinitions, $fielddefinitions);
                }
                $tags = array_merge($tags, $patterns);
            }
        }

        // split the entry template to tags and html
        $parts = $this->split_tags($tags, $this->param2);

        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($def = $patterndefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = $part;
            }
        }

        return $elements;
    }

    /**
     *
     */
    protected function make_table($cols, $rows) {
        $table = new html_table();
        $table->align = array_fill(0, $cols, 'center');
        // $table->wrap = array_fill(0, $cols, 'false');
        $table->attributes['align'] = 'center';
        for ($r = 0; $r < $rows; $r++) {
            $row = new html_table_row();
            for ($c = 0; $c < $cols; $c++) {
                $cell = new html_table_cell();
                $cell->text = '##begintablecell##';
                $row->cells[] = $cell;
            }
            $table->data[] = $row;
        }

        return $table;
    }

}
