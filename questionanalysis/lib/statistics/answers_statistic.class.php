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

require_once(dirname(__FILE__).'/question_statistic.interface.php');

/**
 * Questions-statistic interface
 *
 * This interface defines the methods required for pluggable statistics that may be added to the question analysis.
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adaptivequiz_answers_statistic implements adaptivequiz_question_statistic {
    
    /**
     * Answer a display-name for this statistic.
     * 
     * @return string
     */
    public function get_display_name () {
    	return get_string('answers_display_name', 'adaptivequiz');
    }
    
    /**
     * Calculate this statistic for a question's results
     * 
     * @param adaptivequiz_question_analyser $question_analyser
     * @return adaptivequiz_question_statistic_result
     */
    public function calculate (adaptivequiz_question_analyser $question_analyser) {
        // Sort the results
        $results = $question_analyser->get_results();
        foreach ($results as $result) {
    	    $sort_keys[] = $result->score->measured_ability_in_logits();
    	}
    	array_multisort($sort_keys, SORT_NUMERIC, SORT_DESC, $results);
    	
    	ob_start();
//         print "<pre>Question level (logit): ".round($question_analyser->get_question_level_in_logits(), 2)."</pre>";
        foreach ($results as $result) {
            if ($result->correct) {
                // If the user answered correctly, the result is in-range if their measured ability + stderr is >= the question level.
                $in_range = ($result->score->measured_ability_in_logits() + $result->score->standard_error_in_logits() >= $question_analyser->get_question_level_in_logits());
            } else {
                // If the user answered incorrectly, the result is in-range if their measured ability - stderr is <= the question level.
                $in_range = ($result->score->measured_ability_in_logits() - $result->score->standard_error_in_logits() <= $question_analyser->get_question_level_in_logits());
            }
            print "<pre style=\"color: ".(($result->correct)?"green":"red")."; ".(($in_range)?"":"font-weight: bold;")."\">";
            print "User: ".$result->user->firstname." ".$result->user->lastname."\n";
            print "Result: ".(($result->correct)?"correct":"incorrect")."\n";
            print "Person ability (scaled): ".round($result->score->measured_ability_in_scale(), 2)."\n";
            print "STDERR (scaled): ".round($result->score->standard_error_in_scale(), 2)."\n";
//             print "Person ability (logit): ".round($result->score->measured_ability_in_logits(), 2)."\n";
//             print "STDERR (logit): ".round($result->score->standard_error_in_logits(), 2)."\n";
            print "</pre>";
        }
    	
    	return new adaptivequiz_answers_statistic_result (count($results), ob_get_clean());
    }
}

/**
 * Questions-statistic-result interface
 *
 * This interface defines the methods required for pluggable statistic-results that may be added to the question analysis.
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adaptivequiz_answers_statistic_result implements adaptivequiz_question_statistic_result {
    
    /** @var int $num_results  */
    protected $num_results = null;
    
    /** @var string $printable  */
    protected $printable = null;
    
    /**
     * Constructor
     * 
     * @param array $num_results
     * @return void
     */
    public function __construct ($num_results, $printable) {
    	$this->num_results = $num_results;
    	$this->printable = $printable;
    }
    
    /**
     * A sortable version of the result.
     * 
     * @return mixed string or numeric
     */
    public function sortable () {
    	return $this->num_results;
    }
    
    /**
     * A printable version of the result.
     * 
     * @param numeric $result
     * @return mixed string or numeric
     */
    public function printable () {
     	return $this->printable;
    }
}
