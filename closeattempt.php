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
 * Confirmation page to close a student attempt
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');

$id = required_param('cmid', PARAM_INT);
$uniqueid = required_param('uniqueid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

if (!$cm = get_coursemodule_from_id('adaptivequiz', $id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

global $OUTPUT, $DB;

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

require_capability('mod/adaptivequiz:viewreport', $context);

$param = array('uniqueid' => $uniqueid, 'userid' => $userid, 'activityid' => $cm->instance);
$sql = 'SELECT a.name, aa.attemptstate, aa.timecreated, aa.timemodified, aa.id, u.firstname, u.lastname, aa.attemptstate,
               aa.questionsattempted, aa.measure, aa.standarderror AS stderror, a.highestlevel, a.lowestlevel
          FROM {adaptivequiz} a
          JOIN {adaptivequiz_attempt} aa ON a.id = aa.instance
          JOIN {user} u ON u.id = aa.userid
         WHERE aa.uniqueid = :uniqueid
               AND aa.userid = :userid
               AND a.id = :activityid
      ORDER BY a.name ASC';
$adaptivequiz  = $DB->get_record_sql($sql, $param);

$returnurl = new moodle_url('/mod/adaptivequiz/viewattemptreport.php', array('cmid' => $cm->id, 'userid' => $userid));

if (empty($adaptivequiz)) {
    print_error('errorclosingattempt', 'adaptivequiz', $returnurl);
}

if ($adaptivequiz->attemptstate == ADAPTIVEQUIZ_ATTEMPT_COMPLETED) {
    print_error('errorclosingattempt_alreadycomplete', 'adaptivequiz', $returnurl);
}

$PAGE->set_url('/mod/adaptivequiz/reviewattempt.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('mod_adaptivequiz');

// Are you sure confirmation message.
global $USER;
$a = new stdClass();
$a->name = format_string($adaptivequiz->firstname.' '.$adaptivequiz->lastname);
$a->started = userdate($adaptivequiz->timecreated);
$a->modified = userdate($adaptivequiz->timemodified);
$a->num_questions = format_string($adaptivequiz->questionsattempted);
$a->measure = $renderer->format_measure($adaptivequiz);
$a->standarderror = $renderer->format_standard_error($adaptivequiz);
$a->current_user_name = format_string($USER->firstname.' '.$USER->lastname);
$a->current_user_id = format_string($USER->id);
$a->now = userdate(time());

$message = html_writer::tag('p', get_string('confirmcloseattempt', 'adaptivequiz', $a))
    .html_writer::tag('p', get_string('confirmcloseattemptstats', 'adaptivequiz', $a))
    .html_writer::tag('p', get_string('confirmcloseattemptscore', 'adaptivequiz', $a));

if ($confirm) {
    // Close the attempt record and redirect.
    $statusmessage = get_string('attemptclosedstatus', 'adaptivequiz', $a);

    $closemessage = get_string('attemptclosed', 'adaptivequiz', $a);

    adaptivequiz_complete_attempt($uniqueid, $cm->instance, $userid, $adaptivequiz->stderror, $statusmessage);
    redirect($returnurl, $closemessage, 4);
}

$confirm = new moodle_url('/mod/adaptivequiz/closeattempt.php', array('uniqueid' => $uniqueid, 'cmid' => $cm->id,
    'userid' => $userid, 'confirm' => 1));
echo $OUTPUT->header();
echo $OUTPUT->confirm($message, $confirm, $returnurl);
echo $OUTPUT->footer();