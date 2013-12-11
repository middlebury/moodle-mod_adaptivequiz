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
 * Adaptive quiz view attempted questions
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');

$id = required_param('cmid', PARAM_INT);
$uniqueid = required_param('uniqueid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

if (!$cm = get_coursemodule_from_id('adaptivequiz', $id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

require_capability('mod/adaptivequiz:viewreport', $context);

$param = array('uniqueid' => $uniqueid, 'userid' => $userid, 'activityid' => $cm->instance);
$sql = 'SELECT a.name, a.highestlevel, a.lowestlevel, aa.timecreated, aa.timemodified, aa.attemptstate, aa.attemptstopcriteria,
               aa.questionsattempted, aa.difficultysum, aa.standarderror, aa.measure
          FROM {adaptivequiz} a
          JOIN {adaptivequiz_attempt} aa ON a.id = aa.instance
         WHERE aa.uniqueid = :uniqueid
               AND aa.userid = :userid
               AND a.id = :activityid
      ORDER BY a.name ASC';
$adaptivequiz  = $DB->get_record_sql($sql, $param);

$PAGE->set_url('/mod/adaptivequiz/reviewattempt.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$output = $PAGE->get_renderer('mod_adaptivequiz');

$PAGE->requires->js_init_call('M.mod_adaptivequiz.init_reviewattempt', null, false, $output->adaptivequiz_get_js_module());


/* print header information */
$header = $output->print_header();
/* Output footer information */
$footer = $output->print_footer();
/* Load question usage by activity object */
$quba = question_engine::load_questions_usage_by_activity($uniqueid);
/* render pager links */
$pager = $output->print_questions_for_review_pager($quba, $page, $cm->id, $userid);
/* Render a button on the page */
$url = new moodle_url('/mod/adaptivequiz/viewattemptreport.php', array('cmid' => $cm->id, 'userid' => $userid));
$txt = get_string('backtoviewattemptreport', 'adaptivequiz');
$button = $output->print_form_and_button($url, $txt);

$user = $DB->get_record('user', array('id' => $userid));
if (!$user) {
    $user = new stdClass();
    $user->firstname = get_string('unknownuser', 'adaptivequiz');
    $user->lastname = '#'.$userid;
}

echo $header;

echo html_writer::tag('h2', get_string('attempt_summary', 'adaptivequiz'));
echo $output->get_attempt_summary_listing($adaptivequiz, $user);

$graphurl = new moodle_url('/mod/adaptivequiz/attemptgraph.php',
    array('uniqueid' => $uniqueid, 'cmid' => $cm->id, 'userid' => $userid));
$params = array('src' => $graphurl, 'class' => 'adaptivequiz-attemptgraph');
echo html_writer::empty_tag('img', $params);

echo ' ';

$graphurl = new moodle_url('/mod/adaptivequiz/answerdistributiongraph.php',
    array('uniqueid' => $uniqueid, 'cmid' => $cm->id, 'userid' => $userid));
$params = array('src' => $graphurl, 'class' => 'adaptivequiz-answerdistributiongraph');
echo html_writer::empty_tag('img', $params);

echo html_writer::start_tag('a', array('href' => '#', 'id' => 'adpq_scoring_table_link'));
echo html_writer::start_tag('h2');
echo html_writer::tag('span', '&#9660;', array('id' => 'adpq_scoring_table_link_icon'));
echo ' '.get_string('scoring_table', 'adaptivequiz');
echo html_writer::end_tag('h3');
echo html_writer::end_tag('a');
echo html_writer::start_tag('div', array('id' => 'adpq_scoring_table'));
echo $output->get_attempt_scoring_table($adaptivequiz, $quba);
echo $output->get_attempt_distribution_table($adaptivequiz, $quba);
echo html_writer::tag('div', '', array('class' => 'clearfix'));
echo html_writer::end_tag('div');

echo html_writer::tag('h2', get_string('attempt_questiondetails', 'adaptivequiz'));
echo $pager;
echo $output->print_questions_for_review($quba, $page, $user, $adaptivequiz->timemodified);
echo $button;
echo html_writer::empty_tag('br');
echo $pager;
echo $footer;