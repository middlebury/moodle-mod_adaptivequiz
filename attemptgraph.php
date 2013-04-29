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
 * Adaptive quiz - generate a graph of the question difficulties asked and the measured
 * ability of the test-taker as the test progressed.
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
require_once($CFG->dirroot.'/mod/adaptivequiz/catalgo.class.php');
require_once($CFG->dirroot.'/lib/graphlib.php');

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
$sql = 'SELECT a.name, a.highestlevel, a.lowestlevel, a.startinglevel, aa.timecreated, aa.timemodified, aa.attemptstate,
               aa.attemptstopcriteria, aa.questionsattempted, aa.difficultysum, aa.standarderror, aa.measure
          FROM {adaptivequiz} a
          JOIN {adaptivequiz_attempt} aa ON a.id = aa.instance
         WHERE aa.uniqueid = :uniqueid
               AND aa.userid = :userid
               AND a.id = :activityid
      ORDER BY a.name ASC';
$adaptivequiz  = $DB->get_record_sql($sql, $param);
$user = $DB->get_record('user', array('id' => $userid));

$g = new graph(750, 300);
$g->parameter['title'] = format_string($adaptivequiz->name).' for '.$user->firstname." ".$user->lastname;
// $g->parameter['x_label'] = 'Day of the Week';
$g->parameter['y_label_left'] = get_string('attemptquestion_ability', 'adaptivequiz');
$g->parameter['legend']        = 'outside-top';
$g->parameter['legend_border'] = 'black';
$g->parameter['legend_offset'] = 4;
$g->parameter['grid_colour'] = 'grayCC';

$question_numbers = array();
$question_difficulty_values = array();
$ability_measure_values = array();
$error_max_values = array();
$error_min_values = array();
$target_level_values = array();

$quba = question_engine::load_questions_usage_by_activity($uniqueid);
$questions_attempted = 0;
$difficulty_sum = 0;
$sum_of_correct_answers = 0;
$sum_of_incorrect_answers = 0;
foreach ($quba->get_slots() as $i => $slot) {
    // The starting target difficulty is set by the test parameters
    if ($i == 0) {
        $target_level = $adaptivequiz->startinglevel;
    } else {
    // Compute the target difficulty based on the last question.
        if ($question_correct) {
            $target_level = round(catalgo::map_logit_to_scale($question_difficulty_in_logits + 2 / $questions_attempted,
                $adaptivequiz->highestlevel, $adaptivequiz->lowestlevel));
            if ($target_level == $question_difficulty && $target_level < $adaptivequiz->highestlevel) {
                $target_level++;
            }
        } else {
            $target_level = round(catalgo::map_logit_to_scale($question_difficulty_in_logits - 2 / $questions_attempted,
                $adaptivequiz->highestlevel, $adaptivequiz->lowestlevel));
            if ($target_level == $question_difficulty && $target_level > $adaptivequiz->lowestlevel) {
                $target_level--;
            }
        }
    }

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

    $question_numbers[] = $questions_attempted;
    $question_difficulty_values[] = $question_difficulty;
    $ability_measure_values[] = $ability;

    $error_max_values[] = min($adaptivequiz->highestlevel, $ability + ($standard_error * ($adaptivequiz->highestlevel - $adaptivequiz->lowestlevel)));
    $error_min_values[] = max($adaptivequiz->lowestlevel, $ability - ($standard_error * ($adaptivequiz->highestlevel - $adaptivequiz->lowestlevel)));

    $target_level_values[] = $target_level;
}


$g->x_data = $question_numbers;
$g->y_data['qdiff'] = $question_difficulty_values;
$g->y_data['ability'] = $ability_measure_values;
$g->y_data['target_level'] = $target_level_values;
$g->y_data['error_max'] = $error_max_values;
$g->y_data['error_min'] = $error_min_values;

// var_dump($g->y_data); exit;

$g->y_format['qdiff'] = array('colour' => 'blue', 'line' => 'brush', 'brush_size' => 2, 'shadow' => 'none', 'legend' => get_string('attemptquestion_level', 'adaptivequiz'));
$g->y_format['target_level'] = array('colour' => 'green', 'line' => 'brush', 'brush_size' => 1, 'shadow' => 'none', 'legend' => get_string('graphlegend_target', 'adaptivequiz'));
$g->y_format['ability'] = array('colour' => 'red', 'line' => 'brush', 'brush_size' => 2, 'shadow' => 'none', 'legend' => get_string('attemptquestion_ability', 'adaptivequiz'));
$g->colour['pink'] = imagecolorallocate($g->image, 0xFF, 0xE5, 0xE5);
$g->y_format['error_max'] = array('colour' => 'pink', 'area' => 'fill','shadow' => 'none', 'legend' => get_string('graphlegend_error', 'adaptivequiz'));
$g->y_format['error_min'] = array('colour' => 'white', 'area' => 'fill', 'shadow' => 'none');

$g->parameter['y_min_left'] = $adaptivequiz->lowestlevel;
$g->parameter['y_max_left'] = $adaptivequiz->highestlevel;
$g->parameter['x_grid'] = 'none';
// $g->parameter['y_grid'] = 'none';

if ($adaptivequiz->highestlevel - $adaptivequiz->lowestlevel <= 20) {
    $g->parameter['y_axis_gridlines'] = $adaptivequiz->highestlevel - $adaptivequiz->lowestlevel + 1;
    $g->parameter['y_decimal_left'] = 0;
} else {
    $g->parameter['y_axis_gridlines'] = 21;
    $g->parameter['y_decimal_left'] = 1;
}

// Ensure that the x-axis text isn't to cramped.
$g->parameter['x_axis_text'] = ceil($questions_attempted / 40);


// $g->draw();
// Draw in custom order to get grid lines on top.
$g->y_order = array('error_max', 'error_min', 'target_level', 'qdiff', 'ability');
$g->init();
// After initializing with all data sets, reset the order to just the standard-error sets and draw them.
$g->y_order = array('error_max', 'error_min');
$g->draw_data();

// Now draw the axis and text on top of the error ranges.
$g->y_order = array('ability', 'error_max', 'target_level', 'qdiff', 'error_min');
$g->draw_y_axis();
$g->draw_text();

// Now reset the order and draw our lines
$g->y_order = array('qdiff', 'target_level', 'ability');
$g->draw_data();

$g->output();
