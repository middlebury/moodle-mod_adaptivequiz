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
 * Adaptive quiz view report script
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->dirroot.'/lib/grouplib.php');
require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/lib/quiz_analyser.class.php');
require_once(dirname(__FILE__).'/renderer.php');

require_once(dirname(__FILE__).'/lib/statistics/times_used_statistic.class.php');
require_once(dirname(__FILE__).'/lib/statistics/percent_correct_statistic.class.php');
require_once(dirname(__FILE__).'/lib/statistics/discrimination_statistic.class.php');
require_once(dirname(__FILE__).'/lib/statistics/answers_statistic.class.php');

$id = required_param('cmid', PARAM_INT);
$qid = required_param('qid', PARAM_INT);
$sortdir = optional_param('sortdir', 'DESC', PARAM_ALPHA);
$sort = optional_param('sort', 'times_used', PARAM_ALPHANUMEXT);
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

$adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $cm->instance), '*');

$quizanalyzer = new adaptivequiz_quiz_analyser();
$quizanalyzer->load_attempts($cm->instance);
$questionanalyzer = $quizanalyzer->get_question_analyzer($qid);
$definition = $questionanalyzer->get_question_definition();

$PAGE->set_url('/mod/adaptivequiz/questionanalysis/single.php', array('cmid' => $cm->id, 'qid' => $qid));
$PAGE->set_title(format_string($definition->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$output = $PAGE->get_renderer('mod_adaptivequiz', 'questions');


$quizanalyzer->add_statistic('times_used', new adaptivequiz_times_used_statistic());
$quizanalyzer->add_statistic('percent_correct', new adaptivequiz_percent_correct_statistic());
$quizanalyzer->add_statistic('discrimination', new adaptivequiz_discrimination_statistic());
$quizanalyzer->add_statistic('answers', new adaptivequiz_answers_statistic());

$headers = $quizanalyzer->get_header();
$record = $quizanalyzer->get_record($qid);

// Get rid of the question id and name columns.
unset($headers['id'], $headers['name']);
array_shift($record);
array_shift($record);

/* print header information */
$header = $output->print_header();
$title = $output->heading(get_string('question_report', 'adaptivequiz'));
/* return link */
$url = new moodle_url('/mod/adaptivequiz/questionanalysis/overview.php',
    array('cmid' => $cm->id, 'sort' => $sort, 'sortdir' => $sortdir, 'page' => $page));
$returnlink = html_writer::link($url, get_string('back_to_all_questions', 'adaptivequiz'));

/* Output attempts table */
$details = $output->get_question_details($questionanalyzer, $context);
$reporttable = $output->get_single_question_report($headers, $record, $cm, '/mod/adaptivequiz/questionanalysis/overview.php',
    $sort, $sortdir);
/* Output footer information */
$footer = $output->print_footer();

echo $header;
echo $returnlink;
echo $title;
echo $details;
echo $reporttable;
echo $footer;
