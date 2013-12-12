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
if (!$user) {
    $user = new stdClass();
    $user->firstname = get_string('unknownuser', 'adaptivequiz');
    $user->lastname = '#'.$userid;
}

$g = new graph(750, 300);

$a = new stdClass;
$a->quiz_name = $adaptivequiz->name;
$a->firstname = $user->firstname;
$a->lastname = $user->lastname;
$g->parameter['title'] = get_string('answerdistgraph_title', 'adaptivequiz', $a);

$g->parameter['x_label'] = get_string('answerdistgraph_questiondifficulty', 'adaptivequiz');
$g->parameter['x_label_angle'] = 0;
$g->parameter['y_label_left'] = get_string('answerdistgraph_numrightwrong', 'adaptivequiz');
$g->parameter['legend'] = 'none';
$g->parameter['legend_border'] = 'black';
$g->parameter['legend_offset'] = 4;
$g->parameter['grid_colour'] = 'grayCC';

$g->parameter['y_resolution_left'] = 1;
$g->parameter['y_decimal_left'] = 0;

$g->parameter['shadow'] = 'grayCC'; // Set default shadow for all data sets.
$g->parameter['bar_size'] = 2;
$g->parameter['bar_spacing'] = 10;
$g->parameter['zero_axis'] = 'black';
$g->parameter['inner_border_type'] = 'y-left'; // Only draw left y axis as zero axis already selected above.


// Set up our data arrays.
$difficulties = array();
$rightanswers = array();
$wronganswers = array();

for ($i = $adaptivequiz->lowestlevel; $i <= $adaptivequiz->highestlevel; $i++) {
    $difficulties[] = intval($i);
    $rightanswers[] = 0;
    $wronganswers[] = 0;
}

$quba = question_engine::load_questions_usage_by_activity($uniqueid);
foreach ($quba->get_slots() as $i => $slot) {
    $question = $quba->get_question($slot);
    $tags = tag_get_tags_array('question', $question->id);
    $difficulty = adaptivequiz_get_difficulty_from_tags($tags);
    $correct = ($quba->get_question_mark($slot) > 0);

    $position = array_search($difficulty, $difficulties);
    if ($correct) {
        $rightanswers[$position]++;
    } else {
        $wronganswers[$position]--;
    }
}

$max = max(max($rightanswers), -1 * min($wronganswers));

$g->x_data = $difficulties;
$g->y_data['right_answers'] = $rightanswers;
$g->y_data['wrong_answers'] = $wronganswers;

$g->y_format['right_answers'] = array('colour' => 'blue', 'bar' => 'fill', 'shadow' => 'none',
    'legend' => get_string('answerdistgraph_right', 'adaptivequiz'));
$g->y_format['wrong_answers'] = array('colour' => 'red', 'bar' => 'fill', 'shadow' => 'none',
    'legend' => get_string('answerdistgraph_wrong', 'adaptivequiz'));

$g->parameter['y_min_left'] = -1 * ($max + 1);
$g->parameter['y_max_left'] = $max + 1;

// Skip some y-axis labels so they aren't too crowded.
$g->parameter['y_axis_text_left'] = ceil($max / 10);

// Space out the bars so that their width slowly decreases as the number of ticks increases.
$g->parameter['bar_spacing'] = max(4, round(700 / count($difficulties)) - 11 + round(8 * count($difficulties) / 100));

// Skip some x-axis labels for legibility.
$g->parameter['x_axis_text'] = ceil(count($difficulties) / 50);

$g->y_order = array('right_answers', 'wrong_answers');

$g->parameter['y_axis_gridlines'] = (2 * $max) + 3;


$g->draw();
