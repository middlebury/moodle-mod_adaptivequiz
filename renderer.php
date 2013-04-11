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
    /** @var string $sortdir the sorting direction being used */
    protected $sortdir = '';
    /** @var moodle_url $sorturl the current base url used for keeping the table sorted */
    protected $sorturl = '';
    /** @var int $groupid static variable used to reference the groupid that is currently being used to filter by */
    public static $groupid = 0;
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
            'strings' => array(array('cancel', 'moodle'), array('changesmadereallygoaway', 'moodle'), array('functiondisabledbysecuremode', 'adaptivequiz'))
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
            $this->page->requires->js_init_call('M.mod_adaptivequiz.secure_window.init_close_button', array($url), $this->adaptivequiz_get_js_module());
            $output .= html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('continue'), 'id' => 'secureclosebutton'));
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
    public function print_reporting_page_header() {
        return $this->header();
    }

    /**
     * This function returns page footer information to be printed to the page
     * @return string HTML markup for footer inforation
     */
    public function print_reporting_page_footer() {
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
        $output = $this->heading(get_string('indvuserreport', 'adaptivequiz', fullname($user)));
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
        $table->head =  $this->format_report_table_headers($cm, $sort, $sortdir);
        $table->align = array('center', 'center', 'center');
        $table->size = array('', '', '');

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
        $standarderror = get_string('standarderror', 'adaptivequiz');
        $timemodifed = get_string('attemptfinishedtimestamp', 'adaptivequiz');

        $table->head = array($attemptstate, $attemptstopcriteria, $questionsattempted, $standarderror, $timemodifed, '');
        $table->align = array('center', 'center', 'center', 'center', 'center', 'center');
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
            $reviewurl = new moodle_url('/mod/adaptivequiz/reviewattempt.php', array('uniqueid' => $record->uniqueid, 'cmid' => $cm->id, 'userid' => $record->userid));
            $link = html_writer::link($reviewurl, get_string('reviewattempt', 'adaptivequiz'));

            if (0 == strcmp('inprogress', $record->attemptstate)) {
                $attemptstate = get_string('recentinprogress', 'adaptivequiz');
            } else {
                $attemptstate = get_string('recentcomplete', 'adaptivequiz');
            }

            $row = array($attemptstate, format_string($record->attemptstopcriteria), $record->questionsattempted, $record->standarderror,
                    userdate($record->timemodified), $link);
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
    protected function format_report_table_headers($cm, $sort, $sortdir) {
        global $OUTPUT;

        $newsortdir = '';
        $columnicon = '';
        $firstname = '';
        $lastname = '';
        $numofattempts = '';
        $standarderror = '';

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
        $param = array('cmid' => $cm->id, 'sort' => 'firstname', 'sortdir' => 'ASC', 'groupid' => self::$groupid);
        $firstnameurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'lastname';
        $lastnameurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'attempts';
        $numofattemptsurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $param['sort'] = 'stderror';
        $standarderrorurl = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);

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
            case 'attempts':
                $numofattemptsurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $numofattemptsurl;
                $numofattempts .= '&nbsp;'.$columnicon;
                break;
            case 'stderror':
                $standarderrorurl->params(array('sortdir' => $newsortdir));
                $this->sorturl = $standarderrorurl;
                $standarderror .= '&nbsp;'.$columnicon;
                break;
        }

        // Create header HTML markup
        $firstname = html_writer::link($firstnameurl, get_string('firstname')).$firstname;
        $lastname = html_writer::link($lastnameurl, get_string('lastname')).$lastname;
        $numofattempts = html_writer::link($numofattemptsurl, get_string('numofattemptshdr', 'adaptivequiz')).$numofattempts;
        $standarderror = html_writer::link($standarderrorurl, get_string('standarderrorhdr', 'adaptivequiz')).$standarderror;

        return array($firstname.' / '.$lastname, $numofattempts, $standarderror);
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
            $attemptlink = new moodle_url('/mod/adaptivequiz/viewattemptreport.php', array('userid' => $record->id, 'cmid' => $cm->id));
            $link = html_writer::link($attemptlink, $record->attempts);
            $row = array($record->firstname.', '.$record->lastname, $link, $record->standarderror);
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
        $baseurl->params(array('group' => self::$groupid, 'sortdir' => $this->sortdir));

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
     * This functio prints a button to take the user back to the main reports page
     * @param string $cmid: course module id
     * @return string - HTML markup displaying the description and form with a submit button
     */
    public function print_back_to_main_report_page($cmid) {
        $html = '';

        $param = array('cmid' => $cmid);
        $target = new moodle_url('/mod/adaptivequiz/viewreport.php', $param);
        $attributes = array('method' => 'POST', 'action' => $target);

        $html .= html_writer::start_tag('form', $attributes);

        $html .= html_writer::empty_tag('br');
        $html .= html_writer::empty_tag('br');
        $html .= html_writer::start_tag('center');

        $buttonlabel = get_string('backtomainreport', 'adaptivequiz');
        $params = array('type' => 'submit', 'value' => $buttonlabel, 'class' => 'submitbtns adaptivequizbtn');
        $html .= html_writer::empty_tag('input', $params);
        $html .= html_writer::end_tag('center');
        $html .= html_writer::end_tag('form');

        return $html;
    }
}