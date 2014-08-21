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
 * Steps definitions related with the dataform activity.
 *
 * @package    mod_dataform
 * @category   tests
 * @copyright  2013 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode;
/**
 * Dataform-related steps definitions.
 *
 * @package    mod_dataform
 * @category   tests
 * @copyright  2013 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_dataform extends behat_base {

    /**
     * Each element specifies:
     * - The data generator sufix used.
     * - The required fields.
     * - The mapping between other elements references and database field names.
     * @var array
     */
    protected static $elements = array(
        'views' => array(
            'datagenerator' => 'view',
            'required' => array('type', 'dataform', 'name'),
            'switchids' => array('dataform' => 'dataid'),
        ),
        'fields' => array(
            'datagenerator' => 'field',
            'required' => array('type', 'dataform', 'name'),
            'switchids' => array('dataform' => 'dataid'),
        ),
        'filters' => array(
            'datagenerator' => 'filter',
            'required' => array('dataform', 'name'),
            'switchids' => array('dataform' => 'dataid'),
        ),
        'entries' => array(
            'datagenerator' => 'entry',
            'required' => array('dataform'),
            'switchids' => array('dataform' => 'dataid', 'user' => 'userid', 'group' => 'groupid'),
        ),
    );

    /**
     * Runs the specified scenario if exists.
     *
     * @Given /^I run dataform scenario "(?P<scenario_name_string>(?:[^"]|\\")*)" with:$/
     * @param string $name
     * @param TableNode $data
     */
    public function i_run_dataform_scenario_with($name, $data) {
        $scenarioname = 'scenario_'. str_replace(' ', '_', $name);
        if (method_exists($this, $scenarioname)) {
            return $this->$scenarioname($data);
        }
        return array();
    }


    /**
     * Resets (truncates) all dataform tables to remove any records and reset sequences.
     * This set of steps is essential for any standalone scenario that adds entries with content
     * since such a scenario has to refer to input elements by the name field_{fieldid}_{entryid}
     * (or field_{fieldid}_-1 for a new entry) and the ids have to persist between runs.
     *
     * @Given /^a fresh site for dataform scenario$/
     * @return array
     */
    public function start_afresh_steps() {
        global $DB;

        // Delete properly any existing dataform instances.
        if ($dataforms = $DB->get_records_menu('dataform', array(), '', 'id, id AS did')) {
            foreach ($dataforms as $dataformid) {
                mod_dataform_dataform::instance($dataformid)->delete();
            }
        }

        // Clean up tables
        $tables = array(
            'dataform',
            'dataform_contents',
            'dataform_entries',
            'dataform_fields',
            'dataform_filters',
            'dataform_views',
        );

        $prefix = $DB->get_prefix();
        foreach ($tables as $table) {
            $DB->execute("TRUNCATE TABLE {$prefix}{$table}");
        }

        $steps = array();

        // Course
        $data = array(
            '| fullname | shortname | category  |',
            '| Course 1 | C1        | 0         |',
        );
        $table = new Behat\Gherkin\Node\TableNode(implode("\n", $data));
        $steps[] = new Given('the following "courses" exist:', $table);

        // Users
        $data = array(
            '| username     | firstname | lastname  | email                 |',
            '| teacher1     | Teacher   | 1         | teacher1@asd.com      |',
            '| assistant1   | Assistant | 1         | assistant1@asd.com    |',
            '| assistant2   | Assistant | 1         | assistant2@asd.com    |',
            '| student1     | Student   | 1         | student1@asd.com      |',
            '| student2     | Student   | 2         | student2@asd.com      |',
            '| student3     | Student   | 3         | student3@asd.com      |',
        );
        $table = new Behat\Gherkin\Node\TableNode(implode("\n", $data));
        $steps[] = new Given('the following "users" exist:', $table);

        // Enrollments
        $data = array(
            '| user         | course | role             |',
            '| teacher1     | C1     | editingteacher   |',
            '| assistant1   | C1     | teacher          |',
            '| student1     | C1     | student          |',
            '| student2     | C1     | student          |',
        );
        $table = new Behat\Gherkin\Node\TableNode(implode("\n", $data));
        $steps[] = new Given('the following "course enrolments" exist:', $table);

        // Groups
        $data = array(
            '| name    | description | course  | idnumber |',
            '| Group 1 | Anything    | C1 | G1   |',
            '| Group 2 | Anything    | C1 | G2   |',
        );
        $table = new Behat\Gherkin\Node\TableNode(implode("\n", $data));
        $steps[] = new Given('the following "groups" exist:', $table);

        // Group members
        $data = array(
            '| user     | group  |',
            '| student1 | G1 |',
            '| student2 | G2 |',
        );
        $table = new Behat\Gherkin\Node\TableNode(implode("\n", $data));
        $steps[] = new Given('the following "group members" exist:', $table);

        return $steps;
    }

    /**
     * Starts afresh with a dataform activity 'Test dataform' in Course 1.
     * See {@link behat_mod_dataform::i_start_afresh()}.
     *
     * @Given /^a fresh site with dataform "(?P<dataform_name_string>(?:[^"]|\\")*)"$/
     * @Given /^I start afresh with dataform "(?P<dataform_name_string>(?:[^"]|\\")*)"$/
     * @param string $name
     */
    public function i_start_afresh_with_dataform($name) {
        $steps = $this->start_afresh_steps();

        // Test dataform
        $data = array(
            '| activity | course | idnumber | name                 | intro                       |',
            "| dataform   | C1     | dataform1  | $name | Test dataform description |",
        );
        $table = new Behat\Gherkin\Node\TableNode(implode("\n", $data));
        $steps[] = new Given('the following "activities" exist:', $table);

        return $steps;
    }

    /**
     * Creates dataform fields.
     *
     * @Given /^the following dataform "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     *
     * @param TableNode $data
     */
    public function the_following_dataform_exist($elementname, TableNode $data) {
        // Now that we need them require the data generators.
        require_once(__DIR__ . '/../generator/lib.php');

        $generator = testing_util::get_data_generator()->get_plugin_generator('mod_dataform');

        $elementdatagenerator = self::$elements[$elementname]['datagenerator'];
        $requiredfields = self::$elements[$elementname]['required'];
        if (!empty(self::$elements[$elementname]['switchids'])) {
            $switchids = self::$elements[$elementname]['switchids'];
        }

        foreach ($data->getHash() as $elementdata) {

            // Check if all the required fields are there.
            foreach ($requiredfields as $requiredfield) {
                if (!isset($elementdata[$requiredfield])) {
                    throw new Exception($elementname . ' requires the field ' . $requiredfield . ' to be specified');
                }
            }

            // Switch from human-friendly references to ids.
            if (isset($switchids)) {
                foreach ($switchids as $element => $field) {
                    $methodname = 'get_' . $element . '_id';

                    // Not all the switch fields are required, default vars will be assigned by data generators.
                    if (isset($elementdata[$element])) {
                        // Temp $id var to avoid problems when $element == $field.
                        $id = $this->{$methodname}($elementdata[$element]);
                        unset($elementdata[$element]);
                        $elementdata[$field] = $id;
                    }
                }
            }

            // Creates element.
            $methodname = 'create_' . $elementdatagenerator;
            if (method_exists($generator, $methodname)) {
                // Using data generators directly.
                $generator->{$methodname}($elementdata);

            } else if (method_exists($this, 'process_' . $elementdatagenerator)) {
                // Using an alternative to the direct data generator call.
                $this->{'process_' . $elementdatagenerator}($elementdata);
            } else {
                throw new Exception($elementname . ' data generator is not implemented');
            }
        }
    }

    // ACTIVITY SETUP STEPS

    /**
     * Adds a dataform as teacher 1 in course 1 and displays the dataform.
     * The step begins in a new test site.
     *
     * @Given /^I add a dataform with "(?P<dataform_url_string>(?:[^"]|\\")*)"$/
     * @param string $data
     */
    public function i_add_a_dataform_with($data) {
        $steps = array();
        $steps[] = new Given('I log in as "teacher1"');
        $steps[] = new Given('I follow "Course 1"');
        $steps[] = new Given('I turn editing mode on');
        $steps[] = new Given('I add a "Dataform" to section "1"');
        $steps[] = new Given('I expand all fieldsets');

        $steps = array_merge($steps, $this->dataform_form_fill_steps($data));

        $steps[] = new Given('I press "Save and return to course"');

        return $steps;
    }

    /**
     * Validates dataform activity settings.
     * The step begins in the activity form.
     *
     * @Then /^the dataform settings should match "(?P<form_data_string>(?:[^"]|\\")*)"$/
     * @param string $data
     */
    public function the_dataform_settings_should_match($data) {
        return $this->dataform_form_match_steps($data);
    }

    /**
     * Adds a test dataform as teacher 1 in course 1 and displays the dataform.
     * The step begins in a new test site.
     *
     * @Given /^I add a test dataform$/
     * @param string $dataformname
     * @param TableNode $table
     */
    public function i_add_a_test_dataform() {
        $steps = array();
        $steps[] = new Given('I log in as "teacher1"');
        $steps[] = new Given('I follow "Course 1"');
        $steps[] = new Given('I turn editing mode on');
        $steps[] = new Given('I add a "Dataform" to section "1"');

        $data = array(
            'Name | Test Dataform',
        );
        $table = new Behat\Gherkin\Node\TableNode(implode("\n", $data));
        $steps[] = new Given('I set the following fields to these values:', $table);

        $steps[] = new Given('I press "Save and display"');

        return $steps;
    }

    /**
     * Deletes the dataform.
     * This step begins inside the designated dataform.
     * Useful at the end of standalone scenarios for cleanup.
     *
     * @Given /^I delete this dataform$/
     */
    public function i_delete_this_dataform() {
        $steps = array();

        $steps[] = new Given('I follow "Delete activity"');
        $steps[] = new Given('I see "Are you absolutely sure you want to completely delete Dataform"');
        $steps[] = new Given('I press "Yes"');
        $steps[] = new Given('I wait to be redirected');

        return $steps;
    }

    /**
     * Go to the specified manage tab of the current dataform.
     * The step begins from the dataform's course page.
     *
     * @Given /^I go to manage dataform "(?P<tab_name_string>(?:[^"]|\\")*)"$/
     * @param string $tabname
     */
    public function i_go_to_manage_dataform($tabname) {
        $node = get_string($tabname, 'dataform');
        $path = "Dataform activity administration > Manage";
        return array(
            new Given('I navigate to "'. $node. '" node in "'. $path. '"'),
        );
    }

    // FIELD

    /**
     * Adds a field of the specified type to the current dataform with the provided table data (usually Name).
     * The step begins in the dataform fields index.
     *
     * @Given /^I add a dataform field "(?P<field_type_string>(?:[^"]|\\")*)" with "(?P<form_data_string>(?:[^"]|\\")*)"$/
     * @param string $type
     * @param string $data
     */
    public function i_add_a_dataform_field_with($type, $data) {
        $fieldclass = 'dataformfield_'. $type;
        $pluginname = get_string('pluginname', $fieldclass);

        $steps = array();

        // Open the form
        $steps[] = new Given('I set the field "' . get_string('fieldadd', 'dataform'). '" to "'. $pluginname. '"');

        // Fill the form
        $func = "field_form_fill_steps_$type";
        $func = method_exists($this, $func) ? $func : "field_form_fill_steps_base";
        $steps = array_merge($steps, $this->$func($data));

        // Save
        $steps[] = new Given('I press "' . get_string('savechanges') . '"');
        $steps[] = new Given('I wait to be redirected');

        return $steps;
    }

    // VIEW.

    /**
     * Adds a view of the specified type to the current dataform with the provided table data (usually Name).
     * The step begins in the dataform's Manage | Views tab.
     *
     * @Given /^I add a dataform view "(?P<view_type_string>(?:[^"]|\\")*)" with "(?P<form_data_string>(?:[^"]|\\")*)"$/
     * @param string $type
     * @param string $data
     */
    public function i_add_a_dataform_view_with($type, $data) {
        $viewclass = 'dataformview_'. $type;
        $pluginname = get_string('pluginname', $viewclass);

        $steps = array();

        // Open the form
        $steps[] = new Given('I set the field "' . get_string('viewadd', 'dataform'). '" to "'. $pluginname. '"');

        // Fill the form
        $formfields = array(
            'Name',
            'Description',
            'Visibility',
            'Filter',
            'Per page',
            'section_editor[text]',
            'param1',
            'param2',
            'param3',
            'param4',
            'param5',
            'param6',
            'param7',
            'param8',
            'param9',
            'param10',
        );
        $table = $this->convert_data_to_table($formfields, $data);
        $steps[] = new Given('I set the following fields to these values:', $table);

        // Save
        $steps[] = new Given('I press "' . get_string('savechanges') . '"');
        $steps[] = new Given('I wait to be redirected');

        return $steps;
    }

    /**
     * Sets a view as the default view of a dataform instance.
     * The step begins in the dataform's Manage | Views tab
     * with the designated view (by name) already added.
     *
     * @Given /^I set "(?P<view_name_string>(?:[^"]|\\")*)" as default view$/
     * @param string $name
     */
    public function i_set_as_default_view($name) {
        // Click the Default button of the view
        $idsetdefault = 'id_'. str_replace(' ', '_', $name). '_set_default';
        $steps[] = new Given('I follow "' . $idsetdefault. '"');

        return $steps;
    }

    /**
     * Sets the view's view template to specified text passed as PyStringNode.
     * Useful for setting textareas.
     * The step begins in a form.
     *
     * @Given /^view "(?P<view_name_string>(?:[^"]|\\")*)" in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" has the following view template:$/
     * @param string $viewname
     * @param string $dataformid
     * @param Behat\Gherkin\Node\PyStringNode $content
     */
    public function view_in_dataform_has_the_following_view_template($viewname, $dataformid, Behat\Gherkin\Node\PyStringNode $content) {
        $df = mod_dataform_dataform::instance($dataformid);
        $view = $df->view_manager->get_view_by_name($viewname);
        $view->set_default_view_template($content->__toString());
        $view->update($view->data);
    }

    /**
     * Sets the view's entry template to specified text passed as PyStringNode.
     * The step begins in a form.
     *
     * @Given /^view "(?P<view_name_string>(?:[^"]|\\")*)" in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" has the following entry template:$/
     * @param string $viewname
     * @param string $dataformid
     * @param Behat\Gherkin\Node\PyStringNode $content
     */
    public function view_in_dataform_has_the_following_entry_template($viewname, $dataformid, Behat\Gherkin\Node\PyStringNode $content) {
        $df = mod_dataform_dataform::instance($dataformid);
        $view = $df->view_manager->get_view_by_name($viewname);
        $view->set_default_entry_template($content->__toString());
        $view->update($view->data);
    }

    /**
     * Sets the css template of the specified dataform to the text passed as PyStringNode.
     *
     * @Given /^dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" has the following css:$/
     * @param string $dataformid
     * @param Behat\Gherkin\Node\PyStringNode $content
     */
    public function dataform_has_the_following_css($dataformid, Behat\Gherkin\Node\PyStringNode $content) {
        $rec = new stdClass;
        $rec->css = $content->__toString();
        $df = mod_dataform_dataform::instance($dataformid);
        $df->update($rec);
    }

    // FILTER.

    /**
     * Adds a filter with the specified data to the current dataform.
     * The step begins in the dataform's Manage | Filters tab.
     *
     * @Given /^I add a dataform filter with "(?P<form_data_string>(?:[^"]|\\")*)"$/
     * @param string $data
     */
    public function i_add_a_dataform_filter_with($data) {

        $steps = array();

        // Open the form
        $steps[] = new Given('I follow "'. get_string('filteradd', 'dataform'). '"');

        // Fill the form
        $formfields = array(
            'Name',
            'Description',
            'Visible',
            'Per page',
            'sortfield0',
            'sortdir0',
            'sortfield1',
            'sortdir1',
            'sortfield2',
            'sortdir2',
            'Search',
            'searchandor0',
            'searchfield0',
            'searchnot0',
            'searchoperator0',
            'searchvalue0',
            'searchandor1',
            'searchfield1',
            'searchnot1',
            'searchoperator1',
            'searchvalue1',
            'searchandor2',
            'searchfield2',
            'searchnot2',
            'searchoperator2',
            'searchvalue2',
        );
        $table = $this->convert_data_to_table($formfields, $data);
        $steps[] = new Given('I set the following fields to these values:', $table);

        // Save
        $steps[] = new Given('I press "' . get_string('savechanges') . '"');
        $steps[] = new Given('I wait to be redirected');

        return $steps;
    }


    /**
     * Sets a sort criterion in the dataform filter.
     * The step begins in the dataform filter form.
     *
     * @Given /^I set sort criterion "(?P<number_string>(?:[^"]|\\")*)" to "(?P<filter_element_string>(?:[^"]|\\")*)" "(?P<sort_direction_string>(?:[^"]|\\")*)"$/
     * @param string $number
     * @param string $fieldelement fieldid,elementname (e.g. 1,content)
     * @param string $direction 0|1 (Ascending|Descending)
     */
    public function i_set_sort_criterion_to($number, $fieldelement, $direction) {

        $steps = array();

        $i = (int) $number - 1;
        $sortfield = "sortfield$i";
        $sortdir = "sortdir$i";

        $steps[] = new Given('I set the field "'. $sortfield. '" to "'. $fieldelement. '"');
        $steps[] = new Given('I set the field "'. $sortdir. '" to "'. $direction. '"');

        return $steps;
    }

    /**
     * Sets a search criterion in the dataform filter.
     * The step begins in the dataform filter form.
     *
     * @Given /^I set search criterion "(?P<number_string>(?:[^"]|\\")*)" to "(?P<filter_element_string>(?:[^"]|\\")*)" "(?P<filter_andor_string>(?:[^"]|\\")*)" "(?P<filter_not_string>(?:[^"]|\\")*)" "(?P<filter_operator_string>(?:[^"]|\\")*)" "(?P<filter_value_string>(?:[^"]|\\")*)"$/
     * @param string $number
     * @param string $andor AND|OR
     * @param string $field fieldid,elementname (e.g. 1,content)
     * @param string $not <empty>|NOT
     * @param string $operator <empty>|=|>|<|>=|<=|BETWEEN|LIKE|IN
     */
    public function i_set_search_criterion_to($number, $andor, $field, $not, $operator, $value) {

        $steps = array();

        $i = (int) $number - 1;
        $searchandor = "searchandor$i";
        $searchfield = "searchfield$i";
        $searchnot = "searchnot$i";
        $searchoperator = "searchoperator$i";
        $searchvalue = "searchvalue$i";

        $steps[] = new Given('I set the field "'. $searchandor. '" to "'. $andor. '"');
        $steps[] = new Given('I set the field "'. $searchfield. '" to "'. $field. '"');
        $steps[] = new Given('I set the field "'. $searchnot. '" to "'. $not. '"');
        $steps[] = new Given('I set the field "'. $searchoperator. '" to "'. $operator. '"');
        $steps[] = new Given('I set the field "'. $searchvalue. '" to "'. $value. '"');

        return $steps;
    }


    /**
     * Prepends text to the field's content.
     * The step begins in a form.
     *
     * @Given /^I prepend "(?P<text_string>(?:[^"]|\\")*)" to field "(?P<field_string>(?:[^"]|\\")*)"$/
     * @param string $text
     * @param string $field
     */
    public function i_prepend_to_field($text, $field) {
        $steps = array();

        $fieldnode = $this->find_field($field);
        $value = $fieldnode->getValue();
        $data = "$field | $text. $value";
        $table = new Behat\Gherkin\Node\TableNode($data);
        $steps[] = new Given('I set the following fields to these values:', $table);

        return $steps;
    }

    /**
     * Appends text to the field's content.
     * The step begins in a form.
     *
     * @Given /^I apppend "(?P<text_string>(?:[^"]|\\")*)" to field "(?P<field_string>(?:[^"]|\\")*)"$/
     * @param string $text
     * @param string $locator
     */
    public function i_append_to_field($text, $locator) {
        $node = $this->find_field($field);
        $value = $node->getValue(). $text;
        $field = behat_field_manager::get_form_field($node, $this->getSession());
        $field->set_value($value);
    }

    /**
     * Replaces text in the field's content.
     * The step begins in a form.
     *
     * @Given /^I replace in field "(?P<field_string>(?:[^"]|\\")*)" "(?P<text_string>(?:[^"]|\\")*)" with "(?P<replacement_string>(?:[^"]|\\")*)"$/
     * @param string $locator
     * @param string $text
     * @param string $replacement
     */
    public function i_replace_in_field_with($locator, $text, $replacement) {
        $node = $this->find_field($locator);
        $field = behat_field_manager::get_form_field($node, $this->getSession());
        $value = $field->get_value();
        $value = str_replace($text, $replacement, $value);

        // Hack to remove new line characters from editor field value
        if (get_class($field) == 'behat_form_editor') {
            $value = str_replace(array("\n", "\r"), '', $value);
        }

        $field->set_value($value);
    }

    /**
     * Sets a dataform field setting to the given content.
     * The step begins in the Fields manage tab.
     *
     * @Given /^I set dataform field "(?P<field_name_string>(?:[^"]|\\")*)" options to "(?P<options_string>(?:[^"]|\\")*)"$/
     * @param string $name
     * @param string $content
     */
    public function i_set_dataform_field_options_to($name, $content) {
        $steps = array();

        $steps[] = new Given('I follow "'. $name. '"');
        $steps[] = new Given('I expand all fieldsets');

        $content = implode("\n", explode('\n', $content));
        $steps[] = new Given('I set the field "Options" to "'. $content. '"');
        $steps[] = new Given('I press "Save changes"');

        return $steps;
    }

    /**
     * Sets field value to the specified text passed as PyStringNode.
     * Useful for setting textareas.
     * The step begins in a form.
     *
     * @Given /^I set the field "(?P<field_name_string>(?:[^"]|\\")*)" to$/
     * @param string $name
     * @param Behat\Gherkin\Node\PyStringNode $content
     */
    public function i_set_the_field_to($name, Behat\Gherkin\Node\PyStringNode $content) {
        return array(new Given('I set the field "'. $name. '" to "'. $content. '"'));
    }

    /**
     * Fills a textarea with the specified text replacing \n with new lines.
     * The step begins in a form.
     *
     * @Given /^I fill textarea "(?P<field_string>(?:[^"]|\\")*)" with "(?P<text_string>(?:[^"]|\\")*)"$/
     * @param string $name
     * @param string $content
     */
    public function i_fill_textarea_with($name, $content) {

        $content = implode("\n", explode('\n', $content));

        return array(new Given('I set the field "'. $name. '" to "'. $content. '"'));
    }

    /**
     * Generic press enter on field. Click on the element of the specified type.
     *
     * @When /^I press Enter on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_press_enter_on($element, $selectortype) {

        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->ensure_node_is_visible($node);
        $node->keyPress(13);
    }

    // ACTIVITY PARTICIPATION STEPS

    /**
     * Opens Dataform url.
     *
     * @Given /^I go to dataform page "(?P<dataform_url_string>(?:[^"]|\\")*)"$/
     */
    public function i_go_to_dataform_page($url) {
        $this->getSession()->visit($this->locate_path("/mod/dataform/$url"));
    }

    /**
     * Verifies that a new entry cannot be added neither via button nor via url.
     *
     * @Given /^I cannot add a new entry in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" view "(?P<view_id_string>(?:[^"]|\\")*)"$/
     * @param string $dataformid
     * @param string $viewid
     */
    public function i_cannot_add_a_new_entry_in_dataform_view($dataformid, $viewid) {
        $steps = array();

        $steps[] = new Given('I do not see "Add a new entry"');
        $steps[] = new Given('I go to dataform page "view.php?d='. $dataformid. '&view='. $viewid. '&editentries=-1"');
        $steps[] = new Given('I do not see "Save"');
        $steps[] = new Given('I go to dataform page "view.php?d='. $dataformid. '&view='. $viewid. '"');

        return $steps;
    }

    /**
     * Verifies that an entry cannot be edited neither via edit link nor via url.
     *
     * @Given /^I cannot edit entry "(?P<entry_id_string>(?:[^"]|\\")*)" in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" view "(?P<view_id_string>(?:[^"]|\\")*)"$/
     * @param string $entryid
     * @param string $dataformid
     * @param string $viewid
     */
    public function i_cannot_edit_entry_in_dataform_view($entryid, $dataformid, $viewid) {
        $steps = array();

        $steps[] = new Given('"Edit Entry '. $entryid. '" "link" does not exist');
        $steps[] = new Given('I go to dataform page "view.php?d='. $dataformid. '&view='. $viewid. '&editentries='. $entryid. '"');
        $steps[] = new Given('"Save" "button" does not exist');
        $steps[] = new Given('I go to dataform page "view.php?d='. $dataformid. '&view='. $viewid. '"');

        return $steps;
    }

    /**
     * Verifies that an entry cannot be deleted neither via delete link nor via url.
     *
     * @Given /^I cannot delete entry "(?P<entry_id_string>(?:[^"]|\\")*)" with content "(?P<text_string>(?:[^"]|\\")*)" in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" view "(?P<view_id_string>(?:[^"]|\\")*)"$/
     * @param string $entryid
     * @param string $content
     * @param string $dataformid
     * @param string $viewid
     */
    public function i_cannot_delete_entry_with_content_in_dataform_view($entryid, $content, $dataformid, $viewid) {
        $steps = array();

        $steps[] = new Given('"Delete Entry '. $entryid. '" "link" does not exist');
        $steps[] = new Given('I go to dataform page "view.php?d='. $dataformid. '&view='. $viewid. '&delete='. $entryid. '&sesskey='. sesskey(). '"');
        $steps[] = new Given('I see "'. $content. '"');

        return $steps;
    }

    // REPHRASES

    /**
     * Checks, that page contains specified text. It also checks if the text is visible when running Javascript tests.
     *
     * @Then /^I see "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $text
     */
    public function i_see($text) {
        return (array(new Given('I should see "'. $text. '"')));
    }

    /**
     * Checks, that page doesn't contain specified text. When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I do not see "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $text
     */
    public function i_do_not_see($text) {
        return (array(new Given('I should not see "'. $text. '"')));
    }

    /**
     * Checks the provided element and selector type exists in the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" exists$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     */
    public function exists($element, $selectortype) {
        return (array(new Given('"'. $element. '" "'. $selectortype. '" should exist')));
    }

    /**
     * Checks that the provided element and selector type not exists in the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" does not exist$/
     * @throws ExpectationException
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     */
    public function does_not_exist($element, $selectortype) {
        return (array(new Given('"'. $element. '" "'. $selectortype. '" should not exist')));
    }

    // SCENARIOS
    /**
     * Returns list of steps for manage view scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_manage_view(TableNode $data) {
        $data = $data->getRowsHash();
        $viewtype = $data['viewtype'];

        $steps = array();

        $steps[] = new Given('a fresh site with dataform "Test Dataform"');

        $steps[] = new Given('I log in as "teacher1"');
        $steps[] = new Given('I follow "Course 1"');
        $steps[] = new Given('I follow "Test Dataform"');
        $steps[] = new Given('I follow "Manage"');

        $steps[] = new Given('I follow "Views"');
        $steps[] = new Given('I add a dataform view "'. $viewtype. '" with "View 01"');
        $steps[] = new Given('I see "View 01"');
        $steps[] = new Given('I follow "Delete View 01"');
        $steps[] = new Given('I press "Continue"');
        $steps[] = new Given('I do not see "View 01"');

        return $steps;
    }

    /**
     * Returns list of steps for view required field scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_view_required_field(TableNode $data) {
        $data = $data->getRowsHash();
        $viewtype = $data['viewtype'];
        $entrytemplate = $data['entrytemplate'];

        $steps = array();

        $steps[] = new Given('a fresh site with dataform "Test Dataform"');

        $steps[] = new Given('I log in as "teacher1"');
        $steps[] = new Given('I follow "Course 1"');
        $steps[] = new Given('I follow "Test Dataform"');
        $steps[] = new Given('I follow "Manage"');

        $steps[] = new Given('I follow "Fields"');
        $steps[] = new Given('I add a dataform field "text" with "Text 01"');

        $steps[] = new Given('I follow "Views"');
        $steps[] = new Given('I add a dataform view "'. $viewtype. '" with "View 01"');
        $steps[] = new Given('I follow "Edit View 01"');
        $steps[] = new Given('I expand all fieldsets');
        $steps[] = new Given('I replace in field "'. $entrytemplate. '" "[[Text 01]]" with "[[*Text 01]]"');
        $steps[] = new Given('I press "Save changes"');
        $steps[] = new Given('I set "View 01" as default view');

        $steps[] = new Given('I follow "Browse"');

        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I press "Save"');
        $steps[] = new Given('I see "You must supply a value here."');
        $steps[] = new Given('I set the field "id_field_1_-1" to "The field is required in View 01"');
        $steps[] = new Given('I press "Save"');
        $steps[] = new Given('I see "The field is required in View 01"');

        return $steps;
    }

    /**
     * Returns list of steps for view submission buttons scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_view_submission_buttons(TableNode $data) {
        $data = $data->getRowsHash();
        $viewtype = $data['viewtype'];
        $actor = $data['actor'];

        $steps = array();

        $steps[] = new Given('a fresh site with dataform "Test Dataform"');

        $steps[] = new Given('I log in as "teacher1"');
        $steps[] = new Given('I follow "Course 1"');
        $steps[] = new Given('I follow "Test Dataform"');
        $steps[] = new Given('I follow "Manage"');

        // Add a text field.
        $steps[] = new Given('I follow "Fields"');
        $steps[] = new Given('I add a dataform field "text" with "Field 01"');

        // Add a view with ALL submission buttons.
        $steps[] = new Given('I follow "Views"');
        $steps[] = new Given('I set the field "Add a view" to "'. $viewtype. '"');
        $steps[] = new Given('I expand all fieldsets');
        $steps[] = new Given('I set the field "Name" to "View 01"');
        $steps[] = new Given('I set the field "savecontbuttonenable" to "check"');
        $steps[] = new Given('I set the field "savenewbuttonenable" to "check"');
        $steps[] = new Given('I set the field "savecontnewbuttonenable" to "check"');
        $steps[] = new Given('I set the field "savenewcontbuttonenable" to "check"');
        $steps[] = new Given('I press "Save changes"');
        $steps[] = new Given('I set "View 01" as default view');

        // Add a view with NO submission buttons.
        $steps[] = new Given('I set the field "Add a view" to "'. $viewtype. '"');
        $steps[] = new Given('I expand all fieldsets');
        $steps[] = new Given('I set the field "Name" to "View 02"');
        $steps[] = new Given('I set the field "savebuttonenable" to ""');
        $steps[] = new Given('I set the field "savecontbuttonenable" to ""');
        $steps[] = new Given('I set the field "savenewbuttonenable" to ""');
        $steps[] = new Given('I set the field "savecontnewbuttonenable" to ""');
        $steps[] = new Given('I set the field "savenewcontbuttonenable" to ""');
        $steps[] = new Given('I set the field "cancelbuttonenable" to ""');
        $steps[] = new Given('I press "Save changes"');

        // Go to browse view.
        $steps[] = new Given('I follow "Browse"');

        // Log in as actor if needed.
        if ($actor != 'teacher1') {
            $steps[] = new Given('I log out');
            $steps[] = new Given('I log in as "'. $actor. '"');
            $steps[] = new Given('I follow "Course 1"');
            $steps[] = new Given('I follow "Test Dataform"');
        }

        // SAVE: The entry should be added.
        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I set the field "field_1_-1" to "Entry 01"');
        $steps[] = new Given('I press "Save"');
        $steps[] = new Given('I see "Entry 01"');

        // CANCEL: The entry should not be added.
        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I set the field "field_1_-1" to "Entry 02"');
        $steps[] = new Given('I press "Cancel"');
        $steps[] = new Given('I see "Entry 01"');
        $steps[] = new Given('I do not see "Entry 02"');

        // SAVE and CONTINUE: The entry should be added and should stay in form.
        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I set the field "field_1_-1" to "Entry 03"');
        $steps[] = new Given('I press "Save and Continue"');
        $steps[] = new Given('I do not see "Add a new entry"');
        $steps[] = new Given('I do not see "Entry 01"');
        $steps[] = new Given('the field "field_1_2" matches value "Entry 03"');

        $steps[] = new Given('I set the field "field_1_2" to "Entry 02"');
        $steps[] = new Given('I press "Save"');
        $steps[] = new Given('I see "Add a new entry"');
        $steps[] = new Given('I see "Entry 01"');
        $steps[] = new Given('I see "Entry 02"');

        // SAVE as NEW (existing entry): A new entry should be added.
        $steps[] = new Given('I follow "id_editentry2"');
        $steps[] = new Given('I set the field "field_1_2" to "Entry 03"');
        $steps[] = new Given('I press "Save as New"');
        $steps[] = new Given('I see "Add a new entry"');
        $steps[] = new Given('I see "Entry 01"');
        $steps[] = new Given('I see "Entry 02"');
        $steps[] = new Given('I see "Entry 03"');

        // SAVE as NEW (new entry): The entry should be added.
        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I set the field "field_1_-1" to "Entry 04"');
        $steps[] = new Given('I press "Save as New"');
        $steps[] = new Given('I see "Add a new entry"');
        $steps[] = new Given('I see "Entry 01"');
        $steps[] = new Given('I see "Entry 02"');
        $steps[] = new Given('I see "Entry 03"');
        $steps[] = new Given('I see "Entry 04"');

        // SAVE and START NEW (new entry): The entry should be added and and new entry form opened.
        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I set the field "field_1_-1" to "Entry 05"');
        $steps[] = new Given('I press "Save and Start New"');
        $steps[] = new Given('I do not see "Add a new entry"');
        $steps[] = new Given('the field "field_1_-1" matches value ""');

        $steps[] = new Given('I set the field "field_1_-1" to "Entry 06"');
        $steps[] = new Given('I press "Save"');
        $steps[] = new Given('I see "Add a new entry"');
        $steps[] = new Given('I see "Entry 01"');
        $steps[] = new Given('I see "Entry 02"');
        $steps[] = new Given('I see "Entry 03"');
        $steps[] = new Given('I see "Entry 04"');
        $steps[] = new Given('I see "Entry 05"');
        $steps[] = new Given('I see "Entry 06"');

        // SAVE and START NEW (existing entry): The entry should be updated and new entry form opened.
        $steps[] = new Given('I follow "id_editentry4"');
        $steps[] = new Given('the field "field_1_4" matches value "Entry 04"');

        $steps[] = new Given('I set the field "field_1_4" to "Entry 04 modified"');
        $steps[] = new Given('I press "Save and Start New"');
        $steps[] = new Given('I do not see "Add a new entry"');
        $steps[] = new Given('the field "field_1_-1" matches value ""');

        $steps[] = new Given('I set the field "field_1_-1" to "Entry 07"');
        $steps[] = new Given('I press "Save"');
        $steps[] = new Given('I see "Add a new entry"');
        $steps[] = new Given('I see "Entry 01"');
        $steps[] = new Given('I see "Entry 02"');
        $steps[] = new Given('I see "Entry 03"');
        $steps[] = new Given('I see "Entry 04 modified"');
        $steps[] = new Given('I see "Entry 05"');
        $steps[] = new Given('I see "Entry 06"');
        $steps[] = new Given('I see "Entry 07"');

        // SAVE as NEW and CONTINUE (new entry): The entry should be added and and remain in its form.
        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I set the field "field_1_-1" to "Entry 08"');
        $steps[] = new Given('I press "Save as New and Continue"');
        $steps[] = new Given('I do not see "Add a new entry"');
        $steps[] = new Given('the field "field_1_8" matches value "Entry 08"');

        $steps[] = new Given('I set the field "field_1_8" to "Entry 08 modified"');
        $steps[] = new Given('I press "Save"');
        $steps[] = new Given('I see "Add a new entry"');
        $steps[] = new Given('I see "Entry 01"');
        $steps[] = new Given('I see "Entry 02"');
        $steps[] = new Given('I see "Entry 03"');
        $steps[] = new Given('I see "Entry 04 modified"');
        $steps[] = new Given('I see "Entry 05"');
        $steps[] = new Given('I see "Entry 06"');
        $steps[] = new Given('I see "Entry 07"');
        $steps[] = new Given('I see "Entry 08 modified"');

        // SAVE as NEW and CONTINUE (existing entry): The entry should be added and remain in its form.
        $steps[] = new Given('I follow "id_editentry8"');
        $steps[] = new Given('the field "field_1_8" matches value "Entry 08 modified"');

        $steps[] = new Given('I set the field "field_1_8" to "Entry 09"');
        $steps[] = new Given('I press "Save as New and Continue"');
        $steps[] = new Given('I do not see "Add a new entry"');
        $steps[] = new Given('the field "field_1_9" matches value "Entry 09"');

        $steps[] = new Given('I press "Save"');
        $steps[] = new Given('I see "Add a new entry"');
        $steps[] = new Given('I see "Entry 01"');
        $steps[] = new Given('I see "Entry 02"');
        $steps[] = new Given('I see "Entry 03"');
        $steps[] = new Given('I see "Entry 04 modified"');
        $steps[] = new Given('I see "Entry 05"');
        $steps[] = new Given('I see "Entry 06"');
        $steps[] = new Given('I see "Entry 07"');
        $steps[] = new Given('I see "Entry 08 modified"');
        $steps[] = new Given('I see "Entry 09"');

        // No submission buttons.
        $steps[] = new Given('I follow "View 02"');
        $steps[] = new Given('I do not see "Add a new entry"');
        $steps[] = new Given('"id_editentry1" "link" does not exist');

        // I shouldn't be able to edit via the url

        return $steps;
    }

    /**
     * Returns list of steps for manage field scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_manage_field(TableNode $data) {
        $data = $data->getRowsHash();
        $fieldtype = $data['fieldtype'];
        $fieldname = !empty($data['fieldname']) ? $data['fieldname'] : 'Field 01';

        $steps = array();

        $steps[] = new Given('a fresh site with dataform "Test Dataform"');

        $steps[] = new Given('I log in as "teacher1"');
        $steps[] = new Given('I follow "Course 1"');
        $steps[] = new Given('I follow "Test Dataform"');
        $steps[] = new Given('I follow "Manage"');

        $steps[] = new Given('I follow "Fields"');
        // Add
        $steps[] = new Given('I add a dataform field "'. $fieldtype. '" with "'. $fieldname. '"');
        $steps[] = new Given('I see "'. $fieldname. '"');
        // Edit
        $steps[] = new Given('I follow "Edit '. $fieldname. '"');
        $steps[] = new Given('I see "Editing \''. $fieldname. '\'"');
        $steps[] = new Given('I set the field "Description" to "'. $fieldname. ' modified"');
        $steps[] = new Given('I press "Save changes"');
        $steps[] = new Given('I see "'. $fieldname. ' modified"');
        // Delete
        $steps[] = new Given('I follow "Delete '. $fieldname. '"');
        $steps[] = new Given('I press "Continue"');
        $steps[] = new Given('I do not see "'. $fieldname. '"');

        return $steps;
    }

    /**
     * Returns list of steps for manage access rule scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_manage_access_rule(TableNode $data) {
        $data = $data->getRowsHash();
        $ruletype = $data['ruletype'];
        $typename = get_string('typename', "block_dataformaccess$ruletype");
        $rulename = !empty($data['rulename']) ? $data['rulename'] : "New $typename rule";

        $steps = array();

        $steps[] = new Given('a fresh site with dataform "Test Dataform"');

        $steps[] = new Given('I log in as "teacher1"');
        $steps[] = new Given('I follow "Course 1"');
        $steps[] = new Given('I follow "Test Dataform"');
        $steps[] = new Given('I follow "Manage"');

        $steps[] = new Given('I follow "Access"');

        // Add a rule
        $steps[] = new Given('I follow "id_add_'. $ruletype. '_access_rule"');
        $steps[] = new Given('I see "'. "New $typename rule". '"');

        // Update the rule
        $steps[] = new Given('I follow "id_editaccess'. $ruletype. '1"');
        $steps[] = new Given('I set the field "Name" to "'. "New $typename rule". ' modified"');
        $steps[] = new Given('I press "Save changes"');
        $steps[] = new Given('I see "'. "New $typename rule". ' modified"');

        // Delete the rule
        $steps[] = new Given('I follow "id_deleteaccess'. $ruletype. '1"');
        $steps[] = new Given('I do not see "'. "New $typename rule". ' modified"');

        return $steps;
    }

    /**
     * Returns list of steps for manage notification rule scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_manage_notification_rule(TableNode $data) {
        $data = $data->getRowsHash();
        $ruletype = !empty($data['ruletype']) ? $data['ruletype'] : null;
        $typename = get_string('typename', "block_dataformnotification$ruletype");
        $rulename = !empty($data['rulename']) ? $data['rulename'] : "New $typename rule";

        $steps = array();

        $steps[] = new Given('a fresh site with dataform "Test Dataform"');

        $steps[] = new Given('I log in as "teacher1"');
        $steps[] = new Given('I follow "Course 1"');
        $steps[] = new Given('I follow "Test Dataform"');
        $steps[] = new Given('I follow "Manage"');

        $steps[] = new Given('I follow "Notifications"');

        // Add a rule
        $steps[] = new Given('I follow "id_add_'. $ruletype. '_notification_rule"');
        $steps[] = new Given('I see "'. "New $typename rule". '"');

        // Update the rule
        $steps[] = new Given('I follow "id_editnotification'. $ruletype. '1"');
        $steps[] = new Given('I set the field "Name" to "'. "New $typename rule". ' modified"');
        $steps[] = new Given('I set the field "Events" to "Entry created"');
        $steps[] = new Given('I set the field "Admin" to "Check"');
        $steps[] = new Given('I press "Save changes"');
        $steps[] = new Given('I see "'. "New $typename rule". ' modified"');

        // Delete the rule
        $steps[] = new Given('I follow "id_deletenotification'. $ruletype. '1"');
        $steps[] = new Given('I do not see "'. "New $typename rule". ' modified"');

        return $steps;
    }

    // HELPERS

    /**
     * Returns list of steps for filling a dataform mod_form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function dataform_form_fill_steps($data) {
        $steps = array();

        $formfields = array(
            'Name',
            'Description',
            'Display description on course page',
            'Activity icon',
            'Inline view',
            'Embedded',
            'Available from',
            'Due',
            'Duration',
            'Number of intervals',
            'Maximum entries',
            'Required entries',
            'Separate participants',
            'Group entries',
            'Anonymize entries',
            'Editing time limit (minutes)',
            'Grade',
            'Grading method',
            'Calculation',
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            $steps[] = new Given('I set the field "' . $name. '" to "'. $val. '"');
        }

        return $steps;
    }

    /**
     * Returns list of steps for filling a dataform mod_form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function dataform_form_match_steps($data) {
        $steps = array();

        $formfields = array(
            'Name',
            'Description',
            'Display description on course page',
            'Activity icon',
            'Inline view',
            'Embedded',
            'Available from',
            'Due',
            'Duration',
            'Number of intervals',
            'Maximum entries',
            'Required entries',
            'Separate participants',
            'Group entries',
            'Anonymize entries',
            'Editing time limit (minutes)',
            'Grade',
            'Grading method',
            'Calculation',
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            if ($name == 'Description') {
                $steps[] = new Given('the field "'. $name. '" matches value "<p>'. $val. '</p>"');
                continue;
            }

            $steps[] = new Given('the field "'. $name. '" matches value "'. $val. '"');
        }

        return $steps;
    }

    /**
     * Returns list of steps for filling a dataformfield general form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_base($data) {
        $steps = array();

        $formfields = array(
            'Name',
            'Description',
            'Visible',
            'Editable',
            'Template',
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            $steps[] = new Given('I set the field "'. $name. '" to "'. $val. '"');
        }

        return $steps;
    }

    /**
     * Returns list of steps for filling a dataformfield selectmulti specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_selectmulti($data) {
        $steps = $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return $steps;
        }

        $formfields = array(
            'Options',
            'Default values',
            'Options separator',
            'Allow adding options'
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            // Fix value for text area
            if ($name == 'Options' or $name == 'Default values' and $val) {
                $val = implode("\n", explode('\n', $val));
            }

            $steps[] = new Given('I set the field "'. $name. '" to "'. $val. '"');
        }

        return $steps;
    }

    /**
     * Returns list of steps for filling a dataformfield selectmulti form.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_checkbox($data) {
        return $this->field_form_fill_steps_selectmulti($data);
    }

    /**
     * Returns list of steps for filling a dataformfield select specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_select($data) {
        $steps = $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return $steps;
        }

        $formfields = array(
            'Options',
            'Default value',
            'Allow adding options'
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            // Fix value for text area
            if ($name == 'Options' and $val) {
                $val = implode("\n", explode('\n', $val));
            }

            $steps[] = new Given('I set the field "'. $name. '" to "'. $val. '"');
        }

        return $steps;
    }

    /**
     * Returns list of steps for filling a dataformfield text specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_text($data) {
        $steps = $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return $steps;
        }

        $formfields = array(
            'Auto link', // Auto link
            'param2', // Width
            'param3', // Width unit
            'Format', // alphanumeric|lettersonly|numeric|email|nopunctuation
            'param5', // Number of character (minlength|maxlength|rangelength)
            'param6', // Min (integer)
            'param7', // Nax (integer)
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            $steps[] = new Given('I set the field "'. $name. '" to "'. $val. '"');
        }

        return $steps;
    }

    /**
     * Returns list of steps for filling a dataformfield number specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_number($data) {
        $steps = $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return $steps;
        }

        $formfields = array(
            'Decimals',
            'param2', // Width
            'param3', // Width unit
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            $steps[] = new Given('I set the field "'. $name. '" to "'. $val. '"');
        }

        return $steps;
    }

    /**
     * Returns list of steps for filling a dataformfield radiobutton specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_radiobutton($data) {
        $steps = $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return $steps;
        }

        $formfields = array(
            'Options',
            'Default value',
            'Options separator',
            'Allow adding options'
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            // Fix value for text area
            if ($name == 'Options' and $val) {
                $val = implode("\n", explode('\n', $val));
            }

            $steps[] = new Given('I set the field "'. $name. '" to "'. $val. '"');
        }

        return $steps;
    }

    /**
     * Returns the data with first n values truncated.
     *
     * @param string $data Tab delimited field form data.
     * @param int $num Number of values to truncate.
     * @return string
     */
    protected function truncate_data_vals($data, $num) {
        $truncated = array_slice(explode("\t", trim($data)), $num);
        return implode("\t", $truncated);
    }

    /**
     * Converts filter data from string to Behat\Gherkin\Node\TableNode.
     *
     * @param string $data Tab delimited filter form data.
     * @return Behat\Gherkin\Node\TableNode
     */
    protected function convert_data_to_table($formfields, $data, $delimiter = "\t") {
        $vals = explode($delimiter, trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array_map(
            function($name, $val) {
                return "$name|$val";
            },
            $names,
            $vals
        );
        return new Behat\Gherkin\Node\TableNode(implode("\n", $tabledata));
    }

    /**
     * Gets the user id from it's username.
     * @throws Exception
     * @param string $username
     * @return int
     */
    protected function get_user_id($username) {
        global $DB;

        if (empty($username)) {
            return 0;
        }

        if (!$id = $DB->get_field('user', 'id', array('username' => $username))) {
            throw new Exception('The specified user with username "' . $username . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the dataform id from it's idnumber.
     * @throws Exception
     * @param string $idnumber
     * @return int
     */
    protected function get_dataform_id($idnumber) {
        global $DB;

        if (!$id = $DB->get_field('course_modules', 'instance', array('idnumber' => $idnumber))) {
            throw new Exception('The specified dataform with idnumber "' . $idnumber . '" does not exist');
        }

        return $id;
    }

    /**
     * Gets the group id from it's idnumber.
     * @throws Exception
     * @param string $idnumber
     * @return int
     */
    protected function get_group_id($idnumber) {
        global $DB;

        if (empty($idnumber)) {
            return 0;
        }

        if (!$id = $DB->get_field('groups', 'id', array('idnumber' => $idnumber))) {
            throw new Exception('The specified group with idnumber "' . $idnumber . '" does not exist');
        }
        return $id;
    }


}
