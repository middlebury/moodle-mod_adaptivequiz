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
 * Computer-Adaptive Testing Algorithm class
 *
 * This class performs the simple algorithm to determine the next level of difficulty a student should attempt.
 * It also recommends whether the calculation has reached an acceptable level of error.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalgo {
    /** @var $quba a question_usage_by_activity object */
    protected $quba = null;

    /** @var $attemptid an adaptivequiz_attempt attempt id */
    protected $attemptid = 0;

    /** @var bool $debugenabled flag to denote developer debugging is enabled and this class should write message to the debug array */
    protected $debugenabled = false;

    /** @var array $debug debugging array of messages */
    protected $debug = array();

    /** @var int $level level of difficulty of the most recently attempted question */
    protected $level = 0;

    /** @var bool $readytostop flag to denote whether to assume the student has met the minimum requirements */
    protected $readytostop = true;

    /** @var int $questattempted the sum number of questions attempted */
    protected $questattempted = 0;

    /** @var $difficultysum the sum of the difficulty levels attempted */
    protected $difficultysum = 0;

    /** @var int $nextdifficulty the next dificulty level to administer */
    protected $nextdifficulty = 0;

    /** @var int $sumofcorrectanswers the sum of questions answered correctly */
    protected $sumofcorrectanswers;

    /** @var int @sumofincorrectanswers the sum of questions answered incorretly */
    protected $sumofincorrectanswers;

    /** @var float $measure the ability measure */
    protected $measure = 0.0;

    /** @var float $standarderror the standard error of the meature */
    protected $standarderror = 0.0;

    /**
     * Constructor to initialize the parameters needed by the adaptive alrogithm
     * @throws moodle_exception - exception is thrown if first argument is not an instance of question_usage_by_activity class or second argument is not a positive integer.
     * @param question_usage_by_activity $quba an object loaded using the unique id of the attempt
     * @param int $attemptid the adaptivequiz_attempt attempt id
     * @param bool $readytostop true of the algo should assume the user has answered the minimum number of question and should compare the results againts the standard error
     * @param int $level the level of difficulty for the most recently attempted question
     * @return void
     */
    public function __construct($quba, $attemptid, $readytostop = true, $level = 0) {
        if (!$quba instanceof question_usage_by_activity) {
            throw new coding_exception('catalgo: Argument 1 is not a question_usage_by_activity object', 'Question usage by activity must be a question_usage_by_activity object');
        }

        if (!is_int($attemptid) || 0 >= $attemptid) {
            throw new coding_exception('catalgo: Argument 2 not a positive integer', 'Attempt id argument must be a positive integer');
        }

        if (!is_int($level) || 0 >= $level) {
            throw new coding_exception('catalgo: Argument 4 not a positive integer', 'level must be a positive integer');
        }

        $this->quba = $quba;
        $this->attemptid = $attemptid;
        $this->readytostop = $readytostop;
        $this->level = $level;

        if (debugging('', DEBUG_DEVELOPER)) {
            $this->debugenabled = true;
        }
    }

    /**
     * This function adds a message to the debugging array
     * @param string $message details of the debugging message
     * @return void
     */
    protected function print_debug($message = '') {
        if ($this->debugenabled) {
            $this->debug[] = $message;
        }
    }

    /**
     * This function returns the debug array
     * @return array array of debugging messages
     */
    public function get_debug() {
        return $this->debug;
    }

    /**
     * This function returns the $difficultysum property
     * @return int returns the $difficultysum property
     */
    public function get_difficultysum() {
        return $this->difficultysum;
    }

    /**
     * This functions retrieves the attempt record
     * @throws dml_missing_record_exception
     * @param int $attemptid the attempt id record
     * @return stdClass adaptivequiz_attempt record
     */
    public function retrieve_attempt_record($attemptid) {
        global $DB;

        $param = array('id' => $attemptid);
        $record = $DB->get_record('adaptivequiz_attempt', $param, 'id,questionsattempted,difficultysum,standarderror', MUST_EXIST);
        return $record;
    }

    /**
     * This function updates the adaptivequiz_attempt record with the difficulty sum.  Also updates the the $difficultysum property
     * @param int $sum the sum of difficulties attempted.  Must be a positive integer
     * @return bool true of update successful, otherwise false
     */
    public function update_difficulty_sum_of_attempt($sum) {
        global $DB;

        if (!is_int($sum) || 0 >= $sum) {
            return false;
        }

        $attempt = new stdClass();
        $attempt->id = $this->attemptid;
        $attempt->difficultysum = $sum;
        $attempt->timemodified = time();

        $DB->update_record('adaptivequiz_attempt', $attempt);

        $this->difficultysum = $sum;

        return true;
    }

    /**
     * Refactored code from adaptiveattempt.class.php @see find_last_quest_used_by_attempt()
     * This function retrieves the last question that was used in the attempt
     * @return int question slot or 0 if no unmarked question could be found
     */
    protected function find_last_quest_used_by_attempt() {
        if (!$this->quba instanceof question_usage_by_activity) {
            $this->print_debug('find_last_quest_used_by_attempt() - Argument was not a question_usage_by_activity object');
            return 0;
        }

        // The last slot in the array should be the last question that was attempted (meaning it was either shown to the user or the user submitted an answer to it)
        $questslots = $this->quba->get_slots();

        if (empty($questslots) || !is_array($questslots)) {
            $this->print_debug('find_last_quest_used_by_attempt() - No question slots found for this question_usage_by_activity object');
            return 0;
        }

        $questslot = end($questslots);
        $this->print_debug('find_last_quest_used_by_attempt() - Found a question slot: '.$questslot);
        return $questslot;
    }

    /**
     * Refactored code from adaptiveattempt.class.php @see was_answer_submitted_to_question()
     * This function determines if the user submitted an answer to the question
     * @param int $slot question slot id
     * @return bool true if an answer to the question was submitted, otherwise false
     */
    protected function was_answer_submitted_to_question($slotid) {
        if (empty($slotid)) {
            $this->print_debug('was_answer_submitted_to_question() refactored - slot id was zero');
            return false;
        }

        $state = $this->quba->get_question_state($slotid);

        // Check if the state of the quesiton attempted was graded right, partially right or wrong
        $marked = $state instanceof question_state_gradedright || $state instanceof question_state_gradedpartial || $state instanceof question_state_gradedwrong;
        if ($marked) {
            return true;
        } else {
            // save some debugging information
            $this->print_debug('was_answer_submitted_to_question() refactored - question state is unrecognized state: '.get_class($state).' question slotid: '.
                    $slotid.' quba id: '.$this->quba->get_id());
        }

        return false;
    }

    /**
     * This function determins whether the user answered the question correctly or incorrectly.
     * If the answer is partially correct it is seen as correct.
     * @param quesiton_usage_by_activity $quba an object loaded using the unique id of the attempt
     * @param int $slotid the slot id of the question
     * @return float|null a float representing the user's mark.  Or null if there was no mark
     */
    public function get_question_mark($quba, $slotid) {
        $mark = $quba->get_question_mark($slotid);

        if (is_float($mark)) {
            return $mark;
        }

        $this->print_debug('get_question_mark() - Question mark was not a float slot id: '.$slotid);
        return null;
    }

    /**
     * This function retreives the mark received from the student's submission to the question
     * @return bool|null true if the question was marked correct.  False if the question was marked incorrect or null if no mark was given
     */
    public function question_was_marked_correct() {
        // Find the last question attempted by the user
        $slotid = $this->find_last_quest_used_by_attempt();

        // Check if the question was marked
        if (!$this->was_answer_submitted_to_question($slotid)) {
            return null;
        }

        // Retrieve the mark received
        $mark = $this->get_question_mark($this->quba, $slotid);

        if (is_null($mark)) {
            return null;
        }

        // Return true if the question was marked correct.
        if ((float) 0 < $mark) {
            return true;
        }

        return false;
    }

    /**
     * This function retrieves the allowed standard error for the attempt
     * @throws dml_missing_record_exception
     * @param int $attemptid adaptivequiz_attempt id
     * @return float the standard error allowed
     */
    public function retrieve_standard_error($attemptid) {
        global $DB;

        $param = array('aaid' => $attemptid);
        $sql = "SELECT a.standarderror
                  FROM {adaptivequiz} a
                  JOIN {adaptivequiz_attempt} aa ON a.id = aa.instance
                 WHERE aa.id = :aaid
              ORDER BY a.standarderror ASC";

        return (float) $DB->get_field_sql($sql, $param, MUST_EXIST);
    }

    /**
     * This function performs the different steps in the CAT simple algorithm
     * @return int returns the next difficulty level or 0 if there was an error
     */
    public function perform_calculation_steps() {
        // Retrieve attempt record
        $record = $this->retrieve_attempt_record($this->attemptid);

        $this->difficultysum = $record->difficultysum;
        $this->questattempted = $record->questionsattempted;

        // If the user answered the previous question correctly, calculate the sum of correct answers
        $correct = $this->question_was_marked_correct();

        if (true === $correct) {
            // Compute the next difficulty level for the next question
            $this->nextdifficulty = $this->compute_next_difficulty($this->level, $this->questattempted, true);
            // Calculate the sum of correct answers
            $this->sumofcorrectanswers = $this->compute_right_answers($this->quba);

        } else if (false === $correct) {
            // Compute the next difficulty level for the next question
            $this->nextdifficulty = $this->compute_next_difficulty($this->level, $this->questattempted, false);

        } else {
            $this->print_debug('perform_calculation_steps() - Last question attempted returned a null as an answer');
            return 0;
        }

        // If he user hasn't met the minimum requirements to end the attempt, then return with the next difficulty level
        if (empty($this->readytostop)) {
            $this->print_debug('perform_calculation_steps() - Not ready to stop the attempt, returning next difficulty number');
            return $this->nextdifficulty;
        }

        $this->sumofincorrectanswers = $this->compute_wrong_answers($this->quba);

        // Added condition to avoid a divide by zero error
        if ((0 == $this->sumofincorrectanswers || 0 == $this->sumofcorrectanswers ) || 0 == $this->questattempted) {
            $this->print_debug('perform_calculation_steps() - (sum of correct answers OR the sum of incorrect answers equals zero) OR number of questions attempted equals zero');
            return 0;
        }

        // Test that the sum of incorrect and correct answers equal to the sum of question attempted
        $validatenumbers = $this->sumofcorrectanswers + $this->sumofincorrectanswers;

        if ($validatenumbers != $this->questattempted) {
            // TODO do something about this
            $this->print_debug('perform_calculation_steps() - Sum of correct and incorrect answers doesn\'t equals the total number of questions attempted');
            return 0;
        }

        // Get the measure estimate
        $this->measure = $this->estimate_measure($this->difficultysum, $this->questattempted, $this->sumofcorrectanswers, $this->sumofincorrectanswers);

        // Get the standard error estimate
        $this->standarderror = $this->estimate_standard_error($this->questattempted, $this->sumofcorrectanswers, $this->sumofincorrectanswers);

        $this->print_debug('perform_calculation_steps() - measure: '.$this->measure.' standard error: '.$this->standarderror);

        return $this->nextdifficulty;
    }

    /**
     * This function estimates the standard error in the measurement
     * @param int $questattempt the number of question attempted
     * @param int $sumcorrect the sum of correct answers
     * @param int $sumincorrect the sum of incorrect answers
     * @return float a decimal rounded to 5 places is returned
     */
    public function estimate_standard_error($questattempt, $sumcorrect, $sumincorrect) {
        $standarderror = 0;
        $product = $sumcorrect * $sumincorrect;
        $quotient = (float) $questattempt / (float) $product;
        $standarderror = sqrt($quotient);

        return round($standarderror, 5);
    }

    /**
     * This function estimates the measure of ability
     * @param int $diffsum the sum of difficulty levels attempted
     * @param int $questattempt the number of question attempted
     * @param int $sumcorrect the sum of correct answers
     * @param int $sumincorrect the sum of incorrect answers
     * @return float an estimate of the measure of ability
     */
    public function estimate_measure($diffsum, $questattempt, $sumcorrect, $sumincorrect) {
        $measure = 0.0;
        $quotient = (float) $sumcorrect / (float) $sumincorrect;
        $quotienttwo = $diffsum / $questattempt;
        $measure = $quotienttwo + log($quotient); // calculate natural log

        return round($measure, 5, PHP_ROUND_HALF_UP);
    }

    /**
     * This function counts the total number of correct answers for the attempt
     * @param question_usage_by_activity $quba an object loaded using the unique id of the attempt
     * @return int the number of correct answer submission
     */
    public function compute_right_answers($quba) {
        $correctanswers = 0;

        // Get question slots for the attempt
        $slots = $quba->get_slots();

        // Iterate over slots and count correct answers
        foreach ($slots as $slot) {
            $mark = $this->get_question_mark($quba, $slot);

            if (0 < $mark) {
                $correctanswers++;
            }
        }

        $this->print_debug('compute_right_answers() - Sum of correct answers: '.$correctanswers);
        return $correctanswers;
    }

    /**
     * This function counts the total number of incorrect answers for the attempt
     * @param question_usage_by_activity $quba an object loaded using the unique id of the attempt
     * @return int the number of correct answer submission
     */
    public function compute_wrong_answers($quba) {
        $incorrectanswers = 0;

        // Get question slots for the attempt
        $slots = $quba->get_slots();

        // Iterate over slots and count correct answers
        foreach ($slots as $slot) {
            $mark = $this->get_question_mark($quba, $slot);

            if (0 >= $mark) {
                $incorrectanswers++;
            }
        }

        $this->print_debug('compute_right_answers() - Sum of incorrect answers: '.$incorrectanswers);
        return $incorrectanswers;
    }

    /**
     * This function does the work to determine the next difficulty level
     * @param int $level the difficulty level of the last question attempted
     * @param int $questattempted the sum of questions attempted
     * @param bool $correct true of the user got the previous question correct, otherwise false
     * @return int the next difficult level
     */
    public function compute_next_difficulty($level, $questattempted, $correct) {
        $nextdifficulty =  0;

        // Check if the last question was marked correctly
        if ($correct) {
            $nextdifficulty = $level + (2 / $questattempted);

            // In the use case where the result is greater/lesser but only by a one tenth or less then we add 1 to the result
            if ((int) $level == (int) $nextdifficulty) {
                $nextdifficulty = 1 + (int) $nextdifficulty;
            }
        } else {
            $nextdifficulty = $level - (2 / $questattempted);

            // In the use case where the result is greater/lesser but only by a one tenth or less then we add 1 to the result
            if ((int) $level == (int) $nextdifficulty) {
                $nextdifficulty = 1 - (int) $nextdifficulty;
            }
        }

        $this->print_debug('compute_next_difficulty() - Next difficulty level is: '.$nextdifficulty);
        return (int) $nextdifficulty;
    }
}