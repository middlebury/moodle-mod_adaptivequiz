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
 * Question-analyser class
 *
 * This class provides a mechanism for analysing the usage, performance, and efficacy
 * of a single question in an adaptive quiz.
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adaptivequiz_question_analyser {

    /** @var context the context this usage belongs to. */
    protected $context;

    /** @var question_definition $definition The question  */
    protected $definition = null;

    /** @var float $level The question level */
    protected $level = null;

    /** @var float $lowestlevel The lowest question-level in the adaptive quiz */
    protected $lowestlevel = null;

    /** @var float $highestlevel The highest question-level in the adaptive quiz */
    protected $highestlevel = null;

    /** @var array $results An array of the re objects */
    protected $results = array();

    /** @var array $statistics An array of the adaptivequiz_question_statistic added to this report */
    protected $statistics = array();

    /** @var array $statisticresults An array of the adaptivequiz_question_statistic_result added to this report */
    protected $statisticresults = array();

    /**
     * Constructor - Create a new analyser.
     *
     * @param object $context
     * @param question_definition $definition
     * @param float $level The level (0-1) of the question.
     * @return void
     */
    public function __construct ($context, question_definition $definition, $level, $lowestlevel, $highestlevel) {
        $this->context = $context;
        $this->definition = $definition;
        $this->level = $level;
        $this->lowestlevel = $lowestlevel;
        $this->highestlevel = $highestlevel;
    }

    /**
     * Add an usage result for this question.
     *
     * @param adaptivequiz_attempt_score $score The user's score on this attempt.
     * @param boolean $correct True if the user answered correctly.
     * @param string $answer
     * @return void
     */
    public function add_result ($attemptid, $user, adaptivequiz_attempt_score $score, $correct, $answer) {
        $result = new stdClass();
        $result->attemptid = $attemptid;
        $result->user = $user;
        $result->score = $score;
        $result->correct = $correct;
        $result->answer = $answer;
        $this->results[] = $result;
    }

    /**
     * @return context the context this usage belongs to.
     */
    public function get_owning_context() {
        return $this->context;
    }

    /**
     * Answer the question definition for this question.
     *
     * @return question_definition
     */
    public function get_question_definition () {
        return $this->definition;
    }

    /**
     * Answer the question level for this question.
     *
     * @return int
     */
    public function get_question_level () {
        return $this->level;
    }

    /**
     * Answer the question level for this question in logits.
     *
     * @return int
     */
    public function get_question_level_in_logits () {
        return catalgo::convert_linear_to_logit($this->level, $this->lowestlevel, $this->highestlevel);
    }

    /**
     * Answer the results for this question
     *
     * @return array An array of stdClass objects.
     */
    public function get_results () {
        return $this->results;
    }

    /**
     * Add and calculate a statistic.
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
        $this->statisticresults[$key] = $statistic->calculate($this);
    }

    /**
     * Answer a statistic result.
     *
     * @param string $key A key to identify this statistic.
     * @return adaptivequiz_question_statistic_result
     */
    public function get_statistic_result ($key) {
        if (empty($this->statisticresults[$key])) {
            throw new InvalidArgumentException("Unknown statistic key '$key'.");
        }
        return $this->statisticresults[$key];
    }

    /**
     * Utility function to map a logit value to this question's scale
     *
     * @param $logit
     * @return float Scaled value
     */
    public function map_logit_to_scale ($logit) {
        return catalgo::map_logit_to_scale($logit, $this->highestlevel, $this->lowestlevel);
    }
}