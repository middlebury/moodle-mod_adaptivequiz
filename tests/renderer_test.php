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
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/renderer.php');

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
     * This functions tests the output rom create_report_table()
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
        $records[1]->standarderror = 0.001;
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
        $this->assertContains('0.001', $output);
        $this->assertContains('5', $output);
        /* Check table column headers */
        $this->assertContains('sort=firstname', $output);
        $this->assertContains('sort=lastname', $output);
        $this->assertContains('sort=attempts', $output);
        $this->assertContains('sort=stderr', $output);
    }
}