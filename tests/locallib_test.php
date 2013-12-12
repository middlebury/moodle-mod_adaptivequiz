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
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');

/**
 * @group mod_adaptivequiz
 */
class mod_adaptivequiz_locallib_testcase extends advanced_testcase {

    private $activitycontext;

    /**
     * This function calls the data generator classes required by these tests
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
        $adaptivequiz = $generator->create_instance(array('course' => $course->id));

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $this->activitycontext = context_module::instance($cm->id);

    }

    /**
     * This functions loads data via the tests/fixtures/mod_adaptivequiz.xml file
     */
    protected function setup_test_data_xml() {
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/mod_adaptivequiz.xml'));
    }

    /**
     * Provide input data to the parameters of the test_count_user_previous_attempts_fail() method.
     * @return $data an array with arrays of data
     */
    public function fail_attempt_data() {
        $data = array(
            array(99, 99),
            array(99, 3),
            array(13, 99),
        );

        return $data;
    }

    /**
     * Provide input data to the parameters of the test_allowed_attempt_fail() method.
     * @return $data an array with arrays of data
     */
    public function attempts_allowed_data_fail() {
        $data = array(
            array(99, 100),
            array(99, 99),
        );

        return $data;
    }

    /**
     * Provide input data to the parameters of the test_allowed_attempt() method.
     * @return $data an array with arrays of data
     */
    public function attempts_allowed_data() {
        $data = array(
            array(99, 98),
            array(0, 99),
        );

        return $data;
    }

    /**
     * Provide input data to the parameters of the test_adaptivequiz_construct_view_report_orderby() method.
     * @return $data - an array with arrays of data
     */
    public function view_reports_data() {
        $data = array(
                array('firstname', 'ASC'),
                array('firstname', 'DESC'),
                array('lastname', 'ASC'),
                array('lastname', 'DESC'),
                array('attempts', 'ASC'),
                array('attempts', 'DESC'),
                array('stderror', 'ASC'),
                array('stderror', 'DESC'),
        );

        return $data;
    }

    /**
     * Test the making of the default course question category
     */
    public function test_make_default_categories() {
        $this->resetAfterTest(true);
        $this->setup_test_data_generator();

        $data = adaptivequiz_make_default_categories($this->activitycontext);

        $this->assertObjectHasAttribute('id', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertObjectHasAttribute('contextid', $data);
    }

    /**
     * Test retrieving an array of question categories
     */
    public function test_get_question_categories() {
        $this->resetAfterTest(true);
        $this->setup_test_data_generator();

        $data = adaptivequiz_make_default_categories($this->activitycontext);

        $data = adaptivequiz_get_question_categories($this->activitycontext);

        $this->assertEquals(1, count($data));
    }

    /**
     * Test retrieving question categories used by the activity instance
     */
    public function test_get_selected_question_cateogires() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $data = adaptivequiz_get_selected_question_cateogires(12);

        $this->assertEquals(6, count($data));
    }

    /**
     * This function tests failing conditions for counting user's previous attempts
     * that have been marked as completed
     * @dataProvider fail_attempt_data
     * @param int $instanceid activity instance id
     * @param int $userid user id
     */
    public function test_count_user_previous_attempts_fail($instanceid, $userid) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_count_user_previous_attempts($instanceid, $userid);

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests a non-failing conditions for counting user's previous attempts
     * that have been marked as completed
     */
    public function test_count_user_previous_attempts_inprogress() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_count_user_previous_attempts(13, 3);

        $this->assertEquals(1, $result);
    }

    /**
     * This function tests failing conditions for determining whether a user is allowed
     * further attemtps at the activity
     * @dataProvider attempts_allowed_data_fail
     * @param int $maxattempts the maximum number of attempts allowed
     * @param int $attempts the number of attempts taken thus far
     */
    public function test_allowed_attempt_no_more_attempts_allowed($maxattempts, $attempts) {
        $data = adaptivequiz_allowed_attempt($maxattempts, $attempts);
        $this->assertFalse($data);
    }

    /**
     * This function tests failing conditions for determining whether a user is allowed
     * further attemtps at the activity
     * @dataProvider attempts_allowed_data
     * @param int $maxattempts the maximum number of attempts allowed
     * @param int $attempts the number of attempts taken thus far
     */
    public function test_allowed_attempt($maxattempts, $attempts) {
        $data = adaptivequiz_allowed_attempt($maxattempts, $attempts);
        $this->assertTrue($data);
    }

    /**
     * This function tests adaptivequiz_uniqueid_part_of_attempt()
     */
    public function test_adaptivequiz_uniqueid_part_of_attempt() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        // Assert that there exists a record where the uniqueid, activity instance and userid all match up
        $result = adaptivequiz_uniqueid_part_of_attempt(3, 1, 2);
        $this->assertTrue($result);

        $result = adaptivequiz_uniqueid_part_of_attempt(1, 1, 1);
        $this->assertFalse($result);
    }

    /**
     * This function tests the updating of the attempt data
     */
    public function test_adaptivequiz_update_attempt_data() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_update_attempt_data(3, 13, 3, 50, 0.002, 0.99);
        $record = $DB->get_record('adaptivequiz_attempt', array('id' => 2));

        $this->assertTrue($result);
        $this->assertEquals(51, $record->difficultysum);
        $this->assertEquals(1, $record->questionsattempted);
        $this->assertEquals(0.002, $record->standarderror);
        $this->assertEquals(0.99, $record->measure);
    }

    /**
     * This function tests the updating of the attempt data
     */
    public function test_adaptivequiz_update_attempt_data_using_infinite_value() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_update_attempt_data(3, 13, 3, -INF, 0.02, 0.1);
        $record = $DB->get_record('adaptivequiz_attempt', array('id' => 2));

        $this->assertFalse($result);
    }

    /**
     * This function tests completing an attempt
     */
    public function test_adaptivequiz_complete_attempt() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_complete_attempt(3, 13, 3, 1, 'php unit test');
        $record = $DB->get_record('adaptivequiz_attempt', array('id' => 2));

        $this->assertTrue($result);
        $this->assertEquals('php unit test', $record->attemptstopcriteria);
        $this->assertEquals(ADAPTIVEQUIZ_ATTEMPT_COMPLETED, $record->attemptstate);
        $this->assertEquals(1, $record->standarderror);
    }

    /**
     * This function tests checking if the minimum number of questions have been attempted
     */
    public function test_adaptivequiz_min_attempts_reached() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_min_attempts_reached(3, 13, 3);
        $this->assertFalse($result);

        $result = adaptivequiz_min_attempts_reached(4, 13, 4);
        $this->assertTrue($result);
    }

    /**
     * This function tests the output from adaptivequiz_construct_view_report_orderby
     * @dataProvider view_reports_data
     * @param string $sort the column to sort on
     * @param string $sortdir the direction to sort in
     */
    public function test_adaptivequiz_construct_view_report_orderby($sort, $sortdir) {
        $this->resetAfterTest(true);

        $data = adaptivequiz_construct_view_report_orderby($sort, $sortdir);
        $this->assertContains('ORDER BY', $data);
    }

    /**
     * This function tests the output from adaptivequiz_construct_view_report_orderby
     */
    public function test_adaptivequiz_construct_view_report_orderby_with_illegit_data() {
        $this->resetAfterTest(true);

        $data = adaptivequiz_construct_view_report_orderby('1234', 'ASC');
        $this->assertContains('ORDER BY firstname', $data);
        $data = adaptivequiz_construct_view_report_orderby('stderr', 'ASC');
        $this->assertContains('ORDER BY firstname', $data);
    }
}