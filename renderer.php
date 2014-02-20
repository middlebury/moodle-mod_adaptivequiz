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
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/adaptivequiz/requiredpassword.class.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/catalgo.class.php');

class mod_adaptivequiz_renderer extends plugin_renderer_base {
    /** @var string $sortdir the sorting direction being used */
    protected $sortdir = '';
    /** @var moodle_url $sorturl the current base url used for keeping the table sorted */
    protected $sorturl = '';
    /** @var int $groupid variable used to reference the groupid that is currently being used to filter by */
    public $groupid = 0;
    /** @var array options that should be used for opening the secure popup. */
    protected static $popupoptions = array(
        'left' => 0,
        'top' => 0,
        'fullscreen' => true,
        'scrollbars' => false,
        'resizeable' => false,
        'directories' => false,
        'toolbar' => false,
        'titlebar' => false,
        'location' => false,
        'status' => false,
        'menubar' => false
    );

    /**
     * This function displays a form with a button to start the assessment attempt
     * @param string $cmid course module id
     * @return string HTML markup displaying the description and form with a submit button
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
        $params = array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey());
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
     * This function displays a form with a button to view the question analysis report
     * @param string $cmid: course module id
     * @return string - HTML markup displaying the description and form with a submit button
     */
    public function display_question_analysis_form($cmid) {
        $html = '';

        $param = array('cmid' => $cmid);
        $target = new moodle_url('/mod/adaptivequiz/questionanalysis/overview.php', $param);
        $attributes = array('method' => 'POST', 'action' => $target);

        $html .= html_writer::start_tag('form', $attributes);

        $html .= html_writer::empty_tag('br');
        $html .= html_writer::empty_tag('br');

        $buttonlabel = get_string('questionanalysisbtn', 'adaptivequiz');
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
            'requires' => array('base', 'dom', 'event-delegate', 'event-key', 'core_question_engine',
                'moodle-core-formchangechecker'),
            'strings' => array(array('cancel', 'moodle'), array('changesmadereallygoaway', 'moodle'),
                array('functiondisabledbysecuremode', 'adaptivequiz'))
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
        $attr = array('action' => $processurl, 'method' => 'post', 'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
            'id' => 'responseform');
        $output .= html_writer::start_tag('form', $attr);
        $output .= html_writer::start_tag('div');

        // Print the question.
        $options = new question_display_options();
        $options->hide_all_feedback();
        $options->flags = question_display_options::HIDDEN;
        $options->marks = question_display_options::MAX_ONLY;

        $output .= $quba->render_question($slot, $options);

        $output .= html_writer::start_tag('div', array('class' => 'submitbtns adaptivequizbtn'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submitanswer',
            'value' => get_string('submitanswer', 'mod_adaptivequiz')));
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
     * @param question_usage_by_activity $quba a question usage by activity object
     * @param int|array $slots slot number of the question to be displayed or an array of slot numbers
     * @return string HTML header information for displaying the question
     */
    public function init_metadata($quba, $slots) {
        $meta = '';

        if (is_array($slots)) {
            foreach ($slots as $slot) {
                $meta .= $quba->render_question_head_html($slot);
            }
        } else {
            $meta .= $quba->render_question_head_html($slots);
        }

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
     * @param bool $popup true if the attempt is using a popup window
     * @return string HTML markup
     */
    public function print_attemptfeedback($attemptfeedback, $cmid, $popup = false) {
        $output = '';
        $output .= $this->header();
        $output .= $this->create_attemptfeedback($attemptfeedback, $cmid, $popup);
        $output .= $this->footer();
        return $output;
    }

    /**
     * This function the attempt feedback
     * @param string $attemptfeedback attempt feedback
     * @param int $cmid course module id
     * @param bool $popup true if the attempt is using a popup window
     * @return string HTML markup
     */
    public function create_attemptfeedback($attemptfeedback, $cmid, $popup = false) {
        $output = '';
        $url = new moodle_url('/mod/adaptivequiz/view.php');
        $attr = array('action' => $url, 'method' => 'post', 'id' => 'attemptfeedback');
        $output .= html_writer::start_tag('form', $attr);
        $output .= html_writer::tag('p', s($attemptfeedback), array('class' => 'submitbtns adaptivequizfeedback'));

        if (empty($popup)) {
            $attr = array('type' => 'submit', 'name' => 'attemptfinished', 'value' => get_string('continue'));
            $output .= html_writer::empty_tag('input', $attr);
        } else {
            // In a 'secure' popup window.
            $this->page->requires->js_init_call('M.mod_adaptivequiz.secure_window.init_close_button', array($url),
                $this->adaptivequiz_get_js_module());
            $output .= html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('continue'),
                'id' => 'secureclosebutton'));
        }

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cmid));
        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * Output a page with an optional message, and JavaScript code to close the
     * current window and redirect the parent window to a new URL.
     * @param moodle_url $url the URL to redirect the parent window to.
     * @param string $message message to display before closing the window. (optional)
     * @return string HTML to output.
     */
    public function close_attempt_popup($url, $message = '') {
        $output = '';
        $output .= $this->header();
        $output .= $this->box_start();

        if ($message) {
            $output .= html_writer::tag('p', $message);
            $output .= html_writer::tag('p', get_string('windowclosing', 'quiz'));
            $delay = 5;
        } else {
            $output .= html_writer::tag('p', get_string('pleaseclose', 'quiz'));
            $delay = 0;
        }
        $this->page->requires->js_init_call('M.mod_quiz.secure_window.close',
                array($url, $delay), false, adaptivequiz_get_js_module());

        $output .= $this->box_end();
        $output .= $this->footer();
        return $output;
    }

    /**
     * This function returns page header information to be printed to the page
     * @return string HTML markup for header inforation
     */
    public function print_header() {
        return $this->header();
    }

    /**
     * This function returns page footer information to be printed to the page
     * @return string HTML markup for footer inforation
     */
    public function print_footer() {
        return $this->footer();
    }

    /**
     * This function returns the HTML markup to display a table of the attempts taken at the activity
     * @param stdClass $records attempt records from adaptivequiz_attempt table
     * @param stdClass $cm course module object set to the instance of the activity
     * @param string $sort the column the the table is to be sorted by
     * @param string $sortdir the direction of the sort
     * @return string HTML markup
     */
    public function print_report_table($records, $cm, $sort, $sortdir) {
        $output = $this->heading(get_string('activityreports', 'adaptivequiz'));

        $output .= html_writer::start_tag('div', array('class' => 'adpq_download'));
        $csvurl = new moodle_url('/mod/adaptivequiz/viewreport.php',
            array('cmid' => $cm->id, 'download' => 'csv', 'sort' => $sort, 'sortdir' => $sortdir));
        $output .= html_writer::link($csvurl, get_string('downloadcsv', 'adaptivequiz'));
        $output .= html_writer::end_tag('div');

        $output .= $this->create_report_table($records, $cm, $sort, $sortdir);
        return $output;
    }

    /**
     * This function returns HTML markup to display a table of a users's attempt
     * @param stdClass $records an array of user attempt table objects
     * @param stdClass $cm course module object set to the instance of the activity
     * @param stdClass $user a user table record
     * @return string HTML markup
     */
    public function print_attempt_report_table($records, $cm, $user) {
        $record = current($records);
        $profileurl = new moodle_url('/user/profile.php', array('id' => $record->userid));
        $namelink = html_writer::link($profileurl, fullname($user));
        $output = $this->heading(get_string('indvuserreport', 'adaptivequiz', $namelink));
        $output .= $this->create_attempt_report_table($records, $cm);
        return $output;
    }

    /**
     * This function generates the HTML required to display the initial reports table
     * @param stdClass $records attempt records from adaptivequiz_attempt table
     * @param stdClass $cm course module object set to the instance of the activity
     * @param string $sort the column the the table is to be sorted by
     * @param string $sortdir the direction of the sort
     * @return string HTML markup
     */
    public function create_report_table($records, $cm, $sort, $sortdir) {
        $output = '';

        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizsummaryofattempt boxaligncenter';
        $table->head = $this->format_report_table_headers($cm, $sort, $sortdir);
        $table->align = array('center', 'center', 'center', 'center', 'center', '');
        $table->size = array('', '', '', '', '');

        $table->data = array();
        $this->get_report_table_rows($records, $cm, $table);
        $output .= html_writer::table($table);

        return $output;
    }

    /**
     * This function generates the HTML required to the attempts report table
     * @param stdClass $records an array of user attempt table objects
     * @param stdClass $cm course module object set to the instance of the activity
     * @return string HTML markup
     */
    protected function create_attempt_report_table($records, $cm) {
        $output = '';

        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizsummaryofuserattempt boxaligncenter';

        $attemptstate = get_string('attemptstate', 'adaptivequiz');
        $attemptstopcriteria = get_string('attemptstopcriteria', 'adaptivequiz');
        $questionsattempted = get_string('questionsattempted', 'adaptivequiz');
        $score = get_string('score', 'adaptivequiz');
        $timemodified = get_string('attemptfinishedtimestamp', 'adaptivequiz');
        $timecreated = get_string('attemptstarttime', 'adaptivequiz');

        $table->head = array($attemptstate, $attemptstopcriteria, $questionsattempted, $score, $timecreated, $timemodified, '');
        $table->align = array('center', 'center', 'center', 'center', 'center', 'center', 'center');
        $table->size = array('', '', '', '', '', '');
        $table->data = array();

        $this->get_attempt_report_table_rows($records, $cm, $table);
        $output .= html_writer::table($table);

        return $output;
    }

    /**
     * This function generates the attempt report rows
     * @param stdClass an array of user attempt table objects
     * @param stdClass $cm course module object set to the instance of the activity
     * @param html_table $table an instance of the html_table class
     */
    protected function get_attempt_report_table_rows($records, $cm, $table) {
        $row = array();
        $attemptstate = '';

        foreach ($records as $record) {
            $reviewurl = new moodle_url('/mod/adaptivequiz/reviewattempt.php',
                array('uniqueid' => $record->uniqueid, 'cmid' => $cm->id, 'userid' => $record->userid));
            $link = html_writer::link($reviewurl, get_string('reviewattempt', 'adaptivequiz'));
            if ($record->attemptstate != ADAPTIVEQUIZ_ATTEMPT_COMPLETED) {
                $closeurl = new moodle_url('/mod/adaptivequiz/closeattempt.php',
                    array('uniqueid' => $record->uniqueid, 'cmid' => $cm->id, 'userid' => $record->userid));
                $closelink = html_writer::link($closeurl, get_string('closeattempt', 'adaptivequiz'));
            } else {
                $closelink = '';
            }
            $deleteurl = new moodle_url('/mod/adaptivequiz/delattempt.php',
                array('uniqueid' => $record->uniqueid, 'cmid' => $cm->id, 'userid' => $record->userid));
            $dellink = html_writer::link($deleteurl, get_string('deleteattemp', 'adaptivequiz'));

            if (0 == strcmp('inprogress', $record->attemptstate)) {
                $attemptstate = get_string('recentinprogress', 'adaptivequiz');
            } else {
                $attemptstate = get_string('recentcomplete', 'adaptivequiz');
            }

            $measure = $this->format_measure_and_standard_error($record);

            $row = array($attemptstate, format_string($record->attemptstopcriteria), $record->questionsattempted, $measure,
                    userdate($record->timecreated), userdate($record->timemodified),
                    $link.($closelink ? '&nbsp;&nbsp;'.$closelink : '').'&nbsp;&nbsp;'.$dellink);
            $table->data[] = $row;
            $table->rowclasses[] = 'studentattempt';
        }
    }

    /**
     * This function creates the table header links that will be used to allow instructor to sort the data
     * @param stdClass $cm a course module object set to the instance of the activity
     * @param string $sort the column the the table is to be sorted by
     * @param string $sortdir the direction of the sort
     * @return array an array of column headers (firstname / lastname, number of attempts, standard error)
     */
    public function format_report_table_headers($cm, $sort, $sortdir) {
        global $OUTPUT;

        $newsortdir = '';
        $columnicon = '';
        $firstname = '';
        $lastname = '';
        $email = '';
        $numofattempts = '';
        $measure = '';
        $standarderror = '';
        $timemodified = '';

        /* Determine the next sorting direction and icon to display */
        switch ($sortdir) {
            case 'ASC':
                $imageparam = array('src' => $OUTPUT->pix_url('t/down'), 'alt' => '');
                $columnicon = html_writer::empty_tag('img', $imageparam);
                $newsortdir = 'DESC';
                break;
            default:
                $imageparam = array('src' => $OUTPUT->pix_url('t/up'), 'alt' => '');
                $columnicon = html_writer::empty_tag('img', $imageparam);
                $newsortdir = 'ASC';
                break;
        }

        /* Set the sort direction class variable */
        $this->sortdir = $sortdir;

        /* Create header links */
        $param = array('cmid' => $cm->id, 'sort' => 'firstname', 'sortdir' => 'ASC', 'group' => $this->groupid);
        $firstnameurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'lastname';
        $lastnameurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'email';
        $emailurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'attempts';
        $numofattemptsurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'measure';
        $measureurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'stderror';
        $standarderrorurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'timemodified';
        $timemodifiedurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);

        /* Update column header links with a sorting directional icon */
        switch ($sort) {
            case 'firstname':
                $firstnameurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $firstnameurl;
                $firstname .= '&nbsp;'.$columnicon;
                break;
            case 'lastname':
                $lastnameurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $lastnameurl;
                $lastname .= '&nbsp;'.$columnicon;
                break;
            case 'email':
                $emailurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $emailurl;
                $email .= '&nbsp;'.$columnicon;
                break;
            case 'attempts':
                $numofattemptsurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $numofattemptsurl;
                $numofattempts .= '&nbsp;'.$columnicon;
                break;
            case 'measure':
                $measureurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $measureurl;
                $measure .= '&nbsp;'.$columnicon;
                break;
            case 'stderror':
                $standarderrorurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $standarderrorurl;
                $standarderror .= '&nbsp;'.$columnicon;
                break;
            case 'timemodified':
                $timemodifiedurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $timemodifiedurl;
                $timemodified .= '&nbsp;'.$columnicon;
                break;
        }

        // Create header HTML markup.
        $firstname = html_writer::link($firstnameurl, get_string('firstname')).$firstname;
        $lastname = html_writer::link($lastnameurl, get_string('lastname')).$lastname;
        $email = html_writer::link($emailurl, get_string('email')).$email;
        $numofattempts = html_writer::link($numofattemptsurl, get_string('numofattemptshdr', 'adaptivequiz')).$numofattempts;
        $measure = html_writer::link($measureurl, get_string('bestscore', 'adaptivequiz')).$measure;
        $standarderror = html_writer::link($standarderrorurl, get_string('bestscorestderror', 'adaptivequiz')).$standarderror;
        $timemodified = html_writer::link($timemodifiedurl, get_string('attemptfinishedtimestamp', 'adaptivequiz')).$timemodified;

        return array($firstname.' / '.$lastname, $email, $numofattempts, $measure, $standarderror, $timemodified);
    }

    /**
     * This function adds rows to the html_table object
     * @param stdClass $records adaptivequiz_attempt records
     * @param stdClass $cm course module object set to the instance of the activity
     * @param html_table $table an instance of the html_table class
     */
    protected function get_report_table_rows($records, $cm, $table) {
        $row = array();

        foreach ($records as $record) {
            $attemptlink = new moodle_url('/mod/adaptivequiz/viewattemptreport.php',
                array('userid' => $record->id, 'cmid' => $cm->id));
            $link = html_writer::link($attemptlink, $record->attempts);
            $measure = $this->format_measure($record);
            if ($record->uniqueid) {
                $attemptlink = new moodle_url('/mod/adaptivequiz/reviewattempt.php',
                    array('userid' => $record->id, 'uniqueid' => $record->uniqueid, 'cmid' => $cm->id));
                $measure = html_writer::link($attemptlink, $measure);
            }
            $stderror = $this->format_standard_error($record);
            if (intval($record->timemodified)) {
                $timemodified = userdate(intval($record->timemodified));
            } else {
                $timemodified = get_string('na', 'adaptivequiz');
            }
            $profileurl = new moodle_url('/user/profile.php', array('id' => $record->id));
            $name = $record->firstname.' '.$record->lastname;
            $namelink = html_writer::link($profileurl, $name);
            $emaillink = html_writer::link('mailto:'.$record->email, $record->email);
            $row = array($namelink, $emaillink, $link, $measure, $stderror, $timemodified);
            $table->data[] = $row;
            $table->rowclasses[] = 'studentattempt';
        }
    }

    /**
     * This function prints paging information
     * @param int $totalrecords the total number of records returned
     * @param int $page the current page the user is on
     * @param int $perpage the number of records displayed on one page
     * @return string HTML markup
     */
    public function print_paging_bar($totalrecords, $page, $perpage) {
        global $OUTPUT;

        $baseurl = $this->sorturl;
        /* Set the currently set group filter and sort dir */
        $baseurl->params(array('group' => $this->groupid, 'sortdir' => $this->sortdir));

        $output = '';
        $output .= $OUTPUT->paging_bar($totalrecords, $page, $perpage, $baseurl);
        return $output;
    }

    /**
     * This function prints a grouping selector
     * @param stdClass $cm course module object set to the instance of the activity
     * @param stdClass $course a data record for the current course
     * @param stdClass $context the context instance for the activity
     * @param int $userid the current user id
     * @return string HTML markup
     */
    public function print_groups_selector($cm, $course, $context, $userid) {
        $output = '';
        $groupmode = groups_get_activity_groupmode($cm, $course);

        if (0 != $groupmode) {
            $baseurl = new moodle_url('/mod/adaptivequiz/viewreport.php', array('cmid' => $cm->id));
            $output = groups_print_activity_menu($cm, $baseurl, true);
        }

        return $output;
    }

    /**
     * Initialized secure browsing mode
     */
    public function init_browser_security() {
        $this->page->set_popup_notification_allowed(false); // Prevent message notifications.
        $this->page->set_cacheable(false);
        $this->page->set_pagelayout('popup');

        $this->page->add_body_class('quiz-secure-window');
        $this->page->requires->js_init_call('M.mod_adaptivequiz.secure_window.init',
                null, false, $this->adaptivequiz_get_js_module());
    }

    /**
     * This functions prints the start attempt button to start a secured browser attempt
     * TODO: fix function name typo
     * @param int $cmid course module id
     * @return string HTML markup for a button
     */
    public function display_start_attempt_form_scured($cmid) {
        $param = array('cmid' => $cmid);
        $url = new moodle_url('/mod/adaptivequiz/attempt.php', $param);

        $buttonlabel = get_string('startattemptbtn', 'adaptivequiz');
        $button = new single_button($url, $buttonlabel);
        $button->class .= ' adaptivequizstartbuttondiv';

        $this->page->requires->js_module($this->adaptivequiz_get_js_module());
        $this->page->requires->js('/mod/adaptivequiz/module.js');

        $popupaction = new popup_action('click', $url, 'adaptivequizpopup', self::$popupoptions);
        $button->class .= ' adaptivequizsecuremoderequired';
        $button->add_action(new component_action('click',
                'M.mod_adaptivequiz.secure_window.start_attempt_action', array(
                    'url' => $url->out(false),
                    'windowname' => 'adaptivequizpopup',
                    'options' => $popupaction->get_js_options(),
                    'fullscreen' => true,
                    'startattemptwarning' => '',
                )));

        $warning = html_writer::tag('noscript', $this->heading(get_string('noscript', 'quiz')));

        return $this->render($button).$warning;
    }

    /**
     * This function displays a form for users to enter a password before entering the attempt
     * @param int $cmid course module id
     * @return mod_adaptivequiz_requiredpassword instance of a formslib object
     */
    public function display_password_form($cmid) {
        $url = new moodle_url('/mod/adaptivequiz/attempt.php');
        return new mod_adaptivequiz_requiredpassword($url->out_omit_querystring(),
            array('hidden' => array('cmid' => $cmid, 'uniqueid' => 0)));
    }

    /**
     * This function prints a paging link for review attemtps page
     * @param question_usage_by_activity $quba initialized to the attempt's unique id
     * @param int $page the the page that is currently selected
     * @param int $cmid the course module id of the activity
     * @param int $userid the user id
     * @return string HTML markup for paging links or nothing if there is only one page
     */
    public function print_questions_for_review_pager($quba, $page, $cmid, $userid) {
        $questslots = $quba->get_slots();
        $output = '';
        $url = '';
        $attr = array('class' => 'viewattemptreportpages');
        $pages = ceil(count($questslots) / ADAPTIVEQUIZ_REV_QUEST_PER_PAGE);

        // Don't print anything if there is only one page.
        if (1 == $pages) {
            return '';
        }

        // Print base url for page links.
        $url = new moodle_url('/mod/adaptivequiz/reviewattempt.php',
            array('cmid' => $cmid, 'uniqueid' => $quba->get_id(), 'userid' => $userid));

        // Print all of the page links.
        $output .= html_writer::start_tag('center');
        for ($i = 0; $i < $pages; $i++) {
            // If we are currently on this page, then don't make it an anchor tag.
            if ($i == $page) {
                $output .= '&nbsp'.html_writer::tag('span', $i + 1, $attr).'&nbsp';
                continue;
            }

            $url->params(array('page' => $i));
            $output .= '&nbsp'.html_writer::link($url, $i + 1, $attr).'&nbsp';
        }
        $output .= html_writer::end_tag('center');

        return $output;
    }

    /**
     * This function returns HTML markup of questions and student's responses
     * @param question_usage_by_activity $quba initialized to the attempt's unique id
     * @param int $offset an offset used to determine which question to start processing from
     * @param stdClass $user user object for the user whos attempt is being reviewed
     * @param int $timestamp time attmept was last modified
     * @return string HTML markup
     */
    public function print_questions_for_review($quba, $offset = 0, $user, $timestamp) {
        $questslots = $quba->get_slots();
        $attr = array('class' => 'questiontags');
        $offset *= ADAPTIVEQUIZ_REV_QUEST_PER_PAGE;

        // Setup heading formation.
        $a = new stdClass();
        $a->fullname = fullname($user);
        $a->finished = userdate($timestamp);
        $output = $this->heading(get_string('reviewattemptreport', 'adaptivequiz', $a));

        // Take a portion of the array of question slots for display.
        $pageqslots = array_slice($questslots, $offset, ADAPTIVEQUIZ_REV_QUEST_PER_PAGE);

        // Setup display options.
        $options = new question_display_options();
        $options->readonly = true;
        $options->flags = question_display_options::HIDDEN;
        $options->marks = question_display_options::MAX_ONLY;
        $options->rightanswer = question_display_options::VISIBLE;
        $options->correctness = question_display_options::VISIBLE;
        $options->numpartscorrect = question_display_options::VISIBLE;

        // Setup quesiton header metadata.
        $output .= $this->init_metadata($quba, $pageqslots);

        foreach ($pageqslots as $slot) {
            $output .= html_writer::empty_tag('hr');

            $label = html_writer::tag('label', get_string('questionnumber', 'adaptivequiz'));
            $output .= html_writer::tag('div', $label.': '.format_string($slot));

            // Retrieve question attempt object.
            $questattempt = $quba->get_question_attempt($slot);
            // Get question definition object.
            $questdef = $questattempt->get_question();
            // Retrieve the tags associated with this question.
            $qtags = tag_get_tags_array('question', $questdef->id);

            $label = html_writer::tag('label', get_string('attemptquestion_level', 'adaptivequiz'));
            $output .= html_writer::tag('div', $label.': '.format_string(adaptivequiz_get_difficulty_from_tags($qtags)));

            $label = html_writer::tag('label', get_string('tags'));
            $output .= html_writer::tag('div', $label.': '.format_string(implode(' ', $qtags)), $attr);

            $output .= $quba->render_question($slot, $options);
        }

        return $output;
    }

    /**
     * This function prints a form and a button that is centered on the page, then the user clicks on the button the user is taken
     * to the url
     * @param moodle_url $url a url
     * @param string $buttontext button caption
     * @return string - HTML markup displaying the description and form with a submit button
     */
    public function print_form_and_button($url, $buttontext) {
        $html = '';

        $attributes = array('method' => 'POST', 'action' => $url);

        $html .= html_writer::start_tag('form', $attributes);
        $html .= html_writer::empty_tag('br');
        $html .= html_writer::empty_tag('br');
        $html .= html_writer::start_tag('center');

        $params = array('type' => 'submit', 'value' => $buttontext, 'class' => 'submitbtns adaptivequizbtn');
        $html .= html_writer::empty_tag('input', $params);
        $html .= html_writer::end_tag('center');
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * This function formats the ability measure into a user friendly format
     * @param stdClass an object with the following properties: measure, highestlevel, lowestlevel and stderror.  The values must
     *      come from the activty instance and the user's
     * attempt record
     * @return string a user friendly format of the ability measure.  Ability measure is rounded to the nearest decimal.
     */
    public function format_measure($record) {
        if (is_null($record->measure)) {
            return 'n/a';
        }
        return round(catalgo::map_logit_to_scale($record->measure, $record->highestlevel, $record->lowestlevel), 1);
    }

    /**
     * This function formats the standard error into a user friendly format
     * @param stdClass an object with the following properties: measure, highestlevel, lowestlevel and stderror.  The values must
     *      come from the activty instance and the user's
     * attempt record
     * @return string a user friendly format of the standard error. Standard error is
     * rounded to the nearest one hundredth then multiplied by 100
     */
    public function format_standard_error($record) {
        if (is_null($record->stderror) || $record->stderror == 0.0) {
            return 'n/a';
        }
        $percent = round(catalgo::convert_logit_to_percent($record->stderror), 2) * 100;
        return '&plusmn; '.$percent.'%';
    }

    /**
     * This function formats the standard error and ability measure into a user friendly format
     * @param stdClass an object with the following properties: measure, highestlevel, lowestlevel and stderror.  The values must
     *      come from the activty instance and the user's
     * attempt record
     * @return string a user friendly format of the ability measure and standard error.  Ability measure is rounded to the nearest
     *      decimal.  Standard error is rounded to the
     * nearest one hundredth then multiplied by 100
     */
    protected function format_measure_and_standard_error($record) {
        if (is_null($record->measure) || is_null($record->stderror) || $record->stderror == 0.0) {
            return 'n/a';
        }
        $measure = round(catalgo::map_logit_to_scale($record->measure, $record->highestlevel, $record->lowestlevel), 1);
        $percent = round(catalgo::convert_logit_to_percent($record->stderror), 2) * 100;
        $format = $measure.' &plusmn; '.$percent.'%';
        return $format;
    }

    /**
     * Answer the summery information about an attempt
     *
     * @param stdClass $adaptivequiz the attempt record.
     * @param stdClass $user the user who took the quiz that created the attempt
     * @return string
     */
    public function get_attempt_summary_listing($adaptivequiz, $user) {
        $html = '';
        $html .= html_writer::start_tag('dl', array('class' => 'adaptivequiz-summarylist'));
        $html .= html_writer::tag('dt', get_string('attempt_user', 'adaptivequiz').': ');
        $html .= html_writer::tag('dd', $user->firstname." ".$user->lastname." (".$user->email.")");
        $html .= html_writer::tag('dt', get_string('attempt_state', 'adaptivequiz').': ');
        $html .= html_writer::tag('dd', $adaptivequiz->attemptstate);
        $html .= html_writer::tag('dt', get_string('score', 'adaptivequiz').': ');
        $abilityfraction = 1 / ( 1 + exp( (-1 * $adaptivequiz->measure) ) );
        $ability = (($adaptivequiz->highestlevel - $adaptivequiz->lowestlevel) * $abilityfraction) + $adaptivequiz->lowestlevel;
        $stderror = catalgo::convert_logit_to_percent($adaptivequiz->standarderror);
        if ($stderror > 0) {
            $score = round($ability, 2)." &nbsp; &plusmn; ".round($stderror * 100, 1)."%";
        } else {
            $score = 'n/a';
        }
        $html .= html_writer::tag('dd', $score);
        $html .= html_writer::end_tag('dl');

        $html .= html_writer::start_tag('dl', array('class' => 'adaptivequiz-summarylist'));
        $html .= html_writer::tag('dt', get_string('attemptstarttime', 'adaptivequiz').': ');
        $html .= html_writer::tag('dd', userdate($adaptivequiz->timecreated));
        $html .= html_writer::tag('dt', get_string('attemptfinishedtimestamp', 'adaptivequiz').': ');
        $html .= html_writer::tag('dd', userdate($adaptivequiz->timemodified));
        $html .= html_writer::tag('dt', get_string('attempttotaltime', 'adaptivequiz').': ');
        $totaltime = $adaptivequiz->timemodified - $adaptivequiz->timecreated;
        $hours = floor($totaltime / 3600);
        $remainder = $totaltime - ($hours * 3600);
        $minutes = floor($remainder / 60);
        $seconds = $remainder - ($minutes * 60);
        $html .= html_writer::tag('dd', sprintf('%02d', $hours).":".sprintf('%02d', $minutes).":".sprintf('%02d', $seconds));
        $html .= html_writer::tag('dt', get_string('attemptstopcriteria', 'adaptivequiz').': ');
        $html .= html_writer::tag('dd', $adaptivequiz->attemptstopcriteria);
        $html .= html_writer::end_tag('dl');
        return $html;
    }

    /**
     * Answer a table of the question difficulties and the intermediate scores
     * throughout the attempt.
     *
     * @param stdClass $adaptivequiz the quiz attempt record
     * @param question_usage_by_activity $quba the questions used in this attempt
     * @return string
     */
    public function get_attempt_scoring_table($adaptivequiz, $quba) {
        $table = new html_table();

        $num = get_string('attemptquestion_num', 'adaptivequiz');
        $level = get_string('attemptquestion_level', 'adaptivequiz');
        $rightwrong = get_string('attemptquestion_rightwrong', 'adaptivequiz');
        $ability = get_string('attemptquestion_ability', 'adaptivequiz');
        $error = get_string('attemptquestion_error', 'adaptivequiz');

        $table->head = array($num, $level, $rightwrong, $ability, $error);
        $table->align = array('center', 'center', 'center', 'center', 'center');
        $table->size = array('', '', '', '', '', '');
        $table->data = array();

        $numattempted = 0;
        $difficultysum = 0;
        $sumcorrect = 0;
        $sumincorrect = 0;
        foreach ($quba->get_slots() as $slot) {
            $question = $quba->get_question($slot);
            $tags = tag_get_tags_array('question', $question->id);
            $qdifficulty = adaptivequiz_get_difficulty_from_tags($tags);
            $qdifficultylogits = catalgo::convert_linear_to_logit($qdifficulty, $adaptivequiz->lowestlevel,
                $adaptivequiz->highestlevel);
            $correct = ($quba->get_question_mark($slot) > 0);

            $numattempted++;
            $difficultysum = $difficultysum + $qdifficultylogits;
            if ($correct) {
                $sumcorrect++;
            } else {
                $sumincorrect++;
            }

            $abilitylogits = catalgo::estimate_measure($difficultysum, $numattempted, $sumcorrect, $sumincorrect);
            $abilityfraction = 1 / ( 1 + exp( (-1 * $abilitylogits) ) );
            $ability = (($adaptivequiz->highestlevel - $adaptivequiz->lowestlevel) * $abilityfraction) + $adaptivequiz->lowestlevel;

            $stderrorlogits = catalgo::estimate_standard_error($numattempted, $sumcorrect, $sumincorrect);
            $stderror = catalgo::convert_logit_to_percent($stderrorlogits);

            $table->data[] = array($slot, $qdifficulty, ($correct ? 'r' : 'w'), round($ability, 2),
                    round($stderror * 100, 1)."%");
        }
        return html_writer::table($table);
    }

    /**
     * Answer a table of the question difficulties and the number of questions answered
     * right and wrong for each difficulty.
     *
     * @param stdClass $adaptivequiz the quiz attempt record
     * @param question_usage_by_activity $quba the questions used in this attempt
     * @return string
     */
    public function get_attempt_distribution_table($adaptivequiz, $quba) {
        $table = new html_table();

        $level = get_string('attemptquestion_level', 'adaptivequiz');
        $numright = get_string('numright', 'adaptivequiz');
        $numwrong = get_string('numwrong', 'adaptivequiz');

        $table->head = array($level, $numright, $numwrong);
        $table->align = array('center', 'center', 'center');
        $table->size = array('', '', '' );
        $table->data = array();

        // Set up our data arrays.
        $qdifficulties = array();
        $rightanswers = array();
        $wronganswers = array();

        for ($i = $adaptivequiz->lowestlevel; $i <= $adaptivequiz->highestlevel; $i++) {
            $qdifficulties[] = intval($i);
            $rightanswers[] = 0;
            $wronganswers[] = 0;
        }

        foreach ($quba->get_slots() as $i => $slot) {
            $question = $quba->get_question($slot);
            $tags = tag_get_tags_array('question', $question->id);
            $qdifficulty = adaptivequiz_get_difficulty_from_tags($tags);
            $correct = ($quba->get_question_mark($slot) > 0);

            $position = array_search($qdifficulty, $qdifficulties);
            if ($correct) {
                $rightanswers[$position]++;
            } else {
                $wronganswers[$position]++;
            }
        }

        foreach ($qdifficulties as $key => $val) {
            $table->data[] = array(
                $val,
                $rightanswers[$key],
                $wronganswers[$key],
            );
        }

        return html_writer::table($table);
    }
}

