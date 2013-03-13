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
 * Adaptive lib.php PHPUnit tests
 *
 * @package    mod_adaptivequiz
 * @category   phpunit
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

/**
 * @group mod_adaptivequiz
 */
class mod_adaptivequiz_locallib_testcase extends advanced_testcase {

    private $activitycontext;

    /**
     * This function calls the data generator classes required by these tests
     * @return void
     */
    protected function setup_test_data_generator() {
        // Create course
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->setAdminUser();
        
        // Create course category and course
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(array('name' => 'Some course', 'category' => $category->id));

        // Create activity
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $generator->create_instance(array('course'=> $course->id));

        $this->activitycontext = context_module::instance($adaptivequiz->id);

    }

    /**
     * This functions loads data via the tests/fixtures/mod_adaptivequiz.xml file
     * @return void
     */
    protected function setup_test_data_xml() {
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/mod_adaptivequiz.xml'));
    }

    /**
     * Test the making of the default course question category
     * @group adaptivequiz_locallib_test
     */
    function test_make_default_categories() {
        $this->resetAfterTest(true);
        $this->setup_test_data_generator();

        $data = adaptivequiz_make_default_categories($this->activitycontext);

        $this->assertObjectHasAttribute('id', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertObjectHasAttribute('contextid', $data);
    }

    /**
     * Test retrieving an array of question categories
     * @group adaptivequiz_locallib_test
     */
    function test_get_question_categories() {
        $this->resetAfterTest(true);
        $this->setup_test_data_generator();

        $data = adaptivequiz_make_default_categories($this->activitycontext);

        $data = adaptivequiz_get_question_categories($this->activitycontext);
        
        $this->assertEquals(1, count($data));
    }

    /**
     * Test retrieving question categories used by the activity instance
     * @group adaptivequiz_locallib_test
     */
    function test_get_selected_question_cateogires() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $data = adaptivequiz_get_selected_question_cateogires(12);

        $this->assertEquals(6, count($data));
    }
}
