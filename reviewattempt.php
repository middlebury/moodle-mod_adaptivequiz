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

echo $header;

$user = $DB->get_record('user', array('id' => $userid));
print "\n<h2>Attempt Summary</h2>";

print "\n<dl style='float: left;'>";
print "\n\t<dt>User: </dt>";
print "\n\t<dd>".$user->firstname." ".$user->lastname." (".$user->email.")</dd>";
print "\n\t<dt>Attempt state: </dt>";
print "\n\t<dd>".$adaptivequiz->attemptstate."</dd>";
print "\n\t<dt>Score: </dt>";
$ability_in_fraction = 1 / ( 1 + exp( (-1 * $adaptivequiz->measure) ) );
$ability = (($adaptivequiz->highestlevel - $adaptivequiz->lowestlevel) * $ability_in_fraction) + $adaptivequiz->lowestlevel;
$standard_error = catalgo::convert_logit_to_percent($adaptivequiz->standarderror);
if ($standard_error > 0) {
    $score = round($ability, 2)." &nbsp; &plusmn; ".round($standard_error * 100, 1)."%";
} else {
    $score = 'n/a';
}
print "\n\t<dd>".$score."</dd>";
print "\n</dl>";

print "\n<dl style='float: left;'>";
print "\n\t<dt>Start Time: </dt>";
print "\n\t<dd>".date('c', $adaptivequiz->timecreated)."</dd>";
print "\n\t<dt>Start Time: </dt>";
print "\n\t<dd>".date('c', $adaptivequiz->timemodified)."</dd>";
print "\n\t<dt>Total Time (hh:mm:ss): </dt>";
$total_time = $adaptivequiz->timemodified - $adaptivequiz->timecreated;
$hours = floor($total_time/3600);
$remainder = $total_time - ($hours * 3600);
$minutes = floor($remainder/60);
$seconds = $remainder - ($minutes * 60);
print "\n\t<dd>".sprintf('%02d', $hours).":".sprintf('%02d', $minutes).":".sprintf('%02d', $seconds)."</dd>";
print "\n\t<dt>Reason for stopping attempt: </dt>";
print "\n\t<dd>".$adaptivequiz->attemptstopcriteria."</dd>";
print "\n</dl>";

$graph_url = new moodle_url('/mod/adaptivequiz/attemptgraph.php', array('uniqueid' => $uniqueid, 'cmid' => $cm->id, 'userid' => $userid));
print "\n<img src=\"".$graph_url."\" style='clear: both;'>";

print "\n<table>";
print "\n\t<tr>";
print "\n\t\t<th>#</th>";
print "\n\t\t<th>Question Level</th>";
print "\n\t\t<th>Right/Wrong</th>";
print "\n\t\t<th>Measured Ability</th>";
print "\n\t\t<th>Standard Error (&plusmn;&nbsp;x%)</th>";
print "\n\t\t<th>Question Difficulty (logits)</th>";
print "\n\t\t<th>Difficulty Sum</th>";
print "\n\t\t<th>Measured Ability (logits)</th>";
print "\n\t\t<th>Standard Error (&plusmn;&nbsp;logits)</th>";
print "\n\t</tr>";
print "\n</thead>";
print "\n<tbody>";

$questions_attempted = 0;
$difficulty_sum = 0;
$sum_of_correct_answers = 0;
$sum_of_incorrect_answers = 0;
foreach ($quba->get_slots() as $slot) {
    $question = $quba->get_question($slot);
    $tags = tag_get_tags_array('question', $question->id);
    $question_difficulty = adaptivequiz_get_difficulty_from_tags($tags);
    $question_difficulty_in_logits = catalgo::convert_linear_to_logit($question_difficulty, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);
    $question_correct = ($quba->get_question_mark($slot) > 0);
    
    $questions_attempted++;
    $difficulty_sum = $difficulty_sum + $question_difficulty_in_logits;
    if ($question_correct) {
        $sum_of_correct_answers++;
    } else {
        $sum_of_incorrect_answers++;
    }
    
    $ability_in_logits = catalgo::estimate_measure($difficulty_sum, $questions_attempted, $sum_of_correct_answers, $sum_of_incorrect_answers);
    $ability_in_fraction = 1 / ( 1 + exp( (-1 * $ability_in_logits) ) );
    $ability = (($adaptivequiz->highestlevel - $adaptivequiz->lowestlevel) * $ability_in_fraction) + $adaptivequiz->lowestlevel;
    
    $standard_error_in_logits = catalgo::estimate_standard_error($questions_attempted, $sum_of_correct_answers, $sum_of_incorrect_answers);
    $standard_error = catalgo::convert_logit_to_percent($standard_error_in_logits);
    
    print "\n\t<tr>";
    print "\n\t\t<td>".$slot."</td>";
    print "\n\t\t<td>".$question_difficulty."</td>";
    print "\n\t\t<td>".($question_correct?'r':'w')."</td>";
    print "\n\t\t<td>".round($ability, 2)."</td>";
    print "\n\t\t<td>".round($standard_error * 100, 1)."%</td>";
    print "\n\t\t<td>".round($question_difficulty_in_logits, 5)."</td>";
    print "\n\t\t<td>".round($difficulty_sum, 5)."</td>";
    print "\n\t\t<td>".round($ability_in_logits, 5)."</td>";
    print "\n\t\t<td>".round($standard_error_in_logits, 5)."</td>";
    print "\n\t</tr>";
}
print "\n</tbody>";
print "\n</table>";

print "\n<h2>Question Details</h2>";
echo $pager;
$user = $DB->get_record('user', array('id' => $userid));
echo $output->print_questions_for_review($quba, $page, $user, $adaptivequiz->timemodified);
echo $button;
echo '<br />';
echo $pager;
echo $footer;