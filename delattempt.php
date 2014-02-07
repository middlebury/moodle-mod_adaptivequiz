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
 * Confirmation page to remove student attempts
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');

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
$sql = 'SELECT a.name, aa.timemodified, aa.id, u.firstname, u.lastname
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
    print_error('errordeletingattempt', 'adaptivequiz', $returnurl);
}

$PAGE->set_url('/mod/adaptivequiz/reviewattempt.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Are you usre confirmation message.
$a = new stdClass();
$a->name = format_string($adaptivequiz->firstname.' '.$adaptivequiz->lastname);
$a->timecompleted = userdate($adaptivequiz->timemodified);
$message = get_string('confirmdeleteattempt', 'adaptivequiz', $a);

if ($confirm) {
    // Remove attempt record and redirect.
    question_engine::delete_questions_usage_by_activity($uniqueid);
    $DB->delete_records('adaptivequiz_attempt', array('instance' => $cm->instance, 'uniqueid' => $uniqueid, 'userid' => $userid));

    // Update the grade book with any changes.
    $adaptivequiz = $DB->get_record('adaptivequiz', array('id' => $cm->instance));
    adaptivequiz_update_grades($adaptivequiz, $userid);

    $message = get_string('attemptdeleted', 'adaptivequiz', $a);
    redirect($returnurl, $message, 4);
}

$confirm = new moodle_url('/mod/adaptivequiz/delattempt.php', array('uniqueid' => $uniqueid, 'cmid' => $cm->id,
    'userid' => $userid, 'confirm' => 1));
echo $OUTPUT->header();
echo $OUTPUT->confirm($message, $confirm, $returnurl);
echo $OUTPUT->footer();