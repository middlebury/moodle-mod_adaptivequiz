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

require_once(dirname(__FILE__).'/question_analyser.class.php');
require_once(dirname(__FILE__).'/attempt_score.class.php');
require_once(dirname(__FILE__).'/statistics/question_statistic.interface.php');
require_once(dirname(__FILE__).'/../../catalgo.class.php');
require_once($CFG->dirroot.'/tag/lib.php');

/**
 * Questions-analyser class
 *
 * This class provides a mechanism for loading and analysing question usage,
 * performance, and efficacy.
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adaptivequiz_quiz_analyser {

    /** @var array $questions An array of all questions loaded and their stats */
    protected $questions = array();

    /** @var array $statistics An array of the statistics added to this report */
    protected $statistics = array();

    /**
     * Constructor - Create a new analyser.
     *
     * @return void
     */
    public function __construct () {

    }

    /**
     * Load attempts from an adaptive quiz instance
     *
     * @param int $instance
     * @return void
     */
    public function load_attempts ($instance) {
        global $DB;

        $adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $instance), '*');

        // Get all of the completed attempts for this adaptive quiz instance.
        $attempts  = $DB->get_records('adaptivequiz_attempt',
            array('instance' => $instance, 'attemptstate' => ADAPTIVEQUIZ_ATTEMPT_COMPLETED));

        foreach ($attempts as $attempt) {
            $user = $DB->get_record('user', array('id' => $attempt->userid));
            if (!$user) {
                $user = new stdClass();
                $user->firstname = get_string('unknownuser', 'adaptivequiz');
                $user->lastname = '#'.$userid;
            }

            // For each attempt, get the attempt's final score.
            $score = new adaptivequiz_attempt_score($attempt->measure, $attempt->standarderror, $adaptivequiz->lowestlevel,
                $adaptivequiz->highestlevel);

            // For each attempt, loop through all questions asked and add that usage
            // to the question.
            $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);
            foreach ($quba->get_slots() as $i => $slot) {
                $question = $quba->get_question($slot);

                // Create a question-analyser for the question.
                if (empty($this->questions[$question->id])) {
                    $tags = tag_get_tags_array('question', $question->id);
                    $difficulty = adaptivequiz_get_difficulty_from_tags($tags);
                    $this->questions[$question->id] = new adaptivequiz_question_analyser($quba->get_owning_context(), $question,
                        $difficulty, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);
                }

                // Record the attempt score and the individual question result.
                $correct = ($quba->get_question_mark($slot) > 0);
                $answer = $quba->get_response_summary($slot);
                $this->questions[$question->id]->add_result($attempt->uniqueid, $user, $score, $correct, $answer);
            }
        }
    }

    /**
     * Add a statistic to calculate.
     *
     * @param string $key A key to identify this statistic for sorting and printing.
     * @param adaptivequiz_question_statistic $statistic
     * @return void
     */
    public function add_statistic ($key, adaptivequiz_question_statistic $statistic) {
        if (!empty($this->statistics[$key])) {
            throw new InvalidArgumentException("Statistic key '$key' is already in use.");
        }
        $this->statistics[$key] = $statistic;
        foreach ($this->questions as $question) {
            $question->add_statistic($key, $statistic);
        }
    }

    /**
     * Answer a header row.
     *
     * @return array
     */
    public function get_header () {
        $header = array();
        $header['id'] = get_string('id', 'adaptivequiz');
        $header['name'] = get_string('adaptivequizname', 'adaptivequiz');
        $header['level'] = get_string('attemptquestion_level', 'adaptivequiz');
        foreach ($this->statistics as $key => $statistic) {
            $header[$key] = $statistic->get_display_name();
        }
        return $header;
    }

    /**
     * Return an array of table records, sorted by the statisic given
     *
     * @param optional string $sort Which statistic to sort on.
     * @param optional string $direction ASC or DESC.
     * @return array
     */
    public function get_records ($sort = null, $direction = 'ASC') {
        $records = array();

        foreach ($this->questions as $question) {
            $record = array();
            $record[] = $question->get_question_definition()->id;
            $record[] = $question->get_question_definition()->name;
            $record[] = $question->get_question_level();
            foreach ($this->statistics as $key => $statistic) {
                $record[] = $question->get_statistic_result($key)->printable();
            }
            $records[] = $record;
        }

        if ($direction != 'ASC' && $direction != 'DESC') {
            throw new InvalidArgumentException('Invalid sort direction. Must be SORT_ASC or SORT_DESC, \''.$direction.'\' given.');
        }
        if ($direction == 'DESC') {
            $direction = SORT_DESC;
        } else {
            $direction = SORT_ASC;
        }

        if (!is_null($sort)) {
            $sortkeys = array();
            foreach ($this->questions as $question) {
                if ($sort == 'name') {
                    $sortkeys[] = $question->get_question_definition()->name;
                    $sorttype = SORT_REGULAR;
                } else if ($sort == 'level') {
                    $sortkeys[] = $question->get_question_level();
                    $sorttype = SORT_NUMERIC;
                } else {
                    $sortkeys[] = $question->get_statistic_result($sort)->sortable();
                    $sorttype = SORT_NUMERIC;
                }
            }
            array_multisort($sortkeys, $direction, $sorttype, $records);
        }

        return $records;
    }

    /**
     * Answer a question-analyzer for a particular question id analyze
     *
     * @param int $qid The question id
     * @return adaptivequiz_question_analyser
     */
    public function get_question_analyzer ($qid) {
        if (!isset($this->questions[$qid])) {
            throw new Exception('Question-id not found.');
        }
        return $this->questions[$qid];
    }

    /**
     * Answer the record for a single question
     *
     * @param int $qid The question id
     * @return array
     */
    public function get_record ($qid) {
        $question = $this->get_question_analyzer($qid);
        $record = array();
        $record[] = $question->get_question_definition()->id;
        $record[] = $question->get_question_definition()->name;
        $record[] = $question->get_question_level();
        foreach ($this->statistics as $key => $statistic) {
            $record[] = $question->get_statistic_result($key)->printable();
        }
        return $record;
    }
}
