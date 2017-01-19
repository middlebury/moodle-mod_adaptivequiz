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
 * Adaptive testing main view page script
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

$id = optional_param('id', 0, PARAM_INT);
$n  = optional_param('n', 0, PARAM_INT);

if ($id) {
    $cm         = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $adaptivequiz->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id, false, MUST_EXIST);
} else {
    print_error('invalidarguments');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$event = \mod_adaptivequiz\event\course_module_viewed::create(
    array(
        'objectid' => $PAGE->cm->instance,
        'context' => $PAGE->context,
    )
);

$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $adaptivequiz);
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/adaptivequiz/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here.
echo $OUTPUT->header();

if ($adaptivequiz->intro) { // Conditions to show the intro can change to look for own settings or whatever.
    echo $OUTPUT->box(format_module_intro('adaptivequiz', $adaptivequiz, $cm->id), 'generalbox mod_introbox', 'newmoduleintro');
}

$renderer = $PAGE->get_renderer('mod_adaptivequiz');

// Check if the instance exists.
if (has_capability('mod/adaptivequiz:attempt', $context)) {

    // Check if the user has any previous attempts at this activity.
    $count = adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $USER->id);

    if (adaptivequiz_allowed_attempt($adaptivequiz->attempts, $count)) {
        if (empty($adaptivequiz->browsersecurity)) {
            echo $renderer->display_start_attempt_form($cm->id);
        } else {
            echo $renderer->display_start_attempt_form_scured($cm->id);
        }
    } else {
        echo $OUTPUT->notification(get_string('noattemptsallowed', 'adaptivequiz'));
    }
}

if (has_capability('mod/adaptivequiz:viewreport', $context)) {
    echo $renderer->display_view_report_form($cm->id);
    echo $renderer->display_question_analysis_form($cm->id);
}
// Finish the page.
echo $OUTPUT->footer();