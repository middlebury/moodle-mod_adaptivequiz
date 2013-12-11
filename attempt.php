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
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
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

// If uniqueid is not empty the process respones.
if (!empty($uniqueid) && confirm_sesskey()) {
    // Check if the uniqueid belongs to the same attempt record the user is currently using.
    $attemptrec = $adaptiveattempt->get_attempt();

    if (!adaptivequiz_uniqueid_part_of_attempt($uniqueid, $cm->instance, $USER->id)) {
        print_error('uniquenotpartofattempt', 'adaptivequiz');
    }

    // Process student's responses.
    try {
        // Set a time stamp for the actions below.
        $time = time();
        // Load the user's current usage from the DB.
        $quba = question_engine::load_questions_usage_by_activity((int) $uniqueid);
        // Update the actions done to the question.
        $quba->process_all_actions($time);
        // Finish the grade attempt at the question.
        $quba->finish_all_questions($time);
        // Save the data about the usage to the DB.
        question_engine::save_questions_usage_by_activity($quba);

        if (!empty($difflevel)) {
            // Check if the minimum number of attempts have been reached.
            $minattemptreached = adaptivequiz_min_attempts_reached($uniqueid, $cm->instance, $USER->id);
            // Create an instance of the CAT algo class.
            $algo = new catalgo($quba, (int) $attemptrec->id, $minattemptreached, (int) $difflevel);
            // Calculate the next difficulty level.
            $nextdiff = $algo->perform_calculation_steps();

            // Increment difficulty level for attempt.
            $everythingokay = false;
            $difflogit = $algo->get_levellogit();
            $standarderror = $algo->get_standarderror();
            $measure = $algo->get_measure();
            $everythingokay = adaptivequiz_update_attempt_data($uniqueid, $cm->instance, $USER->id, $difflogit, $standarderror,
                                    $measure);

            // Something went wrong with updating the attempt.  Print an error.
            if (!$everythingokay) {
                $url = new moodle_url('/mod/adaptivequiz/attempt.php', array('cmid' => $id));
                print_error('unableupdatediffsum', 'adaptivequiz', $url);
            }

            // Check whether the status property is empty.
            $message = $algo->get_status();

            if (!empty($message)) {

                $standarderror = $algo->get_standarderror();
                // Set the attempt to complete, update the standard error and attempt message, then redirect the user to the
                // attempt finished page.
                adaptivequiz_complete_attempt($uniqueid, $cm->instance, $USER->id, $standarderror, $message);

                $param = array('cmid' => $cm->id, 'id' => $cm->instance, 'uattid' => $uniqueid);
                $url = new moodle_url('/mod/adaptivequiz/attemptfinished.php', $param);
                redirect($url);
            }

            // Lastly decrement the sum of questions for the attempted difficulty level.
            $fetchquestion = new fetchquestion($quba, $difflevel, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);
            $tagquestcount = $fetchquestion->get_tagquestsum();
            $tagquestcount = $fetchquestion->decrement_question_sum_from_difficulty($tagquestcount, $difflevel);
            $fetchquestion->set_tagquestsum($tagquestcount);
            // Force the class to deconstruct the object and save the updated mapping to the session global.
            unset($fetchquestion);
        }
    } catch (question_out_of_sequence_exception $e) {
        $url = new moodle_url('/mod/adaptivequiz/attempt.php', array('cmid' => $id));
        print_error('submissionoutofsequencefriendlymessage', 'question', $url);

    } catch (Exception $e) {
        $url = new moodle_url('/mod/adaptivequiz/attempt.php', array('cmid' => $id));
        $debuginfo = '';

        if (!empty($e->debuginfo)) {
            $debuginfo = $e->debuginfo;
        }

        print_error('errorprocessingresponses', 'question', $url, $e->getMessage(), $debuginfo);
    }
}

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

// Retrieve the question slot id.
$slot = $adaptiveattempt->get_question_slot_number();
// Retrieve the question_usage_by_activity object.
$quba = $adaptiveattempt->get_quba();
// If $nextdiff is null then this is either a new attempt or a continuation of an previous attempt.  Calculate the current
// difficulty level the attempt should be at.
if (is_null($nextdiff)) {
    // Calculate the current difficulty level.
    $adaptivequiz->lowestlevel = (int) $adaptivequiz->lowestlevel;
    $adaptivequiz->highestlevel = (int) $adaptivequiz->highestlevel;
    $adaptivequiz->startinglevel = (int) $adaptivequiz->startinglevel;
    // Create an instance of the catalgo class, however constructor arguments are not important.
    $algo = new catalgo($quba, 1, false, 1);
    $level = $algo->get_current_diff_level($quba, $adaptivequiz->startinglevel, $adaptivequiz);
} else {
    // Retrieve the currently set difficulty level.
    $level = $adaptiveattempt->get_level();
}

$headtags = $output->init_metadata($quba, $slot);
$PAGE->requires->js_init_call('M.mod_adaptivequiz.init_attempt_form', array($viewurl->out(), $adaptivequiz->browsersecurity),
    false, $output->adaptivequiz_get_js_module());

// Init secure window if enabled.
if (!empty($adaptivequiz->browsersecurity)) {
    $PAGE->blocks->show_only_fake_blocks();
    $output->init_browser_security();
} else {
    $PAGE->set_heading(format_string($course->fullname));
}

// Check if the user entered a password.
$condition = adaptivequiz_user_entered_password($adaptivequiz->id);

if (!empty($adaptivequiz->password) && empty($condition)) {
    echo $output->print_header();

    if ($passwordattempt) {
        $mform->set_data(array('message' => get_string('wrongpassword', 'adaptivequiz')));
    }

    $mform->display();
    echo $output->print_footer();
} else {
    // Render the question to the page.
    echo $output->print_question($id, $quba, $slot, $level);
}