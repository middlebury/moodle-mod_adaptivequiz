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
 * Adaptive quiz attempt script
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner and Andriy Semenets.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2017 onwards Andriy Semenets {semteacher@gmail.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');

$cmid = required_param('cmid', PARAM_INT); // Course module id.
$instance = required_param('id', PARAM_INT); // activity instance id.
$uniqueid = required_param('uattid', PARAM_INT); // attempt unique id.

if (!$cm = get_coursemodule_from_id('adaptivequiz', $cmid)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

//$adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $cm->instance), '*', MUST_EXIST);
//get adaptivequiz info along with attempt summary info
$param = array('uniqueid' => $uniqueid, 'userid' => $USER->id, 'activityid' => $cm->instance);
$sql = 'SELECT a.name, a.highestlevel, a.lowestlevel, a.attemptfeedback, aa.timecreated, aa.timemodified, aa.attemptstate, aa.attemptstopcriteria,
               aa.questionsattempted, aa.difficultysum, aa.standarderror, aa.measure
          FROM {adaptivequiz} a
          JOIN {adaptivequiz_attempt} aa ON a.id = aa.instance
         WHERE aa.uniqueid = :uniqueid
               AND aa.userid = :userid
               AND a.id = :activityid
      ORDER BY a.name ASC';
$adaptivequiz  = $DB->get_record_sql($sql, $param);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// TODO - check if user has capability to attempt.

// Check if this is the owner of the attempt.
$validattempt = adaptivequiz_uniqueid_part_of_attempt($uniqueid, $instance, $USER->id);

// Displayan error message if this is not the owner of the attempt.
if (!$validattempt) {
    $url = new moodle_url('/mod/adaptivequiz/attempt.php', array('cmid' => $cm->id));
    print_error('notyourattempt', 'adaptivequiz', $url);
}

$PAGE->set_url('/mod/adaptivequiz/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_context($context);

$output = $PAGE->get_renderer('mod_adaptivequiz');

// Init secure window if enabled.
$popup = false;
if (!empty($adaptivequiz->browsersecurity)) {
    $PAGE->blocks->show_only_fake_blocks();
    $output->init_browser_security();
    $popup = true;
} else {
    $PAGE->set_heading(format_string($course->fullname));
}

$user = $DB->get_record('user', array('id' => $USER->id));
if (!$user) {
    $user = new stdClass();
    $user->firstname = get_string('unknownuser', 'adaptivequiz');
    $user->lastname = '#'.$userid;
}

//echo $output->print_attemptfeedback($cm->id, $popup);
echo $output->print_largeattemptfeedback($adaptivequiz, $user, $cm->id, $popup);


