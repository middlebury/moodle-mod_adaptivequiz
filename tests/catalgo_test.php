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
 * PHPUnit tests for catalgo class
 *
 * @package    mod_adaptivequiz
 * @category   phpunit
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/catalgo.class.php');

/**
 * @group mod_adaptivequiz
 */
class mod_adaptivequiz_catalgo_testcase extends advanced_testcase {
    /**
     * This function loads data into the PHPUnit tables for testing
     */
    protected function setup_test_data_xml() {
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/mod_adaptivequiz_catalgo.xml'));
    }

    /**
     * This fuction tests instantiating the cataglo class without an instance of question_usage_by_activity
     * @expectedException coding_exception
     */
    public function test_init_catalgo_no_quba_object_instance() {
        $this->resetAfterTest(true);

        $dummy = new stdClass();
        $algo = new catalgo($dummy, 1, true, 1);
    }

    /**
     * This fuction tests instantiating the cataglo class with a non positive integer and throwing an exception
     * @expectedException coding_exception
     */
    public function test_init_catalgo_negative_int_throw_except() {
        $this->resetAfterTest(true);

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $algo = new catalgo($mockquba, -1);
    }

    /**
     * This fuction tests instantiating the cataglo class without setting the level argument
     * @expectedException coding_exception
     */
    public function test_init_catalgo_no_level_throw_except() {
        $this->resetAfterTest(true);

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $algo = new catalgo($mockquba, 1, true);
    }

    /**
     * This fuction tests the retrieval of an attempt record
     */
    public function test_retrieve_attempt_record() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $algo = new catalgo($mockquba, 1, true, 1);

        $result = $algo->retrieve_attempt_record(1);
        $expected = new stdClass();
        $expected->id = 1;
        $expected->questionsattempted = 0;
        $expected->difficultysum = 99;
        $expected->standarderror = 1.2;