/**
 * A substitute renderer class that outputs CSV results instead of HTML.
 */
class mod_adaptivequiz_csv_renderer extends mod_adaptivequiz_renderer {
    /**
     * This function returns page header information to be printed to the page
     * @return string HTML markup for header inforation
     */
    public function print_header() {
        header('Content-type: text/csv');
        $filename = $this->page->title;
        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $filename);
        $filename = preg_replace('/_{2,}/', '_', $filename);
        $filename = $filename.'.csv';
        header("Content-Disposition: attachment; filename=$filename");
    }

    /**
     * This function returns page footer information to be printed to the page
     * @return string HTML markup for footer inforation
     */
    public function print_footer() {
        // Do nothing.
    }

    /**
     * This function prints paging information
     * @param int $totalrecords the total number of records returned
     * @param int $page the current page the user is on
     * @param int $perpage the number of records displayed on one page
     * @return string HTML markup
     */
    public function print_paging_bar($totalrecords, $page, $perpage) {
        // Do nothing.
    }

    /**
     * This function returns the HTML markup to display a table of the attempts taken at the activity
     * @param stdClass $records attempt records from adaptivequiz_attempt table
     * @param stdClass $cm course module object set to the instance of the activity
     * @param string $sort the column the the table is to be sorted by
     * @param string $sortdir the direction of the sort
     * @return string HTML markup
     */
    public function print_report_table($records, $cm, $sort, $sortdir) {
        ob_start();
        $output = fopen('php://output', 'w');

        $headers = array(
            get_string('firstname'),
            get_string('lastname'),
            get_string('email'),
            get_string('numofattemptshdr', 'adaptivequiz'),
            get_string('bestscore', 'adaptivequiz'),
            get_string('bestscorestderror', 'adaptivequiz'),
            get_string('attemptfinishedtimestamp', 'adaptivequiz'),
        );
        fputcsv($output, $headers);

        foreach ($records as $record) {
            if (intval($record->timemodified)) {
                $timemodified = date('c', intval($record->timemodified));
            } else {
                $timemodified = get_string('na', 'adaptivequiz');
            }

            $row = array(
                $record->firstname,
                $record->lastname,
                $record->email,
                $record->attempts,
                $this->format_measure($record),
                $this->format_standard_error($record),
                $timemodified,
            );

            fputcsv($output, $row);
        }

        return ob_get_clean();
    }

    /**
     * This function formats the ability measure into a user friendly format
     * @param stdClass an object with the following properties: measure, highestlevel, lowestlevel and stderror.  The values must
     *      come from the activty instance and the user's
     * attempt record
     * @return string a user friendly format of the ability measure.  Ability measure is rounded to the nearest decimal.
     */
    public function format_measure($record) {
        if (is_null($record->measure)) {
            return 'n/a';
        }
        return round(catalgo::map_logit_to_scale($record->measure, $record->highestlevel, $record->lowestlevel), 2);
    }

    /**
     * This function formats the standard error into a user friendly format
     * @param stdClass an object with the following properties: measure, highestlevel, lowestlevel and stderror.  The values must
     *      come from the activty instance and the user's
     * attempt record
     * @return string a user friendly format of the standard error. Standard error is
     * rounded to the nearest one hundredth then multiplied by 100
     */
    public function format_standard_error($record) {
        if (is_null($record->stderror) || $record->stderror == 0.0) {
            return 'n/a';
        }
        $percent = round(catalgo::convert_logit_to_percent($record->stderror), 2) * 100;
        return $percent.'%';
    }
}
