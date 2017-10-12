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
 * Adaptive quiz start attempt script
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
require_once($CFG->dirroot.'/mod/adaptivequiz/adaptiveattempt.class.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/fetchquestion.class.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/catalgo.class.php');
require_once($CFG->dirroot.'/tag/lib.php');

$id = required_param('cmid', PARAM_INT); // Course module id.
$uniqueid  = optional_param('uniqueid', 0, PARAM_INT);  // uniqueid of the attempt.
$difflevel  = optional_param('dl', 0, PARAM_INT);  // difficulty level of question.

if (!$cm = get_coursemodule_from_id('adaptivequiz', $id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

global $USER, $DB, $SESSION;

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$passwordattempt = false;

try {
    $adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $cm->instance), '*', MUST_EXIST);
} catch (dml_exception $e) {
    $url = new moodle_url('/mod/adaptivequiz/attempt.php', array('cmid' => $id));
    $debuginfo = '';

    if (!empty($e->debuginfo)) {
        $debuginfo = $e->debuginfo;
    }

    print_error('invalidmodule', 'error', $url, $e->getMessage(), $debuginfo);
}

// Setup page global for standard viewing.
$viewurl = new moodle_url('/mod/adaptivequiz/view.php', array('id' => $cm->id));
$PAGE->set_url('/mod/adaptivequiz/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_context($context);

// Check if the user has the attempt capability.
require_capability('mod/adaptivequiz:attempt', $context);

// Check if the user has any previous attempts at this activity.
$count = adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $USER->id);

if (!adaptivequiz_allowed_attempt($adaptivequiz->attempts, $count)) {
    print_error('noattemptsallowed', 'adaptivequiz');
}

// Create an instance of the module renderer class.
$output = $PAGE->get_renderer('mod_adaptivequiz');
// Setup password required form.
$mform = $output->display_password_form($cm->id);
// Check if a password is required.
if (!empty($adaptivequiz->password)) {
    // Check if the user has alredy entered in their password.
    $condition = adaptivequiz_user_entered_password($adaptivequiz->id);

    if (empty($condition) && $mform->is_cancelled()) {
        // Return user to landing page.
        redirect($viewurl);
    } else if (empty($condition) && $data = $mform->get_data()) {
        $SESSION->passwordcheckedadpq = array();

        if (0 == strcmp($data->quizpassword, $adaptivequiz->password)) {
            $SESSION->passwordcheckedadpq[$adaptivequiz->id] = true;
        } else {
            $SESSION->passwordcheckedadpq[$adaptivequiz->id] = false;
            $passwordattempt = true;
        }
    }
}

// Create an instance of the adaptiveattempt class.
$adaptiveattempt = new adaptiveattempt($adaptivequiz, $USER->id);
$algo = new stdClass();
$nextdiff = null;
$standarderror = 0.0;
$message = '';

$adaptivequiz->context = $context;
$adaptivequiz->cm = $cm;

// If value is null then set the difficulty level to the starting level for the attempt.
if (!is_null($nextdiff)) {
    $adaptiveattempt->set_level((int) $nextdiff);
} else {
    $adaptiveattempt->set_level((int) $adaptivequiz->startinglevel);
}

// If we have a previous difficulty level, pass that off to the attempt so that it
// can modify the next-question search process based on this level.
if (isset($difflevel) && !is_null($difflevel)) {
    $adaptiveattempt->set_last_difficulty_level($difflevel);
}

$attemptstatus = $adaptiveattempt->start_attempt();

// Check if attempt status is set to ready.
if (empty($attemptstatus)) {
    // Retrieve the most recent status message for the attempt.
    $message = $adaptiveattempt->get_status();

    // Set the attempt to complete, update the standard error and attempt message, then redirect the user to the attempt-finished
    // page.
    if ($algo instanceof catalgo) {
        $standarderror = $algo->get_standarderror();
    }

    adaptivequiz_complete_attempt($uniqueid, $cm->instance, $USER->id, $standarderror, $message);
    // Redirect the user to the attemptfeedback page.
    $param = array('cmid' => $cm->id, 'id' => $cm->instance, 'uattid' => $uniqueid);
    $url = new moodle_url('/mod/adaptivequiz/attemptfinished.php', $param);
    redirect($url);
}

// Redirect to the attempt page.
$param = array('cmid' => $cm->id, 'attid' => $adaptiveattempt->get_id());
$url = new moodle_url('/mod/adaptivequiz/attempt.php', $param);
redirect($url);