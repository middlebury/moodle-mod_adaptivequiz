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
 * Adaptive quiz renderer class
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_adaptivequiz_renderer extends plugin_renderer_base {
    /**
     * This function displays a form with a button to start the assessment attempt
     * @param string $cmid: course module id
     * @return string - HTML markup displaying the description and form with a submit button
     */
    public function display_start_attempt_form($cmid) {
        $html = '';

        $param = array('cmid' => $cmid);
        $target = new moodle_url('/mod/adaptivequiz/attempt.php', $param);
        $attributes = array('method' => 'POST', 'action' => $target);

        $html .= html_writer::start_tag('form', $attributes);

        $html .= html_writer::empty_tag('br');
        $html .= html_writer::empty_tag('br');

        $buttonlabel = get_string('startattemptbtn', 'adaptivequiz');
        $params = array('type' => 'submit', 'value' => $buttonlabel, 'class' => 'submitbtns adaptivequizbtn');
        $html .= html_writer::empty_tag('input', $params);
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * This function displays a form with a button to view stubmissions report
     * @param string $cmid: course module id
     * @return string - HTML markup displaying the description and form with a submit button
     */
    public function display_view_report_form($cmid) {
        $html = '';

        $param = array('cmid' => $cmid);
        $target = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $attributes = array('method' => 'POST', 'action' => $target);

        $html .= html_writer::start_tag('form', $attributes);

        $html .= html_writer::empty_tag('br');
        $html .= html_writer::empty_tag('br');

        $buttonlabel = get_string('viewreportbtn', 'adaptivequiz');
        $params = array('type' => 'submit', 'value' => $buttonlabel, 'class' => 'submitbtns adaptivequizbtn');
        $html .= html_writer::empty_tag('input', $params);
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * This function sets up the javascript required by the page
     * @return array a standard jsmodule structure.
     */
    public function adaptivequiz_get_js_module() {
        return array(
            'name' => 'mod_adaptivequiz',
            'fullpath' => '/mod/adaptivequiz/module.js',
            'requires' => array('base', 'dom', 'event-delegate', 'event-key', 'core_question_engine', 'moodle-core-formchangechecker'),
            'strings' => array(array('cancel', 'moodle'), array('changesmadereallygoaway', 'moodle')),
        );
    }

    /**
     * This function generates the HTML markup to render the submission form
     * @param int $cmid: course module id
     * @param question_usage_by_activity $quba: a question usage by activity object
     * @param int $slot: slot number of the question to be displayed
     * @param int $level: difficulty level of question
     * @return string - HTML markup
     */
    public function create_submit_form($cmid, $quba, $slot, $level) {
        $output = '';

        $processurl = new moodle_url('/mod/adaptivequiz/attempt.php');

        // Start the form.
        $attr = array('action' => $processurl, 'method' => 'post', 'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8', 'id' => 'responseform');
        $output .= html_writer::start_tag('form', $attr);
        $output .= html_writer::start_tag('div');

        // Print the question
        $options = new question_display_options();
        $options->hide_all_feedback();
        $options->flags = question_display_options::HIDDEN;
        $options->marks = question_display_options::MAX_ONLY;

        $output .= $quba->render_question($slot, $options);

        $output .= html_writer::start_tag('div', array('class' => 'submitbtns adaptivequizbtn'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submitanswer', 'value' => get_string('submitanswer', 'mod_adaptivequiz')));
        $output .= html_writer::end_tag('div');

        // Some hidden fields to track what is going on.
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'cmid', 'value' => $cmid));

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'uniqueid', 'value' => $quba->get_id()));

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'slots', 'value' => $slot));

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'dl', 'value' => $level));

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * This function initializing the metadata that needs to be included in the page header
     * before the page is rendered.
     * @param question_usage_by_activity $quba: a question usage by activity object
     * @param int $slot: slot number of the question to be displayed
     * @return void
     */
    public function init_metadata($quba, $slot) {
        $meta = $quba->render_question_head_html($slot);
        $meta .= question_engine::initialise_js();
        return $meta;
    }

    /**
     * This function prints the question
     * @param int $cmid: course module id
     * @param question_usage_by_activity $quba: a question usage by activity object
     * @param int $slot: slot number of the question to be displayed
     * @param int $level: difficulty level of question
     * @return string - HTML markup
     */
    public function print_question($cmid, $quba, $slot, $level) {
        $output = '';
        $output .= $this->header();
        $output .= $this->create_submit_form($cmid, $quba, $slot, $level);
        $output .= $this->footer();
        return $output;
    }

    /**
     * This function the attempt feedback
     * @param string $attemptfeedback attempt feedback
     * @param int $cmid course module id
     * @return string HTML markup
     */
    public function print_attemptfeedback($attemptfeedback, $cmid) {
        $output = '';
        $output .= $this->header();
        $output .= $this->create_attemptfeedback($attemptfeedback, $cmid);
        $output .= $this->footer();
        return $output;
    }

    /**
     * This function the attempt feedback
     * @param string $attemptfeedback attempt feedback
     * @param int $cmid course module id
     * @return string HTML markup
     */
    public function create_attemptfeedback($attemptfeedback, $cmid) {
        $output = '';
        $url = new moodle_url('/mod/adaptivequiz/view.php');
        $attr = array('action' => $url, 'method' => 'post', 'id' => 'attemptfeedback');
        $output .= html_writer::start_tag('form', $attr);
        $output .= html_writer::tag('p', s($attemptfeedback), array('class' => 'submitbtns adaptivequizfeedback'));
        $attr = array('type' => 'submit', 'name' => 'attemptfinished', 'value' => get_string('continue'));
        $output .= html_writer::empty_tag('input', $attr);
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cmid));
        $output .= html_writer::end_tag('form');

        return $output;
    }
}