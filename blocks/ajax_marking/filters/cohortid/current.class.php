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
 * These classes provide filters that modify the dynamic query that fetches the nodes. Depending on
 * what node is being requested and what that node's ancestor nodes are, a different combination
 * of filters will be applied. There is one class per type of node, and one method with the class
 * for the type of operation. If there is a courseid node as an ancestor, we want to use the
 * courseid::where_filter, but if we are asking for courseid nodes, we want the
 * courseid::count_select filter.
 *
 * @package    block
 * @subpackage ajax_marking
 * @copyright  2012 Matt Gibson
 * @author     Matt Gibson {@link http://moodle.org/user/view.php?id=81450}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/ajax_marking/filters/base.class.php');

/**
 * Applies the filter needed for course nodes or their descendants
 */
class block_ajax_marking_filter_cohortid_current extends block_ajax_marking_query_decorator_base {

    /**
     * Applies the filter needed for course nodes or their descendants
     */
    protected function alter_query() {

        // What do we need for the nodes?
        $table = array(
            'join' => 'INNER JOIN',
            'table' => 'cohort',
            'on' => 'countwrapperquery.id = cohort.id'
        );
        $this->wrappedquery->add_from($table);
        $conditions = array(
            'table' => 'cohort',
            'column' => 'name');
        $this->wrappedquery->add_select($conditions);
        $conditions = array(
            'table' => 'cohort',
            'column' => 'description');
        $this->wrappedquery->add_select($conditions);

        $this->wrappedquery->add_orderby('cohort.name ASC');
    }


}
