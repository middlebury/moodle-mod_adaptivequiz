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
 * PHPUnit tests for renderer class
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/renderer.php');
require_once($CFG->dirroot.'/tag/lib.php');

/**
 * @group mod_adaptivequiz
 */
class mod_adaptivequiz_renderer_testcase extends advanced_testcase {
    /**
     * This function tests the output for the start attempt form
     */
    public function test_display_start_attempt_form() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $output = $renderer->display_start_attempt_form(9999);

        $this->assertContains('<form', $output);
        $this->assertContains('/mod/adaptivequiz/attempt.php?cmid=9999', $output);
        $this->assertContains('<input', $output);
        $this->assertContains('type="submit"', $output);
        $this->assertContains('class="submitbtns adaptivequizbtn"', $output);
        $this->assertContains('type="hidden"', $output);
        $this->assertContains('name="sesskey"', $output);
        $this->assertContains('</form>', $output);
    }

    /**
     * This function tests the output for the view report form
     * @return void
     */
    public function test_display_view_report_form() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $output = $renderer->display_view_report_form(9999);

        $this->assertContains('<form', $output);
        $this->assertContains('/mod/adaptivequiz/viewreport.php?cmid=9999', $output);
        $this->assertContains('type="submit"', $output);
        $this->assertContains('class="submitbtns adaptivequizbtn"', $output);
        $this->assertContains('</form>', $output);
    }

    /**
     * This function tests the output from the get_js_module
     * @return void
     */
    public function test_adaptivequiz_get_js_module() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $output = $renderer->adaptivequiz_get_js_module();

        $this->assertArrayHasKey('name', $output);
        $this->assertContains('mod_adaptivequiz', $output);
        $this->assertArrayHasKey('fullpath', $output);
        $this->assertContains('/mod/adaptivequiz/module.js', $output);
        $this->assertArrayHasKey('requires', $output);
        $this->assertEquals(array('base', 'dom', 'event-delegate', 'event-key', 'core_question_engine', 'moodle-core-formchangechecker'), $output['requires']);
        $this->assertArrayHasKey('strings', $output);
    }

    /**
     * This function tests the output from the create_submit_form
     * @return void
     */
    public function test_create_submit_form() {

        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $mockquba = $this->getMock('question_usage_by_activity', array('render_question'), array(), '', false);

        $mockquba->expects($this->once())
                ->method('render_question')
                ->withAnyParameters()
                ->will($this->returnValue('output'));

        $output = $renderer->create_submit_form(9999, $mockquba, 8888, 7777);

        // Test form attributes
        $this->assertContains('<form', $output);
        $this->assertContains('enctype="multipart/form-data"', $output);
        $this->assertContains('accept-charset="utf-8"', $output);
        $this->assertContains('id="responseform"', $output);

        // Test submit button and class
        $this->assertContains('type="submit"', $output);
        $this->assertContains('class="submitbtns adaptivequizbtn"', $output);

        // Test output contains required elements
        $this->assertContains('name="cmid"', $output);
        $this->assertContains('name="uniqueid"', $output);
        $this->assertContains('name="sesskey"', $output);
        $this->assertContains('name="slots"', $output);
        $this->assertContains('name="dl"', $output);

        $this->assertContains('</form>', $output);
    }

    /**
     * This function tests the output from create_attemptfeedback
     */
    public function test_create_attemptfeedback() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $output = $renderer->create_attemptfeedback('Test attempt feedback', 99);

        // Test form attributes
        $this->assertContains('<form', $output);
        $this->assertContains('/mod/adaptivequiz/view.php', $output);
        $this->assertContains('id="attemptfeedback"', $output);

        // Test submit button and class
        $this->assertContains('type="submit"', $output);
        $this->assertContains('class="submitbtns adaptivequizfeedback"', $output);

        // Test output contains required elements
        $this->assertContains('name="id"', $output);

        $this->assertContains('</form>', $output);
    }

    /**
     * This functions tests the output from create_report_table()
     */
    public function test_create_report_table() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $records = array();
        $records[1] = new stdClass();
        $records[1]->id = 1;
        $records[1]->firstname = 'test firstname';
        $records[1]->lastname = 'test lastname';
        $records[1]->email = 'test@example.edu';
        $records[1]->measure = -0.6;
        $records[1]->stderror = 0.17;
        $records[1]->timemodified = 12345678;
        $records[1]->uniqueid = 1111;
        $records[1]->highestlevel = 16;
        $records[1]->lowestlevel = 1;
        $records[1]->attempts = 5;

        $cm = new stdClass();
        $cm->id = 1;

        $sort = 'firstname';
        $sortdir = 'ASC';

        $output = $renderer->create_report_table($records, $cm, $sort, $sortdir);
        $this->assertContains('<table', $output);
        $this->assertContains('/mod/adaptivequiz/viewreport.php', $output);
        /* Check table row */
        $this->assertContains('test firstname', $output);
        $this->assertContains('test lastname', $output);
        $this->assertContains('test@example.edu', $output);
        $this->assertContains('/user/profile.php?id=1', $output);
        $this->assertContains('6.3', $output);
        $this->assertContains('&plusmn; 4%', $output);
        $this->assertContains('5', $output);
        /* Check table column headers */
        $this->assertContains('sort=firstname', $output);
        $this->assertContains('sort=lastname', $output);
        $this->assertContains('sort=email', $output);
        $this->assertContains('sort=attempts', $output);
        $this->assertContains('sort=stderror', $output);
    }

    /**
     * This function tests how init_metadata() handlss an integer
     */
    public function test_init_metadata_with_integer() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $mockquba = $this->getMock('question_usage_by_activity', array('render_question_head_html'), array(), '', false);

        $mockquba->expects($this->once())
                ->method('render_question_head_html')
                ->will($this->returnValue(''));

        // Only testing that the mock object's method is called once
        $renderer->init_metadata($mockquba, 1);
    }

    /**
     * This function tests the output from print_questions_for_review_pager()
     */
    public function test_print_questions_for_review_pager_with_one_page_of_output() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots'), array(), '', false);

        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue(array(1, 2, 3)));

        $output = $renderer->print_questions_for_review_pager($mockquba, 0, 1, 1);
        $this->assertEquals('', $output);
    }

    /**
     * This function tests the output from print_questions_for_review_pager()
     */
    public function test_print_questions_for_review_pager_with_three_pages_of_output() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots'), array(), '', false);

        $mockpages = array_keys(array_fill(0, 25, 1));
        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue($mockpages));

        // Unable to mock quba->get_id()

        $output = $renderer->print_questions_for_review_pager($mockquba, 0, 1, 1);

        $this->assertContains('/mod/adaptivequiz/reviewattempt.php', $output);
        $this->assertContains('cmid=1', $output);
        $this->assertContains('userid=1', $output);
        $this->assertContains('<span class="viewattemptreportpages">1</span>', $output);
        $this->assertContains('page=1', $output);
        $this->assertContains('page=2', $output);
        $this->assertNotContains('page=3', $output);
    }

    /**
     * This function tests the output from print_questions_for_review_pager()
     */
    public function test_print_questions_for_review_pager_with_two_pages_of_output() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots'), array(), '', false);

        $mockpages = array_keys(array_fill(0, 11, 1));
        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue($mockpages));

        $output = $renderer->print_questions_for_review_pager($mockquba, 0, 1, 1);

        $this->assertContains('/mod/adaptivequiz/reviewattempt.php', $output);
        $this->assertContains('cmid=1', $output);
        $this->assertContains('userid=1', $output);
        $this->assertContains('<span class="viewattemptreportpages">1</span>', $output);
        $this->assertContains('page=1', $output);
        $this->assertNotContains('page=2', $output);
    }

    /**
     * This function tests the output from print_questions_for_review_pager()
     */
    public function test_print_questions_for_review_pager_with_two_pages_of_output_page_two_selected() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots'), array(), '', false);

        $mockpages = array_keys(array_fill(0, 11, 1));
        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue($mockpages));

        $output = $renderer->print_questions_for_review_pager($mockquba, 1, 1, 1);

        $this->assertContains('/mod/adaptivequiz/reviewattempt.php', $output);
        $this->assertContains('cmid=1', $output);
        $this->assertContains('userid=1', $output);
        $this->assertContains('<span class="viewattemptreportpages">2</span>', $output);
        $this->assertContains('page=0', $output);
        $this->assertNotContains('page=2', $output);
        $this->assertNotContains('page=1', $output);
    }

    /**
     * This function tests the output from print_questions_for_review_pager().  Mostly this function is testing the functions are alled the correct number of times.
     */
    public function test_print_questions_for_review() {
        global $DB;
        $user = $DB->get_record('user', array('id' => 2));

        $renderer = $this->getMock('mod_adaptivequiz_renderer', array('init_metadata', 'heading'), array(), '', false);

        $renderer->expects($this->once())
                ->method('init_metadata')
                ->will($this->returnValue(''));

        $renderer->expects($this->once())
                ->method('heading')
                ->will($this->returnValue('phpunit test heading'));

        $mockquestattempt = $this->getMock('question_attempt', array('get_question'), array(), '', false);

        $dummy = new stdClass();
        $dummy->id = 1;
        $mockquestattempt->expects($this->exactly(3))
                ->method('get_question')
                ->will($this->returnValue($dummy));

        $mockquba = $this->getMock('question_usage_by_activity', array('get_slots', 'render_question', 'get_question_attempt'), array(), '', false);

        $mockquba->expects($this->once())
                ->method('get_slots')
                ->will($this->returnValue(array(1, 2, 3)));

        $mockquba->expects($this->exactly(3))
                ->method('render_question')
                ->will($this->returnValue('mock render question output'));

        $mockquba->expects($this->exactly(3))
                ->method('get_question_attempt')
                ->will($this->returnValue($mockquestattempt));

        $output = $renderer->print_questions_for_review($mockquba, 0, $user, 12345);

        $this->assertContains('mock render question output', $output);
        $this->assertContains('phpunit test heading', $output);
    }

    /**
     * This function tests the output from print_form_and_button()
     */
    public function test_print_form_and_button() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $url = new moodle_url('/test/phpunittest/test.php', array('cmid' => 99));
        $text = 'phpunit test button';

        $output = $renderer->print_form_and_button($url, $text);
        $this->assertContains('<form', $output);
        $this->assertContains('<input', $output);
        $this->assertContains('type="submit"', $output);
        $this->assertContains('/test/phpunittest/test.php', $output);
        $this->assertContains('cmid=99', $output);
        $this->assertContains('phpunit test button', $output);
        $this->assertContains('<center>', $output);
        $this->assertContains('</center>', $output);
        $this->assertContains('</form>', $output);
    }

    /**
     * This functions tests the output from print_attempt_report_table()
     */
    public function test_print_attempt_report_table() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $records = array();
        $records[1] = new stdClass();
        $records[1]->id = 1;
        $records[1]->instance = 1;
        $records[1]->userid = 1;
        $records[1]->uniqueid = 123;
        $records[1]->attemptstate = 'completed';
        $records[1]->attemptstopcriteria = 'stopped for some reason';
        $records[1]->questionsattempted = 12;
        $records[1]->standarderror = 0.001;
        $records[1]->measure = -0.6;
        $records[1]->stderror = 0.17;
        $records[1]->highestlevel = 16;
        $records[1]->lowestlevel = 1;
        $records[1]->timemodified = 12345678;
        $records[1]->timecreated = 12345600;

        $cm = new stdClass();
        $cm->id = 1;

        $output = $renderer->print_attempt_report_table($records, $cm, new stdClass);
        $this->assertContains('<table', $output);
        $this->assertContains('/mod/adaptivequiz/reviewattempt.php', $output);
        $this->assertContains('uniqueid=123', $output);
        $this->assertContains('userid=1', $output);
        $this->assertContains('cmid=1', $output);
        /* Check table row */
        $this->assertContains('stopped for some reason', $output);
        $this->assertContains('6.3 &plusmn; 4%', $output);
        $this->assertContains('12', $output);
        $this->assertContains('</table>', $output);
    }

    /**
     * This function tests the output from format_report_table_headers()
     */
    public function test_format_report_table_headers() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $dummycm = new stdClass();
        $dummycm->id = 99;

        $output = $renderer->format_report_table_headers($dummycm, 'stderror', 'ASC');
        $this->assertEquals(6, count($output));
        $this->assertContains('/mod/adaptivequiz/viewreport.php', $output[0]);
        $this->assertContains('sort=firstname&amp;sortdir=ASC', $output[0]);
        $this->assertContains('sort=lastname&amp;sortdir=ASC', $output[0]);
        $this->assertContains('/mod/adaptivequiz/viewreport.php', $output[1]);
        $this->assertContains('sort=email&amp;sortdir=ASC', $output[1]);
        $this->assertContains('/mod/adaptivequiz/viewreport.php', $output[2]);
        $this->assertContains('sort=attempts&amp;sortdir=ASC', $output[2]);
        $this->assertContains('/mod/adaptivequiz/viewreport.php', $output[3]);
        $this->assertContains('sort=measure&amp;sortdir=ASC', $output[3]);
        $this->assertContains('/mod/adaptivequiz/viewreport.php', $output[4]);
        $this->assertContains('sort=stderror&amp;sortdir=DESC', $output[4]);
        $this->assertContains('/mod/adaptivequiz/viewreport.php', $output[5]);
        $this->assertContains('sort=timemodified&amp;sortdir=ASC', $output[5]);
    }
}