        $this->assertEquals($expected, $result);
    }

    /**
     * This fuction tests the retrieval of using illegit attempt id
     * @expectedException dml_missing_record_exception
     */
    public function test_retrieve_illegit_attempt_record_throw_except() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $algo = new catalgo($mockquba, 1, true, 1);

        $result = $algo->retrieve_attempt_record(511);
    }

    /**
     * This fuction tests updating the sum of difficulty for the attempt
     */
    public function test_update_sum_diff_of_attempt() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $algo = new catalgo($mockquba, 1, true, 1);

        $algo->update_difficulty_sum_of_attempt(101);
        $result = $algo->get_difficultysum();

        $this->assertEquals(101, $result);

        $param = array('id' => 1);
        $this->assertEquals(101, $DB->get_field('adaptivequiz_attempt', 'difficultysum', $param));

    }

    /**
     * This function tests was_answer_submitted_to_question() returning a false instead of a true
     */
    public function test_quest_was_marked_correct_no_submit_prev_quest_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock('catalgo', array('find_last_quest_used_by_attempt', 'was_answer_submitted_to_question', 'get_question_mark'), array(), '', false);

        $mockcatalgo->expects($this->once())
                ->method('find_last_quest_used_by_attempt')
                ->will($this->returnValue(99));

        $mockcatalgo->expects($this->once())
                ->method('was_answer_submitted_to_question')
                ->with(99)
                ->will($this->returnValue(false));

        $mockcatalgo->expects($this->never())
                ->method('get_question_mark');

        $result = $mockcatalgo->question_was_marked_correct();

        $this->assertFalse($result);
    }

    /**
     * This function tests was_answer_submitted_to_question() returning a 0 instead of a slot number
     */
    public function test_quest_was_marked_correct_zero_slot_number_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock('catalgo', array('find_last_quest_used_by_attempt', 'get_question_mark'), array(), '', false);

        $mockcatalgo->expects($this->once())
                ->method('find_last_quest_used_by_attempt')
                ->will($this->returnValue(0));

        $mockcatalgo->expects($this->never())
                ->method('get_question_mark');

        $result = $mockcatalgo->question_was_marked_correct();

        $this->assertNull($result);
    }

    /**
     * This function tests get_question_mark() returning null
     */
    public function test_quest_was_marked_correct_mark_is_null_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock('catalgo', array('find_last_quest_used_by_attempt', 'was_answer_submitted_to_question', 'get_question_mark'), array(), '', false);
        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $mockcatalgo->expects($this->once())
                ->method('find_last_quest_used_by_attempt')
                ->will($this->returnValue(99));

        $mockcatalgo->expects($this->once())
                ->method('was_answer_submitted_to_question')
                ->with(99)
                ->will($this->returnValue(true));

        $mockcatalgo->expects($this->once())
                ->method('get_question_mark')
                ->withAnyParameters()
                ->will($this->returnValue(null));

        $result = $mockcatalgo->question_was_marked_correct();

        $this->assertNull($result);
    }

    /**
     * This function tests get_question_mark() returning a mark of zero
     */
    public function test_quest_was_marked_correct_mark_zero() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock('catalgo', array('find_last_quest_used_by_attempt', 'was_answer_submitted_to_question', 'get_question_mark'), array(), '', false);
        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $mockcatalgo->expects($this->once())
                ->method('find_last_quest_used_by_attempt')
                ->will($this->returnValue(99));

        $mockcatalgo->expects($this->once())
                ->method('was_answer_submitted_to_question')
                ->with(99)
                ->will($this->returnValue(true));

        $mockcatalgo->expects($this->once())
                ->method('get_question_mark')
                ->withAnyParameters()
                ->will($this->returnValue(0.00));

        $result = $mockcatalgo->question_was_marked_correct();

        $this->assertFalse($result);
    }

    /**
     * This function tests get_question_mark() returning a mark of greater than zero
     */
    public function test_quest_was_marked_correct_mark_non_zero() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock('catalgo', array('find_last_quest_used_by_attempt', 'was_answer_submitted_to_question', 'get_question_mark'), array(), '', false);
        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $mockcatalgo->expects($this->once())
                ->method('find_last_quest_used_by_attempt')
                ->will($this->returnValue(99));

        $mockcatalgo->expects($this->once())
                ->method('was_answer_submitted_to_question')
                ->with(99)
                ->will($this->returnValue(true));

        $mockcatalgo->expects($this->once())
                ->method('get_question_mark')
                ->withAnyParameters()
                ->will($this->returnValue(0.10));

        $result = $mockcatalgo->question_was_marked_correct();

        $this->assertTrue($result);
    }

    /**
     * This functino tests retrieve_standard_error(), retrieving the standard error value set for the activity
     */
    public function test_retrieve_standard_error() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $algo = new catalgo($mockquba, 1, true, 1);

        $result = $algo->retrieve_standard_error(1);
        $this->assertEquals(9.9, $result);
    }

    /**
     * This function tests retrieve_standard_error() with illegit attempt id
     * @expectedException dml_missing_record_exception
     */
    public function test_retrieve_standard_error_throw_excep() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $algo = new catalgo($mockquba, 1, true, 1);

        $result = $algo->retrieve_standard_error(511);
    }

    /**
     * This function tests compute_next_difficulty()
     */
    public function test_compute_next_difficulty() {
        $this->resetAfterTest(true);

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $catalgo = new catalgo($mockquba, 1, true, 1);

        // Test retrieving the next difficulty level if the user answered the previous question incorrectly
        $result = $catalgo->compute_next_difficulty(50, 60, false);
        $this->assertEquals(49, $result);

        // Test retrieving the next difficulty level if the user answered the previous question correctly
        $result = $catalgo->compute_next_difficulty(50, 60, true);
        $this->assertEquals(51, $result);
    }

    /**
     * This function tests results returned from get_question_mark()
     */
    public function test_get_question_mark() {
        $this->resetAfterTest(true);

        // Test quba returning a mark of 1.0
        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $mockquba->expects($this->once())
                ->method('get_question_mark')
                ->will($this->returnValue(1.0));

        $catalgo = new catalgo($mockquba, 1, true, 1);
        $result = $catalgo->get_question_mark($mockquba, 1);
        $this->assertEquals(1.0, $result);

        // Test quba returning a non float value
        $mockqubatwo = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        $mockqubatwo->expects($this->once())
                ->method('get_question_mark')
                ->will($this->returnValue(1));

        $catalgo = new catalgo($mockqubatwo, 1, true, 1);
        $result = $catalgo->get_question_mark($mockqubatwo, 1);
        $this->assertNull($result);
    }

    /**
     * This function tests the return data from compute_right_answers()
     */
    public function test_compute_right_answers() {
        $this->resetAfterTest(true);

        // Test use case where user got all 5 question correct
        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots', 'get_question_mark'), array(), '', false);

        $mockquba->expects($this->exactly(5))
                ->method('get_question_mark')
                ->will($this->returnValue(1.0));

        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue(array(1, 2, 3, 4, 5)));

        $catalgo = new catalgo($mockquba, 1, true, 1);
        $result = $catalgo->compute_right_answers($mockquba);
        $this->assertEquals(5, $result);
    }

    /**
     * This function tests the return data from compute_right_answers()
     */
    public function test_compute_right_answers_none_correct() {
        $this->resetAfterTest(true);

        // Test use case where user got all 5 question incorrect
        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots', 'get_question_mark'), array(), '', false);

        $mockquba->expects($this->exactly(5))
                ->method('get_question_mark')
                ->will($this->returnValue(0));

        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue(array(1, 2, 3, 4, 5)));

        $catalgo = new catalgo($mockquba, 1, true, 1);
        $result = $catalgo->compute_right_answers($mockquba);
        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from compute_right_answers()
     */
    public function test_compute_wrong_answers() {
        $this->resetAfterTest(true);

        // Test use case where user got all 5 question incorrect
        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots', 'get_question_mark'), array(), '', false);

        $mockquba->expects($this->exactly(5))
                ->method('get_question_mark')
                ->will($this->returnValue(0));

        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue(array(1, 2, 3, 4, 5)));

        $catalgo = new catalgo($mockquba, 1, true, 1);
        $result = $catalgo->compute_wrong_answers($mockquba);
        $this->assertEquals(5, $result);
    }

    /**
     * This function tests the return data from compute_right_answers()
     */
    public function test_compute_wrong_answers_all_correct() {
        $this->resetAfterTest(true);

        // Test use case where user got all 5 question correct
        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots', 'get_question_mark'), array(), '', false);

        $mockquba->expects($this->exactly(5))
                ->method('get_question_mark')
                ->will($this->returnValue(1.0));

        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue(array(1, 2, 3, 4, 5)));

        $catalgo = new catalgo($mockquba, 1, true, 1);
        $result = $catalgo->compute_wrong_answers($mockquba);
        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from estimate_measure()
     */
    public function test_estimate_measure() {
        $this->resetAfterTest(true);

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        // Test an attempt with the following details:
        // sum of difficulty - 20, number of questions attempted - 10, number of correct answers - 7, number of incorrect answers - 3
        $catalgo = new catalgo($mockquba, 1, true, 1);
        $result = $catalgo->estimate_measure(20, 10, 7, 3);
        $this->assertEquals(2.8473, $result);
    }

    /**
     * This function tests the return data from estimate_standard_error()
     */
    public function test_estimate_standard_error() {
        $this->resetAfterTest(true);

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        // Test an attempt with the following details;
        // sum of questions attempted - 10, number of correct answers - 7, number of incorrect answers - 3
        $catalgo = new catalgo($mockquba, 1, true, 1);
        $result = $catalgo->estimate_standard_error(10, 7, 3);
        $this->assertEquals(0.69007, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where question_was_marked_correct() returns null
     */
    public function test_perform_calc_steps_marked_correct_return_null_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock(
                'catalgo',
                array('retrieve_attempt_record', 'question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers'),
                array(), '', false);

        $dummyattempt = new stdClass();
        $dummyattempt->questionsattempted = 10;
        $dummyattempt->difficultysum = 20;

        $mockcatalgo->expects($this->once())
                ->method('retrieve_attempt_record')
                ->will($this->returnValue($dummyattempt));

        $mockcatalgo->expects($this->once())
                ->method('question_was_marked_correct')
                ->will($this->returnValue(null)); // Questions marked correctly returns null

        $mockcatalgo->expects($this->never())
                ->method('compute_next_difficulty');

        $mockcatalgo->expects($this->never())
                ->method('compute_right_answers');

        $result = $mockcatalgo->perform_calculation_steps();

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where compute_right_answers() returns 0
     */
    public function test_perform_calc_steps_compute_right_ans_return_zero_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock(
                'catalgo',
                array('retrieve_attempt_record', 'question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers'),
                array(), '', false);

        $dummyattempt = new stdClass();
        $dummyattempt->questionsattempted = 10;
        $dummyattempt->difficultysum = 20;

        $mockcatalgo->expects($this->once())
                ->method('retrieve_attempt_record')
                ->will($this->returnValue($dummyattempt));

        $mockcatalgo->expects($this->once())
                ->method('question_was_marked_correct')
                ->will($this->returnValue(true));

        $mockcatalgo->expects($this->once())
                ->method('compute_next_difficulty')
                ->will($this->returnValue(30));

        $mockcatalgo->expects($this->once())
                ->method('compute_right_answers')
                ->will($this->returnValue(0)); // Right answers is set to zero

        $mockcatalgo->expects($this->once())
                ->method('compute_wrong_answers')
                ->will($this->returnValue(1));

        $result = $mockcatalgo->perform_calculation_steps();

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where compute_wrong_answers() returns 0
     */
    public function test_perform_calc_steps_compute_wrong_ans_return_zero_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock(
                'catalgo',
                array('retrieve_attempt_record', 'question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers'),
                array(), '', false);

        $dummyattempt = new stdClass();
        $dummyattempt->questionsattempted = 10;
        $dummyattempt->difficultysum = 20;

        $mockcatalgo->expects($this->once())
                ->method('retrieve_attempt_record')
                ->will($this->returnValue($dummyattempt));

        $mockcatalgo->expects($this->once())
                ->method('question_was_marked_correct')
                ->will($this->returnValue(true));

        $mockcatalgo->expects($this->once())
                ->method('compute_next_difficulty')
                ->will($this->returnValue(30));

        $mockcatalgo->expects($this->once())
                ->method('compute_right_answers')
                ->will($this->returnValue(1));

        $mockcatalgo->expects($this->once())
                ->method('compute_wrong_answers')
                ->will($this->returnValue(0)); // Wrong answers is set to zero

        $result = $mockcatalgo->perform_calculation_steps();

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where questions attempted is set to 0
     */
    public function test_perform_calc_steps_quest_attempted_return_zero_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock(
                'catalgo',
                array('retrieve_attempt_record', 'question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers'),
                array(), '', false);

        $dummyattempt = new stdClass();
        $dummyattempt->questionsattempted = 0; // Questions attempted is set to zero
        $dummyattempt->difficultysum = 20;

        $mockcatalgo->expects($this->once())
                ->method('retrieve_attempt_record')
                ->will($this->returnValue($dummyattempt));

        $mockcatalgo->expects($this->once())
                ->method('question_was_marked_correct')
                ->will($this->returnValue(true));

        $mockcatalgo->expects($this->once())
                ->method('compute_next_difficulty')
                ->will($this->returnValue(30));

        $mockcatalgo->expects($this->once())
                ->method('compute_right_answers')
                ->will($this->returnValue(1));

        $mockcatalgo->expects($this->once())
                ->method('compute_wrong_answers')
                ->will($this->returnValue(1));

        $result = $mockcatalgo->perform_calculation_steps();

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where the sum of correct and incorrect answers does not equal the sum of questions attempted
     */
    public function test_perform_calc_steps_sum_corr_and_incorr_not_equl_sum_quest_attempt_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->getMock(
                'catalgo',
                array('retrieve_attempt_record', 'question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers'),
                array(), '', false);

        $dummyattempt = new stdClass();
        $dummyattempt->questionsattempted = 3; // Sum of question attempted
        $dummyattempt->difficultysum = 20;

        $mockcatalgo->expects($this->once())
                ->method('retrieve_attempt_record')
                ->will($this->returnValue($dummyattempt));

        $mockcatalgo->expects($this->once())
                ->method('question_was_marked_correct')
                ->will($this->returnValue(true));

        $mockcatalgo->expects($this->once())
                ->method('compute_next_difficulty')
                ->will($this->returnValue(30));

        $mockcatalgo->expects($this->once())
                ->method('compute_right_answers')
                ->will($this->returnValue(1)); // Sum of right answers

        $mockcatalgo->expects($this->once())
                ->method('compute_wrong_answers')
                ->will($this->returnValue(1)); // Sum of wrong answers

        $result = $mockcatalgo->perform_calculation_steps();

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where the user answered the last qustion corectly and the attempt has not met the minimum
     * stopping criteria
     */
    public function test_perform_calculation_steps_nostop_correct_answer() {
        $this->resetAfterTest(true);

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        // Minimum stopping criteria has not been met
        $mockcatalgo = $this->getMock(
                'catalgo',
                array('retrieve_attempt_record', 'question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers'),
                array($mockquba, 1, false, 50));

        $dummyattempt = new stdClass();
        $dummyattempt->questionsattempted = 1;
        $dummyattempt->difficultysum = 50;

        $mockcatalgo->expects($this->once())
                ->method('retrieve_attempt_record')
                ->will($this->returnValue($dummyattempt));

        $mockcatalgo->expects($this->once())
                ->method('question_was_marked_correct')
                ->will($this->returnValue(true)); // Last attempted question marked correctly

        $mockcatalgo->expects($this->once())
                ->method('compute_next_difficulty')
                ->will($this->returnValue(52));

        $mockcatalgo->expects($this->once())
                ->method('compute_right_answers')
                ->will($this->returnValue(1));

        $mockcatalgo->expects($this->never())
                ->method('compute_wrong_answers');

        $result = $mockcatalgo->perform_calculation_steps();

        $this->assertEquals(52, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where the user answered the last qustion incorrectly and the attempt has not met the minimum
     * stopping criteria
     */
    public function test_perform_calculation_steps_nostop_incorrect_answer() {
        $this->resetAfterTest(true);

        $mockquba = $this->getMock('question_usage_by_activity', array(), array(), '', false);

        // Minimum stopping criteria has not been met
        $mockcatalgo = $this->getMock(
                'catalgo',
                array('retrieve_attempt_record', 'question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers'),
                array($mockquba, 1, false, 50));

        $dummyattempt = new stdClass();
        $dummyattempt->questionsattempted = 1;
        $dummyattempt->difficultysum = 50;

        $mockcatalgo->expects($this->once())
                ->method('retrieve_attempt_record')
                ->will($this->returnValue($dummyattempt));

        $mockcatalgo->expects($this->once())
                ->method('question_was_marked_correct')
                ->will($this->returnValue(false)); // Last attempted question marked incorrectly

        $mockcatalgo->expects($this->once())
                ->method('compute_next_difficulty')
                ->will($this->returnValue(48));

        $mockcatalgo->expects($this->never())
                ->method('compute_right_answers');

        $mockcatalgo->expects($this->never())
                ->method('compute_wrong_answers');

        $result = $mockcatalgo->perform_calculation_steps();

        $this->assertEquals(48, $result);
    }
}